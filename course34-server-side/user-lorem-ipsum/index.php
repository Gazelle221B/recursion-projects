<?php
declare(strict_types=1);

spl_autoload_extensions(".php");
spl_autoload_register();

use Helpers\RandomGenerator;

$min = isset($_GET["min"]) ? (int) $_GET["min"] : 5;
$max = isset($_GET["max"]) ? (int) $_GET["max"] : 20;
if ($min < 1) {
    $min = 1;
}
if ($max < $min) {
    $max = $min;
}

$users = RandomGenerator::users($min, $max);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Lorem Ipsum</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Random Users</h1>
    <p>Generated <?php echo count($users); ?> users. Try <code>?min=10&amp;max=10</code> to change the count.</p>
    <?php foreach ($users as $user): ?>
        <?php echo $user->toHTML(); ?>
    <?php endforeach; ?>
    <p><a href="generate.php">Download generated users</a></p>
</body>
</html>
