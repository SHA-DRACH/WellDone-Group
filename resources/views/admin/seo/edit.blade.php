@extends('admin.layouts.app')

@section('title', 'SEO — '.$pageLabel)
@section('page-title', 'SEO: '.$pageLabel)
@section('page-subtitle', 'Anything left blank falls back to the site defaults in Website Settings.')

@section('page-actions')
    <a href="{{ route('admin.seo.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Back to SEO
    </a>
@endsection

@section('content')

    <form method="POST"
          action="{{ $record->exists ? route('admin.seo.update', $record) : route('admin.seo.store') }}"
          enctype="multipart/form-data" novalidate>
        @csrf
        @if($record->exists) @method('PUT') @endif
        <input type="hidden" name="route_name" value="{{ $record->route_name }}">

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card-soft p-4 mb-4">
                    <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Search results</h2>

                    <x-form.input name="title" label="Meta title" :value="$record->title"
                                  help="Aim for 50–60 characters. Longer titles get truncated in search results." />

                    <x-form.textarea name="description" label="Meta description" :value="$record->description" :rows="3"
                                     help="Aim for 150–160 characters. This is the snippet under the title." />

                    <x-form.textarea name="keywords" label="Keywords" :value="$record->keywords" :rows="2"
                                     help="Comma-separated. Most search engines ignore these, but they cost nothing." />
                </div>

                <div class="card-soft p-4">
                    <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">
                        Social sharing preview
                    </h2>

                    <x-form.input name="og_title" label="Social title" :value="$record->og_title"
                                  help="Falls back to the meta title." />

                    <x-form.textarea name="og_description" label="Social description"
                                     :value="$record->og_description" :rows="3"
                                     help="Falls back to the meta description." />

                    <x-form.image name="og_image" label="Social share image"
                                  :current="$record->og_image_url"
                                  help="1200 × 630px works best across LinkedIn, Facebook and X." />
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card-soft p-4 mb-4">
                    <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Indexing</h2>

                    <x-form.select name="robots" label="Robots directive" required
                                   :value="$record->robots ?: 'index, follow'"
                                   :options="[
                                       'index, follow' => 'Index and follow (default)',
                                       'index, nofollow' => 'Index, do not follow links',
                                       'noindex, follow' => 'Do not index, follow links',
                                       'noindex, nofollow' => 'Do not index or follow',
                                   ]"
                                   help="Leave as the default unless you have a reason to hide the page." />

                    <x-form.input name="canonical_url" label="Canonical URL" :value="$record->canonical_url"
                                  help="Only set this when the same content is reachable at another address." />
                </div>

                <div class="card-soft p-4">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Save metadata
                    </button>
                    <a href="{{ route('admin.seo.index') }}" class="btn btn-outline-secondary w-100">Cancel</a>
                </div>
            </div>
        </div>
    </form>

@endsection
