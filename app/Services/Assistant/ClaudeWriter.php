<?php

namespace App\Services\Assistant;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIException;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Phrases an answer with Claude, from context the retriever already chose.
 *
 * This is the optional half of the assistant. It never decides *what* is true —
 * the knowledge base does that — it only turns matched entries into a reply
 * that reads like a person wrote it, and resolves follow-up questions against
 * the conversation so far.
 *
 * Everything here fails soft. No API key, an outage, a timeout, a refusal: the
 * caller gets null and falls back to the locally composed answer. A visitor
 * should never see a chat widget break because a third party is down.
 */
class ClaudeWriter
{
    protected ?Client $client = null;

    public function configured(): bool
    {
        return filled(config('assistant.claude.api_key'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $matches  knowledge-base entries, best first
     * @param  array<int, array{role: string, content: string}>  $history  earlier turns, oldest first
     * @return string|null the reply, or null if Claude could not be reached
     */
    public function write(string $question, array $matches, array $history = []): ?string
    {
        if (! $this->configured()) {
            return null;
        }

        try {
            $message = $this->client()->messages->create(
                maxTokens: (int) config('assistant.claude.max_tokens', 2000),
                messages: $this->messages($question, $history),
                model: (string) config('assistant.claude.model', 'claude-opus-5'),
                outputConfig: ['effort' => (string) config('assistant.claude.effort', 'low')],
                system: $this->system($matches),
                thinking: ['type' => 'adaptive'],
                requestOptions: [
                    'timeout' => (float) config('assistant.claude.timeout', 30),
                    'maxRetries' => (int) config('assistant.claude.max_retries', 1),
                ],
            );
        } catch (APIException|Throwable $e) {
            Log::warning('Site assistant: Claude call failed, using the local answer instead.', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return null;
        }

        // Safety classifiers can decline a request. That arrives as a normal
        // successful response with an empty body, so check before reading it.
        if ($message->stopReason === 'refusal') {
            return null;
        }

        $text = '';

        foreach ($message->content as $block) {
            if (($block->type ?? null) === 'text') {
                $text .= $block->text;
            }
        }

        return trim($text) !== '' ? trim($text) : null;
    }

    protected function client(): Client
    {
        return $this->client ??= new Client(
            apiKey: (string) config('assistant.claude.api_key'),
        );
    }

    /**
     * The reply is only allowed to draw on the entries passed in. Anything
     * outside them is a referral to a human, not a guess — a wrong answer
     * about a tender deadline or a job closing date costs more than an
     * unhelpful one.
     *
     * @param  array<int, array<string, mixed>>  $matches
     */
    protected function system(array $matches): string
    {
        $company = setting('company.name', 'WellDone Group Inc.');
        $name = setting('assistant.name', 'the '.$company.' assistant');

        $context = $matches === []
            ? '(No matching page was found for this question.)'
            : collect($matches)
                ->map(fn (array $m) => trim(
                    '### '.$m['title']."\n".
                    ($m['url'] ? 'Page: '.$m['url']."\n" : '').
                    $m['body']
                ))
                ->implode("\n\n");

        return <<<PROMPT
        You are {$name}, the guide on the {$company} website. You help visitors
        find their way around the site and walk them through what they came to
        do — requesting a quotation, applying for a job, reaching the right
        department, finding a document.

        Answer ONLY from the site content below. It was selected for this
        question and it is the whole of what you know.

        - If the content does not cover the question, say so plainly in one
          sentence and point the visitor at the contact page or the phone
          number, if either appears below. Never guess at prices, timelines,
          deadlines, availability or anything else that is not written here.
        - When something is done on a particular page, name that page and give
          its URL exactly as it appears below. Never invent a URL.
        - Two to four sentences. Plain prose, no headings, no bullet lists, no
          markdown. Write the way a helpful receptionist speaks.
        - Where a task needs a signed-in and verified account, say so up front —
          it is the thing visitors most often get stuck on.
        - Speak as "we" for the company. Be warm and direct; skip preamble and
          skip closing pleasantries like "let me know if you need anything".
        - If the question is not about this company or this website, say that
          is outside what you can help with and steer back to the site.
        - Answer in the language the visitor used.

        # Site content

        {$context}
        PROMPT;
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array<int, array{role: string, content: string}>
     */
    protected function messages(string $question, array $history): array
    {
        $messages = [];

        foreach ($history as $turn) {
            if (in_array($turn['role'] ?? null, ['user', 'assistant'], true) && filled($turn['content'] ?? null)) {
                $messages[] = ['role' => $turn['role'], 'content' => $turn['content']];
            }
        }

        // The API requires the exchange to start with the visitor.
        while ($messages !== [] && $messages[0]['role'] !== 'user') {
            array_shift($messages);
        }

        $messages[] = ['role' => 'user', 'content' => $question];

        return $messages;
    }
}
