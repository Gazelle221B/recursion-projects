<?php
declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $path = __DIR__ . "/" . str_replace("\\", "/", $class) . ".php";
    if (file_exists($path)) {
        require_once $path;
    }
});

use Helpers\RandomGenerator;

$count = $_POST["count"] ?? null;
$format = $_POST["format"] ?? null;

// Server-side validation
if ($count === null || $format === null) {
    http_response_code(412);
    exit("Missing parameters.");
}
if (!is_numeric($count) || (int) $count < 1 || (int) $count > 100) {
    http_response_code(412);
    exit("Invalid count. Must be a number between 1 and 100.");
}
$allowedFormats = ["html", "markdown", "json", "txt"];
if (!in_array($format, $allowedFormats, true)) {
    http_response_code(412);
    exit("Invalid type. Must be one of: " . implode(", ", $allowedFormats));
}

$count = (int) $count;
$users = RandomGenerator::users($count, $count);

switch ($format) {
    case "markdown":
        header("Content-Type: text/markdown");
        header('Content-Disposition: attachment; filename="users.md"');
        foreach ($users as $user) {
            echo $user->toMarkdown() . "\n";
        }
        break;
    case "json":
        header("Content-Type: application/json");
        header('Content-Disposition: attachment; filename="users.json"');
        echo json_encode(array_map(fn($user) => $user->toArray(), $users), JSON_PRETTY_PRINT);
        break;
    case "txt":
        header("Content-Type: text/plain");
        header('Content-Disposition: attachment; filename="users.txt"');
        foreach ($users as $user) {
            echo $user->toString() . "\n";
        }
        break;
    default: // html
        header("Content-Type: text/html");
        foreach ($users as $user) {
            echo $user->toHTML() . "\n";
        }
        break;
}
