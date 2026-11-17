<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * The DAS-branded e-mail carrying a new customer's 6-digit verification code.
 * Sent immediately (not queued): the customer is waiting on the verify screen.
 */
class VerifyEmailCodeNotification extends Notification
{
    public function __construct(
        public readonly string $code,
        public readonly int $minutes,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $siteName = (string) config('app.name');
        $firstName = Str::of((string) ($notifiable->name ?? ''))->trim()->before(' ')->value();
        $spacedCode = substr($this->code, 0, 3).' '.substr($this->code, 3);

        return (new MailMessage)
            ->subject("{$this->code} is your {$siteName} verification code")
            ->greeting($firstName !== '' ? "Welcome, {$firstName}!" : 'Welcome!')
            ->line("Thanks for creating a {$siteName} account. Enter this code on the verification screen to confirm your email address:")
            ->line(new HtmlString('<p class="verification-code">'.e($spacedCode).'</p>'))
            ->line("The code expires in {$this->minutes} minutes. For your security, never share it with anyone, including our team.")
            ->line('Didn’t create an account? You can safely ignore this email.')
            ->salutation("Warm regards,  \nThe {$siteName} team");
    }
}
