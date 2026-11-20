<?php
declare(strict_types=1);

namespace FoodItems;

class Burger extends FoodItem
{
    public const CATEGORY = "burger";

    public static function getCategory(): string
    {
        return self::CATEGORY;
    }
}
