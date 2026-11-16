<?php

namespace App\Http\Requests;

use App\Rules\BangladeshiPhoneNumber;
use App\Support\PhoneNumber;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreContactEnquiryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Phone numbers are stored in the standard 11-digit form (01XXXXXXXXX), so
     * "+880 1712-345678" and "01712345678" are the same number.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('phone'))) {
            $this->merge(['phone' => PhoneNumber::normalizeBangladeshi($this->input('phone'))]);
        }
    }

    /**
     * @return array<string, array<int, string|ValidationRule>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'business_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', new BangladeshiPhoneNumber],
            'email' => ['nullable', 'email', 'max:255'],
            'product_name' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            // Honeypot: hidden from people, so only bots fill it in.
            'website' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'Please tell us how we can help.',
        ];
    }

    public function isHoneypotTriggered(): bool
    {
        return filled($this->input('website'));
    }
}
