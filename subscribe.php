<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // Validate Email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Invalid Email Address'); window.history.back();</script>";
        exit();
    }

    // Check if already subscribed
    $check = $conn->query("SELECT id FROM subscribers WHERE email='$email'");
    if ($check->num_rows > 0) {
        echo "<script>alert('You are already subscribed!'); window.history.back();</script>";
        exit();
    }

    // Insert into DB
    $stmt = $conn->prepare("INSERT INTO subscribers (email) VALUES (?)");
    $stmt->bind_param("s", $email);

    if ($stmt->execute()) {
        echo "<script>alert('Successfully Subscribed! 🚀'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Database Error'); window.history.back();</script>";
    }
}
?>