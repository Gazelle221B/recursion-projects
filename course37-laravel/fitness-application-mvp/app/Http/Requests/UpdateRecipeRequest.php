<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecipeRequest extends FormRequest
{
    /**
     * Only the owner of the recipe can update it (see RecipePolicy).
     */
    public function authorize(): bool
    {
        $recipe = $this->route('recipe');

        return $recipe && $this->user()->can('update', $recipe);
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
