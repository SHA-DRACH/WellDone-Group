<?php

namespace App\Listeners;

use App\Support\Brand;
use Illuminate\Mail\Events\MessageSending;

/**
 * Attaches the company logo to every outgoing email that asks for it.
 *
 * The mail templates reference the logo as `cid:` + self::CONTENT_ID rather
 * than by URL. A URL is a request the recipient's mail client has to make back
 * to this server, and it fails in all the ordinary cases: while the site runs
 * on 127.0.0.1 (which, on the recipient's machine, is their own computer),
 * behind any private network, and in the many clients that refuse remote
 * images until the reader clicks "display images". Sending the bytes with the
 * message removes the fetch entirely, so the logo is there on first open.
 *
 * This runs at send time rather than in the template because the Blade
 * components that build the mail header have an isolated scope and never see
 * the message being assembled. Here the whole message is in hand.
 */
class EmbedBrandLogo
{
    /**
     * Fixed rather than generated, because the template has to name it before
     * this listener runs.
     */
    public const CONTENT_ID = 'brand-logo';

    public function handle(MessageSending $event): void
    {
        $email = $event->message;

        $body = $email->getHtmlBody();

        // Plain-text mail, and anything that did not ask for the logo, is left
        // alone rather than carrying an attachment nothing displays.
        if (! is_string($body) || ! str_contains($body, 'cid:'.self::CONTENT_ID)) {
            return;
        }

        $path = Brand::logoPath();

        if ($path === null || ! is_file($path)) {
            return;
        }

        $email->embedFromPath($path, self::CONTENT_ID);
    }
}
