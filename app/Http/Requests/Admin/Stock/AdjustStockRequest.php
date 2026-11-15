<?php

namespace App\Http\Requests\Admin\Stock;

use App\Support\FieldLimits;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('product')) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['add', 'remove'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:'.FieldLimits::get('number.stock_quantity')],
            'note' => ['nullable', 'string', FieldLimits::max('stock.note')],
        ];
    }
}
