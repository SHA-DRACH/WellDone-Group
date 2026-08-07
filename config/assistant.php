<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Site assistant
    |--------------------------------------------------------------------------
    |
    | The on-site guide that answers visitor questions about the company and
    | walks people through the things they came to do — request a quotation,
    | apply for a job, reach the right department.
    |
    | It always works. Answers are grounded in a knowledge base built from the
    | site's own content (divisions, services, projects, careers, downloads,
    | FAQs and settings), so nothing is invented and nothing goes stale.
    |
    | Set ANTHROPIC_API_KEY to have Claude phrase those answers conversationally
    | instead of returning the matched entry verbatim. The retrieved content is
    | identical either way — the key only buys nicer wording, and if the API is
    | unreachable the reply falls back to the local phrasing rather than failing.
    |
    */

    'enabled' => (bool) env('ASSISTANT_ENABLED', true),

    /*
     * How many knowledge-base entries a single question may pull in, and the
     * score an entry must reach to count as a match at all. Below the floor
     * the assistant says it does not know rather than guessing.
     */
    'matches' => 8,
    'score_floor' => 1.5,

    /*
     * Turns of conversation kept in the visitor's session and replayed to the
     * model, so follow-ups like "and how long does that take?" resolve. Each
     * turn is one question and one answer.
     */
    'history_turns' => 6,

    /*
     * Seconds the compiled knowledge base is cached. Publishing a service or
     * a vacancy therefore shows up in the assistant within this window; the
     * cache is also flushed by hand from `php artisan assistant:refresh`.
     */
    'cache_ttl' => 900,

    'max_question_length' => 500,

    /*
    |--------------------------------------------------------------------------
    | Claude
    |--------------------------------------------------------------------------
    |
    | Optional. Credentials come from the Anthropic Console → API Keys.
    |
    | `effort` trades answer depth against latency and cost. A visitor waiting
    | on a chat bubble wants a fast reply and the questions are shallow, so the
    | default is the lowest setting. Thinking is left on (adaptive) — on this
    | model turning it off is the more expensive lever, and low effort already
    | keeps the spend down.
    |
    */

    'claude' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-opus-5'),
        'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 2000),
        'effort' => env('ANTHROPIC_EFFORT', 'low'),
        'timeout' => (float) env('ANTHROPIC_TIMEOUT', 30),
        'max_retries' => (int) env('ANTHROPIC_MAX_RETRIES', 1),
    ],

];
