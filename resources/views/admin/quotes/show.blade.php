@extends('admin.layouts.app')

@section('title', 'Request '.$quote->reference)
@section('page-title', $quote->project_title)
@section('page-subtitle', $quote->reference.' · received '.$quote->created_at->format('j F Y, H:i'))

@section('page-actions')
    <a href="{{ route('admin.quotes.index') }}" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-2"></i>Back to queue
    </a>

    <a href="mailto:{{ $quote->email }}?subject={{ rawurlencode('Your request '.$quote->reference) }}"
       class="btn btn-primary">
        <i class="fa-solid fa-reply me-2"></i>Reply by email
    </a>
@endsection

@section('content')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card-soft p-4 mb-4">
                <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Requirement</h2>

                <div class="mb-3">
                    {!! nl2br(e($quote->description)) !!}
                </div>

                <dl class="row small mb-0 border-top pt-3">
                    <dt class="col-sm-4 text-muted fw-normal">Division</dt>
                    <dd class="col-sm-8">{{ $quote->division?->name ?? 'Not specified' }}</dd>

                    <dt class="col-sm-4 text-muted fw-normal">Services requested</dt>
                    <dd class="col-sm-8">
                        @forelse($quote->services as $service)
                            <span class="badge bg-primary-subtle text-primary mb-1">{{ $service->name }}</span>
                        @empty
                            Not specified
                        @endforelse
                    </dd>

                    <dt class="col-sm-4 text-muted fw-normal">Budget</dt>
                    <dd class="col-sm-8">{{ $quote->budget_range ?: 'Not stated' }} ({{ $quote->currency }})</dd>

                    <dt class="col-sm-4 text-muted fw-normal">Timeline</dt>
                    <dd class="col-sm-8">{{ $quote->timeline ?: 'Not stated' }}</dd>

                    <dt class="col-sm-4 text-muted fw-normal">Preferred start</dt>
                    <dd class="col-sm-8">{{ $quote->preferred_start_date?->format('j F Y') ?: 'Not stated' }}</dd>

                    <dt class="col-sm-4 text-muted fw-normal">Delivery location</dt>
                    <dd class="col-sm-8">{{ $quote->delivery_location ?: 'Not stated' }}</dd>
                </dl>
            </div>

            @if($quote->files->isNotEmpty())
                <div class="card-soft p-4 mb-4">
                    <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">
                        Attachments ({{ $quote->files->count() }})
                    </h2>

                    <ul class="list-unstyled mb-0">
                        @foreach($quote->files as $file)
                            <li class="d-flex justify-content-between align-items-center {{ $loop->last ? '' : 'mb-2 pb-2 border-bottom' }}">
                                <span class="small">
                                    <i class="fa-solid fa-paperclip text-muted me-2"></i>{{ $file->original_name }}
                                    <span class="text-muted">({{ $file->human_size }})</span>
                                </span>

                                @can('downloadFiles', $quote)
                                    <a href="{{ route('admin.quotes.files.download', $file) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fa-solid fa-download"></i>
                                    </a>
                                @endcan
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Working the lead --}}
            @can('update', $quote)
                <div class="card-soft p-4">
                    <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Update this request</h2>

                    <form method="POST" action="{{ route('admin.quotes.update', $quote) }}">
                        @csrf @method('PATCH')

                        <div class="row">
                            <div class="col-md-4">
                                <x-form.select name="status" label="Status" :value="$quote->status" required
                                    :options="collect(\App\Models\QuoteRequest::STATUSES)
                                        ->mapWithKeys(fn ($s) => [$s => ucwords(str_replace('_', ' ', $s))])->all()" />
                            </div>

                            <div class="col-md-4">
                                <x-form.select name="priority" label="Priority" :value="$quote->priority" required
                                    :options="collect(\App\Models\QuoteRequest::PRIORITIES)
                                        ->mapWithKeys(fn ($p) => [$p => ucfirst($p)])->all()" />
                            </div>

                            <div class="col-md-4">
                                <x-form.input name="quoted_amount" type="number" step="0.01" min="0"
                                              label="Quoted amount ({{ $quote->currency }})"
                                              :value="$quote->quoted_amount" />
                            </div>

                            @can('assign', $quote)
                                <div class="col-md-6">
                                    <x-form.select name="assigned_to" label="Assigned to"
                                                   :value="$quote->assigned_to"
                                                   :options="$assignees->all()"
                                                   placeholder="Unassigned" />
                                </div>
                            @endcan

                            <div class="col-12">
                                <x-form.textarea name="internal_notes" label="Internal notes"
                                                 :value="$quote->internal_notes" :rows="4"
                                                 help="Only visible to staff — never shown to the requester." />
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-floppy-disk me-2"></i>Save
                        </button>
                    </form>
                </div>
            @endcan
        </div>

        <div class="col-lg-4">
            <div class="card-soft p-4 mb-4">
                <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Requester</h2>

                <dl class="row small mb-0">
                    <dt class="col-5 text-muted fw-normal">Name</dt>
                    <dd class="col-7">{{ $quote->contact_name }}</dd>

                    <dt class="col-5 text-muted fw-normal">Organisation</dt>
                    <dd class="col-7">{{ $quote->company_name ?: '—' }}</dd>

                    <dt class="col-5 text-muted fw-normal">Email</dt>
                    <dd class="col-7"><a href="mailto:{{ $quote->email }}">{{ $quote->email }}</a></dd>

                    <dt class="col-5 text-muted fw-normal">Phone</dt>
                    <dd class="col-7"><a href="tel:{{ preg_replace('/[^\d+]/', '', $quote->phone) }}">{{ $quote->phone }}</a></dd>

                    <dt class="col-5 text-muted fw-normal">Location</dt>
                    <dd class="col-7">{{ collect([$quote->city, $quote->country])->filter()->implode(', ') ?: '—' }}</dd>
                </dl>
            </div>

            <div class="card-soft p-4 mb-4">
                <h2 class="h6 text-uppercase text-muted mb-3" style="letter-spacing:.1em;">Progress</h2>

                <dl class="row small mb-0">
                    <dt class="col-6 text-muted fw-normal">Status</dt>
                    <dd class="col-6 text-end">
                        <span class="badge bg-{{ $quote->status_color }}-subtle text-{{ $quote->status_color }}">
                            {{ $quote->status_label }}
                        </span>
                    </dd>
                    <dt class="col-6 text-muted fw-normal">Received</dt>
                    <dd class="col-6 text-end">{{ $quote->created_at->format('j M Y') }}</dd>
                    <dt class="col-6 text-muted fw-normal">First response</dt>
                    <dd class="col-6 text-end">{{ $quote->responded_at?->format('j M Y') ?: 'Pending' }}</dd>
                    <dt class="col-6 text-muted fw-normal">Quoted</dt>
                    <dd class="col-6 text-end">
                        {{ $quote->quoted_amount ? \App\Support\Format::money($quote->quoted_amount, $quote->currency) : '—' }}
                    </dd>
                    <dt class="col-6 text-muted fw-normal">Source</dt>
                    <dd class="col-6 text-end">{{ ucfirst($quote->source) }}</dd>
                </dl>
            </div>

            @can('delete', $quote)
                <div class="card-soft p-4">
                    <h2 class="h6 text-danger mb-2">Delete request</h2>
                    <p class="small text-muted">
                        Removes the request and its attachments permanently.
                    </p>
                    <form method="POST" action="{{ route('admin.quotes.destroy', $quote) }}"
                          data-confirm="Permanently delete {{ $quote->reference }} and its attachments?">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger btn-sm w-100">
                            <i class="fa-solid fa-trash me-2"></i>Delete permanently
                        </button>
                    </form>
                </div>
            @endcan
        </div>
    </div>

@endsection
