<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'creation_date' => ['nullable', 'date'],
            'food_ids' => ['required', 'array', 'min:1'],
            'food_ids.*' => ['integer', 'exists:foods,id'],
        ];
    }
}
