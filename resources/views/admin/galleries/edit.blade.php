@extends('admin.generic.form')

@section('content')
    @parent

    <div class="card-soft p-4 mt-4">
        <div class="mb-3">
            <h2 class="h6 mb-1">Photographs</h2>
            <small class="text-muted">
                {{ $images->count() }} {{ \Illuminate\Support\Str::plural('photo', $images->count()) }} in this gallery.
            </small>
        </div>

        <form method="POST" action="{{ route('admin.galleries.images.store', $record) }}"
              enctype="multipart/form-data" class="row g-2 align-items-end mb-4">
            @csrf
            <div class="col-md-8">
                <label class="form-label" for="gallery-photos">Add photographs</label>
                <input type="file" class="form-control @error('images.*') is-invalid @enderror"
                       id="gallery-photos" name="images[]" multiple accept="image/*" required>
                @error('images.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Up to 40 at a time, 6 MB each.</div>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fa-solid fa-upload me-2"></i>Upload
                </button>
            </div>
        </form>

        @if($images->isNotEmpty())
            <div class="row g-3">
                @foreach($images as $image)
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="border rounded-3 overflow-hidden position-relative">
                            <img src="{{ $image->url }}" alt="{{ $image->alt_text }}"
                                 class="w-100" style="aspect-ratio: 4/3; object-fit: cover;" loading="lazy">

                            <form method="POST" action="{{ route('admin.galleries.images.destroy', $image) }}"
                                  class="position-absolute top-0 end-0 m-2"
                                  data-confirm="Remove this photograph?">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger"
                                        data-bs-toggle="tooltip" title="Remove">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="empty-state py-4">
                <i class="fa-regular fa-images"></i>
                <p class="mb-0">No photographs yet. The gallery stays hidden from the public page until you add some.</p>
            </div>
        @endif
    </div>
@endsection
