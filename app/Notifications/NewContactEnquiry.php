<?php

namespace App\Notifications;

use App\Models\ContactEnquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the business about a new storefront enquiry, so it isn't only discovered
 * by someone opening the admin inbox. Queued: requires a running queue worker.
 */
class NewContactEnquiry extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ContactEnquiry $enquiry) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $enquiry = $this->enquiry;

        $message = (new MailMessage)
            ->subject('New enquiry from '.$enquiry->name)
            ->greeting('New website enquiry')
            ->line('Name: '.$enquiry->name)
            ->line('Phone: '.$enquiry->phone);

        if ($enquiry->business_name) {
            $message->line('Business: '.$enquiry->business_name);
        }

        if ($enquiry->email) {
            $message->line('Email: '.$enquiry->email)->replyTo($enquiry->email, $enquiry->name);
        }

        if ($enquiry->product_name) {
            $message->line('Product: '.$enquiry->product_name);
        }

        if ($enquiry->message) {
            $message->line('Message: '.$enquiry->message);
        }

        return $message->action('Open the enquiry inbox', route('admin.enquiries.index'));
    }
}
