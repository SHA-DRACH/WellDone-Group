<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Sends a real message through the configured mailer.
 *
 * Useful for confirming EmailJS credentials without having to submit a form
 * and wait for the queue, and for proving which mailer is actually active.
 */
class SendTestMail extends Command
{
    protected $signature = 'mail:test
                            {email : Where to send the test message}
                            {--mailer= : Override the configured mailer, e.g. emailjs}';

    protected $description = 'Send a test email through the configured mailer';

    public function handle(): int
    {
        $recipient = $this->argument('email');
        $mailer = $this->option('mailer') ?: config('mail.default');

        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error("\"{$recipient}\" is not a valid email address.");

            return self::FAILURE;
        }

        $this->line('');
        $this->line('  Mailer     : <options=bold>'.$mailer.'</>');
        $this->line('  From       : '.config('mail.from.address'));
        $this->line('  Recipient  : '.$recipient);

        if ($mailer === 'emailjs') {
            $service = config('services.emailjs.service_id');
            $template = config('services.emailjs.template_id');

            if (blank($service) || blank($template)) {
                $this->line('');
                $this->error('  EmailJS is not configured — set EMAILJS_* in .env first.');

                return self::FAILURE;
            }

            $this->line('  Service    : '.$service);
            $this->line('  Template   : '.$template);
        }

        $this->line('');

        try {
            Mail::mailer($mailer)->send([], [], function ($message) use ($recipient) {
                $message->to($recipient)
                    ->subject('Test message from '.setting('company.name', config('app.name')))
                    ->html($this->body());
            });
        } catch (\Throwable $e) {
            $this->error('  Sending failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('  Sent. Check the inbox'.($mailer === 'smtp' ? ' (or Mailpit at http://localhost:8025)' : '').'.');
        $this->line('');

        return self::SUCCESS;
    }

    protected function body(): string
    {
        $company = setting('company.name', config('app.name'));

        return <<<HTML
        <h2>Mail is working</h2>
        <p>This is a test message from the <strong>{$company}</strong> website.</p>
        <p>If you are reading this, the configured mailer is delivering correctly
        and the site can send quotation confirmations, application receipts,
        enquiry replies and account verification links.</p>
        <p style="color:#6b7280;font-size:13px;">Sent automatically by
        <code>php artisan mail:test</code> — no reply is needed.</p>
        HTML;
    }
}
