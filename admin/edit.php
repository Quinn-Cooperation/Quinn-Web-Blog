<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}
include '../db.php';

// Force UTF-8
$conn->set_charset("utf8mb4");

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}
$id = (int)$_GET['id'];

// Fetch the existing post
$stmt_get = $conn->prepare("SELECT * FROM posts WHERE id = ?");
$stmt_get->bind_param("i", $id);
$stmt_get->execute();
$post = $stmt_get->get_result()->fetch_assoc();

if (!$post) {
    header("Location: dashboard.php");
    exit();
}

// Fetch categories
$cat_options = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$error = "";

// --- DEEP CLEANER ---
$clean_content = $post['content'];

// 1. Remove HTML tags
$clean_content = str_replace(array('<p>', '</p>'), '', $clean_content);
$clean_content = str_ireplace(array('<br />', '<br>', '<br/>'), "\n", $clean_content);

// 2. Strip slashes multiple times to fix nested escaping
for ($i = 0; $i < 3; $i++) {
    $clean_content = stripslashes($clean_content);
    $clean_content = str_replace(array('\r', '\n'), "\n", $clean_content);
}
$clean_content = trim($clean_content);
// --------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $cat_id = $_POST['category'];
    $new_cat = trim($_POST['new_category']);
    $contentRaw = trim($_POST['content']);

    // Handle Custom Category
    if (!empty($new_cat)) {
        $check_cat = $conn->prepare("SELECT id FROM categories WHERE name = ?");
        $check_cat->bind_param("s", $new_cat);
        $check_cat->execute();
        $res = $check_cat->get_result();

        if ($res->num_rows > 0) {
            $cat_id = $res->fetch_assoc()['id'];
        } else {
            $stmt_new_cat = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt_new_cat->bind_param("s", $new_cat);
            $stmt_new_cat->execute();
            $cat_id = $conn->insert_id;
        }
    }

    if (empty($title) || empty($contentRaw) || empty($cat_id)) {
        $error = "All fields are required.";
    } else {
        // Convert newlines to <br> for saving
        $content = nl2br($contentRaw);

        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower($title));
        $slug = trim($slug, '-');

        // Image Logic
        if (!empty($_FILES["image"]["name"])) {
            $filename = time() . "_" . basename($_FILES["image"]["name"]);
            $target_dir = "../img/blog/uploads/";
            $target_file = $target_dir . $filename;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check === false) {
                $error = "File is not an image.";
            } elseif (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
                $error = "Only JPG, JPEG, PNG & GIF allowed.";
            } else {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $db_image_url = "img/blog/uploads/" . $filename;
                    $stmt_update = $conn->prepare("UPDATE posts SET title=?, slug=?, content=?, category_id=?, image_url=? WHERE id=?");
                    $stmt_update->bind_param("sssisi", $title, $slug, $content, $cat_id, $db_image_url, $id);
                } else {
                    $error = "Error uploading image.";
                }
            }
        } else {
            $stmt_update = $conn->prepare("UPDATE posts SET title=?, slug=?, content=?, category_id=? WHERE id=?");
            $stmt_update->bind_param("sssii", $title, $slug, $content, $cat_id, $id);
        }

        if (empty($error)) {
            if ($stmt_update->execute()) {
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
    <meta charset="UTF-8">
    <title>Edit Post</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/plugins/bootstrap-grid.css" />
    <style>
        body {
            background-color: #050505;
            color: #e0e0e0;
            font-family: 'Helvetica', sans-serif;
        }

        .admin-nav {
            background: #111;
            padding: 20px 0;
            border-bottom: 1px solid #222;
            margin-bottom: 40px;
        }

        input,
        textarea,
        select {
            width: 100%;
            background: #0f0f0f;
            border: 1px solid #333;
            padding: 15px;
            color: #fff;
            border-radius: 8px;
            margin-bottom: 15px;
            text-transform: none;
        }

        input:focus,
        textarea:focus {
            border-color: #ff9800;
            outline: none;
        }

        .btn-submit {
            background: #ff9800;
            color: #000;
            font-weight: bold;
            border: none;
            width: 100%;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
        }

        .error-msg {
            background: #ff3333;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        label {
            color: #888;
            font-size: 14px;
            display: block;
            margin-bottom: 5px;
        }
    </style>
</head>

<body>
    <div class="admin-nav">
        <div class="container">
            <h3>Edit Post</h3>
            <a href="dashboard.php" style="color:#aaa;">&larr; Cancel</a>
        </div>
    </div>
    <div class="container">
        <?php if ($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-lg-8">
                    <label>Post Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($post['title']); ?>" required>

                    <label>Content</label>
                    <textarea name="content" rows="15" required><?php echo htmlspecialchars($clean_content); ?></textarea>
                </div>
                <div class="col-lg-4">
                    <label>Category</label>
                    <select name="category">
                        <?php
                        while ($c = $cat_options->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>" <?php if ($post['category_id'] == $c['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <label>Add New Category Instead</label>
                    <input type="text" name="new_category" placeholder="Type new category name...">

                    <label>Current Featured Image</label>
                    <?php if (!empty($post['image_url'])): ?>
                        <img src="../<?php echo $post['image_url']; ?>" style="width:100%; margin-bottom:10px; border-radius:5px; border: 1px solid #333;">
                    <?php else: ?>
                        <p style="color:#666;">No image uploaded</p>
                    <?php endif; ?>

                    <label>Change Image (Optional)</label>
                    <input type="file" name="image" accept="image/*">

                    <button type="submit" class="btn-submit">Update Post</button>
                </div>
            </div>
        </form>
    </div>
</body>

</html>