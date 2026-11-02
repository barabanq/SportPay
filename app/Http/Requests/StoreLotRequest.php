<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'product_type' => ['required', 'string', 'in:training_plan,coaching,race_slot,equipment,nutrition'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:50', 'max:1000000'],
            'stock_quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
