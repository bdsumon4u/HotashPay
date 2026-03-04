<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateInvoiceRequest extends FormRequest
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
            'invoice_id' => 'required|string|max:255',
            'client_name' => 'required|string|max:255',
            'client_email' => 'required_without:client_phone|email|max:255|nullable',
            'client_phone' => 'required_without:client_email|string|max:20|nullable',
            'amount' => 'required|numeric|gt:0',
            'currency' => 'nullable|string|size:3',
            'redirect_url' => 'nullable|url',
            'cancel_url' => 'nullable|url',
            'webhook_url' => 'nullable|url',
            'metadata' => 'nullable|array',
        ];
    }
}
