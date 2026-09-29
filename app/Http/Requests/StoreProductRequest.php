<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            // Catalog is water-in-gallons only — this is deliberately a closed
            // list, not free text, so the shop can never drift into other products.
            'container_type' => ['required', Rule::in(['Round Gallon', 'Slim Gallon', 'Other Gallon'])],
            'unit' => ['required', Rule::in(['gallon'])],
            'price' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'quantity_on_hand' => ['required', 'integer', 'min:0'],
            'reorder_level' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
