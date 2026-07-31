@extends('admin.layouts.app')

@section('title', 'SEO Metadata')
@section('page-subtitle', 'Per-page titles, descriptions and social previews. Pages without an override use the site defaults from Website Settings.')

@section('content')

    <div class="alert alert-info small d-flex gap-2">
        <i class="fa-solid fa-circle-info mt-1"></i>
        <div>
            This screen covers the static pages. Metadata for an individual project, article,
            service or vacancy is edited on that record itself.
            <strong>{{ $configured }}</strong> of {{ $pages->count() }} pages currently have an override.
        </div>
    </div>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Page</th>
                        <th>Meta title</th>
                        <th>Description</th>
                        <th>Robots</th>
                        <th class="text-end" style="width: 150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pages as $page)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $page['label'] }}</div>
                                @if($page['url'])
                                    <a class="small text-muted" href="{{ $page['url'] }}" target="_blank" rel="noopener">
                                        {{ parse_url($page['url'], PHP_URL_PATH) ?: '/' }}
                                    </a>
                                @endif
                            </td>
                            <td class="small">
                                {{ $page['record']?->title ? \Illuminate\Support\Str::limit($page['record']->title, 50) : '—' }}
                            </td>
                            <td class="small text-muted">
                                {{ $page['record']?->description ? \Illuminate\Support\Str::limit($page['record']->description, 60) : '—' }}
                            </td>
                            <td>
                                @if($page['record'])
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $page['record']->robots }}</span>
                                @else
                                    <span class="badge bg-light text-muted">default</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @can('seo.update')
                                    @if($page['record'])
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('admin.seo.edit', $page['record']) }}"
                                               class="btn btn-outline-secondary">Edit</a>
                                            <form method="POST" action="{{ route('admin.seo.destroy', $page['record']) }}"
                                                  data-confirm="Remove this override? The page will fall back to the site defaults.">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-outline-danger" type="submit"
                                                        data-bs-toggle="tooltip" title="Remove override">
                                                    <i class="fa-solid fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <a href="{{ route('admin.seo.create', ['route' => $page['route']]) }}"
                                           class="btn btn-sm btn-outline-primary">Add override</a>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

@endsection
