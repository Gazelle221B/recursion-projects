<?php
declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = "RestaurantChain\\";
    $relative = str_starts_with($class, $prefix) ? substr($class, strlen($prefix)) : $class;
    $path = __DIR__ . "/" . str_replace("\\", "/", $relative) . ".php";
    if (file_exists($path)) {
        require_once $path;
    }
});

use RestaurantChain\Models\RestaurantChain;

$chain = RestaurantChain::RandomGenerator();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Restaurant Chain Mockup</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <h1>Restaurant Chain Mockup</h1>
    <?php echo $chain->toHTML(); ?>
    <p><a href="generate.php">Customize and download chain data</a></p>
</body>
</html>
