<?php
declare(strict_types=1);

namespace FoodItems;

class Pasta extends FoodItem
{
    public const CATEGORY = "pasta";

    public static function getCategory(): string
    {
        return self::CATEGORY;
    }
}
