@extends('admin.layouts.app')

@section('title', 'Newsletter Subscribers')
@section('page-subtitle', 'Double opt-in: only confirmed addresses are mailable, and only those are exported.')

@section('page-actions')
    @can('export', App\Models\Newsletter::class)
        <a href="{{ route('admin.newsletters.export') }}" class="btn btn-primary">
            <i class="fa-solid fa-file-csv me-2"></i>Export confirmed ({{ $confirmed }})
        </a>
    @endcan
@endsection

@section('content')

    <div class="row g-3 mb-4">
        @foreach([
            ['label' => 'Confirmed',    'value' => $confirmed,    'icon' => 'fa-circle-check',  'colour' => 'success'],
            ['label' => 'Awaiting confirmation', 'value' => $pending, 'icon' => 'fa-hourglass-half', 'colour' => 'warning'],
            ['label' => 'Unsubscribed', 'value' => $unsubscribed, 'icon' => 'fa-circle-minus',  'colour' => 'secondary'],
        ] as $tile)
            <div class="col-md-4">
                <div class="stat-tile h-100">
                    <span class="stat-tile__icon bg-{{ $tile['colour'] }}-subtle text-{{ $tile['colour'] }}">
                        <i class="fa-solid {{ $tile['icon'] }}"></i>
                    </span>
                    <div>
                        <div class="stat-tile__value">{{ number_format($tile['value']) }}</div>
                        <div class="stat-tile__label">{{ $tile['label'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach(['' => 'All', 'confirmed' => 'Confirmed', 'pending' => 'Awaiting confirmation', 'unsubscribed' => 'Unsubscribed'] as $key => $label)
            <a href="{{ route('admin.newsletters.index', $key ? ['state' => $key] : []) }}"
               class="btn btn-sm {{ request('state', '') === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="table-card mb-3">
        <div class="p-3 border-bottom">
            <form method="GET" class="row g-2">
                <input type="hidden" name="state" value="{{ request('state') }}">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fa-solid fa-magnifying-glass"></i></span>
                        <input type="search" name="q" value="{{ $search }}" class="form-control"
                               placeholder="Email address">
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Name</th>
                        <th>State</th>
                        <th>Signed up</th>
                        <th class="text-end" style="width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $subscriber)
                        <tr>
                            <td>{{ $subscriber->email }}</td>
                            <td class="small">{{ $subscriber->name ?: '—' }}</td>
                            <td>
                                @if($subscriber->unsubscribed_at)
                                    <span class="badge bg-secondary-subtle text-secondary">Unsubscribed</span>
                                @elseif($subscriber->confirmed_at)
                                    <span class="badge bg-success-subtle text-success">Confirmed</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">Awaiting confirmation</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $subscriber->created_at->format('j M Y') }}</td>
                            <td class="text-end">
                                @can('delete', $subscriber)
                                    <form method="POST" action="{{ route('admin.newsletters.destroy', $subscriber) }}"
                                          data-confirm="Remove this subscriber?">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit"
                                                data-bs-toggle="tooltip" title="Remove">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <i class="fa-solid fa-paper-plane"></i>
                                    <p class="mb-0">No subscribers match your filters.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $records->links() }}

@endsection
