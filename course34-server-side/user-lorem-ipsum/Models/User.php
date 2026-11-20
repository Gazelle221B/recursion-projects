<?php
declare(strict_types=1);

namespace Models;

use DateTime;

class User
{
    private int $id;
    private string $firstName;
    private string $lastName;
    private string $email;
    private string $hashedPassword;
    private string $phoneNumber;
    private string $address;
    private DateTime $birthDate;
    private DateTime $membershipExpirationDate;
    private string $role;

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

    public function login(string $password): bool
    {
        return password_verify($password, $this->hashedPassword);
    }

    public function updateProfile(string $address, string $phoneNumber): void
    {
        $this->address = $address;
        $this->phoneNumber = $phoneNumber;
    }

    public function renewMembership(DateTime $expirationDate): void
    {
        $this->membershipExpirationDate = $expirationDate;
    }

    public function changePassword(string $newPassword): void
    {
        $this->hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    }

    public function hasMembershipExpired(): bool
    {
        return new DateTime() > $this->membershipExpirationDate;
    }

    public function toString(): string
    {
        return sprintf(
            "User #%d: %s %s (%s) - Role: %s, Phone: %s, Address: %s, Birth: %s, Membership expires: %s",
            $this->id,
            $this->firstName,
            $this->lastName,
            $this->email,
            $this->role,
            $this->phoneNumber,
            $this->address,
            $this->birthDate->format("Y-m-d"),
            $this->membershipExpirationDate->format("Y-m-d")
        );
    }

    public function toHTML(): string
    {
        return sprintf(
            '<div class="user-card"><h3>%s %s</h3><p>Email: %s</p><p>Role: %s</p><p>Phone: %s</p><p>Address: %s</p></div>',
            htmlspecialchars($this->firstName),
            htmlspecialchars($this->lastName),
            htmlspecialchars($this->email),
            htmlspecialchars($this->role),
            htmlspecialchars($this->phoneNumber),
            htmlspecialchars($this->address)
        );
    }

    public function toMarkdown(): string
    {
        return sprintf(
            "### %s %s\n\n- Email: %s\n- Role: %s\n- Phone: %s\n- Address: %s\n- Birth: %s\n- Membership expires: %s\n",
            $this->firstName,
            $this->lastName,
            $this->email,
            $this->role,
            $this->phoneNumber,
            $this->address,
            $this->birthDate->format("Y-m-d"),
            $this->membershipExpirationDate->format("Y-m-d")
        );
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
}
