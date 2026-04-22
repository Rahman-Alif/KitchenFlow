<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id'         => ['sometimes', 'integer', 'exists:categories,id'],
            'name'                => ['sometimes', 'string', 'max:150'],
            'description'         => ['nullable', 'string'],
            'image'               => ['nullable', 'image', 'max:2048'],
            'price'               => ['sometimes', 'numeric', 'min:0'],
            'stock_quantity'      => ['sometimes', 'integer', 'min:0'],
            'low_stock_threshold' => ['sometimes', 'integer', 'min:0'],
            'is_available'        => ['sometimes', 'boolean'],
        ];
    }
}