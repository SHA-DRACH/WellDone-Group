@extends('admin.layouts.app')

@section('title', 'Employee Documents')
@section('page-subtitle', 'Contracts, ID copies, permits and certificates — stored privately, with expiry tracking.')

@section('page-actions')
    <a href="{{ route('admin.employee-documents.export', $filters) }}" class="btn btn-success">
        <i class="fa-solid fa-file-excel me-2"></i>Download Excel
    </a>
    @can('create', App\Models\EmployeeDocument::class)
        <a href="{{ route('admin.employee-documents.create') }}" class="btn btn-primary">
            <i class="fa-solid fa-upload me-2"></i>Upload document
        </a>
    @endcan
@endsection

@section('content')
    @include('admin.hr._tabs')

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="metric-card metric-card--accent">
                <div class="metric-card__label">Documents on file</div>
                <div class="metric-card__value">{{ number_format($summary['total']) }}</div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <a href="{{ route('admin.employee-documents.index', ['expiry' => 'expiring']) }}" class="text-reset text-decoration-none">
                <div class="metric-card">
                    <span class="metric-card__icon bg-warning-subtle text-warning"><i class="fa-solid fa-hourglass-half"></i></span>
                    <div class="metric-card__label">Expiring ≤ 60 days</div>
                    <div class="metric-card__value">{{ $summary['expiring'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <a href="{{ route('admin.employee-documents.index', ['expiry' => 'expired']) }}" class="text-reset text-decoration-none">
                <div class="metric-card">
                    <span class="metric-card__icon bg-danger-subtle text-danger"><i class="fa-solid fa-triangle-exclamation"></i></span>
                    <div class="metric-card__label">Expired</div>
                    <div class="metric-card__value">{{ $summary['expired'] }}</div>
                </div>
            </a>
        </div>
        <div class="col-6 col-xl-3">
            <div class="metric-card">
                <span class="metric-card__icon bg-info-subtle text-info"><i class="fa-solid fa-file-signature"></i></span>
                <div class="metric-card__label">Staff without a contract on file</div>
                <div class="metric-card__value">{{ $summary['missing_contracts'] }}</div>
            </div>
        </div>
    </div>

    <div class="table-card">
        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control form-control-sm" placeholder="Title or employee">
                </div>
                <div class="col-6 col-md-3">
                    <select name="type" class="form-select form-select-sm" data-auto-submit>
                        <option value="">All document types</option>
                        @foreach(App\Models\EmployeeDocument::TYPES as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['type'] ?? null) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select name="expiry" class="form-select form-select-sm" data-auto-submit>
                        <option value="">Any validity</option>
                        <option value="expiring" @selected(($filters['expiry'] ?? null) === 'expiring')>Expiring soon</option>
                        <option value="expired" @selected(($filters['expiry'] ?? null) === 'expired')>Expired</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <button class="btn btn-sm btn-primary"><i class="fa-solid fa-filter"></i></button>
                    @if(array_filter($filters))
                        <a href="{{ route('admin.employee-documents.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Document</th>
                        <th>Issued</th>
                        <th>Validity</th>
                        <th>Uploaded</th>
                        <th class="text-end" style="width: 120px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $document)
                        <tr>
                            <td>
                                <a href="{{ route('admin.employees.show', $document->employee_id) }}#tab-documents" class="fw-semibold">{{ $document->employee?->full_name }}</a>
                                <div class="small text-muted">{{ $document->employee?->employee_number }}</div>
                            </td>
                            <td>
                                <div>{{ $document->title }}</div>
                                <div class="small text-muted">{{ $document->type_label }}</div>
                            </td>
                            <td class="small">{{ $document->issue_date?->format('j M Y') ?? '—' }}</td>
                            <td><span class="badge bg-{{ $document->expiry_color }}-subtle text-{{ $document->expiry_color }}">{{ $document->expiry_label }}</span></td>
                            <td class="small text-muted">
                                {{ $document->created_at->format('j M Y') }}
                                <div>{{ $document->uploader?->name }}</div>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    @if($document->file_path)
                                        <a href="{{ route('admin.employee-documents.download', $document) }}" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Download">
                                            <i class="fa-solid fa-download"></i>
                                        </a>
                                    @endif
                                    @can('update', $document)
                                        <a href="{{ route('admin.employee-documents.edit', $document) }}" class="btn btn-outline-secondary" data-bs-toggle="tooltip" title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                    @endcan
                                    @can('delete', $document)
                                        <form method="POST" action="{{ route('admin.employee-documents.destroy', $document) }}" data-confirm="Remove {{ $document->title }} from the file?">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-outline-danger" type="submit"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-5">
                            <i class="fa-solid fa-folder-open fa-2x d-block mb-2 opacity-50"></i>No documents{{ array_filter($filters) ? ' match these filters' : ' uploaded yet' }}.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
            <div class="p-3 border-top">{{ $records->links() }}</div>
        @endif
    </div>
@endsection
