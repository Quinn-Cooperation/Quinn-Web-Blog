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

// Fetch Post
$stmt_get = $conn->prepare("SELECT * FROM posts WHERE id = ?");
$stmt_get->bind_param("i", $id);
$stmt_get->execute();
$post = $stmt_get->get_result()->fetch_assoc();

if (!$post) {
    header("Location: dashboard.php");
    exit();
}

// Fetch Categories
$cat_options = $conn->query("SELECT * FROM categories ORDER BY name ASC");

$error = "";

// --- DEEP CLEANER ---
$clean_content = $post['content'];
$clean_content = str_replace(array('<p>', '</p>'), '', $clean_content);
$clean_content = str_ireplace(array('<br />', '<br>', '<br/>'), "\n", $clean_content);
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

    // Custom Category Logic
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
        $content = nl2br($contentRaw);
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower($title));
        $slug = trim($slug, '-');

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
                // --- FIX: Dynamic Redirect (Prevents 404 Error) ---
                $currentPage = basename($_SERVER['PHP_SELF']);
                header("Location: $currentPage?id=$id&msg=updated");
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

        /* TEXT AREA */
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
                    <div class="editor-container">
                        <div class="toolbar">
                            <button type="button" class="tool-btn" onclick="formatText('<b>', '</b>')" title="Bold">B</button>
                            <button type="button" class="tool-btn" onclick="formatText('<i>', '</i>')" title="Italic">I</button>
                            <button type="button" class="tool-btn" onclick="formatText('<u>', '</u>')" title="Underline">U</button>
                            <span style="border-right:1px solid #444; margin:0 5px;"></span>

                            <button type="button" class="tool-btn" onclick="formatList('ul')" title="Bullet Points">• Points</button>
                            <button type="button" class="tool-btn" onclick="formatList('ol')" title="Numbered List">1. List</button>
                            <span style="border-right:1px solid #444; margin:0 5px;"></span>

                            <button type="button" class="tool-btn" onclick="openResetModal()" title="Reset Added Features" style="color:#ffcc00;">↺ Reset</button>
                            <span style="border-right:1px solid #444; margin:0 5px;"></span>

                            <button type="button" class="tool-btn" onclick="formatText('<h2>', '</h2>')" title="Heading 2">H2</button>
                            <button type="button" class="tool-btn" onclick="openLinkModal()" title="Link">Link</button>
                            <button type="button" class="tool-btn" onclick="selectAll()" title="Select All">Select All</button>

                            <span style="flex-grow:1;"></span>
                            <button type="button" class="tool-btn" onclick="togglePreview()" style="background:#555;">👁️ Preview</button>
                        </div>

                        <textarea name="content" id="postContent" required placeholder="Start writing..." oninput="updateCounter()"><?php echo htmlspecialchars($clean_content); ?></textarea>

                        <div class="counter-box" id="wordCount">Words: 0 | Chars: 0</div>
                        <div id="previewBox"></div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <label>Category</label>
                    <select name="category">
                        <?php while ($c = $cat_options->fetch_assoc()): ?>
                            <option value="<?php echo $c['id']; ?>" <?php if ($post['category_id'] == $c['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($c['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <label>Add New Category</label>
                    <input type="text" name="new_category" placeholder="Type new category name...">

                    <label>Current Featured Image</label>
                    <?php if (!empty($post['image_url'])): ?>
                        <div style="background: #111; padding: 5px; border-radius: 5px; margin-bottom: 10px; border: 1px solid #333;">
                            <img src="../<?php echo $post['image_url']; ?>" style="width:100%; display:block;" alt="Post Image">
                            <p style="font-size:10px; color:#666; margin:5px 0 0 0; word-break:break-all;">
                                Path: <?php echo '../' . htmlspecialchars($post['image_url']); ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <p style="color:#666; border:1px solid #333; padding:10px; border-radius:5px;">No image uploaded.</p>
                    <?php endif; ?>

                    <label>Change Image</label>
                    <input type="file" name="image" accept="image/*">
                    <button type="submit" class="btn-submit">Update Post</button>
                </div>
            </div>
        </form>
    </div>

    <div id="toast" class="toast">Action Successful!</div>

    <div id="linkModal" class="modal-overlay">
        <div class="custom-modal">
            <h3>Insert Link</h3>
            <input type="text" id="linkInput" placeholder="https://" value="https://" style="width:100%; padding:10px; border-radius:5px; border:1px solid #555; background:#111; color:#fff;">
            <div class="modal-btns">
                <button type="button" class="btn-confirm" onclick="confirmLink()">Insert</button>
                <button type="button" class="btn-cancel" onclick="closeLinkModal()">Cancel</button>
            </div>
        </div>
    </div>

    <div id="resetModal" class="modal-overlay">
        <div class="custom-modal">
            <h3 style="color:#ff4444;">⚠ Reset Formatting</h3>
            <p>Remove formatting from selection?</p>
            <div class="modal-btns">
                <button type="button" class="btn-danger" onclick="confirmReset()">Yes, Clean It</button>
                <button type="button" class="btn-cancel" onclick="closeResetModal()">Cancel</button>
            </div>
        </div>
    </div>

    <script>
        // MOUSE FIX
        window.addEventListener('load', function() {
            var ta = document.getElementById('postContent');
            ta.style.pointerEvents = 'auto';
            ta.style.userSelect = 'text';
        });

        function selectAll() {
            var textarea = document.getElementById("postContent");
            textarea.focus();
            textarea.select();
        }

        function formatText(openTag, closeTag) {
            var textarea = document.getElementById("postContent");
            var start = textarea.selectionStart;
            var end = textarea.selectionEnd;
            var text = textarea.value;
            var newText = text.substring(0, start) + openTag + text.substring(start, end) + closeTag + text.substring(end);
            textarea.value = newText;
            textarea.focus();
            if (start === end) {
                textarea.setSelectionRange(start + openTag.length, start + openTag.length);
            } else {
                textarea.setSelectionRange(start, end + openTag.length + closeTag.length);
            }
            updateCounter();
        }

        function formatList(type) {
            var textarea = document.getElementById("postContent");
            var start = textarea.selectionStart;
            var end = textarea.selectionEnd;
            var text = textarea.value;
            var selectedText = text.substring(start, end);
            var listTag = type === 'ul' ? 'ul' : 'ol';

            if (selectedText.length > 0) {
                var newText = text.substring(0, start) + "<" + listTag + ">\n  <li>" + selectedText + "</li>\n</" + listTag + ">" + text.substring(end);
                textarea.value = newText;
            } else {
                var newText = text.substring(0, start) + "<" + listTag + ">\n  <li>Item</li>\n</" + listTag + ">" + text.substring(end);
                textarea.value = newText;
            }
            textarea.focus();
            updateCounter();
        }

        // RESET MODAL LOGIC
        function openResetModal() {
            document.getElementById('resetModal').style.display = 'flex';
        }

        function closeResetModal() {
            document.getElementById('resetModal').style.display = 'none';
        }

        function confirmReset() {
            var textarea = document.getElementById("postContent");
            var start = textarea.selectionStart;
            var end = textarea.selectionEnd;
            var text = textarea.value;
            var selectedText = text.substring(start, end);

            if (selectedText.length > 0) {
                var cleanText = selectedText.replace(/<\/?[^>]+(>|$)/g, "");
                textarea.value = text.substring(0, start) + cleanText + text.substring(end);
                showToast("Selection Cleaned!");
            } else {
                textarea.value = textarea.value.replace(/<\/?[^>]+(>|$)/g, "");
                showToast("Entire Post Cleaned!");
            }
            closeResetModal();
            updateCounter();
        }

        // LINK MODAL LOGIC (FIXED)
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
                // FIX: If it starts with "/" (like /dashboard), remove the slash to make it relative
                // to the current project folder instead of localhost root.
                if (url.startsWith('/')) {
                    url = url.substring(1);
                }
                // FIX: If it looks like a website (has dot) but no http, add https
                else if (url.indexOf('.') > -1 && !url.startsWith('http') && !url.startsWith('#')) {
                    url = 'https://' + url;
                }

                var textarea = document.getElementById("postContent");
                var start = textarea.selectionStart;
                var end = textarea.selectionEnd;
                var selectedText = textarea.value.substring(start, end);

                // If nothing is selected, use the URL as the text so it's visible
                if (selectedText.length === 0) {
                    formatText('<a href="' + url + '" target="_blank">' + url, '</a>');
                } else {
                    formatText('<a href="' + url + '" target="_blank">', '</a>');
                }

                showToast("Link Inserted!");
            }
            closeLinkModal();
            urlInput.value = "";
        }

        function showToast(message) {
            var x = document.getElementById("toast");
            x.innerText = message;
            x.className = "toast show";
            setTimeout(function() {
                x.className = x.className.replace("show", "");
            }, 3000);
        }

        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('msg') === 'updated') {
                showToast("✅ Post Updated Successfully!");
                window.history.replaceState(null, null, window.location.pathname + "?id=<?php echo $id; ?>");
            }
        };

        function updateCounter() {
            var text = document.getElementById("postContent").value;
            document.getElementById("wordCount").innerText = "Words: " + (text.trim().split(/\s+/).filter(n => n != '').length) + " | Chars: " + text.length;
        }

        function togglePreview() {
            var textarea = document.getElementById("postContent");
            var preview = document.getElementById("previewBox");
            if (preview.style.display === "block") {
                preview.style.display = "none";
                textarea.style.display = "block";
            } else {
                preview.innerHTML = "<h4 style='margin-top:0; color:#ff9800;'>Preview</h4>" + textarea.value.replace(/\n/g, "<br>");
                preview.style.display = "block";
                textarea.style.display = "none";
            }
        }
    </script>
</body>

</html>