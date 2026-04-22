<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // role enforced by EnsureRole middleware
    }

    public function rules(): array
    {
        return [
            'items'                => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.quantity'     => ['required', 'integer', 'min:1'],
            'notes'                => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.min' => 'At least one item is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('items') && is_array($this->items) && count($this->items) === 0) {
            $this->merge(['items' => null]);
        }
    }
}