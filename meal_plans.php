<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

function generateMealPlan($pdo, $user) {
    $dietary_preferences = $user['dietary_preferences'] ? explode(',', $user['dietary_preferences']) : [];
    $allergies = $user['allergies'] ? explode(',', $user['allergies']) : [];

    $query = "SELECT * FROM foods WHERE 1=1";
    $params = [];
    if (!empty($allergies)) {
        foreach ($allergies as $allergy) {
            $query .= " AND name NOT LIKE ?";
            $params[] = "%" . trim($allergy) . "%";
        }
    }
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $meal_plan = [];
    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $meals = ['Breakfast', 'Lunch', 'Dinner'];
    $target_calories = 2500;

    foreach ($days as $day) {
        $daily_meals = [];
        $daily_calories = 0;
        foreach ($meals as $meal) {
            $meal_foods = [];
            $meal_calories = 0;
            while ($meal_calories < $target_calories / 3 && count($foods) > 0) {
                $food = $foods[array_rand($foods)];
                if (!empty($dietary_preferences) && in_array('vegetarian', $dietary_preferences) && $food['category'] == 'protein' && $food['name'] != 'Tofu') {
                    continue;
                }
                $meal_foods[] = $food;
                $meal_calories += $food['calories'];
            }
            $daily_meals[$meal] = [
                'foods' => $meal_foods,
                'calories' => $meal_calories,
                'macros' => [
                    'carbs' => array_sum(array_column($meal_foods, 'carbs')),
                    'protein' => array_sum(array_column($meal_foods, 'protein')),
                    'fat' => array_sum(array_column($meal_foods, 'fat'))
                ]
            ];
            $daily_calories += $meal_calories;
        }
        $meal_plan[$day] = [
            'meals' => $daily_meals,
            'total_calories' => $daily_calories
        ];
    }
    return $meal_plan;
}

if (isset($_POST['generate_plan'])) {
    $week_number = $_POST['week_number'];
    $meal_plan = generateMealPlan($pdo, $user);
    $plan_data = json_encode($meal_plan);
    $stmt = $pdo->prepare("INSERT INTO meal_plans (user_id, week_number, plan_data) VALUES (?, ?, ?)");
    if ($stmt->execute([$user_id, $week_number, $plan_data])) {
        $success_message = "Meal plan generated successfully!";
    } else {
        $error_message = "Failed to generate meal plan.";
    }
}

if (isset($_GET['delete'])) {
    $plan_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM meal_plans WHERE id = ? AND user_id = ?");
    if ($stmt->execute([$plan_id, $user_id])) {
        $success_message = "Meal plan deleted successfully!";
    } else {
        $error_message = "Failed to delete meal plan.";
    }
}

$stmt = $pdo->prepare("SELECT * FROM meal_plans WHERE user_id = ? ORDER BY week_number DESC");
$stmt->execute([$user_id]);
$meal_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Plans - NutriLift</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="hero">
        <div class="container">
            <h1>Your Meal Plans</h1>
            <p>Generate and view your personalized meal plans below.</p>
        </div>
    </section>

    <main class="container">
        <section>
            <h2>Generate a New Meal Plan</h2>
            <form method="POST" class="edit-form">
                <div class="form-group">
                    <label for="week_number">Week Number</label>
                    <input type="number" id="week_number" name="week_number" required>
                </div>
                <button type="submit" name="generate_plan" class="btn btn-primary">Generate Plan</button>
            </form>

            <h2>Your Meal Plans</h2>
            <?php if (!empty($success_message)) echo "<p class='success-message'>$success_message</p>"; ?>
            <?php if (!empty($error_message)) echo "<p class='error-message'>$error_message</p>"; ?>

            <?php if (empty($meal_plans)): ?>
                <p>You have no meal plans. Generate one above to get started.</p>
            <?php else: ?>
                <div class="meal-plans-grid">
                    <?php foreach ($meal_plans as $plan): ?>
                        <div class="meal-plan-card">
                            <h3>Week <?php echo htmlspecialchars($plan['week_number']); ?></h3>
                            <p><strong>Generated On:</strong> <?php echo htmlspecialchars($plan['created_at']); ?></p>
                            <?php
                            $plan_data = json_decode($plan['plan_data'], true);
                            foreach ($plan_data as $day => $day_data): ?>
                                <h4><?php echo $day; ?></h4>
                                <?php foreach ($day_data['meals'] as $meal => $meal_data): ?>
                                    <p><strong><?php echo $meal; ?>:</strong></p>
                                    <ul>
                                        <?php foreach ($meal_data['foods'] as $food): ?>
                                            <li><?php echo htmlspecialchars($food['name']); ?> (<?php echo $food['calories']; ?> kcal)</li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <p><strong>Calories:</strong> <?php echo round($meal_data['calories'], 2); ?> kcal</p>
                                    <p><strong>Macros:</strong> Carbs: <?php echo round($meal_data['macros']['carbs'], 2); ?>g, Protein: <?php echo round($meal_data['macros']['protein'], 2); ?>g, Fat: <?php echo round($meal_data['macros']['fat'], 2); ?>g</p>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            <a href="meal_plans.php?delete=<?php echo $plan['id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this meal plan?');">Delete</a>
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