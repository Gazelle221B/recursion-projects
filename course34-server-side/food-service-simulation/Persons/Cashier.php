<?php
declare(strict_types=1);

namespace Persons;

use Restaurants\FoodOrder;
use Restaurants\Invoice;

class Cashier extends Employee
{
    public function generateInvoice(FoodOrder $order): Invoice
    {
        echo sprintf("the cashier %s created an invoice\n", $this->name);
        return new Invoice($order->getTotalPrice(), $order->getEstimatedTimeInMinutes());
    }

    public function generateOrder(array $items): FoodOrder
    {
        echo sprintf("the cashier %s created a food order\n", $this->name);
        return new FoodOrder($items);
    }
}
