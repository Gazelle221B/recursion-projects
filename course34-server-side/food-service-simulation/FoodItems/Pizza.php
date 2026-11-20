<?php
declare(strict_types=1);

namespace FoodItems;

class Pizza extends FoodItem
{
    public const CATEGORY = "pizza";

    public static function getCategory(): string
    {
        return self::CATEGORY;
    }
}
