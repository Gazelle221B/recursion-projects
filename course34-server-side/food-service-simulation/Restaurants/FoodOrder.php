<?php
declare(strict_types=1);

namespace Restaurants;

use FoodItems\FoodItem;

class FoodOrder
{
    /** @var FoodItem[] */
    private array $items;
    private string $orderTime;

    /** @param FoodItem[] $items */
    public function __construct(array $items)
    {
        $this->items = $items;
        $this->orderTime = date("D M d, Y G:i");
    }

    /** @return FoodItem[] */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getOrderTime(): string
    {
        return $this->orderTime;
    }

    public function getTotalPrice(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item->getPrice();
        }
        return $total;
    }

    public function getEstimatedTimeInMinutes(): int
    {
        // Simple simulation: 5 minutes per item.
        return count($this->items) * 5;
    }
}
