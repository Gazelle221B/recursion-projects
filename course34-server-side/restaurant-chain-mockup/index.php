<?php
declare(strict_types=1);

spl_autoload_extensions(".php");
spl_autoload_register();

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
