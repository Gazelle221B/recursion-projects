<?php
declare(strict_types=1);

require_once __DIR__ . "/PlantUmlEncoder.php";

$prompts = json_decode((string) file_get_contents(__DIR__ . "/data/prompts.json"), true);
$perPage = 2;
$totalPages = max(1, (int) ceil(count($prompts) / $perPage));
$page = isset($_GET["page"]) ? max(1, min($totalPages, (int) $_GET["page"])) : 1;
$pagePrompts = array_slice($prompts, ($page - 1) * $perPage, $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PlantUML Practice Prompts</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Practice Prompts</h1>
    <p><a href="index.php">Back to the editor</a></p>

    <?php foreach ($pagePrompts as $prompt): ?>
        <div class="prompt">
            <h2>#<?php echo $prompt["id"]; ?> <?php echo htmlspecialchars($prompt["title"]); ?>（<?php echo htmlspecialchars($prompt["theme"]); ?>）</h2>
            <p><?php echo htmlspecialchars($prompt["prompt"]); ?></p>
            <p>
                Your answer: write it in the <a href="index.php">editor</a> and compare it with the example below.
            </p>
            <button type="button" onclick="toggleAnswer(<?php echo $prompt["id"]; ?>)">Show / hide the example answer</button>
            <div id="answer-<?php echo $prompt["id"]; ?>" style="display: none;">
                <pre><code><?php echo htmlspecialchars($prompt["uml"]); ?></code></pre>
                <img src="<?php echo PlantUmlEncoder::url($prompt["uml"], "svg"); ?>" alt="Example diagram">
            </div>
        </div>
    <?php endforeach; ?>

    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php if ($i === $page): ?>
                <strong><?php echo $i; ?></strong>
            <?php else: ?>
                <a href="practice.php?page=<?php echo $i; ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
    </div>

    <script>
        function toggleAnswer(id) {
            const el = document.getElementById("answer-" + id);
            el.style.display = el.style.display === "none" ? "block" : "none";
        }
    </script>
</body>
</html>
