<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: index.php"); exit(); }
include '../db.php';

if (!isset($_GET['id'])) { header("Location: dashboard.php"); exit(); }
$id = $_GET['id'];
$result = $conn->query("SELECT * FROM posts WHERE id=$id");
$post = $result->fetch_assoc();

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $cat = $_POST['category'];
    $contentRaw = trim($_POST['content']);
    
    // Validation
    if (empty($title) || empty($contentRaw) || empty($cat)) {
        $error = "All fields are required.";
    } else {
        // Wrap content in a single paragraph tag
        $content = "<p>" . $conn->real_escape_string($contentRaw) . "</p>";

        // If new image is uploaded
        if (!empty($_FILES["image"]["name"])) {
            $filename = time() . "_" . basename($_FILES["image"]["name"]);
            $target_file = "../img/blog/uploads/" . $filename;
            $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

            // Validate Image
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if($check === false) {
                $error = "File is not an image.";
            } elseif($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
                 $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            } else {
                if(move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $db_image_url = "img/blog/uploads/" . $filename;
                    $sql = "UPDATE posts SET title='$title', content='$content', category_id='$cat', image_url='$db_image_url' WHERE id=$id";
                } else {
                    $error = "Error uploading image.";
                }
            }
        } else {
            // Keep old image
            $sql = "UPDATE posts SET title='$title', content='$content', category_id='$cat' WHERE id=$id";
        }

        if (empty($error)) {
            if ($conn->query($sql) === TRUE) {
                header("Location: dashboard.php?msg=updated");
                exit();
            } else {
                $error = "Database Error: " . $conn->error;
            }
        }
    }
}
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
        /* Added text-transform: none to allow proper casing and numbers */
        input, textarea, select { width: 100%; background: #0f0f0f; border: 1px solid #333; padding: 15px; color: #fff; border-radius: 8px; margin-bottom: 25px; text-transform: none; }
        input:focus, textarea:focus { border-color: #ff9800; outline: none; }
        .btn-submit { background: #ff9800; color: #000; font-weight: bold; border: none; width: 100%; padding: 15px; border-radius: 8px; cursor: pointer; }
        .error-msg { background: #ff3333; color: white; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
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
        <?php if($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-lg-8">
                    <label style="color:#888;">Post Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>
                    
                    <label style="color:#888;">Content</label>
                    <textarea name="content" rows="15" required><?php echo strip_tags($post['content']); ?></textarea>
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