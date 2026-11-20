<?php
declare(strict_types=1);

namespace Persons;

use Restaurants\Restaurant;

class Customer extends Person
{
    /** @var string[] */
    private array $interestedCategories;

    /** @param string[] $interestedCategories */
    public function __construct(string $name, int $age, string $address, array $interestedCategories = [])
    {
        parent::__construct($name, $age, $address);
        $this->interestedCategories = $interestedCategories;
    }

    /**
     * Returns the categories this customer is interested in,
     * limited to the categories the given restaurant actually serves.
     *
     * @return string[]
     */
    public function interestedCategories(Restaurant $restaurant): array
    {
        $available = [];
        foreach ($restaurant->getMenu() as $item) {
            $available[$item::getCategory()] = true;
        }
        return array_values(array_filter(
            $this->interestedCategories,
            fn(string $category): bool => isset($available[$category])
        ));
    }
}
