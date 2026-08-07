<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Notifications\ContactMessageReply;
use App\Support\DivisionScope;
use App\Support\Navigation;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class ContactMessageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ContactMessage::class);

        $messages = $this->scopedQuery()
            ->with('division:id,name')
            ->status($request->query('status'))
            ->search($request->query('q'))
            ->when($request->query('department'), fn ($q, $d) => $q->where('department', $d))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.messages.index', [
            'records' => $messages,
            'search' => $request->query('q', ''),
            'counts' => $this->scopedQuery()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status'),
            'scopedDivision' => Navigation::isDivisionScoped($request->user())
                ? $request->user()->division
                : null,
        ]);
    }

    public function show(ContactMessage $message): View
    {
        $this->authorize('view', $message);
        $this->assertWithinScope($message);

        // Opening an enquiry marks it read, so the "new" badge reflects what
        // nobody has looked at yet.
        $message->markAsRead();

        $message->load('repliedBy:id,name');

        return view('admin.messages.show', ['message' => $message]);
    }

    public function update(Request $request, ContactMessage $message): RedirectResponse
    {
        $this->authorize('update', $message);
        $this->assertWithinScope($message);

        $data = $request->validate([
            'status' => ['required', Rule::in(ContactMessage::STATUSES)],
        ]);

        $message->update($data);

        return back()->with('success', 'Enquiry marked as '.$data['status'].'.');
    }

    /**
     * Reply from inside the panel. The reply is emailed to the enquirer and
     * kept on the record, so the thread is not lost in someone's mailbox.
     */
    public function reply(Request $request, ContactMessage $message): RedirectResponse
    {
        $this->authorize('reply', $message);
        $this->assertWithinScope($message);

        $data = $request->validate([
            'reply_body' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $message->update([
            'reply_body' => $data['reply_body'],
            'replied_by' => $request->user()->id,
            'replied_at' => now(),
            'status' => 'replied',
        ]);

        Notification::route('mail', $message->email)
            ->notify(new ContactMessageReply($message, $request->user()->name));

        return back()->with('success', 'Reply sent to '.$message->email.'.');
    }

    public function destroy(ContactMessage $message): RedirectResponse
    {
        $this->authorize('delete', $message);
        $this->assertWithinScope($message);

        $message->delete();

        return redirect()
            ->route('admin.messages.index')
            ->with('success', 'Enquiry deleted.');
    }

    // ------------------------------------------------------------------

    /**
     * Base query, already narrowed to what this user may see.
     *
     * A division head sees only enquiries routed to their own division —
     * applied to the query rather than the view, so it cannot be bypassed
     * with a crafted filter or a guessed id.
     */
    protected function scopedQuery(): Builder
    {
        return ContactMessage::query()->withinDivisionOf(auth()->user());
    }

    protected function assertWithinScope(ContactMessage $message): void
    {
        DivisionScope::assertVisible(auth()->user(), $message->division_id, 'enquiry');
    }
}
