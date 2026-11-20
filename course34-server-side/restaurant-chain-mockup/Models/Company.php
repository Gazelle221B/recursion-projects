<?php
declare(strict_types=1);

namespace RestaurantChain\Models;

class Company implements FileConvertible
{
    protected string $name;
    protected int $foundingYear;
    protected string $description;
    protected string $website;
    protected string $phone;
    protected string $industry;
    protected string $ceo;
    protected bool $isPubliclyTraded;
    protected string $country;
    protected string $founder;
    protected int $totalEmployees;

    public function __construct(
        string $name,
        int $foundingYear,
        string $description,
        string $website,
        string $phone,
        string $industry,
        string $ceo,
        bool $isPubliclyTraded,
        string $country = "",
        string $founder = "",
        int $totalEmployees = 0
    ) {
        $this->name = $name;
        $this->foundingYear = $foundingYear;
        $this->description = $description;
        $this->website = $website;
        $this->phone = $phone;
        $this->industry = $industry;
        $this->ceo = $ceo;
        $this->isPubliclyTraded = $isPubliclyTraded;
        $this->country = $country;
        $this->founder = $founder;
        $this->totalEmployees = $totalEmployees;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function displayCompanyInfo(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return sprintf(
            "%s (founded %d) - %s | Industry: %s, CEO: %s, Website: %s, Phone: %s, Publicly traded: %s",
            $this->name,
            $this->foundingYear,
            $this->description,
            $this->industry,
            $this->ceo,
            $this->website,
            $this->phone,
            $this->isPubliclyTraded ? "yes" : "no"
        );
    }

    public function toHTML(): string
    {
        return sprintf(
            '<div class="company"><h2>%s</h2><p>%s</p><p>Founded: %d | Industry: %s | CEO: %s</p><p>Website: %s | Phone: %s | Publicly traded: %s</p></div>',
            htmlspecialchars($this->name),
            htmlspecialchars($this->description),
            $this->foundingYear,
            htmlspecialchars($this->industry),
            htmlspecialchars($this->ceo),
            htmlspecialchars($this->website),
            htmlspecialchars($this->phone),
            $this->isPubliclyTraded ? "yes" : "no"
        );
    }

    public function toMarkdown(): string
    {
        return sprintf(
            "## %s\n\n%s\n\n- Founded: %d\n- Industry: %s\n- CEO: %s\n- Website: %s\n- Phone: %s\n- Publicly traded: %s\n",
            $this->name,
            $this->description,
            $this->foundingYear,
            $this->industry,
            $this->ceo,
            $this->website,
            $this->phone,
            $this->isPubliclyTraded ? "yes" : "no"
        );
    }

    public function toArray(): array
    {
        return [
            "name" => $this->name,
            "foundingYear" => $this->foundingYear,
            "description" => $this->description,
            "website" => $this->website,
            "phone" => $this->phone,
            "industry" => $this->industry,
            "ceo" => $this->ceo,
            "isPubliclyTraded" => $this->isPubliclyTraded,
            "country" => $this->country,
            "founder" => $this->founder,
            "totalEmployees" => $this->totalEmployees,
        ];
    }

    public static function RandomGenerator(): static
    {
        $names = ["Tasty Group", "Golden Fork Inc", "Urban Eats", "Flavor Works", "Prime Dining Co"];
        $name = $names[array_rand($names)];
        return new static(
            $name,
            random_int(1950, 2015),
            "A company operating in the food and hospitality business.",
            "https://www." . strtolower(str_replace(" ", "", $name)) . ".example.com",
            sprintf("555-%04d", random_int(0, 9999)),
            "Food & Beverage",
            "Jordan Smith",
            (bool) random_int(0, 1),
            "USA",
            "Alex Founder",
            random_int(100, 50000)
        );
    }
}
