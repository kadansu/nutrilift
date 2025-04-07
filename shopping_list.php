<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT * FROM meal_plans WHERE user_id = ? ORDER BY week_number DESC");
$stmt->execute([$user_id]);
$meal_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

$shopping_list = [];
foreach ($meal_plans as $plan) {
    $plan_data = json_decode($plan['plan_data'], true);
    foreach ($plan_data as $day => $day_data) {
        foreach ($day_data['meals'] as $meal => $meal_data) {
            foreach ($meal_data['foods'] as $food) {
                $food_name = $food['name'];
                if (!isset($shopping_list[$food_name])) {
                    $shopping_list[$food_name] = 0;
                }
                $shopping_list[$food_name] += 1;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping List - NutriLift</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="hero">
        <div class="container">
            <h1>Your Shopping List</h1>
            <p>Items needed for your meal plans.</p>
        </div>
    </section>

    <main class="container">
        <section>
            <h2>Shopping List</h2>
            <?php if (empty($shopping_list)): ?>
                <p>No items in your shopping list. Generate a meal plan to get started.</p>
            <?php else: ?>
                <div class="shopping-list-grid">
                    <?php foreach ($shopping_list as $item => $quantity): ?>
                        <div class="shopping-item">
                            <h3><?php echo htmlspecialchars($item); ?></h3>
                            <p><strong>Quantity:</strong> <?php echo $quantity; ?> (approx.)</p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <?php include 'footer.php'; ?>
    <script src="script.js"></script>
</body>
</html>