<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

/**
 * The "forgot password" email, in the DAS voice. Laravel's own notification
 * builds the reset link (so `ResetPassword::createUrlUsing()` still applies);
 * only the wording changes. This class replaces `ResetPassword::toMailUsing()`
 * for customising the email, so that hook is not consulted. The look comes
 * from the DAS mail theme (resources/views/vendor/mail, config/mail.php).
 */
class ResetPasswordNotification extends ResetPassword
{
    /**
     * @param  User  $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $siteName = (string) config('app.name');
        $firstName = Str::of((string) $notifiable->name)->trim()->before(' ')->value();
        $minutes = (int) config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject("Reset your {$siteName} password")
            ->greeting($firstName !== '' ? "Hello {$firstName}," : 'Hello,')
            ->line("We received a request to reset the password for your {$siteName} account. Choose a new one with the button below.")
            ->action('Reset my password', $this->resetUrl($notifiable))
            ->line("For your security, this link expires in {$minutes} minutes and can only be used once.")
            ->line('Didn’t ask for this? You can safely ignore this email. Your password stays the same.')
            ->salutation("Warm regards,  \nThe {$siteName} team");
    }
}
