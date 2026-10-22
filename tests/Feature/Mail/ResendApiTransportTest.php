<?php

namespace Tests\Feature\Mail;

use App\Mail\Transport\ResendApiTransport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class ResendApiTransportTest extends TestCase
{
    public function test_the_configured_mailer_resolves_without_an_external_sdk(): void
    {
        config(['services.resend.key' => 'test-key']);
        $this->assertInstanceOf(ResendApiTransport::class, Mail::mailer('resend')->getSymfonyTransport());
    }

    public function test_it_sends_the_message_and_decoded_pdf_bytes_via_https(): void
    {
        Http::preventStrayRequests();
        Http::fake(['api.resend.com/emails' => Http::response(['id' => 'mail-test-id'], 200)]);
        $pdf = "%PDF-1.4\nreceipt bytes";
        $email = (new Email)->from('Club <onboarding@resend.dev>')
            ->to('customer@example.com')->bcc('audit@example.com')
            ->replyTo('club@example.com')->subject('Reservation confirmed')
            ->html('<p>Confirmed</p>')->text('Confirmed')
            ->attach($pdf, 'receipt.pdf', 'application/pdf');

        $sent = (new ResendApiTransport('test-key'))->send($email);

        $this->assertSame('mail-test-id', $sent->getMessageId());
        Http::assertSent(fn ($request) => $request->url() === 'https://api.resend.com/emails'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && $request['to'] === ['customer@example.com']
            && $request['bcc'] === ['audit@example.com']
            && $request['attachments'][0]['content'] === base64_encode($pdf)
            && $request['attachments'][0]['filename'] === 'receipt.pdf'
            && $request['html'] === '<p>Confirmed</p>');
        Http::assertSentCount(1);
    }

    public function test_missing_key_fails_before_any_request(): void
    {
        Http::fake();
        try {
            (new ResendApiTransport(''))->send(
                (new Email)->from('club@example.com')->to('customer@example.com')->text('Receipt'),
            );
            $this->fail('Expected a transport exception.');
        } catch (TransportException $exception) {
            $this->assertStringContainsString('RESEND_API_KEY is missing', $exception->getMessage());
            Http::assertNothingSent();
        }
    }

    public function test_provider_rejection_is_not_reported_as_success_or_leaked_into_logs(): void
    {
        Http::fake(['api.resend.com/emails' => Http::response(['message' => 'private recipient data'], 403)]);
        $this->expectException(TransportException::class);
        $this->expectExceptionMessage('Resend email request failed (HTTP 403). Check the verified sender domain.');

        (new ResendApiTransport('test-key'))->send(
            (new Email)->from('onboarding@resend.dev')->to('customer@example.com')->text('Receipt'),
        );
    }
}
