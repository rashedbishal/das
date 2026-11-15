<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactEnquiryRequest;
use App\Models\ContactEnquiry;
use App\Notifications\NewContactEnquiry;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Inertia\Inertia;

class ContactEnquirySubmissionController extends Controller
{
    public function __construct(private readonly SettingsService $settings) {}

    public function store(StoreContactEnquiryRequest $request): RedirectResponse
    {
        // A filled honeypot field means a bot: report success without storing anything,
        // so the bot gets no signal to adapt to.
        if (! $request->isHoneypotTriggered()) {
            $enquiry = ContactEnquiry::query()->create($request->safe()->except('website'));

            $recipient = $this->settings->get('general', 'contact_email');

            if (is_string($recipient) && $recipient !== '') {
                Notification::route('mail', $recipient)->notify(new NewContactEnquiry($enquiry));
            }
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Your enquiry has been received. Our team will be in touch.',
        ]);

        return back();
    }
}
