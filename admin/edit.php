<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: index.php"); exit(); }
include '../db.php';

$id = $_GET['id'];
$result = $conn->query("SELECT * FROM posts WHERE id=$id");
$post = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $cat = $_POST['category'];
    
    $raw_content = $_POST['content'];
    $content = nl2br($conn->real_escape_string($raw_content));

    if (!empty($_FILES["image"]["name"])) {
        $filename = time() . "_" . basename($_FILES["image"]["name"]);
        move_uploaded_file($_FILES["image"]["tmp_name"], "../img/blog/uploads/" . $filename);
        $db_image_url = "img/blog/uploads/" . $filename;
        $sql = "UPDATE posts SET title='$title', content='$content', category_id='$cat', image_url='$db_image_url' WHERE id=$id";
    } else {
        $sql = "UPDATE posts SET title='$title', content='$content', category_id='$cat' WHERE id=$id";
    }

    if ($conn->query($sql) === TRUE) {
        header("Location: dashboard.php?msg=updated");
        exit();
    }
}

// --- FIX: Convert <br> and </p> to newlines for editing ---
$content_for_edit = $post['content'];
$content_for_edit = preg_replace('/<br\s*\/?>/i', "\n", $content_for_edit); // Turn <br> into new line
$content_for_edit = preg_replace('/<\/p>/i', "\n\n", $content_for_edit); // Turn </p> into double line
$content_for_edit = strip_tags($content_for_edit); // Remove remaining tags
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Edit Post</title>
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
            <h3 style="color:#fff;">Edit Post</h3>
            <a href="dashboard.php" style="color:#aaa;">&larr; Cancel</a>
        </div>
    </div>
    <div class="container">
        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-lg-8">
                    <label style="color:#888;">Post Title</label>
                    <input type="text" name="title" value="<?php echo $post['title']; ?>" required>
                    
                    <label style="color:#888;">Content</label>
                    <textarea name="content" rows="15" required 
                              style="text-transform: none; line-height: 1.5;"><?php echo htmlspecialchars($content_for_edit); ?></textarea>
                </div>
                <div class="col-lg-4">
                    <label style="color:#888;">Category</label>
                    <select name="category">
                        <option value="1" <?php if($post['category_id'] == 1) echo 'selected'; ?>>Technology</option>
                        <option value="2" <?php if($post['category_id'] == 2) echo 'selected'; ?>>Design</option>
                        <option value="3" <?php if($post['category_id'] == 3) echo 'selected'; ?>>News</option>
                    </select>
                    
                    <label style="color:#888;">Change Image (Optional)</label>
                    <img src="../<?php echo $post['image_url']; ?>" style="width:100%; margin-bottom:10px; border-radius:5px;">
                    <input type="file" name="image" accept="image/*">
                    
                    <button type="submit" class="btn-submit">Update Post</button>
                </div>
            </div>
        </form>
    </div>
</body>
</html>