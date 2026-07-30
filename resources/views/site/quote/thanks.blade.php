@extends('site.layouts.app')

@section('content')

    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-7 text-center">
                    <span class="icon-badge icon-badge--gold mx-auto mb-4" style="width:84px;height:84px;font-size:2rem;">
                        <i class="fa-solid fa-circle-check"></i>
                    </span>

                    <h1 class="mb-3">Thank you — your request is with us</h1>

                    <p class="lead text-muted mb-4">
                        We have received your quotation request and sent a confirmation to your email address.
                    </p>

                    <div class="card-soft p-4 mb-4 d-inline-block">
                        <p class="small text-muted mb-1">Your reference number</p>
                        <p class="h4 mb-0 text-primary">{{ $reference }}</p>
                    </div>

                    <div class="text-start card-soft p-4 mb-4">
                        <h2 class="h6 mb-3">What happens next</h2>
                        <ol class="mb-0 small text-muted ps-3">
                            <li class="mb-2">Your request is routed to the division best placed to handle it.</li>
                            <li class="mb-2">We review the scope and come back to you if anything needs clarifying.</li>
                            <li class="mb-2">You receive a written quotation, typically within three to five working days.</li>
                            <li>International sourcing may extend that — we will tell you if so.</li>
                        </ol>
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <a href="{{ route('site.home') }}" class="btn btn-primary">Back to home</a>
                        <a href="{{ route('site.projects.index') }}" class="btn btn-outline-primary">Browse our projects</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
