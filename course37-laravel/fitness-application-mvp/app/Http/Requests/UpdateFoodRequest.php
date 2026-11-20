<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFoodRequest extends FormRequest
{
    /**
     * Only the creator of the food can update it (see FoodPolicy).
     */
    public function authorize(): bool
    {
        $food = $this->route('food');

        return $food && $this->user()->can('update', $food);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $food = $this->route('food');

        return [
            // The food being updated is excluded from the unique check.
            'name' => ['required', 'string', 'max:255', Rule::unique('foods', 'name')->ignore($food)],
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
