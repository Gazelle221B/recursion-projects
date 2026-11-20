<?php

namespace App\Policies;

use App\Models\Food;
use App\Models\User;

class FoodPolicy
{
    /**
     * Any signed-in user can browse foods.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any signed-in user can view a food.
     */
    public function view(User $user, Food $food): bool
    {
        return true;
    }

    /**
     * Any signed-in user can create a food.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the creator can update a food.
     */
    public function update(User $user, Food $food): bool
    {
        return $food->user_id !== null && $user->id === $food->user_id;
    }

    /**
     * Only the creator can delete a food.
     */
    public function delete(User $user, Food $food): bool
    {
        return $food->user_id !== null && $user->id === $food->user_id;
    }

    public function restore(User $user, Food $food): bool
    {
        return $this->update($user, $food);
    }

    public function forceDelete(User $user, Food $food): bool
    {
        return $this->delete($user, $food);
    }
}
