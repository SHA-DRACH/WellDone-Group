<?php

namespace Tests\Feature;

use App\Models\Division;
use App\Models\DocumentReview;
use App\Models\StaffMessage;
use App\Models\User;
use App\Notifications\DocumentReviewed;
use App\Notifications\DocumentSubmittedForReview;
use App\Notifications\StaffMessageReceived;
use App\Support\Roles;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A division sends a document up; the CEO / Managing Director review it; the
 * executive writes to a named member of staff, who replies. Documents and
 * messages stay with the people they belong to.
 */
class DocumentReviewAndMessagingTest extends TestCase
{
    use RefreshDatabase;

    protected Division $medical;

    protected Division $logistics;

    protected User $ceo;

    protected User $md;

    protected User $medicalHead;

    protected User $logisticsHead;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Notification::fake();

        $this->seed(RolePermissionSeeder::class);

        $this->medical = Division::factory()->create(['name' => 'Medical Supply', 'slug' => 'medical', 'is_active' => true]);
        $this->logistics = Division::factory()->create(['name' => 'Logistics', 'slug' => 'logistics', 'is_active' => true]);

        $this->ceo = $this->staff(Roles::CEO);
        $this->md = $this->staff(Roles::MANAGING_DIRECTOR);
        $this->medicalHead = $this->staff(Roles::DIVISION_MANAGER, $this->medical);
        $this->logisticsHead = $this->staff(Roles::DIVISION_MANAGER, $this->logistics);
    }

    protected function staff(string $role, ?Division $division = null): User
    {
        $user = User::factory()->create([
            'division_id' => $division?->id,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $user->assignRole($role);

        return $user;
    }

    protected function submit(User $as, Division $division, array $overrides = [])
    {
        return $this->actingAs($as)->post(route('admin.division.reviews.store', $division->slug), $overrides + [
            'doc_title' => 'October report',
            'doc_category' => 'report',
            'doc_notes' => 'For approval by Friday.',
            'doc_file' => UploadedFile::fake()->create('october.pdf', 120, 'application/pdf'),
        ]);
    }

    // ------------------------------------------------------------ reviews

    public function test_a_division_head_submits_a_document_and_both_reviewers_are_told(): void
    {
        $this->submit($this->medicalHead, $this->medical)->assertRedirect();

        $review = DocumentReview::query()->sole();

        $this->assertSame($this->medical->id, $review->division_id);
        $this->assertSame('pending', $review->status);
        Storage::disk('local')->assertExists($review->file_path);
        Notification::assertSentTo([$this->ceo, $this->md], DocumentSubmittedForReview::class);
        Notification::assertNotSentTo($this->logisticsHead, DocumentSubmittedForReview::class);
    }

    public function test_a_division_head_cannot_submit_for_another_division(): void
    {
        $this->submit($this->medicalHead, $this->logistics)->assertForbidden();

        $this->assertDatabaseCount('document_reviews', 0);
    }

    public function test_a_document_is_required(): void
    {
        $this->submit($this->medicalHead, $this->medical, ['doc_file' => null])
            ->assertSessionHasErrorsIn('review', 'doc_file');
    }

    public function test_another_division_cannot_open_the_document(): void
    {
        $this->submit($this->medicalHead, $this->medical);
        $review = DocumentReview::query()->sole();

        $this->actingAs($this->logisticsHead)->get(route('admin.reviews.show', $review))->assertForbidden();
        $this->actingAs($this->logisticsHead)->get(route('admin.reviews.file', $review))->assertForbidden();
        $this->actingAs($this->logisticsHead)->get(route('admin.reviews.index'))->assertOk()->assertDontSee('October report');
    }

    public function test_the_ceo_opens_and_approves_and_the_submitter_is_told(): void
    {
        $this->submit($this->medicalHead, $this->medical);
        $review = DocumentReview::query()->sole();

        $this->actingAs($this->ceo)->get(route('admin.dashboard'))->assertOk()->assertSee('October report');
        $this->actingAs($this->ceo)->get(route('admin.reviews.file', $review))->assertOk();

        $this->actingAs($this->ceo)
            ->post(route('admin.reviews.decide', $review), ['decision' => 'approved'])
            ->assertRedirect(route('admin.reviews.show', $review));

        $review->refresh();
        $this->assertSame('approved', $review->status);
        $this->assertSame($this->ceo->id, $review->reviewed_by);
        Notification::assertSentTo($this->medicalHead, DocumentReviewed::class);
    }

    public function test_asking_for_changes_needs_a_comment(): void
    {
        $this->submit($this->medicalHead, $this->medical);
        $review = DocumentReview::query()->sole();

        $this->actingAs($this->md)
            ->post(route('admin.reviews.decide', $review), ['decision' => 'changes_requested'])
            ->assertSessionHasErrors('review_comment');

        $this->assertSame('pending', $review->fresh()->status);
    }

    public function test_a_division_head_cannot_decide(): void
    {
        $this->submit($this->medicalHead, $this->medical);
        $review = DocumentReview::query()->sole();

        $this->actingAs($this->medicalHead)
            ->post(route('admin.reviews.decide', $review), ['decision' => 'approved'])
            ->assertForbidden();
    }

    // ------------------------------------------------------------ messages

    public function test_the_ceo_sends_a_message_with_a_document_and_the_recipient_replies(): void
    {
        $this->actingAs($this->ceo)->post(route('admin.inbox.store'), [
            'recipient_id' => $this->medicalHead->id,
            'subject' => 'Q1 budget guidance',
            'body' => 'Please use the attached guidance.',
            'attachment' => UploadedFile::fake()->create('guidance.pdf', 80, 'application/pdf'),
        ])->assertRedirect();

        $message = StaffMessage::query()->sole();
        Notification::assertSentTo($this->medicalHead, StaffMessageReceived::class);

        // The recipient reads it (marking it read) and opens the document.
        $this->actingAs($this->medicalHead)->get(route('admin.inbox.show', $message))->assertOk()->assertSee('Q1 budget guidance');
        $this->assertNotNull($message->fresh()->read_at);
        $this->actingAs($this->medicalHead)->get(route('admin.inbox.file', $message))->assertOk();

        // …and replies, which reaches the CEO.
        $this->actingAs($this->medicalHead)
            ->post(route('admin.inbox.reply', $message), ['body' => 'Received, thank you.'])
            ->assertRedirect();

        $reply = StaffMessage::query()->latest('id')->first();
        $this->assertSame($this->ceo->id, $reply->recipient_id);
        $this->assertSame('Re: Q1 budget guidance', $reply->subject);
        Notification::assertSentTo($this->ceo, StaffMessageReceived::class);
    }

    public function test_a_message_is_private_to_sender_and_recipient(): void
    {
        $this->actingAs($this->ceo)->post(route('admin.inbox.store'), [
            'recipient_id' => $this->medicalHead->id,
            'subject' => 'Private',
            'body' => 'For you only.',
            'attachment' => UploadedFile::fake()->create('private.pdf', 10, 'application/pdf'),
        ]);
        $message = StaffMessage::query()->sole();

        $this->actingAs($this->logisticsHead)->get(route('admin.inbox.show', $message))->assertForbidden();
        $this->actingAs($this->logisticsHead)->get(route('admin.inbox.file', $message))->assertForbidden();
    }

    public function test_a_division_head_cannot_start_a_new_message(): void
    {
        $this->actingAs($this->medicalHead)->get(route('admin.inbox.create'))->assertForbidden();
        $this->actingAs($this->medicalHead)->get(route('admin.inbox.index'))->assertOk();
    }
}
