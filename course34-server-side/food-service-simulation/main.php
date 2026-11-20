<?php
declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $path = __DIR__ . "/" . str_replace("\\", "/", $class) . ".php";
    if (file_exists($path)) {
        require_once $path;
    }
});

use FoodItems\Burger;
use FoodItems\Pasta;
use FoodItems\Pizza;
use Persons\Cashier;
use Persons\Chef;
use Persons\Customer;
use Restaurants\Restaurant;

// Menu of the restaurant "saizeriya"
$cheeseBurger = new Burger("CheeseBurger", "A classic cheeseburger", 8.99);
$fettuccine = new Pasta("Fettuccine", "Creamy fettuccine pasta", 12.49);
$hawaiianPizza = new Pizza("HawaiianPizza", "Pizza with ham and pineapple", 11.99);
$spaghetti = new Pasta("Spaghetti", "Spaghetti with tomato sauce", 10.99);

// Staff: chef Invah and cashier Nadia
$chef = new Chef("Invah", 34, "123 Culinary Lane", 1, 3200.0);
$cashier = new Cashier("Nadia", 27, "456 Register Road", 2, 2600.0);

$saizeriya = new Restaurant(
    [$cheeseBurger, $fettuccine, $hawaiianPizza, $spaghetti],
    [$chef, $cashier]
);

// Customer Tom is interested in pizza, burgers and pasta
$tom = new Customer("Tom", 30, "789 Diner Street", [Pizza::getCategory(), Burger::getCategory(), Pasta::getCategory()]);

$invoice = $saizeriya->order($tom->interestedCategories($saizeriya));
$invoice->printInvoice();
