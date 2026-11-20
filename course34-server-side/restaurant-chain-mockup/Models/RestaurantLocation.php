<?php
declare(strict_types=1);

namespace RestaurantChain\Models;

class RestaurantLocation implements FileConvertible
{
    private string $name;
    private string $address;
    private string $city;
    private string $state;
    private string $zipCode;
    /** @var Employee[] */
    private array $employees;
    private bool $isOpen;

    /** @param Employee[] $employees */
    public function __construct(
        string $name,
        string $address,
        string $city,
        string $state,
        string $zipCode,
        array $employees,
        bool $isOpen
    ) {
        $this->name = $name;
        $this->address = $address;
        $this->city = $city;
        $this->state = $state;
        $this->zipCode = $zipCode;
        $this->employees = $employees;
        $this->isOpen = $isOpen;
    }

    /** @return Employee[] */
    public function getEmployees(): array
    {
        return $this->employees;
    }

    public function toString(): string
    {
        $lines = [sprintf(
            "%s - %s, %s, %s %s (%s)",
            $this->name,
            $this->address,
            $this->city,
            $this->state,
            $this->zipCode,
            $this->isOpen ? "Open" : "Closed"
        )];
        foreach ($this->employees as $employee) {
            $lines[] = "  - " . $employee->toString();
        }
        return implode("\n", $lines);
    }

    public function toHTML(): string
    {
        $html = sprintf(
            '<div class="location"><h3>%s</h3><p>%s, %s, %s %s - %s</p>',
            htmlspecialchars($this->name),
            htmlspecialchars($this->address),
            htmlspecialchars($this->city),
            htmlspecialchars($this->state),
            htmlspecialchars($this->zipCode),
            $this->isOpen ? "Open" : "Closed"
        );
        foreach ($this->employees as $employee) {
            $html .= $employee->toHTML();
        }
        return $html . "</div>";
    }

    public function toMarkdown(): string
    {
        $md = sprintf(
            "### %s\n\n%s, %s, %s %s - %s\n\n",
            $this->name,
            $this->address,
            $this->city,
            $this->state,
            $this->zipCode,
            $this->isOpen ? "Open" : "Closed"
        );
        foreach ($this->employees as $employee) {
            $md .= $employee->toMarkdown() . "\n";
        }
        return $md;
    }

    public function toArray(): array
    {
        return [
            "name" => $this->name,
            "address" => $this->address,
            "city" => $this->city,
            "state" => $this->state,
            "zipCode" => $this->zipCode,
            "employees" => array_map(fn(Employee $e) => $e->toArray(), $this->employees),
            "isOpen" => $this->isOpen,
        ];
    }

    public static function RandomGenerator(
        int $employeeCount = 3,
        float $minSalary = 25000.0,
        float $maxSalary = 90000.0,
        int $zipMin = 10000,
        int $zipMax = 99999
    ): static {
        $cities = [
            ["Springfield", "IL"], ["Austin", "TX"], ["Denver", "CO"],
            ["Portland", "OR"], ["Miami", "FL"], ["Chicago", "IL"],
        ];
        [$city, $state] = $cities[array_rand($cities)];
        $employees = [];
        for ($i = 0; $i < $employeeCount; $i++) {
            $employees[] = Employee::RandomGenerator($minSalary, $maxSalary);
        }
        return new static(
            sprintf("%s #%d", $city, random_int(1, 99)),
            sprintf("%d Main Street", random_int(1, 999)),
            $city,
            $state,
            (string) random_int($zipMin, $zipMax),
            $employees,
            (bool) random_int(0, 1)
        );
    }
}
