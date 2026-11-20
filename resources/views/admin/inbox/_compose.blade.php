{{--
    Write to one member of staff, with an optional document.
    Used on the Compose page and on the executive's dashboard.

    Expects: $recipients (id => label). Optional: $selected (recipient id), $compact.
--}}
@php($msgErrors = $errors->getBag('message'))

<form method="POST" action="{{ route('admin.inbox.store') }}" enctype="multipart/form-data" novalidate>
    @csrf

    @if($msgErrors->any())
        <div class="alert alert-danger small py-2">
            @foreach($msgErrors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <div class="mb-2">
        <label class="form-label small mb-1" for="msg-to">To <span class="required-mark">*</span></label>
        <select id="msg-to" name="recipient_id" required
                class="form-select form-select-sm {{ $msgErrors->has('recipient_id') ? 'is-invalid' : '' }}">
            <option value="">Choose a member of staff…</option>
            @foreach($recipients as $id => $label)
                <option value="{{ $id }}" @selected((int) old('recipient_id', $selected ?? 0) === $id)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="mb-2">
        <label class="form-label small mb-1" for="msg-subject">Subject <span class="required-mark">*</span></label>
        <input type="text" id="msg-subject" name="subject" value="{{ old('subject') }}" maxlength="200" required
               class="form-control form-control-sm {{ $msgErrors->has('subject') ? 'is-invalid' : '' }}">
    </div>

    <div class="mb-2">
        <label class="form-label small mb-1" for="msg-body">Message <span class="required-mark">*</span></label>
        <textarea id="msg-body" name="body" rows="{{ ($compact ?? false) ? 4 : 8 }}" maxlength="10000" required
                  class="form-control form-control-sm {{ $msgErrors->has('body') ? 'is-invalid' : '' }}">{{ old('body') }}</textarea>
    </div>

    <div class="mb-3">
        <label class="form-label small mb-1" for="msg-attachment">Attach a document <span class="text-muted">(optional)</span></label>
        <input type="file" id="msg-attachment" name="attachment"
               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.zip"
               class="form-control form-control-sm {{ $msgErrors->has('attachment') ? 'is-invalid' : '' }}">
        <div class="form-text">PDF, Word, Excel, image or zip · up to 20 MB</div>
    </div>

    <button type="submit" class="btn btn-primary btn-sm">
        <i class="fa-solid fa-paper-plane me-1"></i>Send
    </button>
    <span class="small text-muted ms-2">They receive it in their inbox and by email.</span>
</form>
