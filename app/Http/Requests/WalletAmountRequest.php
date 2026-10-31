<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WalletAmountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:100', 'max:500000'],
        ];
    }

    public function amount(): float
    {
        return round((float) $this->validated('amount'), 2);
    }
}