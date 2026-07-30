@foreach(['success' => 'circle-check', 'error' => 'circle-exclamation', 'warning' => 'triangle-exclamation', 'info' => 'circle-info'] as $key => $icon)
    @if(session($key))
        <div class="alert alert-{{ $key === 'error' ? 'danger' : $key }} alert-dismissible fade show d-flex align-items-start gap-2"
             role="alert" data-auto-dismiss="6000">
            <i class="fa-solid fa-{{ $icon }} mt-1"></i>
            <div>{{ session($key) }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Dismiss"></button>
        </div>
    @endif
@endforeach

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <div class="d-flex align-items-start gap-2">
            <i class="fa-solid fa-triangle-exclamation mt-1"></i>
            <div>
                <strong>Please correct the following:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Dismiss"></button>
        </div>
    </div>
@endif
