<?php
ob_start();
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}
include '../db.php';

// Force UTF-8
$conn->set_charset("utf8mb4");

$error = "";

// 1. Fetch existing categories
$cat_options = $conn->query("SELECT * FROM categories ORDER BY name ASC");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $cat_id = $_POST['category'];
    $new_cat = trim($_POST['new_category']);
    $contentRaw = trim($_POST['content']);

    // 2. Handle Custom Category
    if (!empty($new_cat)) {
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
            $cat_id = $conn->insert_id;
        }
    }

    if (empty($title) || empty($contentRaw) || (empty($cat_id) && empty($new_cat))) {
        $error = "All fields are required.";
    } elseif (empty($_FILES["image"]["name"])) {
        $error = "Featured image is required.";
    } else {
        $content = nl2br($contentRaw);
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower($title));
        $slug = trim($slug, '-');

        $target_dir = "../img/blog/uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $filename = time() . "_" . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $filename;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check === false) {
            $error = "File is not an image.";
        } elseif ($_FILES["image"]["size"] > 10000000) {
            $error = "Sorry, your file is too large (Max 10MB).";
        } elseif (!in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
            $error = "Only JPG, JPEG, PNG & GIF allowed.";
        } else {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
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
                $error = "Error uploading file.";
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
       <link rel="icon" type="image/png" href="../img/headimg.png" />
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
        select:focus,
        textarea:focus {
            border-color: #ff9800;
            outline: none;
        }

        .editor-container {
            border: 1px solid #333;
            border-radius: 8px;
            margin-bottom: 25px;
            background: #0f0f0f;
            display: flex;
            flex-direction: column;
        }

        .toolbar {
            background: #222;
            padding: 10px;
            border-bottom: 1px solid #333;
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }

        .tool-btn {
            background: #333;
            color: #ddd;
            border: 1px solid #555;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
        }

        .tool-btn:hover {
            background: #ff9800;
            color: #000;
        }

        textarea#postContent {
            width: 100%;
            background: #0f0f0f;
            border: none;
            padding: 20px;
            color: #fff;
            line-height: 1.6;
            font-size: 16px;
            min-height: 450px;
            resize: vertical;
            font-family: 'Courier New', monospace;
            position: relative;
            z-index: 100 !important;
            pointer-events: auto !important;
            user-select: text !important;
        }

        #previewBox {
            display: none;
            padding: 20px;
            background: #1a1a1a;
            border-top: 1px dashed #444;
            color: #fff;
        }

        #previewBox a {
            color: #ff9800;
            text-decoration: underline;
        }

        /* Make links visible in preview */

        .counter-box {
            padding: 5px 15px;
            font-size: 12px;
            color: #666;
            background: #111;
            border-top: 1px solid #333;
            text-align: right;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        /* MODAL STYLES */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000000 !important;
            justify-content: center;
            align-items: center;
        }

        .toast {
            visibility: hidden;
            min-width: 250px;
            background-color: #333;
            color: #fff;
            text-align: center;
            border-radius: 5px;
            padding: 16px;
            position: fixed;
            z-index: 2000000 !important;
            left: 50%;
            bottom: 30px;
            transform: translateX(-50%);
            border: 1px solid #ff9800;
            box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.5);
            font-size: 15px;
        }

        .toast.show {
            visibility: visible;
            animation: fadein 0.5s, fadeout 0.5s 2.5s;
        }

        .custom-modal {
            background: #222;
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
            border: 1px solid #444;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.8);
            text-align: center;
        }

        .custom-modal h3 {
            margin-top: 0;
            color: #ff9800;
        }

        .btn-confirm {
            background: #ff9800;
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-cancel {
            background: #444;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .btn-danger {
            background: #ff4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
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

        @keyframes fadein {
            from {
                bottom: 0;
                opacity: 0;
            }

            to {
                bottom: 30px;
                opacity: 1;
            }
        }

        @keyframes fadeout {
            from {
                bottom: 30px;
                opacity: 1;
            }

            to {
                bottom: 0;
                opacity: 0;
            }
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
            <a href="./dashboard.php" style="color:#aaa;">&larr; Back to Dashboard</a>
        </div>
    </div>
    <div class="container">
        <?php if ($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <div class="row">
                <div class="col-lg-8">
                    <label>Post Title</label>
                    <input type="text" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" placeholder="Enter title..." required>

                    <label>Content</label>
                    <div class="editor-container">
                        <div class="toolbar">
                            <button type="button" class="tool-btn" onclick="formatText('<b>', '</b>')" title="Bold">B</button>
                            <button type="button" class="tool-btn" onclick="formatText('<i>', '</i>')" title="Italic">I</button>
                            <button type="button" class="tool-btn" onclick="formatText('<u>', '</u>')" title="Underline">U</button>
                            <span style="border-right:1px solid #444; margin:0 5px;"></span>
                            <button type="button" class="tool-btn" onclick="formatList('ul')" title="Points">• Points</button>
                            <button type="button" class="tool-btn" onclick="formatList('ol')" title="Numbers">1. List</button>
                            <span style="border-right:1px solid #444; margin:0 5px;"></span>
                            <button type="button" class="tool-btn" onclick="openResetModal()" style="color:#ffcc00;">↺ Reset</button>
                            <span style="border-right:1px solid #444; margin:0 5px;"></span>
                            <button type="button" class="tool-btn" onclick="formatText('<h2>', '</h2>')">H2</button>
                            <button type="button" class="tool-btn" onclick="openLinkModal()">Link</button>
                            <button type="button" class="tool-btn" onclick="selectAll()">Select All</button>
                            <span style="flex-grow:1;"></span>
                            <button type="button" class="tool-btn" onclick="togglePreview()" style="background:#555;">👁️ Preview</button>
                        </div>

                        <textarea name="content" id="postContent" required placeholder="Write content here..." oninput="updateCounter()"><?php echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; ?></textarea>
                        <div class="counter-box" id="wordCount">Words: 0 | Chars: 0</div>
                        <div id="previewBox"></div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <label>Select Category</label>
                    <select name="category">
                        <option value="">-- Choose Existing --</option>
                        <?php $cat_options->data_seek(0);
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

    <div id="toast" class="toast">Action Successful!</div>

    <div id="linkModal" class="modal-overlay">
        <div class="custom-modal">
            <h3>Insert Link</h3>
            <input type="text" id="linkInput" placeholder="google.com" value="" style="width:100%; padding:10px; border-radius:5px; border:1px solid #555; background:#111; color:#fff;">
            <div class="modal-btns">
                <button type="button" class="btn-confirm" onclick="confirmLink()">Insert</button>
                <button type="button" class="btn-cancel" onclick="closeLinkModal()">Cancel</button>
            </div>
        </div>
    </div>

    <div id="resetModal" class="modal-overlay">
        <div class="custom-modal">
            <h3 style="color:#ff4444;">⚠ Reset Content</h3>
            <p>Clear all text?</p>
            <div class="modal-btns">
                <button type="button" class="btn-danger" onclick="confirmReset()">Yes, Clear All</button>
                <button type="button" class="btn-cancel" onclick="closeResetModal()">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function() {
            var ta = document.getElementById('postContent');
            ta.style.pointerEvents = 'auto';
            ta.style.userSelect = 'text';
        });

        function selectAll() {
            document.getElementById("postContent").select();
        }

        function formatText(openTag, closeTag) {
            var ta = document.getElementById("postContent");
            var start = ta.selectionStart;
            var end = ta.selectionEnd;
            ta.value = ta.value.substring(0, start) + openTag + ta.value.substring(start, end) + closeTag + ta.value.substring(end);
            ta.focus();
            ta.setSelectionRange(start + openTag.length, start + openTag.length + (end - start));
            updateCounter();
        }

        function formatList(type) {
            var ta = document.getElementById("postContent");
            var start = ta.selectionStart;
            var end = ta.selectionEnd;
            var text = ta.value.substring(start, end);
            var tag = type === 'ul' ? 'ul' : 'ol';
            ta.value = ta.value.substring(0, start) + "<" + tag + ">\n  <li>" + (text || "List Item") + "</li>\n</" + tag + ">" + ta.value.substring(end);
            ta.focus();
            updateCounter();
        }

        function openResetModal() {
            document.getElementById('resetModal').style.display = 'flex';
        }

        function closeResetModal() {
            document.getElementById('resetModal').style.display = 'none';
        }

        function confirmReset() {
            document.getElementById("postContent").value = "";
            showToast("Cleared!");
            closeResetModal();
            updateCounter();
        }

        function openLinkModal() {
            document.getElementById('linkModal').style.display = 'flex';
            setTimeout(function() {
                document.getElementById('linkInput').focus();
            }, 100);
        }

        function closeLinkModal() {
            document.getElementById('linkModal').style.display = 'none';
        }

        function confirmLink() {
            var urlInput = document.getElementById('linkInput');
            var url = urlInput.value.trim();

            if (url) {
                // --- FIX FOR XAMPP REDIRECT ---
                // If it starts with "/" (like /dashboard), remove the slash to make it relative to current folder
                if (url.startsWith('/')) {
                    url = url.substring(1);
                }
                // If it looks like a website (has dot) but no http, add https
                else if (url.indexOf('.') > -1 && !url.startsWith('http')) {
                    url = 'https://' + url;
                }

                var ta = document.getElementById("postContent");
                var start = ta.selectionStart;
                var end = ta.selectionEnd;
                var text = ta.value.substring(start, end);

                // Always use target="_blank" to prevent navigating away
                var linkTag = '<a href="' + url + '" target="_blank">' + (text || url) + '</a>';
                ta.value = ta.value.substring(0, start) + linkTag + ta.value.substring(end);

                showToast("Link Inserted!");
            }
            closeLinkModal();
            urlInput.value = "";
        }

        function showToast(msg) {
            var x = document.getElementById("toast");
            x.innerText = msg;
            x.className = "toast show";
            setTimeout(function() {
                x.className = x.className.replace("show", "");
            }, 3000);
        }

        function updateCounter() {
            var text = document.getElementById("postContent").value;
            document.getElementById("wordCount").innerText = "Words: " + (text.trim().split(/\s+/).filter(n => n != '').length) + " | Chars: " + text.length;
        }

        function togglePreview() {
            var ta = document.getElementById("postContent");
            var prev = document.getElementById("previewBox");
            if (prev.style.display === "block") {
                prev.style.display = "none";
                ta.style.display = "block";
            } else {
                prev.innerHTML = "<h4 style='margin-top:0; color:#ff9800;'>Preview</h4>" + ta.value.replace(/\n/g, "<br>");
                prev.style.display = "block";
                ta.style.display = "none";
            }
        }
    </script>
</body>

</html>