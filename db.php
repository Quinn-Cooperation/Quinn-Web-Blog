<?php
$host = "127.0.0.1";
$user = "root"; // Update this
$pass = ""; // Update this
$db   = "quinn_blog";   // Update this

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
?>