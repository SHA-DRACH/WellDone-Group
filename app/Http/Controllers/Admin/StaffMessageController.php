<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffMessage;
use App\Models\User;
use App\Notifications\StaffMessageReceived;
use App\Services\MediaUploader;
use App\Support\Permissions;
use App\Support\Roles;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Staff-to-staff messages with an optional document.
 *
 * The CEO and Managing Director (and administrators) start conversations with
 * any member of staff; every staff member has an inbox and can reply. Each new
 * message is also announced by email.
 */
class StaffMessageController extends Controller
{
    public function __construct(protected MediaUploader $uploader) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $box = $request->query('box') === 'sent' ? 'sent' : 'inbox';

        $messages = StaffMessage::query()
            ->with(['sender:id,name,position,avatar', 'recipient:id,name,position,avatar'])
            ->where($box === 'sent' ? 'sender_id' : 'recipient_id', $user->id)
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.inbox.index', [
            'messages' => $messages,
            'box' => $box,
            'unread' => StaffMessage::query()->unreadFor($user)->count(),
            'canSend' => $user->can(Permissions::STAFF_MESSAGES_SEND),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', StaffMessage::class);

        return view('admin.inbox.create', [
            'recipients' => self::recipientOptions($request->user()),
            'selected' => $request->integer('to') ?: null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', StaffMessage::class);

        $sender = $request->user();
        $allowed = self::recipientOptions($sender)->keys()->all();

        $data = $request->validateWithBag('message', [
            'recipient_id' => ['required', 'integer', 'in:'.implode(',', $allowed ?: [0])],
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'mimes:'.StaffMessage::FILE_TYPES, 'max:'.StaffMessage::MAX_KB],
        ], [
            'recipient_id.required' => 'Choose who the message is for.',
            'recipient_id.in' => 'Choose a member of staff from the list.',
            'attachment.mimes' => 'Attach a PDF, Word, Excel, image or zip file.',
            'attachment.max' => 'The attachment must be 20 MB or smaller.',
        ]);

        $message = $this->send($sender, (int) $data['recipient_id'], $data['subject'], $data['body'], $request);

        return redirect()->route('admin.inbox.show', $message)
            ->with('success', 'Message sent to '.$message->recipient->name.'. They have been emailed too.');
    }

    public function show(Request $request, StaffMessage $message): View
    {
        $this->authorize('view', $message);

        if ($message->recipient_id === $request->user()->id && ! $message->read_at) {
            $message->forceFill(['read_at' => now()])->save();
        }

        $message->load(['sender:id,name,position,email,avatar', 'recipient:id,name,position,email,avatar', 'replyTo.sender:id,name']);

        return view('admin.inbox.show', ['message' => $message]);
    }

    public function reply(Request $request, StaffMessage $message): RedirectResponse
    {
        $this->authorize('reply', $message);

        $data = $request->validateWithBag('reply', [
            'body' => ['required', 'string', 'max:10000'],
            'attachment' => ['nullable', 'file', 'mimes:'.StaffMessage::FILE_TYPES, 'max:'.StaffMessage::MAX_KB],
        ], [
            'body.required' => 'Write your reply.',
        ]);

        $subject = Str::startsWith($message->subject, 'Re: ') ? $message->subject : 'Re: '.$message->subject;

        $reply = $this->send($request->user(), $message->sender_id, $subject, $data['body'], $request, $message->id);

        return redirect()->route('admin.inbox.show', $reply)
            ->with('success', 'Reply sent to '.$reply->recipient->name.'.');
    }

    public function file(Request $request, StaffMessage $message): Response
    {
        $this->authorize('view', $message);

        abort_unless($message->hasAttachment() && Storage::disk('local')->exists($message->file_path), 404);

        if ($request->boolean('download') || ! $message->previewable) {
            return Storage::disk('local')->download($message->file_path, $message->original_name);
        }

        return Storage::disk('local')->response($message->file_path, $message->original_name, [
            'Content-Disposition' => 'inline; filename="'.addslashes((string) $message->original_name).'"',
        ]);
    }

    protected function send(User $sender, int $recipientId, string $subject, string $body, Request $request, ?int $replyTo = null): StaffMessage
    {
        $attributes = [
            'sender_id' => $sender->id,
            'recipient_id' => $recipientId,
            'reply_to_id' => $replyTo,
            'subject' => $subject,
            'body' => $body,
        ];

        if ($file = $request->file('attachment')) {
            $attributes += [
                'file_path' => $this->uploader->privateFile($file, 'messages'),
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'file_size' => $file->getSize(),
            ];
        }

        $message = StaffMessage::query()->create($attributes);
        $message->load('recipient');

        activity('staff-message')->performedOn($message)->causedBy($sender)->event('sent')
            ->log('Sent a message to '.$message->recipient->name);

        $message->recipient->notify(new StaffMessageReceived($message));

        return $message;
    }

    /**
     * Active staff the sender may write to: everyone with a staff role
     * except themselves, labelled with position and division.
     *
     * @return Collection<int, string>
     */
    public static function recipientOptions(User $sender): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereKeyNot($sender->id)
            ->whereHas('roles', fn ($q) => $q->whereIn('name', Roles::staff()))
            ->with('division:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'position', 'division_id'])
            ->mapWithKeys(fn (User $u) => [$u->id => $u->name
                .($u->position ? ' — '.$u->position : '')
                .($u->division ? ' ('.$u->division->name.')' : '')]);
    }
}
