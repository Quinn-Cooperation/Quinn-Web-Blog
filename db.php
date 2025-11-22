<?php
$host = "localhost";
$user = "magaqmco_quinn"; // Update this
$pass = "Quinn@2025"; // Update this
$db   = "magaqmco_quinn_blog";   // Update this

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
?>