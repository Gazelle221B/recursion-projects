<?php

namespace App\Http\Requests;

use App\Models\Food;
use Illuminate\Foundation\Http\FormRequest;

class StoreFoodRequest extends FormRequest
{
    /**
     * The user must be allowed to create foods (see FoodPolicy).
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Food::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:foods,name'],
            'protein' => ['required', 'numeric', 'min:0'],
            'carbs' => ['required', 'numeric', 'min:0'],
            'fat' => ['required', 'numeric', 'min:0'],
            'grams' => ['required', 'numeric', 'gt:0'],
            'food_type_name' => ['nullable', 'string', 'exists:food_types,name'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:food_tags,id'],
        ];
    }
}
