<?php
include 'db.php';

// 1. Check if an admin already exists
$check = $conn->query("SELECT COUNT(*) as count FROM admins");
$row = $check->fetch_assoc();

if ($row['count'] > 0) {
    // 2. If admin exists, lock the page
    $locked = true;
} else {
    $locked = false;
    
    // 3. Handle Form Submission to Create Admin
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (!empty($username) && !empty($password)) {
            // Secure password hashing
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $username, $hashed_password);

            if ($stmt->execute()) {
                $success = "Admin User Created Successfully! This form is now disabled.";
                $locked = true; // Lock immediately after success
            } else {
                $error = "Database Error: " . $conn->error;
            }
        } else {
            $error = "Please fill in all fields.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Blog Setup</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/plugins/bootstrap-grid.css">
    <style>
        body { background: #000; color: #fff; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .setup-box { background: #111; padding: 40px; border: 1px solid #333; max-width: 400px; width: 100%; text-align: center; }
        input { width: 100%; padding: 15px; margin-bottom: 15px; background: #000; border: 1px solid #333; color: #fff; }
        button { width: 100%; padding: 15px; background: #fff; color: #000; font-weight: bold; border: none; cursor: pointer; }
        button:hover { background: #ccc; }
        .msg-success { color: #4caf50; margin-bottom: 20px; }
        .msg-error { color: #f44336; margin-bottom: 20px; }
        .locked-msg { font-size: 1.2rem; line-height: 1.6; }
    </style>
</head>
<body>

    <div class="setup-box">
        <h2 class="mil-mb-30">Blog Setup</h2>

        <?php if ($locked): ?>
            <div class="msg-success">
                <?php if(isset($success)) echo $success; ?>
            </div>
            <div class="locked-msg">
                <p>Setup has already been completed.</p>
                <p>For security, the setup form is now <strong>hidden</strong>.</p>
                <br>
                <a href="admin/login.php" style="color: #fff; text-decoration: underline;">Go to Admin Login</a>
                <br><br>
                <small style="color: #666;">(Recommended: Delete 'setup.php' from your server file manager)</small>
            </div>

        <?php else: ?>
            <?php if(isset($error)) echo "<div class='msg-error'>$error</div>"; ?>
            
            <p style="margin-bottom: 20px; color: #999;">Set your Admin credentials below. This can only be done once.</p>

            <form method="post">
                <input type="text" name="username" placeholder="Set Username" required autocomplete="off">
                <input type="password" name="password" placeholder="Set Password" required autocomplete="off">
                <button type="submit">Create Admin Account</button>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>