<?php
require_once 'config.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $age = $_POST['age'];
    $weight_goal = $_POST['weight_goal'];
    $dietary_preferences = $_POST['dietary_preferences'];
    $allergies = $_POST['allergies'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $error_message = "Email already exists. Please use a different email.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, age, weight_goal, dietary_preferences, allergies) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$first_name, $last_name, $email, $password, $age, $weight_goal, $dietary_preferences, $allergies])) {
            $success_message = "Sign-up successful! You will be redirected to the login page in 3 seconds.";
            // Redirect to login page after 3 seconds
            header("Refresh: 3; url=login.php");
        } else {
            $error_message = "Failed to sign up. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - NutriLift</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="hero">
        <div class="container">
            <h1>Sign Up for NutriLift</h1>
            <p>Create your account to start your healthy weight gain journey.</p>
        </div>
    </section>

    <main class="container">
        <section class="signup-form">
            <h2>Sign Up</h2>
            <?php if (!empty($success_message)): ?>
                <p class="success-message"><?php echo $success_message; ?></p>
            <?php elseif (!empty($error_message)): ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>
            <?php if (empty($success_message)): ?>
                <form method="POST">
                    <div class="form-flex">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="age">Age</label>
                        <input type="number" id="age" name="age" required>
                    </div>
                    <div class="form-group">
                        <label for="weight_goal">Weight Goal (kg)</label>
                        <input type="number" step="0.1" id="weight_goal" name="weight_goal" required>
                    </div>
                    <div class="form-group">
                        <label for="dietary_preferences">Dietary Preferences (comma-separated, e.g., vegetarian, vegan)</label>
                        <textarea id="dietary_preferences" name="dietary_preferences"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="allergies">Allergies (comma-separated, e.g., nuts, dairy)</label>
                        <textarea id="allergies" name="allergies"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Sign Up</button>
                </form>
                <p>Already have an account? <a href="login.php">Log in</a>.</p>
            <?php endif; ?>
        </section>
    </main>

    <?php include 'footer.php'; ?>
    <script src="script.js"></script>
</body>
</html>