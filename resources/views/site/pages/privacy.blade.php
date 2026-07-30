@extends('site.layouts.app')

@section('content')

    @include('site.partials.page-header', [
        'title' => 'Privacy Policy',
        'subtitle' => 'How we handle the information you share with us.',
        'breadcrumbs' => ['Privacy Policy' => null],
    ])

    <section class="section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="alert alert-info small">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        This is a starting template. Have it reviewed against Liberian data protection
                        requirements and your own practices before the site goes live.
                    </div>

                    <div class="prose">
                        <p class="text-muted"><em>Last updated {{ now()->format('F Y') }}</em></p>

                        <h2>Who we are</h2>
                        <p>
                            {{ setting('company.legal_name', setting('company.name')) }} is a corporation registered in
                            Liberia, with offices at {{ setting('contact.address_line1') }},
                            {{ setting('contact.city') }}, {{ setting('contact.country') }}. This policy explains what
                            we collect through this website and what we do with it.
                        </p>

                        <h2>What we collect</h2>
                        <ul>
                            <li><strong>Enquiries and quotation requests</strong> — your name, organisation, email address, telephone number, and whatever you tell us about your requirement, including any documents you attach.</li>
                            <li><strong>Job applications</strong> — the details on the application form together with your résumé and any supporting links.</li>
                            <li><strong>Newsletter subscriptions</strong> — your email address and, optionally, your name.</li>
                            <li><strong>Website usage</strong> — the pages visited and a one-way hash of your IP address and browser, which we use to count visitors. We do not store your IP address in a readable form.</li>
                        </ul>

                        <h2>Why we hold it</h2>
                        <p>
                            We use the information you submit to respond to your enquiry, prepare a quotation, assess your
                            application, or send you the newsletter you asked for. We do not sell it, and we do not share
                            it with third parties for their own marketing.
                        </p>

                        <h2>How long we keep it</h2>
                        <p>
                            Quotation requests and enquiries are retained for as long as needed to manage the commercial
                            relationship and to meet our record-keeping obligations. Unsuccessful job applications are kept
                            for up to twenty-four months in case a suitable role arises, unless you ask us to remove them
                            sooner. Newsletter subscriptions are kept until you unsubscribe.
                        </p>

                        <h2>Newsletter consent</h2>
                        <p>
                            We use double opt-in: an address is only added to the mailing list after the confirmation link
                            we email has been followed. Every message includes an unsubscribe link.
                        </p>

                        <h2>Security</h2>
                        <p>
                            Documents you upload — résumés and quotation attachments — are stored outside the public web
                            root and are accessible only to authorised staff. Access to administrative systems is
                            role-based and logged.
                        </p>

                        <h2>Your choices</h2>
                        <p>
                            You may ask us what we hold about you, ask for it to be corrected, or ask for it to be deleted.
                            Write to <a href="mailto:{{ setting('contact.email_general') }}">{{ setting('contact.email_general') }}</a>
                            and we will respond.
                        </p>

                        <h2>Cookies</h2>
                        <p>
                            This site sets a session cookie needed for security (including protection against cross-site
                            request forgery). If an analytics provider is enabled by the site administrator, it may set
                            additional cookies; these are not required for the site to work.
                        </p>

                        <h2>Contact</h2>
                        <p>
                            Questions about this policy can be sent to
                            <a href="mailto:{{ setting('contact.email_general') }}">{{ setting('contact.email_general') }}</a>
                            or {{ setting('contact.phone_primary') }}.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
