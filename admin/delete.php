<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: index.php"); exit(); }
include '../db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // 1. Get Image Path to delete file
    $result = $conn->query("SELECT image_url FROM posts WHERE id=$id");
    $row = $result->fetch_assoc();
    $image_path = "../" . $row['image_url'];

    // 2. Delete Post from DB
    $sql = "DELETE FROM posts WHERE id=$id";
    if ($conn->query($sql) === TRUE) {
        // 3. Delete Image file if it exists (Optional, saves space)
        if (file_exists($image_path) && strpos($image_path, 'uploads') !== false) {
            unlink($image_path);
        }
        header("Location: dashboard.php?msg=deleted");
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}
?>