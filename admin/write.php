<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}
include '../db.php';

// Force the connection to use UTF-8 for Sinhala support
$conn->set_charset("utf8mb4");

$error = "";

// 1. Fetch existing categories dynamically from the database
$cat_options = $conn->query("SELECT * FROM categories ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $cat_id = $_POST['category'];
    $new_cat = trim($_POST['new_category']);
    $contentRaw = trim($_POST['content']);

    // 2. Handle Custom Category Addition
    if (!empty($new_cat)) {
        // Check if category already exists to avoid duplicates
        $check_cat = $conn->prepare("SELECT id FROM categories WHERE name = ?");
        $check_cat->bind_param("s", $new_cat);
        $check_cat->execute();
        $res = $check_cat->get_result();

        if ($res->num_rows > 0) {
            $cat_id = $res->fetch_assoc()['id'];
        } else {
            $stmt_cat = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt_cat->bind_param("s", $new_cat);
            $stmt_cat->execute();
            $cat_id = $conn->insert_id; // Use the newly created category ID
        }
    }

    // Validation
    if (empty($title) || empty($contentRaw) || (empty($cat_id) && empty($new_cat))) {
        $error = "All fields are required.";
    } elseif (empty($_FILES["image"]["name"])) {
        $error = "Featured image is required.";
    } else {
        // Wrap content in a single paragraph tag
        $content = "<p>" . $conn->real_escape_string($contentRaw) . "</p>";

        // 3. Improve Slug for Sinhala: Replace spaces/special chars with hyphens
        // mb_strtolower handles unicode characters properly
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower($title));
        $slug = trim($slug, '-');

        $target_dir = "../img/blog/uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $filename = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $filename;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Image Validation
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check === false) {
            $error = "File is not an image.";
        } elseif ($_FILES["image"]["size"] > 10000000) {
            $error = "Sorry, your file is too large (Max 10MB).";
        } elseif (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
            $error = "Only JPG, JPEG, PNG & GIF allowed.";
        } else {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // Save path relative to root
                $db_image_url = "img/blog/uploads/" . $filename;

                $stmt = $conn->prepare("INSERT INTO posts (title, slug, content, image_url, category_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssi", $title, $slug, $content, $db_image_url, $cat_id);

                if ($stmt->execute()) {
                    header("Location: dashboard.php?msg=created");
                    exit();
                } else {
                    $error = "Database Error: " . $stmt->error;
                }
            } else {
                $error = "Error uploading file. Check folder permissions on the server.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Write New Post</title>
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

        /* text-transform: none is vital so Sinhala text isn't messed up by CSS */
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
            margin-top: 10px;
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
            display: block;
            margin-bottom: 5px;
            font-size: 14px;
        }
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
        <?php if ($error): ?>
            <div class="error-msg"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-lg-8">
                    <label>Post Title (Sinhala Supported)</label>
                    <input type="text" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" placeholder="Enter title here..." required>

                    <label>Content</label>
                    <textarea name="content" rows="15" placeholder="Write your blog content..." required><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                </div>
                <div class="col-lg-4">
                    <label>Select Category</label>
                    <select name="category">
                        <option value="">-- Choose Existing --</option>
                        <?php
                        // Reset pointer to loop through categories again if needed
                        $cat_options->data_seek(0);
                        while ($c = $cat_options->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                        <?php endwhile; ?>
                    </select>

                    <label>OR Add New Category</label>
                    <input type="text" name="new_category" placeholder="Type new category name">

                    <label>Featured Image</label>
                    <input type="file" name="image" required accept="image/*">

                    <button type="submit" class="btn-submit">Publish Post</button>
                </div>
            </div>
        </form>
    </div>
</body>

</html>