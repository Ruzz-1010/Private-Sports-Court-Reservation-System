<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

class ResendApiTransport extends AbstractTransport
{
    public function __construct(private readonly string $apiKey)
    {
        parent::__construct();
    }

    public function __toString(): string
    {
        return 'resend+https';
    }

    protected function doSend(SentMessage $message): void
    {
        if (trim($this->apiKey) === '') {
            throw new TransportException('RESEND_API_KEY is missing. Set it in the web service environment.');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $payload = [
            'from' => ($email->getFrom()[0] ?? $message->getEnvelope()->getSender())->toString(),
            'subject' => $email->getSubject() ?? '',
        ];

        // Preserve To/Cc/Bcc separately; never expose blind recipients as To.
        foreach (['to' => $email->getTo(), 'cc' => $email->getCc(), 'bcc' => $email->getBcc(), 'reply_to' => $email->getReplyTo()] as $field => $addresses) {
            if ($addresses !== []) {
                $payload[$field] = array_map(fn (Address $address): string => $address->toString(), $addresses);
            }
        }

        if (($html = $email->getHtmlBody()) !== null) {
            $payload['html'] = is_resource($html) ? stream_get_contents($html) : $html;
        }
        if (($text = $email->getTextBody()) !== null) {
            $payload['text'] = is_resource($text) ? stream_get_contents($text) : $text;
        }

        foreach ($email->getAttachments() as $attachment) {
            $part = [
                'filename' => $attachment->getFilename() ?? 'attachment',
                'content' => base64_encode($attachment->getBody()),
                'content_type' => $attachment->getContentType(),
            ];
            if ($attachment->getDisposition() === 'inline') {
                $part['content_id'] = $attachment->getContentId();
            }
            $payload['attachments'][] = $part;
        }

        $response = Http::withToken($this->apiKey)
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(20)
            ->post('https://api.resend.com/emails', $payload);

        // Do not log provider response bodies: they can contain recipient data.
        if (! $response->successful()) {
            $hint = match ($response->status()) {
                401 => 'Check RESEND_API_KEY.',
                403 => 'Check the verified sender domain. onboarding@resend.dev can only send to your Resend account email.',
                422 => 'Check the sender address and email fields.',
                429 => 'Resend rate limit reached. Retry later.',
                default => 'Check Resend email logs and try again.',
            };
            throw new TransportException('Resend email request failed (HTTP '.$response->status().'). '.$hint);
        }

        $id = $response->json('id');
        if (! is_string($id) || $id === '') {
            throw new TransportException('Resend did not return an email delivery ID.');
        }

        $message->setMessageId($id);
    }
}
