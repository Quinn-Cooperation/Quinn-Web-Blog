<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: index.php"); exit(); }
include '../db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $cat = $_POST['category'];
    $contentRaw = trim($_POST['content']);
    
    // Validation
    if (empty($title) || empty($contentRaw) || empty($cat)) {
        $error = "All fields are required.";
    } elseif (empty($_FILES["image"]["name"])) {
        $error = "Featured image is required.";
    } else {
        // Wrap content in a single paragraph tag as requested
        $content = "<p>" . $conn->real_escape_string($contentRaw) . "</p>";
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));

        $target_dir = "../img/blog/uploads/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        
        $filename = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $filename;
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        
        // Image Validation
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check === false) {
            $error = "File is not an image.";
        } elseif ($_FILES["image"]["size"] > 10000000) { // 10MB Limit
            $error = "Sorry, your file is too large.";
        } elseif($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
             $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        } else {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $db_image_url = "img/blog/uploads/" . $filename;
                $stmt = $conn->prepare("INSERT INTO posts (title, slug, content, image_url, category_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $title, $slug, $content, $db_image_url, $cat);
                
                if ($stmt->execute()) {
                    header("Location: dashboard.php?msg=created");
                    exit();
                } else {
                    $error = "Database Error: " . $stmt->error;
                }
            } else {
                $error = "Sorry, there was an error uploading your file.";
            }
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
            <h3 style="color:#fff;">Write New Post</h3>
            <a href="dashboard.php" style="color:#aaa;">&larr; Back to Dashboard</a>
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
                    <input type="text" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                    
                    <label style="color:#888;">Content</label>
                    <textarea name="content" rows="15" required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
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