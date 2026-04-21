<?php

namespace App\Http\Requests\KitchenStaff;

use Illuminate\Foundation\Http\FormRequest;

class RecordTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role enforced by EnsureRole middleware
    }

    public function rules(): array
    {
        return [
            'tendered_amount' => ['required', 'numeric', 'min:0'],
        ];
    }
}