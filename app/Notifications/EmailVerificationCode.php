<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Carries the six-digit code that proves a new account owns its email address.
 *
 * The code is short lived and single use. It is never written to the log or the
 * audit trail, only to the customer's inbox.
 */
class EmailVerificationCode extends Notification
{
    public function __construct(public readonly string $code) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = (int) config('reservation.email_verification.expires_minutes', 15);

        return (new MailMessage)
            ->subject('Your verification code')
            ->view('emails.verification-code', [
                'subject' => 'Your verification code',
                'facilityName' => config('reservation.facility_name'),
                'facilityAddress' => config('reservation.facility_address'),
                'customerName' => $notifiable->name,
                'code' => $this->code,
                'expiresMinutes' => $minutes,
            ]);
    }
}
