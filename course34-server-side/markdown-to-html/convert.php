<?php
declare(strict_types=1);

require_once __DIR__ . "/MarkdownConverter.php";

$markdown = $_POST["markdown"] ?? null;
$mode = $_POST["mode"] ?? "display";

if ($markdown === null) {
    http_response_code(412);
    exit("Missing parameters.");
}
if (!in_array($mode, ["display", "download"], true)) {
    http_response_code(412);
    exit("Invalid mode. Must be one of: display, download");
}

$html = MarkdownConverter::convert($markdown);

if ($mode === "download") {
    header("Content-Type: text/html");
    header('Content-Disposition: attachment; filename="converted.html"');
    echo $html;
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Converted HTML</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Converted HTML</h1>
    <div class="preview">
        <?php echo $html; ?>
    </div>
    <h2>HTML source</h2>
    <pre><code><?php echo htmlspecialchars($html); ?></code></pre>
    <p><a href="index.php">Back to the editor</a></p>
</body>
</html>
