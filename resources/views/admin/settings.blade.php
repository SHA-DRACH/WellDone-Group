@extends('admin.layouts.app')

@section('title', 'Website Settings')
@section('page-subtitle', 'Company details, contact information, homepage copy and SEO defaults — all editable without a developer.')

@section('content')

    <div class="row g-4">
        <div class="col-lg-3">
            <div class="card-soft p-2">
                <div class="nav flex-column nav-pills">
                    @foreach($groups->keys() as $group)
                        <a class="nav-link text-start {{ $group === $activeGroup ? 'active' : '' }}"
                           href="{{ route('admin.settings.edit', $group) }}">
                            @php($icon = match($group) {
                                'company' => 'fa-building',
                                'contact' => 'fa-address-book',
                                'social' => 'fa-share-nodes',
                                'home' => 'fa-house',
                                'seo' => 'fa-magnifying-glass-chart',
                                default => 'fa-sliders',
                            })
                            <i class="fa-solid {{ $icon }} me-2"></i>{{ ucfirst($group) }}
                            <span class="badge bg-light text-muted float-end">{{ $groups[$group]->count() }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-9">
            <form method="POST" action="{{ route('admin.settings.update', $activeGroup) }}" enctype="multipart/form-data">
                @csrf @method('PUT')

                <div class="card-soft p-4">
                    <h2 class="h5 mb-4">{{ ucfirst($activeGroup) }} settings</h2>

                    @foreach($settings as $setting)
                        @php($name = $setting->key)

                        @switch($setting->type)
                            @case('boolean')
                                <x-form.checkbox :name="$name" :label="$setting->label"
                                                 :value="(bool) $setting->typed_value"
                                                 :help="$setting->description" />
                                @break

                            @case('text')
                                <x-form.textarea :name="$name" :label="$setting->label"
                                                 :value="$setting->value" :rows="4"
                                                 :help="$setting->description" />
                                @break

                            @case('richtext')
                                <x-form.editor :name="$name" :label="$setting->label"
                                               :value="$setting->value"
                                               :help="$setting->description" />
                                @break

                            @case('json')
                                <x-form.textarea :name="$name" :label="$setting->label"
                                                 :value="$setting->value" :rows="8"
                                                 class="form-control font-monospace small"
                                                 :help="$setting->description ?: 'JSON. Invalid JSON is rejected and the previous value kept.'" />
                                @break

                            @case('image')
                                <x-form.image :name="$name" :label="$setting->label"
                                              :current="$setting->value
                                                  ? (str_starts_with($setting->value, '/')
                                                      ? (file_exists(public_path(ltrim($setting->value, '/'))) ? asset(ltrim($setting->value, '/')) : null)
                                                      : \Illuminate\Support\Facades\Storage::disk('public')->url($setting->value))
                                                  : null"
                                              :help="$setting->description" />
                                @break

                            @case('file')
                                <div class="mb-3">
                                    <label class="form-label" for="field-{{ $name }}">{{ $setting->label }}</label>
                                    <input type="file" id="field-{{ $name }}" name="{{ $name }}" class="form-control">
                                    @if($setting->description)<div class="form-text">{{ $setting->description }}</div>@endif
                                    @if($setting->value)
                                        <div class="form-text">
                                            Current: <code>{{ basename($setting->value) }}</code>
                                            <div class="form-check mt-1">
                                                <input class="form-check-input" type="checkbox"
                                                       name="remove_{{ $name }}" value="1" id="remove-{{ $name }}">
                                                <label class="form-check-label small text-danger" for="remove-{{ $name }}">
                                                    Remove the current file
                                                </label>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                @break

                            @case('video')
                                <x-form.video :name="$name" :label="$setting->label"
                                              :current="$setting->value ? \Illuminate\Support\Facades\Storage::disk('public')->url($setting->value) : null"
                                              :help="$setting->description" />
                                @break

                            @case('video_url')
                                <x-form.input :name="$name" :label="$setting->label" type="url"
                                              :value="$setting->value" :help="$setting->description"
                                              placeholder="https://www.youtube.com/watch?v=…" />
                                @break

                            @case('integer')
                                <x-form.input :name="$name" :label="$setting->label" type="number"
                                              :value="$setting->value" :help="$setting->description" />
                                @break

                            @case('color')
                                <x-form.input :name="$name" :label="$setting->label" type="color"
                                              :value="$setting->value" :help="$setting->description" />
                                @break

                            @default
                                <x-form.input :name="$name" :label="$setting->label"
                                              :value="$setting->value" :help="$setting->description" />
                        @endswitch
                    @endforeach

                    <div class="d-flex gap-2 mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Save {{ $activeGroup }} settings
                        </button>
                        <a href="{{ route('site.home') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-arrow-up-right-from-square me-2"></i>Preview site
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection
