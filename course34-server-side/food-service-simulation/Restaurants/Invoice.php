<?php
declare(strict_types=1);

namespace Restaurants;

class Invoice
{
    private float $finalPrice;
    private string $orderTime;
    private int $estimatedTimeInMinutes;

    public function __construct(float $finalPrice, int $estimatedTimeInMinutes)
    {
        $this->finalPrice = $finalPrice;
        $this->orderTime = date("D M d, Y G:i");
        $this->estimatedTimeInMinutes = $estimatedTimeInMinutes;
    }

    public function getFinalPrice(): float
    {
        return $this->finalPrice;
    }

    public function getOrderTime(): string
    {
        return $this->orderTime;
    }

    public function getEstimatedTimeInMinutes(): int
    {
        return $this->estimatedTimeInMinutes;
    }

    public function printInvoice(): void
    {
        echo "----- Invoice -----\n";
        echo sprintf("Final Price: $%.2f\n", $this->finalPrice);
        echo sprintf("Order Time: %s\n", $this->orderTime);
        echo sprintf("Estimated Time: %d minutes\n", $this->estimatedTimeInMinutes);
    }
}
