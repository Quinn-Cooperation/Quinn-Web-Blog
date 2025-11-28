<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: index.php"); exit(); }
include '../db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $cat = $_POST['category'];
    $para1 = $_POST['para1'];
    $para2 = $_POST['para2'];
    $content = "<p>" . $conn->real_escape_string($para1) . "</p><br><p>" . $conn->real_escape_string($para2) . "</p>";
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

    $target_dir = "../img/blog/uploads/";
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
    
    $filename = time() . "_" . basename($_FILES["image"]["name"]);
    $target_file = $target_dir . $filename;
    
    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
        $db_image_url = "img/blog/uploads/" . $filename;
        $stmt = $conn->prepare("INSERT INTO posts (title, slug, content, image_url, category_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $title, $slug, $content, $db_image_url, $cat);
        
        if ($stmt->execute()) {
            header("Location: dashboard.php?msg=created"); // Redirect to list
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Write New Post</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/plugins/bootstrap-grid.css" />
    <style>
        body { background-color: #050505; color: #e0e0e0; font-family: 'Helvetica', sans-serif; }
        .admin-nav { background: #111; padding: 20px 0; border-bottom: 1px solid #222; margin-bottom: 40px; }
        input, textarea, select { width: 100%; background: #0f0f0f; border: 1px solid #333; padding: 15px; color: #fff; border-radius: 8px; margin-bottom: 25px; }
        input:focus, textarea:focus { border-color: #ff9800; outline: none; }
        .btn-submit { background: #ff9800; color: #000; font-weight: bold; border: none; width: 100%; padding: 15px; border-radius: 8px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="admin-nav">
        <div class="container">
            <h3 style="color:#fff;">Write New Post</h3>
            <a href="dashboard.php" style="color:#aaa;">&larr; Back to Dashboard</a>
        </div>
    </div>
    <div class="container">
        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-lg-8">
                    <label style="color:#888;">Post Title</label>
                    <input type="text" name="title" required>
                    <label style="color:#888;">Paragraph 1</label>
                    <textarea name="para1" rows="6" required></textarea>
                    <label style="color:#888;">Paragraph 2</label>
                    <textarea name="para2" rows="10" required></textarea>
                </div>
                <div class="col-lg-4">
                    <label style="color:#888;">Category</label>
                    <select name="category">
                        <option value="1">Technology</option>
                        <option value="2">Design</option>
                        <option value="3">News</option>
                    </select>
                    <label style="color:#888;">Featured Image</label>
                    <input type="file" name="image" required accept="image/*">
                    <button type="submit" class="btn-submit">Publish Post</button>
                </div>
            </div>
        </form>
    </div>
</body>
</html>