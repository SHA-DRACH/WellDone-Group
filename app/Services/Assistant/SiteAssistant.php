<?php

namespace App\Services\Assistant;

/**
 * Answers a visitor's question about the site.
 *
 * The flow is: find the relevant entries, then phrase them. Retrieval is what
 * makes the answer true, so it always runs. Phrasing is best-effort — Claude
 * if it is configured and reachable, otherwise the matched entry composed
 * locally, which is plainer but says the same thing.
 *
 * Every reply comes back with links, because the useful outcome of asking the
 * assistant is almost always landing on the right page.
 */
class SiteAssistant
{
    public function __construct(
        protected KnowledgeBase $knowledge,
        protected Retriever $retriever,
        protected ClaudeWriter $writer,
    ) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{answer: string, links: array<int, array{label: string, url: string}>, suggestions: array<int, string>, matched: bool}
     */
    public function ask(string $question, array $history = []): array
    {
        $question = trim($question);

        $matches = $this->retriever->search(
            $question,
            $this->knowledge->all(),
            (int) config('assistant.matches', 8),
            (float) config('assistant.score_floor', 1.5),
        );

        $answer = $this->writer->write($question, $matches, $history)
            ?? $this->compose($question, $matches);

        return [
            'answer' => $answer,
            'links' => $this->links($matches),
            'suggestions' => $this->suggestions($matches),
            'matched' => $matches !== [],
        ];
    }

    /**
     * The offline phrasing. Leads with the best match and, when a second match
     * is close behind, mentions it too — a question like "jobs in logistics"
     * legitimately spans two entries.
     *
     * @param  array<int, array<string, mixed>>  $matches
     */
    protected function compose(string $question, array $matches): string
    {
        if ($matches === []) {
            return $this->apology();
        }

        $best = $matches[0];
        $answer = rtrim($best['body'], ' .').'.';

        if ($best['url'] && $best['label']) {
            $answer .= ' You will find it on the '.$best['label'].' page.';
        }

        // Only worth mentioning a second entry when it scored close to the
        // first; otherwise it is noise dressed up as helpfulness.
        if (isset($matches[1]) && $matches[1]['score'] >= $best['score'] * 0.7) {
            $answer .= ' Related: '.rtrim($matches[1]['title'], '.').'.';
        }

        return $answer;
    }

    protected function apology(): string
    {
        $phone = setting('contact.phone_primary');
        $email = setting('contact.email_general');

        $answer = 'I could not find anything on the site that answers that. ';

        if ($phone && $email) {
            $answer .= "The team can help directly — call {$phone} or email {$email}, ";
            $answer .= 'or use the contact form and someone will come back to you.';
        } elseif ($phone || $email) {
            $answer .= 'The team can help directly on '.($phone ?: $email).', ';
            $answer .= 'or use the contact form and someone will come back to you.';
        } else {
            $answer .= 'Send it through the contact form and someone will come back to you.';
        }

        return $answer;
    }

    /**
     * Buttons under the reply. Deduplicated by URL, because several matched
     * entries commonly point at the same page.
     *
     * Only entries that scored near the best one earn a button. The retriever
     * returns a wide set on purpose — the model benefits from the surrounding
     * context — but a visitor asking for the office address should not be
     * offered the third-best guess as somewhere to click.
     *
     * @param  array<int, array<string, mixed>>  $matches
     * @return array<int, array{label: string, url: string}>
     */
    protected function links(array $matches): array
    {
        $links = [];
        $threshold = ($matches[0]['score'] ?? 0) * 0.6;

        foreach ($matches as $match) {
            if ($match['score'] < $threshold) {
                break;
            }

            if (! $match['url'] || ! $match['label'] || isset($links[$match['url']])) {
                continue;
            }

            $links[$match['url']] = ['label' => $match['label'], 'url' => $match['url']];

            if (count($links) === 3) {
                break;
            }
        }

        if ($links === []) {
            $links[] = ['label' => 'Contact us', 'url' => route('site.contact')];
        }

        return array_values($links);
    }

    /**
     * Follow-up questions to offer as chips. When nothing matched, these are
     * the way back to solid ground rather than a second failed guess.
     *
     * @param  array<int, array<string, mixed>>  $matches
     * @return array<int, string>
     */
    protected function suggestions(array $matches): array
    {
        $defaults = $this->defaultSuggestions();

        if ($matches === []) {
            return $defaults;
        }

        return match ($matches[0]['kind']) {
            'career' => ['How do I apply?', 'What documents do I need?', 'Are there other openings?'],
            'service', 'division' => ['How do I request a quote?', 'Have you done this before?', 'Who do I talk to?'],
            'project' => ['What else have you built?', 'Can I see photos?', 'How do I request a quote?'],
            'download' => ['What else can I download?', 'How do I contact you?'],
            default => $defaults,
        };
    }

    /** @return array<int, string> */
    protected function defaultSuggestions(): array
    {
        $suggestions = setting('assistant.suggestions');

        if (is_array($suggestions) && $suggestions !== []) {
            return array_values(array_slice(array_filter($suggestions, 'is_string'), 0, 4));
        }

        return [
            'How do I request a quotation?',
            'What services do you offer?',
            'Are you hiring?',
            'How do I contact you?',
        ];
    }
}
