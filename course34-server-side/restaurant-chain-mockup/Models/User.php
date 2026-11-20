<?php
declare(strict_types=1);

namespace RestaurantChain\Models;

use DateTime;

class User implements FileConvertible
{
    protected int $id;
    protected string $firstName;
    protected string $lastName;
    protected string $email;
    protected string $hashedPassword;
    protected string $phoneNumber;
    protected string $address;
    protected DateTime $birthDate;
    protected DateTime $membershipExpirationDate;
    protected string $role;

    public function __construct(
        int $id,
        string $firstName,
        string $lastName,
        string $email,
        string $password,
        string $phoneNumber,
        string $address,
        DateTime $birthDate,
        DateTime $membershipExpirationDate,
        string $role
    ) {
        $this->id = $id;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $this->phoneNumber = $phoneNumber;
        $this->address = $address;
        $this->birthDate = $birthDate;
        $this->membershipExpirationDate = $membershipExpirationDate;
        $this->role = $role;
    }

    public function getFullName(): string
    {
        return $this->firstName . " " . $this->lastName;
    }

    public function login(string $password): bool
    {
        return password_verify($password, $this->hashedPassword);
    }

    public function toString(): string
    {
        return sprintf("%s (%s) - %s", $this->getFullName(), $this->email, $this->role);
    }

    public function toHTML(): string
    {
        return sprintf(
            '<div class="user"><strong>%s</strong> - %s (%s)</div>',
            htmlspecialchars($this->getFullName()),
            htmlspecialchars($this->email),
            htmlspecialchars($this->role)
        );
    }

    public function toMarkdown(): string
    {
        return sprintf("- **%s** - %s (%s)", $this->getFullName(), $this->email, $this->role);
    }

    public function toArray(): array
    {
        return [
            "id" => $this->id,
            "firstName" => $this->firstName,
            "lastName" => $this->lastName,
            "email" => $this->email,
            "phoneNumber" => $this->phoneNumber,
            "address" => $this->address,
            "birthDate" => $this->birthDate->format("Y-m-d"),
            "membershipExpirationDate" => $this->membershipExpirationDate->format("Y-m-d"),
            "role" => $this->role,
        ];
    }

    public static function RandomGenerator(): static
    {
        $firstNames = ["Alice", "Bob", "Carol", "David", "Emma", "Frank", "Grace", "Henry", "Ivy", "Jack"];
        $lastNames = ["Smith", "Johnson", "Brown", "Taylor", "Miller", "Davis", "Garcia", "Wilson", "Moore", "Lee"];
        $firstName = $firstNames[array_rand($firstNames)];
        $lastName = $lastNames[array_rand($lastNames)];
        return new static(
            random_int(1, 100000),
            $firstName,
            $lastName,
            strtolower($firstName . "." . $lastName . random_int(1, 999) . "@example.com"),
            bin2hex(random_bytes(6)),
            sprintf("555-%04d", random_int(0, 9999)),
            sprintf("%d Main Street", random_int(1, 999)),
            new DateTime(sprintf("-%d years", random_int(18, 65))),
            new DateTime(sprintf("%+d years", random_int(-1, 5))),
            ["admin", "user", "editor"][array_rand(["admin", "user", "editor"])]
        );
    }
}
