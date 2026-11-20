<?php
declare(strict_types=1);

namespace Persons;

use Restaurants\FoodOrder;

class Chef extends Employee
{
    public function prepareFood(FoodOrder $order): string
    {
        $cooked = [];
        foreach ($order->getItems() as $item) {
            $cooked[] = $item->getName();
            echo sprintf("the chef %s cooked a %s\n", $this->name, $item->getName());
        }
        return implode(", ", $cooked);
    }
}
