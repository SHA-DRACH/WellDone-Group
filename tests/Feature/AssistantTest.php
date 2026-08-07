<?php

namespace Tests\Feature;

use App\Models\Career;
use App\Models\Division;
use App\Models\Service;
use App\Services\Assistant\KnowledgeBase;
use App\Services\Assistant\Retriever;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * The guarantees this suite protects: the assistant answers from the site's
 * own content, it works with no language-model key configured, and it says it
 * does not know rather than inventing something.
 */
class AssistantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // No key: exercise the retrieval path and the local phrasing, which is
        // also what a visitor gets whenever the API is unreachable.
        config()->set('assistant.claude.api_key', null);

        Cache::forget(KnowledgeBase::CACHE_KEY);
    }

    public function test_it_answers_a_question_about_requesting_a_quotation(): void
    {
        $response = $this->postJson(route('site.assistant.ask'), [
            'message' => 'How much does it cost to hire you?',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['answer', 'links', 'suggestions']);

        // "cost" has to reach the quotation guide through the synonym table.
        $this->assertStringContainsString('quot', strtolower($response->json('answer')));
        $this->assertContains(
            route('site.quote.create'),
            array_column($response->json('links'), 'url')
        );
    }

    public function test_it_finds_content_that_administrators_have_published(): void
    {
        $division = Division::query()->create([
            'name' => 'Geothermal Drilling',
            'slug' => 'geothermal-drilling',
            'summary' => 'Deep bore drilling for geothermal energy across West Africa.',
            'is_active' => true,
        ]);

        Service::query()->create([
            'division_id' => $division->id,
            'name' => 'Borehole Survey',
            'slug' => 'borehole-survey',
            'summary' => 'Site assessment before drilling begins.',
            'is_active' => true,
            'is_quotable' => true,
        ]);

        $answer = $this->postJson(route('site.assistant.ask'), [
            'message' => 'Do you do borehole surveys?',
        ])->assertOk()->json('answer');

        $this->assertStringContainsString('drilling begins', $answer);
    }

    public function test_it_admits_when_the_site_has_no_answer(): void
    {
        $answer = $this->postJson(route('site.assistant.ask'), [
            'message' => 'zzqqxx wibblefrotz',
        ])->assertOk()->json('answer');

        $this->assertStringContainsString('could not find', $answer);
    }

    public function test_it_rejects_an_empty_or_oversized_question(): void
    {
        $this->postJson(route('site.assistant.ask'), ['message' => ''])
            ->assertStatus(422);

        $this->postJson(route('site.assistant.ask'), ['message' => str_repeat('a', 2000)])
            ->assertStatus(422);
    }

    public function test_it_remembers_the_conversation_between_questions(): void
    {
        $this->postJson(route('site.assistant.ask'), ['message' => 'Are you hiring?'])->assertOk();

        $this->assertCount(2, session('assistant.history'));

        $this->deleteJson(route('site.assistant.reset'))->assertOk();

        $this->assertNull(session('assistant.history'));
    }

    public function test_the_widget_disappears_when_it_is_switched_off(): void
    {
        config()->set('assistant.enabled', false);

        $this->get(route('site.home'))
            ->assertOk()
            ->assertDontSee('data-assistant-panel', false);
    }

    public function test_an_open_vacancy_is_reachable_by_its_title(): void
    {
        Career::query()->create([
            'title' => 'Diesel Mechanic',
            'slug' => 'diesel-mechanic',
            'department' => 'Fleet',
            'location' => 'Monrovia',
            'employment_type' => 'full_time',
            'description' => 'Maintain and repair heavy plant engines.',
            'is_active' => true,
        ]);

        $matches = (new Retriever)->search(
            'any diesel mechanic jobs?',
            app(KnowledgeBase::class)->all(),
        );

        $this->assertNotEmpty($matches);
        $this->assertSame('Vacancy: Diesel Mechanic', $matches[0]['title']);
    }
}
