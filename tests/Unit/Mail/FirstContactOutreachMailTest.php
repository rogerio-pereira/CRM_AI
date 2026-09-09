<?php

namespace Tests\Unit\Mail;

use App\Mail\FirstContactOutreachMail;
use Tests\TestCase;

class FirstContactOutreachMailTest extends TestCase
{
    public function test_rendered_html_uses_the_markdown_body_and_subject(): void
    {
        $mail = new FirstContactOutreachMail(
            'A simple way to bring in more local conversations',
            "Hello **Bill**\n\nLet's talk about the site.",
        );

        $html = $mail->render();
        $envelope = $mail->envelope();

        $this->assertSame(
            'A simple way to bring in more local conversations',
            $envelope->subject,
        );
        $this->assertStringContainsString('<strong>Bill</strong>', $html);
        $this->assertStringContainsString("Let's talk about the site.", $html);
    }

    public function test_signature_keeps_a_line_break_between_name_and_company_link(): void
    {
        $mail = new FirstContactOutreachMail(
            'A simple way to bring in more local conversations',
            "Roger Pereira\n[Front Porch Creative](https://frontporchcreative.io)",
        );

        $html = $mail->render();

        $this->assertStringContainsString('Roger Pereira<br', $html);
        $this->assertStringContainsString('https://frontporchcreative.io', $html);
        $this->assertStringNotContainsString('Roger Pereira Front Porch Creative', $html);
    }
}
