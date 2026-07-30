@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'Frequently Asked Questions',
        'subtitle' => 'Answers to the questions we are asked most often.',
        'breadcrumbs' => ['FAQs' => null],
    ])

    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9">
                    @forelse($groups as $category => $faqs)
                        <div class="{{ $loop->last ? '' : 'mb-5' }}">
                            <h2 class="h4 mb-3">{{ ucwords(str_replace(['_', '-'], ' ', $category)) }}</h2>

                            <div class="accordion" id="faq-{{ $loop->index }}">
                                @foreach($faqs as $faq)
                                    <div class="accordion-item">
                                        <h3 class="accordion-header">
                                            <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }}" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#faq-item-{{ $faq->id }}"
                                                    aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                                {{ $faq->question }}
                                            </button>
                                        </h3>
                                        <div id="faq-item-{{ $faq->id }}"
                                             class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                                             data-bs-parent="#faq-{{ $loop->parent->index }}">
                                            <div class="accordion-body prose">{!! $faq->answer !!}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">
                            <i class="fa-regular fa-circle-question"></i>
                            <p>No questions have been published yet.</p>
                        </div>
                    @endforelse

                    <div class="cta-panel mt-5">
                        <div class="row align-items-center g-4">
                            <div class="col-lg-8">
                                <h2 class="h4">Still have a question?</h2>
                                <p>Our team is happy to help — get in touch and we will come back to you.</p>
                            </div>
                            <div class="col-lg-4 text-lg-end">
                                <a href="{{ route('site.contact') }}" class="btn btn-gold">Contact us</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
