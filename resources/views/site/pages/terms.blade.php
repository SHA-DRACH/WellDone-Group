@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'Terms of Use',
        'subtitle' => 'The terms on which this website is made available.',
        'breadcrumbs' => ['Terms of Use' => null],
    ])

    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="alert alert-info small">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        This is a starting template. Have it reviewed by your legal adviser before the site goes live.
                    </div>

                    <div class="prose">
                        <p class="text-muted"><em>Last updated {{ now()->format('F Y') }}</em></p>

                        <h2>Acceptance</h2>
                        <p>
                            By using this website you accept these terms. If you do not accept them, please do not use
                            the site.
                        </p>

                        <h2>Information on this site</h2>
                        <p>
                            We take care to keep the content accurate and current, but the material here is provided for
                            general information. It is not an offer, and it does not form part of any contract.
                            Specifications, availability and pricing are confirmed only in a written quotation issued by us.
                        </p>

                        <h2>Quotations and enquiries</h2>
                        <p>
                            Submitting a quotation request does not create a contract. A contract arises only when we
                            issue a written quotation and you accept it in writing, subject to our agreed terms of supply.
                        </p>

                        <h2>Intellectual property</h2>
                        <p>
                            The content, layout, graphics and marks on this site belong to
                            {{ setting('company.legal_name', setting('company.name')) }} or its licensors. You may view
                            and print pages for your own use. You may not reproduce, distribute or otherwise exploit them
                            commercially without our written permission.
                        </p>

                        <h2>Acceptable use</h2>
                        <p>You agree not to use this site to:</p>
                        <ul>
                            <li>submit false, misleading or unlawful information;</li>
                            <li>interfere with the operation or security of the site;</li>
                            <li>attempt to gain unauthorised access to any part of it; or</li>
                            <li>upload any file containing malicious code.</li>
                        </ul>

                        <h2>Third-party links</h2>
                        <p>
                            Where we link to other websites we do so for convenience. We do not control them and we are
                            not responsible for their content or practices.
                        </p>

                        <h2>Availability</h2>
                        <p>
                            We aim to keep the site available but do not guarantee uninterrupted access. We may suspend,
                            withdraw or change any part of it without notice.
                        </p>

                        <h2>Governing law</h2>
                        <p>
                            These terms are governed by the laws of the Republic of Liberia, and the courts of Liberia
                            have exclusive jurisdiction.
                        </p>

                        <h2>Contact</h2>
                        <p>
                            Questions about these terms can be sent to
                            <a href="mailto:{{ setting('contact.email_general') }}">{{ setting('contact.email_general') }}</a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
