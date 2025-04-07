<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$success_message = '';
$error_message = '';

$upload_dir = 'upload/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $filename = uniqid() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $destination = $upload_dir . $filename;
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file['type'], $allowed_types)) {
            $error_message = "Invalid file type. Only JPEG, PNG, and GIF files are allowed.";
        } else {
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                $stmt = $pdo->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$filename, $user_id]);
                $success_message = "Profile picture updated successfully!";
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
            } else {
                $error_message = "Failed to upload profile picture.";
            }
        }
    } else {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $age = $_POST['age'];
        $weight_goal = $_POST['weight_goal'];
        $dietary_preferences = $_POST['dietary_preferences'];
        $allergies = $_POST['allergies'];
        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, age = ?, weight_goal = ?, dietary_preferences = ?, allergies = ? WHERE id = ?");
        if ($stmt->execute([$first_name, $last_name, $email, $age, $weight_goal, $dietary_preferences, $allergies, $user_id])) {
            $success_message = "Profile updated successfully!";
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } else {
            $error_message = "Failed to update profile.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - NutriLift</title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="hero">
        <div class="container">
            <h1>Your Profile</h1>
            <p>Manage your personal details below.</p>
        </div>
    </section>

    <main class="container">
        <section class="profile-container">
            <h2>Profile Details</h2>
            <?php if (!empty($success_message)) echo "<p class='success-message'>$success_message</p>"; ?>
            <?php if (!empty($error_message)) echo "<p class='error-message'>$error_message</p>"; ?>
            
            <?php if ($user['profile_picture']): ?>
                <img src="upload/<?php echo htmlspecialchars($user['profile_picture']); ?>" class="profile-pic" alt="Profile Picture">
            <?php else: ?>
                <i class="fas fa-user-circle profile-icon"></i>
            <?php endif; ?>

            <form action="profile.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="profile_picture">Profile Picture</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                    <button type="submit" class="btn btn-primary">Upload</button>
                </div>
            </form>

            <form action="profile.php" method="POST">
                <div class="form-flex">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="age">Age</label>
                    <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($user['age']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="weight_goal">Weight Goal (kg)</label>
                    <input type="number" step="0.1" id="weight_goal" name="weight_goal" value="<?php echo htmlspecialchars($user['weight_goal']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="dietary_preferences">Dietary Preferences</label>
                    <textarea id="dietary_preferences" name="dietary_preferences"><?php echo htmlspecialchars($user['dietary_preferences']); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="allergies">Allergies</label>
                    <textarea id="allergies" name="allergies"><?php echo htmlspecialchars($user['allergies']); ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </section>
    </main>

    <?php include 'footer.php'; ?>
    <script src="script.js"></script>
</body>
</html>