<?php
declare(strict_types=1);

require_once __DIR__ . "/PlantUmlEncoder.php";

$uml = $_GET["uml"] ?? null;
$format = $_GET["format"] ?? "svg";
$download = isset($_GET["download"]);

if ($uml === null || $uml === "") {
    http_response_code(412);
    exit("Missing parameters.");
}
$allowedFormats = ["svg", "png", "txt"];
if (!in_array($format, $allowedFormats, true)) {
    http_response_code(412);
    exit("Invalid format. Must be one of: " . implode(", ", $allowedFormats));
}

// Rendering is delegated to a PlantUML server. In production, host
// plantuml.jar on your own server and point this URL at it; generated
// files are not stored locally, which keeps storage usage minimal.
$url = PlantUmlEncoder::url($uml, $format);
$content = @file_get_contents($url);
if ($content === false) {
    http_response_code(502);
    exit("Failed to render the diagram.");
}

$contentTypes = [
    "svg" => "image/svg+xml",
    "png" => "image/png",
    "txt" => "text/plain",
];
header("Content-Type: " . $contentTypes[$format]);
if ($download) {
    $extensions = ["svg" => "svg", "png" => "png", "txt" => "txt"];
    header(sprintf('Content-Disposition: attachment; filename="diagram.%s"', $extensions[$format]));
}
echo $content;
