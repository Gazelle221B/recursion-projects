<?php
declare(strict_types=1);

spl_autoload_extensions(".php");
spl_autoload_register();

use RestaurantChain\Models\RestaurantChain;

// Server-side validation
$employeeCount = $_POST["employeeCount"] ?? null;
$minSalary = $_POST["minSalary"] ?? null;
$maxSalary = $_POST["maxSalary"] ?? null;
$locationCount = $_POST["locationCount"] ?? null;
$zipMin = $_POST["zipMin"] ?? null;
$zipMax = $_POST["zipMax"] ?? null;
$format = $_POST["format"] ?? null;

if ($employeeCount === null || $minSalary === null || $maxSalary === null || $locationCount === null || $zipMin === null || $zipMax === null || $format === null) {
    http_response_code(412);
    exit("Missing parameters.");
}
if (!is_numeric($employeeCount) || (int) $employeeCount < 1 || (int) $employeeCount > 20) {
    http_response_code(412);
    exit("Invalid employee count. Must be a number between 1 and 20.");
}
if (!is_numeric($minSalary) || !is_numeric($maxSalary) || (float) $minSalary < 0 || (float) $maxSalary < (float) $minSalary) {
    http_response_code(412);
    exit("Invalid salary range.");
}
if (!is_numeric($locationCount) || (int) $locationCount < 1 || (int) $locationCount > 10) {
    http_response_code(412);
    exit("Invalid location count. Must be a number between 1 and 10.");
}
if (!is_numeric($zipMin) || !is_numeric($zipMax) || (int) $zipMin < 0 || (int) $zipMax > 99999 || (int) $zipMax < (int) $zipMin) {
    http_response_code(412);
    exit("Invalid zip code range.");
}
$allowedFormats = ["html", "json", "txt", "markdown"];
if (!in_array($format, $allowedFormats, true)) {
    http_response_code(412);
    exit("Invalid type. Must be one of: " . implode(", ", $allowedFormats));
}

$chain = RestaurantChain::RandomGenerator(
    (int) $locationCount,
    (int) $employeeCount,
    (float) $minSalary,
    (float) $maxSalary,
    (int) $zipMin,
    (int) $zipMax
);

switch ($format) {
    case "json":
        header("Content-Type: application/json");
        header('Content-Disposition: attachment; filename="restaurant_chain.json"');
        echo json_encode($chain->toArray(), JSON_PRETTY_PRINT);
        break;
    case "txt":
        header("Content-Type: text/plain");
        header('Content-Disposition: attachment; filename="restaurant_chain.txt"');
        echo $chain->toString();
        break;
    case "markdown":
        header("Content-Type: text/markdown");
        header('Content-Disposition: attachment; filename="restaurant_chain.md"');
        echo $chain->toMarkdown();
        break;
    default: // html
        header("Content-Type: text/html");
        echo $chain->toHTML();
        break;
}
