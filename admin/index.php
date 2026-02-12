<?php
session_start();
include '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Secure Login Logic
    $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['admin'] = true;
            header("Location: dashboard.php");
            exit();
        }
    }
    $error = "Invalid Username or Password";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Admin Login - Quinn</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/plugins/bootstrap-grid.css" />
    <link rel="icon" type="image/png" href="../img/headimg.png" />
    <style>
        /* --- MODERN LOGIN STYLES --- */
        body {
            background-color: #050505;
            color: #e0e0e0;
            font-family: 'Helvetica', sans-serif;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: #111;
            padding: 40px;
            border-radius: 12px;
            border: 1px solid #222;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .logo {
            text-align: center;
            font-weight: 900;
            font-size: 28px;
            color: #fff;
            margin-bottom: 30px;
            letter-spacing: -1px;
        }

        label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #666;
            margin-bottom: 8px;
            display: block;
            font-weight: 700;
        }

        input {
            width: 100%;
            background: #0f0f0f;
            border: 1px solid #333;
            padding: 15px;
            color: #fff;
            border-radius: 8px;
            font-size: 16px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        input:focus {
            border-color: #ff9800;
            outline: none;
            background: #151515;
            box-shadow: 0 0 10px rgba(255, 152, 0, 0.1);
        }

        .btn-login {
            background: #ff9800;
            color: #000;
            font-weight: bold;
            border: none;
            width: 100%;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-login:hover {
            background: #ffb74d;
            transform: translateY(-2px);
        }

        .error-msg {
            background: #b71c1c;
            color: #ffcdd2;
            padding: 12px;
            border-radius: 6px;
            font-size: 14px;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid #ef5350;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #555;
            font-size: 14px;
            text-decoration: none;
            transition: 0.3s;
        }

        .back-link:hover {
            color: #888;
        }
    </style>
</head>

<body>

    <div class="login-card">
        <div class="logo">Quinn.<span style="color:#ff9800; font-weight:100;">Admin</span></div>

        <?php if (isset($error)): ?>
            <div class="error-msg">⚠️ <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="post">
            <label>Username</label>
            <input type="text" name="username" required placeholder="Enter username" autocomplete="off">

            <label>Password</label>
            <input type="password" name="password" required placeholder="Enter password">

            <button type="submit" class="btn-login">Login Access</button>
        </form>

        <a href="../index.php" class="back-link">&larr; Back to Website</a>
    </div>

</body>

</html>