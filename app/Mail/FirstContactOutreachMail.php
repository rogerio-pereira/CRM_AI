<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class FirstContactOutreachMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $emailSubject,
        public string $markdownBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    public function content(): Content
    {
        $markdownOptions = [
            'renderer' => [
                'soft_break' => "<br>\n",
            ],
        ];
        $html = Str::markdown($this->markdownBody, $markdownOptions);

        return new Content(
            htmlString: $html,
        );
    }
}
