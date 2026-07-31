@extends('admin.layouts.app')

@section('title', 'Media Library')
@section('page-subtitle', 'Everything uploaded to the public disk, including images added from the content modules.')

@section('content')

    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card-soft p-2 mb-4">
                <div class="nav flex-column nav-pills">
                    @foreach($folders as $key => $label)
                        <a class="nav-link text-start small {{ $folder === $key ? 'active' : '' }}"
                           href="{{ route('admin.media.index', ['folder' => $key]) }}">
                            <i class="fa-regular fa-folder me-2"></i>{{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>

            @can('media.create')
                <div class="card-soft p-4">
                    <h2 class="h6 mb-3">Upload</h2>
                    <form method="POST" action="{{ route('admin.media.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <input type="file" class="form-control form-control-sm @error('files.*') is-invalid @enderror"
                                   name="files[]" multiple required>
                            @error('files.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">
                                Up to 20 files, 10 MB each. Images are resized to 2000px wide.
                                Uploads land in the media library folder.
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fa-solid fa-upload me-2"></i>Upload files
                        </button>
                    </form>
                </div>
            @endcan
        </div>

        <div class="col-lg-9">
            <div class="card-soft p-3 mb-3">
                <form method="GET" class="row g-2 align-items-center">
                    <input type="hidden" name="folder" value="{{ $folder }}">
                    <div class="col-md-6">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="search" name="q" value="{{ $search }}" class="form-control"
                                   placeholder="Filter by filename">
                        </div>
                    </div>
                    <div class="col-md-auto ms-auto">
                        <span class="small text-muted">
                            {{ $files->count() }} {{ \Illuminate\Support\Str::plural('file', $files->count()) }}
                            · {{ \App\Support\Format::fileSize($totalSize) }}
                        </span>
                    </div>
                </form>
            </div>

            @if($files->isEmpty())
                <div class="card-soft">
                    <div class="empty-state">
                        <i class="fa-regular fa-folder-open"></i>
                        <p class="mb-0">Nothing in this folder yet.</p>
                    </div>
                </div>
            @else
                <div class="row g-3">
                    @foreach($files as $file)
                        <div class="col-6 col-md-4 col-xl-3">
                            <div class="card-soft h-100 overflow-hidden">
                                @if($file['is_image'])
                                    <a href="{{ $file['url'] }}" target="_blank" rel="noopener">
                                        <img src="{{ $file['url'] }}" alt="{{ $file['name'] }}"
                                             class="w-100" style="aspect-ratio: 4/3; object-fit: cover;" loading="lazy">
                                    </a>
                                @else
                                    <div class="d-grid" style="aspect-ratio: 4/3; place-items: center; background: var(--wd-surface);">
                                        <i class="fa-solid fa-file-lines fa-2x text-primary opacity-50"></i>
                                    </div>
                                @endif

                                <div class="p-2">
                                    <div class="small text-truncate" title="{{ $file['name'] }}">{{ $file['name'] }}</div>
                                    <div class="text-muted" style="font-size:.74rem;">
                                        {{ \App\Support\Format::fileSize($file['size']) }}
                                        · {{ \Illuminate\Support\Carbon::createFromTimestamp($file['modified'])->format('j M Y') }}
                                    </div>

                                    <div class="d-flex gap-1 mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary flex-grow-1"
                                                data-copy-url="{{ $file['url'] }}"
                                                data-bs-toggle="tooltip" title="Copy the URL">
                                            <i class="fa-regular fa-copy"></i>
                                        </button>

                                        @can('media.delete')
                                            <form method="POST" action="{{ route('admin.media.destroy') }}"
                                                  data-confirm="Delete {{ $file['name'] }}? Pages still using it will show a placeholder.">
                                                @csrf @method('DELETE')
                                                <input type="hidden" name="path" value="{{ $file['path'] }}">
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        data-bs-toggle="tooltip" title="Delete">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

@endsection

@push('scripts')
<script>
    // Copy a file's public URL for pasting into an article or a settings field.
    document.querySelectorAll('[data-copy-url]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(btn.dataset.copyUrl);
                const icon = btn.querySelector('i');
                const previous = icon.className;
                icon.className = 'fa-solid fa-check';
                setTimeout(() => { icon.className = previous; }, 1500);
            } catch {
                window.prompt('Copy this URL:', btn.dataset.copyUrl);
            }
        });
    });
</script>
@endpush
