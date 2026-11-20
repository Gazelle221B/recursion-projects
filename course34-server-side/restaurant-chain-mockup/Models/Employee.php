<?php
declare(strict_types=1);

namespace RestaurantChain\Models;

use DateTime;

class Employee extends User
{
    private string $jobTitle;
    private float $salary;
    private DateTime $startDate;
    /** @var string[] */
    private array $awards;

    /** @param string[] $awards */
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
        string $role,
        string $jobTitle,
        float $salary,
        DateTime $startDate,
        array $awards = []
    ) {
        parent::__construct($id, $firstName, $lastName, $email, $password, $phoneNumber, $address, $birthDate, $membershipExpirationDate, $role);
        $this->jobTitle = $jobTitle;
        $this->salary = $salary;
        $this->startDate = $startDate;
        $this->awards = $awards;
    }

    public function getJobTitle(): string
    {
        return $this->jobTitle;
    }

    public function getSalary(): float
    {
        return $this->salary;
    }

    public function toString(): string
    {
        return sprintf(
            "%s - %s, Salary: $%.2f, Started: %s, Awards: %s",
            $this->getFullName(),
            $this->jobTitle,
            $this->salary,
            $this->startDate->format("Y-m-d"),
            $this->awards === [] ? "none" : implode(", ", $this->awards)
        );
    }

    public function toHTML(): string
    {
        return sprintf(
            '<div class="employee"><strong>%s</strong> - %s<br>Salary: $%.2f<br>Started: %s<br>Awards: %s</div>',
            htmlspecialchars($this->getFullName()),
            htmlspecialchars($this->jobTitle),
            $this->salary,
            $this->startDate->format("Y-m-d"),
            htmlspecialchars($this->awards === [] ? "none" : implode(", ", $this->awards))
        );
    }

    public function toMarkdown(): string
    {
        return sprintf(
            "- **%s** - %s, Salary: $%.2f, Started: %s, Awards: %s",
            $this->getFullName(),
            $this->jobTitle,
            $this->salary,
            $this->startDate->format("Y-m-d"),
            $this->awards === [] ? "none" : implode(", ", $this->awards)
        );
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            "jobTitle" => $this->jobTitle,
            "salary" => $this->salary,
            "startDate" => $this->startDate->format("Y-m-d"),
            "awards" => $this->awards,
        ]);
    }

    public static function RandomGenerator(float $minSalary = 25000.0, float $maxSalary = 90000.0): static
    {
        $user = User::RandomGenerator();
        $data = $user->toArray();
        $jobTitles = ["Chef", "Cashier", "Server", "Manager", "Dishwasher", "Host"];
        $awardPool = ["Employee of the Month", "Best Customer Service", "Perfect Attendance", "Top Chef"];
        $awards = [];
        $awardCount = random_int(0, 2);
        for ($i = 0; $i < $awardCount; $i++) {
            $awards[] = $awardPool[array_rand($awardPool)];
        }
        return new static(
            $data["id"],
            $data["firstName"],
            $data["lastName"],
            $data["email"],
            bin2hex(random_bytes(6)),
            $data["phoneNumber"],
            $data["address"],
            new DateTime($data["birthDate"]),
            new DateTime($data["membershipExpirationDate"]),
            "employee",
            $jobTitles[array_rand($jobTitles)],
            round(random_int((int) $minSalary, (int) $maxSalary) + random_int(0, 99) / 100, 2),
            new DateTime(sprintf("-%d years", random_int(0, 15))),
            $awards
        );
    }
}
