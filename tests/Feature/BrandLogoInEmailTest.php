<?php

namespace Tests\Feature;

use App\Listeners\EmbedBrandLogo;
use App\Notifications\OtpCodeIssued;
use App\Services\OtpService;
use App\Support\Brand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/**
 * The guarantee this suite protects: the logo arrives with the email.
 *
 * Linking to it does not work. The recipient's mail client has to fetch the
 * URL, which is impossible while the site runs on 127.0.0.1 — on their machine
 * that address is their own computer — and is refused by default in most
 * clients even once the site is public.
 */
class BrandLogoInEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // phpunit.xml pins the array transport but leaves the sender to .env,
        // which the test environment does not read.
        config()->set('mail.from.address', 'noreply@example.test');
        config()->set('mail.from.name', 'WellDone Group Inc.');
    }

    protected function sendOne(): Email
    {
        Notification::route('mail', 'recipient@example.test')
            ->notify(new OtpCodeIssued(
                '123456',
                OtpService::PURPOSE_EMAIL_VERIFICATION,
                'Recipient',
            ));

        $messages = Mail::getSymfonyTransport()->messages();

        $this->assertCount(1, $messages, 'Expected exactly one message to have been sent.');

        return $messages->first()->getOriginalMessage();
    }

    public function test_the_logo_is_attached_to_the_message(): void
    {
        $email = $this->sendOne();

        $images = array_filter(
            $email->getAttachments(),
            fn ($part) => str_starts_with($part->getMediaType().'/'.$part->getMediaSubtype(), 'image/')
        );

        $this->assertNotEmpty($images, 'The logo should travel with the email, not be linked.');

        $logo = reset($images);

        $this->assertSame(EmbedBrandLogo::CONTENT_ID, $logo->getName());
        $this->assertSame('inline', $logo->getDisposition(), 'A linked logo must be shown in place, not offered as a download.');
    }

    public function test_the_html_points_at_the_attachment_rather_than_a_url(): void
    {
        $email = $this->sendOne();

        // Symfony rewrites `cid:brand-logo` to the part's generated id while
        // serialising, so read the body after that has happened.
        $email->toString();
        $html = $email->getHtmlBody();

        $this->assertMatchesRegularExpression(
            '/<img[^>]+src="cid:[^"]+"[^>]*class="logo"/',
            $html,
            'The logo image should reference the embedded attachment.'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/<img[^>]+src="https?:\/\/[^"]*"[^>]*class="logo"/',
            $html,
            'The logo should never be left as a link the recipient has to fetch.'
        );
    }

    /**
     * The end of the line: what actually goes over the wire.
     *
     * Symfony rewrites `cid:brand-logo` into the part's generated Content-ID
     * while serialising, on a copy — so this has to read the serialised
     * message rather than the Email object, which still holds the placeholder.
     */
    public function test_the_referenced_id_resolves_to_the_attached_image(): void
    {
        $wire = $this->sendOne()->toString();

        // Undo quoted-printable soft line breaks so the markup can be read.
        $decoded = quoted_printable_decode(preg_replace('/=\r?\n/', '', $wire));

        preg_match('/<img[^>]+src="cid:([^"]+)"[^>]*class="logo"/', $decoded, $referenced);

        $this->assertNotEmpty($referenced, 'No embedded logo reference found in the sent message.');

        preg_match_all('/Content-ID: <([^>]+)>/', $wire, $ids);

        $this->assertContains(
            $referenced[1],
            $ids[1],
            'The cid in the HTML must match an attachment, or the image renders broken.'
        );

        $this->assertStringContainsString(
            'Content-Type: image/',
            $wire,
            'The message should carry the logo as an image part.'
        );
    }

    public function test_nothing_is_attached_when_there_is_no_local_artwork(): void
    {
        // A logo hosted elsewhere has no file to attach; the template falls
        // back to the URL and the listener must not add a stray attachment.
        setting()->set('company.logo', 'https://cdn.example.com/logo.png');

        $this->assertNull(Brand::logoPath());

        $email = $this->sendOne();

        $images = array_filter(
            $email->getAttachments(),
            fn ($part) => $part->getMediaType() === 'image'
        );

        $this->assertEmpty($images);
    }
}
