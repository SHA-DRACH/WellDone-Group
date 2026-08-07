<?php

namespace App\Services\Assistant;

/**
 * Picks the knowledge-base entries a question is about.
 *
 * Deliberately a term-overlap scorer rather than anything vector-based: the
 * corpus is a few hundred short entries, it changes whenever an administrator
 * saves a form, and there is no embedding step to keep in sync. Weighted
 * overlap over titles and keywords is enough at that size, runs in a
 * millisecond, and needs no external service to be up.
 *
 * The two pieces of real work are the synonym table — visitors ask for a
 * "price" and the site says "quotation" — and the score floor, which lets the
 * assistant admit it does not know instead of confidently returning whatever
 * scored highest.
 */
class Retriever
{
    /**
     * Words that carry no signal. Note that "how", "what" and friends are
     * dropped: nearly every question contains one, so they only add noise.
     *
     * @var array<int, string>
     */
    protected const STOP_WORDS = [
        'a', 'about', 'am', 'an', 'and', 'any', 'are', 'as', 'at', 'be', 'been', 'but', 'by', 'can',
        'could', 'did', 'do', 'does', 'doing', 'for', 'from', 'get', 'give', 'guys', 'had', 'has',
        'have', 'hello', 'help', 'hey', 'hi', 'how', 'i', 'if', 'in', 'is', 'it', 'its', 'just',
        'know', 'like', 'may', 'me', 'might', 'much', 'must', 'my', 'need', 'of', 'on', 'or', 'our',
        'out', 'over', 'please', 'shall', 'should', 'so', 'some', 'tell', 'than', 'that', 'the',
        'their', 'them', 'then', 'there', 'these', 'they', 'this', 'to', 'up', 'us', 'want', 'was',
        'we', 'were', 'what', 'when', 'where', 'which', 'who', 'whom', 'why', 'will', 'with',
        'would', 'you', 'your', 'yours',
    ];

    /**
     * Everyday phrasing mapped onto the vocabulary the site actually uses.
     * Keys are what a visitor types; values are added to the search terms.
     *
     * @var array<string, array<int, string>>
     */
    protected const SYNONYMS = [
        'price' => ['quote', 'quotation', 'pricing', 'cost'],
        'pricing' => ['quote', 'quotation', 'cost'],
        'cost' => ['quote', 'quotation', 'pricing'],
        'costs' => ['quote', 'quotation', 'pricing'],
        'estimate' => ['quote', 'quotation'],
        'quotation' => ['quote'],
        'quote' => ['quotation'],
        'bid' => ['quote', 'quotation', 'tender'],
        'tender' => ['quote', 'quotation'],
        'proposal' => ['quote', 'quotation'],
        'hire' => ['quote', 'quotation', 'service'],
        'buy' => ['quote', 'quotation', 'service'],

        'job' => ['career', 'vacancy', 'hiring', 'employment'],
        'jobs' => ['career', 'vacancy', 'hiring', 'employment'],
        'vacancy' => ['career', 'job'],
        'vacancies' => ['career', 'job'],
        'hiring' => ['career', 'job', 'vacancy'],
        'recruitment' => ['career', 'job', 'vacancy'],
        'employment' => ['career', 'job'],
        'cv' => ['career', 'job', 'apply', 'application'],
        'resume' => ['career', 'job', 'apply', 'application'],
        'internship' => ['career', 'job', 'vacancy'],

        'call' => ['phone', 'contact'],
        'phone' => ['contact', 'call'],
        'number' => ['phone', 'contact'],
        'email' => ['contact'],
        'address' => ['contact', 'office', 'location'],
        'located' => ['contact', 'office', 'location', 'address'],
        'location' => ['contact', 'office', 'address'],
        'office' => ['contact', 'address', 'hours'],
        'open' => ['hours', 'office'],
        'reach' => ['contact'],
        'speak' => ['contact'],
        'talk' => ['contact'],
        'complaint' => ['contact', 'enquiry'],

        'signup' => ['register', 'account'],
        'signin' => ['login', 'account'],
        'login' => ['account', 'password'],
        'logout' => ['account'],
        'register' => ['account'],
        'password' => ['login', 'account', 'reset'],
        'otp' => ['code', 'verify', 'verification'],
        'code' => ['verify', 'verification', 'otp'],

        'brochure' => ['download', 'profile', 'document'],
        'catalogue' => ['download', 'brochure'],
        'catalog' => ['download', 'brochure'],
        'pdf' => ['download', 'document'],

        'work' => ['project', 'service', 'portfolio'],
        'portfolio' => ['project'],
        'company' => ['about'],
        'business' => ['about', 'company'],
        'certified' => ['certification', 'accreditation'],
        'licence' => ['certification'],
        'license' => ['certification'],
    ];

    /**
     * @param  array<int, array<string, mixed>>  $documents
     * @return array<int, array<string, mixed>> the matches, best first
     */
    public function search(string $question, array $documents, int $limit = 8, float $floor = 1.5): array
    {
        $terms = $this->terms($question);

        if ($terms === []) {
            return [];
        }

        $scored = [];

        foreach ($documents as $document) {
            $score = $this->score($document, $terms, $question);

            if ($score >= $floor) {
                $scored[] = ['score' => $score] + $document;
            }
        }

        usort($scored, fn (array $a, array $b) => $b['score'] <=> $a['score']);

        return array_slice($scored, 0, $limit);
    }

    /**
     * Search terms for a question: significant words, singularised, plus
     * whatever the synonym table adds.
     *
     * @return array<int, string>
     */
    public function terms(string $question): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($question), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $terms = [];

        foreach ($words as $word) {
            if (mb_strlen($word) < 2 || in_array($word, self::STOP_WORDS, true)) {
                continue;
            }

            $terms[] = $word;
            $terms = array_merge($terms, self::SYNONYMS[$word] ?? []);

            // Crude singularisation, enough to make "services" match "service".
            if (mb_strlen($word) > 3 && str_ends_with($word, 's') && ! str_ends_with($word, 'ss')) {
                $singular = mb_substr($word, 0, -1);
                $terms[] = $singular;
                $terms = array_merge($terms, self::SYNONYMS[$singular] ?? []);
            }
        }

        return array_values(array_unique($terms));
    }

    /**
     * Weighted overlap. A hit in the title says far more about relevance than
     * a hit somewhere in the body, and an exact phrase match says more still.
     *
     * @param  array<string, mixed>  $document
     * @param  array<int, string>  $terms
     */
    protected function score(array $document, array $terms, string $question): float
    {
        $title = mb_strtolower((string) $document['title']);
        $keywords = ' '.implode(' ', array_map('mb_strtolower', $document['keywords'] ?? [])).' ';
        $body = mb_strtolower((string) $document['body']);

        $score = 0.0;

        foreach ($terms as $term) {
            if (str_contains($keywords, ' '.$term.' ')) {
                $score += 2.0;
            }

            if (str_contains($title, $term)) {
                $score += 2.5;
            }

            if (str_contains($body, $term)) {
                $score += 0.75;
            }
        }

        // A question that quotes the entry's own title almost verbatim — most
        // often an FAQ — should outrank anything that merely shares words.
        $phrase = trim(mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $question) ?? ''));

        if ($phrase !== '' && mb_strlen($phrase) > 8 && str_contains($title, $phrase)) {
            $score += 6.0;
        }

        // Task guides answer "how do I…", which is what most visitors are
        // really asking even when they phrase it as a noun.
        if (($document['kind'] ?? null) === 'guide') {
            $score *= 1.15;
        }

        return $score;
    }
}
