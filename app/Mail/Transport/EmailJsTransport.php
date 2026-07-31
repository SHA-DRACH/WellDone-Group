<?php

namespace App\Mail\Transport;

use Illuminate\Http\Client\Factory as HttpClient;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\MessageConverter;

/**
 * Sends mail through the EmailJS REST API.
 *
 * EmailJS is normally driven from the browser with a public key. That is not
 * usable here: the recipient of an internal alert would be decided by
 * JavaScript, and anyone could read the credentials in the page source and
 * send mail as the company. This transport instead calls EmailJS's REST
 * endpoint from the server with the *private* key, which never reaches the
 * browser.
 *
 * Because it plugs in as a Symfony transport, every existing Mailable and
 * Notification keeps working untouched — switching providers is a one-line
 * change in .env.
 *
 * EmailJS renders the message from a template defined in its dashboard, not
 * from the HTML we generate. The rendered body is therefore passed as a
 * template parameter, and the dashboard template must output it raw. See the
 * "EmailJS" section of the README for the exact setup.
 */
class EmailJsTransport extends AbstractTransport
{
    protected const ENDPOINT = 'https://api.emailjs.com/api/v1.0/email/send';

    public function __construct(
        protected HttpClient $http,
        protected string $serviceId,
        protected string $templateId,
        protected string $publicKey,
        protected string $privateKey,
        protected int $timeout = 30,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        /*
         * EmailJS sends to one recipient per call, so a message addressed to
         * several people becomes several calls. Our mail is transactional and
         * almost always single-recipient, so this is rarely more than one.
         */
        foreach ($this->recipients($email) as $recipient) {
            $this->dispatch($email, $recipient);
        }
    }

    /** @return array<int, Address> */
    protected function recipients(Email $email): array
    {
        $recipients = array_merge($email->getTo(), $email->getCc(), $email->getBcc());

        return $recipients ?: [];
    }

    protected function dispatch(Email $email, Address $recipient): void
    {
        $from = $email->getFrom()[0] ?? null;
        $replyTo = $email->getReplyTo()[0] ?? null;

        $response = $this->http
            ->timeout($this->timeout)
            ->asJson()
            // EmailJS rejects non-browser calls without an Origin unless the
            // account has API access enabled; sending one keeps it happy.
            ->withHeaders(['origin' => config('app.url')])
            ->post(self::ENDPOINT, [
                'service_id' => $this->serviceId,
                'template_id' => $this->templateId,
                'user_id' => $this->publicKey,
                'accessToken' => $this->privateKey,
                'template_params' => [
                    'to_email' => $recipient->getAddress(),
                    'to_name' => $recipient->getName() ?: $recipient->getAddress(),
                    'from_name' => $from?->getName() ?: config('mail.from.name'),
                    'from_email' => $from?->getAddress() ?: config('mail.from.address'),
                    'reply_to' => $replyTo?->getAddress() ?: ($from?->getAddress() ?? config('mail.from.address')),
                    'subject' => $email->getSubject() ?? '',
                    'message_html' => $this->htmlBody($email),
                    'message' => $this->textBody($email),
                ],
            ]);

        if ($response->failed()) {
            /*
             * Throwing here matters: the notification is queued, so a failure
             * lands the job in `failed_jobs` with this message rather than
             * disappearing silently.
             */
            throw new \RuntimeException(sprintf(
                'EmailJS rejected the message to %s (HTTP %d): %s',
                $recipient->getAddress(),
                $response->status(),
                Str::limit(trim($response->body()) ?: 'no response body', 300)
            ));
        }
    }

    protected function htmlBody(Email $email): string
    {
        $html = $email->getHtmlBody();

        if (is_resource($html)) {
            $html = stream_get_contents($html);
        }

        return (string) ($html ?: nl2br(e($this->textBody($email))));
    }

    protected function textBody(Email $email): string
    {
        $text = $email->getTextBody();

        if (is_resource($text)) {
            $text = stream_get_contents($text);
        }

        if (filled($text)) {
            return (string) $text;
        }

        $html = $email->getHtmlBody();

        if (is_resource($html)) {
            $html = stream_get_contents($html);
        }

        return trim(html_entity_decode(strip_tags((string) $html)));
    }

    public function __toString(): string
    {
        return 'emailjs://'.$this->serviceId;
    }
}
