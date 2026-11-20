<?php
declare(strict_types=1);

namespace RestaurantChain\Models;

class RestaurantChain extends Company
{
    private int $chainId;
    /** @var RestaurantLocation[] */
    private array $locations;
    private string $cuisineType;
    private bool $hasDriveThru;
    private string $parentCompany;

    /** @param RestaurantLocation[] $locations */
    public function __construct(
        string $name,
        int $foundingYear,
        string $description,
        string $website,
        string $phone,
        string $industry,
        string $ceo,
        bool $isPubliclyTraded,
        string $country,
        string $founder,
        int $totalEmployees,
        int $chainId,
        array $locations,
        string $cuisineType,
        bool $hasDriveThru,
        string $parentCompany
    ) {
        parent::__construct($name, $foundingYear, $description, $website, $phone, $industry, $ceo, $isPubliclyTraded, $country, $founder, $totalEmployees);
        $this->chainId = $chainId;
        $this->locations = $locations;
        $this->cuisineType = $cuisineType;
        $this->hasDriveThru = $hasDriveThru;
        $this->parentCompany = $parentCompany;
    }

    public function addLocation(RestaurantLocation $location): void
    {
        $this->locations[] = $location;
    }

    /** @return RestaurantLocation[] */
    public function getLocations(): array
    {
        return $this->locations;
    }

    public function getTotalLocations(): int
    {
        return count($this->locations);
    }

    public function displayLocations(): string
    {
        return implode("\n", array_map(fn(RestaurantLocation $l) => $l->toString(), $this->locations));
    }

    public function toString(): string
    {
        $lines = [parent::toString()];
        $lines[] = sprintf(
            "Chain #%d - Cuisine: %s, Locations: %d, Drive-thru: %s, Parent: %s",
            $this->chainId,
            $this->cuisineType,
            $this->getTotalLocations(),
            $this->hasDriveThru ? "yes" : "no",
            $this->parentCompany
        );
        $lines[] = $this->displayLocations();
        return implode("\n", $lines);
    }

    public function toHTML(): string
    {
        $html = parent::toHTML();
        $html .= sprintf(
            '<div class="chain"><p>Chain #%d | Cuisine: %s | Locations: %d | Drive-thru: %s | Parent: %s</p>',
            $this->chainId,
            htmlspecialchars($this->cuisineType),
            $this->getTotalLocations(),
            $this->hasDriveThru ? "yes" : "no",
            htmlspecialchars($this->parentCompany)
        );
        foreach ($this->locations as $location) {
            $html .= $location->toHTML();
        }
        return $html . "</div>";
    }

    public function toMarkdown(): string
    {
        $md = parent::toMarkdown();
        $md .= sprintf(
            "\n- Chain ID: %d\n- Cuisine: %s\n- Locations: %d\n- Drive-thru: %s\n- Parent company: %s\n\n",
            $this->chainId,
            $this->cuisineType,
            $this->getTotalLocations(),
            $this->hasDriveThru ? "yes" : "no",
            $this->parentCompany
        );
        foreach ($this->locations as $location) {
            $md .= $location->toMarkdown() . "\n";
        }
        return $md;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            "chainId" => $this->chainId,
            "cuisineType" => $this->cuisineType,
            "totalLocations" => $this->getTotalLocations(),
            "hasDriveThru" => $this->hasDriveThru,
            "parentCompany" => $this->parentCompany,
            "locations" => array_map(fn(RestaurantLocation $l) => $l->toArray(), $this->locations),
        ]);
    }

    public static function RandomGenerator(
        int $locationCount = 3,
        int $employeeCount = 3,
        float $minSalary = 25000.0,
        float $maxSalary = 90000.0,
        int $zipMin = 10000,
        int $zipMax = 99999
    ): static {
        $company = Company::RandomGenerator();
        $data = $company->toArray();
        $cuisines = ["Italian", "Japanese", "Mexican", "American", "Indian", "Thai"];
        $locations = [];
        for ($i = 0; $i < $locationCount; $i++) {
            $locations[] = RestaurantLocation::RandomGenerator($employeeCount, $minSalary, $maxSalary, $zipMin, $zipMax);
        }
        $totalEmployees = 0;
        foreach ($locations as $location) {
            $totalEmployees += count($location->getEmployees());
        }
        return new static(
            $data["name"],
            $data["foundingYear"],
            $data["description"],
            $data["website"],
            $data["phone"],
            $data["industry"],
            $data["ceo"],
            $data["isPubliclyTraded"],
            $data["country"],
            $data["founder"],
            $totalEmployees,
            random_int(1, 1000),
            $locations,
            $cuisines[array_rand($cuisines)],
            (bool) random_int(0, 1),
            "Global Food Holdings"
        );
    }
}
