<?php
$host = "localhost";
$user = "buwaggif_quinnblog"; // Update this
$pass = "Quinn@2025"; // Update this
$db   = "buwaggif_quinn_blog";   // Update this

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

// --- FIX TIMEZONE (ADD THESE LINES) ---
// 1. Tell PHP to use Sri Lanka Time
date_default_timezone_set('Asia/Colombo');

// 2. Tell MySQL Database to use Sri Lanka Time (+05:30)
$conn->query("SET time_zone = '+05:30'");
// --------------------------------------

?>

