<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentWebhookReques extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'idempotency_key' => 'required|string',
            'order_number' => 'required|string',
            'status' => 'required|string|in:success,failed',
        ];
        
    }

    public function messages(): array
    {
        return [
            'idempotency_key.required' => 'Idempotency key is required.',
            'order_id.required' => 'Order ID is required.',
            'order_id.integer' => 'Order ID must be an integer.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be either processed or failed.',
        ];
    }
}
