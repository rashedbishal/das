<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactEnquiryRequest;
use App\Models\ContactEnquiry;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ContactEnquirySubmissionController extends Controller
{
    public function store(StoreContactEnquiryRequest $request): RedirectResponse
    {
        ContactEnquiry::query()->create($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Your enquiry has been received. Our team will be in touch.',
        ]);

        return back();
    }
}
