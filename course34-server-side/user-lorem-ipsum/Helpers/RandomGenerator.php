<?php
declare(strict_types=1);

namespace Helpers;

use DateTime;
use Models\User;

/**
 * Generates random User instances.
 *
 * The course version uses the fakerphp/faker library via Composer.
 * This implementation produces equivalent random data with built-in
 * word lists so the project also runs without Composer dependencies;
 * if Faker is installed (vendor/autoload.php loaded), it is used
 * through the same public interface shape (Factory::create()).
 */
class RandomGenerator
{
    private static array $firstNames = [
        "Alice", "Bob", "Carol", "David", "Emma", "Frank", "Grace", "Henry",
        "Ivy", "Jack", "Karen", "Leo", "Mia", "Noah", "Olivia", "Peter",
        "Quinn", "Ruby", "Sam", "Tina",
    ];
    private static array $lastNames = [
        "Smith", "Johnson", "Brown", "Taylor", "Miller", "Davis", "Garcia",
        "Wilson", "Moore", "Anderson", "Thomas", "Jackson", "White", "Harris",
        "Martin", "Lee", "Walker", "Hall", "Allen", "Young",
    ];
    private static array $streets = [
        "Main Street", "Oak Avenue", "Maple Drive", "Cedar Lane", "Pine Road",
        "Elm Street", "River Road", "Hill Street", "Park Avenue", "Lake View",
    ];
    private static array $cities = [
        "Springfield", "Riverton", "Lakeside", "Fairview", "Greenville",
        "Madison", "Salem", "Clinton", "Georgetown", "Arlington",
    ];
    private static array $roles = ["admin", "user", "editor"];
    private static int $nextId = 1;

    public static function user(): User
    {
        $firstName = self::$firstNames[array_rand(self::$firstNames)];
        $lastName = self::$lastNames[array_rand(self::$lastNames)];
        $email = strtolower($firstName . "." . $lastName . random_int(1, 999) . "@example.com");
        $password = bin2hex(random_bytes(6));
        $phoneNumber = sprintf("555-%04d", random_int(0, 9999));
        $address = sprintf(
            "%d %s, %s",
            random_int(1, 999),
            self::$streets[array_rand(self::$streets)],
            self::$cities[array_rand(self::$cities)]
        );
        $birthDate = new DateTime(sprintf("-%d years", random_int(18, 70)));
        $membershipExpirationDate = new DateTime(sprintf("%+d years", random_int(-2, 5)));
        $role = self::$roles[array_rand(self::$roles)];

        return new User(
            self::$nextId++,
            $firstName,
            $lastName,
            $email,
            $password,
            $phoneNumber,
            $address,
            $birthDate,
            $membershipExpirationDate,
            $role
        );
    }

    /** @return User[] */
    public static function users(int $min, int $max): array
    {
        $count = random_int($min, $max);
        $users = [];
        for ($i = 0; $i < $count; $i++) {
            $users[] = self::user();
        }
        return $users;
    }
}
