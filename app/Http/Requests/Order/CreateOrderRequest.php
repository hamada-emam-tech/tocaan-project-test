<?php


namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.product_name' => ['required_without:items.*.product_id', 'nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.price' => ['required_without:items.*.product_id', 'nullable', 'numeric', 'min:0'],
        ];

        // Admin must specify customer_id when creating orders
        if (auth()->user()->isAdmin()) {
            $rules['customer_id'] = ['required', 'exists:users,id'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Please provide at least one item.',
            'items.min' => 'Order must contain at least one item.',
            'items.*.product_name.required' => 'Product name is required for all items.',
            'items.*.quantity.required' => 'Quantity is required for all items.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.price.required' => 'Price is required for all items.',
            'items.*.price.min' => 'Price must be a positive number.',
        ];
    }
}
