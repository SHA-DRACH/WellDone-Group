@if(session('status') || $errors->any())
    <div class="container mt-4">
        @if(session('status'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-start gap-2" role="status" data-auto-dismiss="8000">
                <i class="fa-solid fa-circle-check mt-1"></i>
                <div>{{ session('status') }}</div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Dismiss"></button>
            </div>
        @endif

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
    </div>
@endif
