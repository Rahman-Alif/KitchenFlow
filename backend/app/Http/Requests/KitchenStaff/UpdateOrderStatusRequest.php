<?php

namespace App\Http\Requests\KitchenStaff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role already enforced by EnsureRole middleware
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in(['preparing', 'ready', 'served', 'canceled']),
            ],
        ];
    }
}