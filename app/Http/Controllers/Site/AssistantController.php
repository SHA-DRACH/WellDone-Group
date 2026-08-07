<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\AssistantAskRequest;
use App\Services\Assistant\SiteAssistant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The chat widget's endpoint.
 *
 * Conversation state lives in the visitor's session rather than a table:
 * these exchanges are throwaway, nobody needs them after the tab closes, and
 * keeping them out of the database means an anonymous visitor's questions are
 * never retained. Only the last few turns are kept, which is all a follow-up
 * question needs and all that is worth sending back to the model.
 */
class AssistantController extends Controller
{
    protected const SESSION_KEY = 'assistant.history';

    public function __construct(protected SiteAssistant $assistant) {}

    public function ask(AssistantAskRequest $request): JsonResponse
    {
        abort_unless($this->enabled(), 404);

        $question = $request->question();
        $history = $this->history($request);

        $reply = $this->assistant->ask($question, $history);

        $this->remember($request, $question, $reply['answer']);

        return response()->json([
            'answer' => $reply['answer'],
            'links' => $reply['links'],
            'suggestions' => $reply['suggestions'],
        ]);
    }

    /** Start over — clears the transcript the model sees, not just the screen. */
    public function reset(Request $request): JsonResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return response()->json(['status' => 'cleared']);
    }

    /** @return array<int, array{role: string, content: string}> */
    protected function history(Request $request): array
    {
        $history = $request->session()->get(self::SESSION_KEY, []);

        return is_array($history) ? $history : [];
    }

    protected function remember(Request $request, string $question, string $answer): void
    {
        $history = $this->history($request);

        $history[] = ['role' => 'user', 'content' => $question];
        $history[] = ['role' => 'assistant', 'content' => $answer];

        // Two entries per turn, oldest dropped first.
        $keep = (int) config('assistant.history_turns', 6) * 2;

        $request->session()->put(self::SESSION_KEY, array_slice($history, -$keep));
    }

    protected function enabled(): bool
    {
        return (bool) config('assistant.enabled', true)
            && (bool) setting('assistant.enabled', true);
    }
}
