<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit();
}
include '../db.php';

// --- PAGINATION LOGIC ---
$limit = 10; // Posts per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get Total Posts count
$count_sql = "SELECT COUNT(*) as total FROM posts";
$count_result = $conn->query($count_sql);
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>Admin Dashboard - Blog Posts</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="icon" type="image/png" href="../img/headimg.png" />

    <style>
        /* --- GLOBAL ADMIN STYLES --- */
        body {
            background-color: #050505;
            color: #e0e0e0;
            font-family: 'Helvetica', sans-serif;
            padding-top: 80px;
        }

        /* NAVBAR */
        .admin-nav {
            background: rgba(17, 17, 17, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #222;
            padding: 15px 0;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo {
            font-weight: 900;
            font-size: 20px;
            color: #fff;
            text-decoration: none;
        }

        .logo span {
            color: #ff9800;
        }

        .nav-links {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .nav-links a {
            color: #aaa;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.3s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: #fff;
        }

        .btn-write-nav {
            background: #ff9800;
            color: #000 !important;
            padding: 8px 16px;
            border-radius: 6px;
        }

        .nav-actions a {
            color: #666;
            font-size: 18px;
            margin-left: 15px;
        }

        .nav-actions a:hover {
            color: #ff5252;
        }

        /* TABLE */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .mil-table-responsive {
            overflow-x: auto;
            background: #111;
            border-radius: 12px;
            border: 1px solid #222;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        th,
        td {
            padding: 18px 25px;
            text-align: left;
            border-bottom: 1px solid #222;
        }

        th {
            background: #181818;
            color: #666;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        tr:hover {
            background: #161616;
        }

        td {
            color: #ccc;
            font-size: 14px;
            vertical-align: middle;
        }

        /* ACTION BUTTONS */
        .action-btn {
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 12px;
            margin-left: 5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: 1px solid transparent;
            cursor: pointer;
            transition: 0.2s;
            font-weight: 600;
        }

        .btn-share {
            background: #004d40;
            color: #80cbc4;
            border-color: #00695c;
        }

        .btn-share:hover {
            background: #00695c;
            color: #fff;
        }

        .btn-edit {
            background: #222;
            color: #fff;
            border-color: #333;
        }

        .btn-edit:hover {
            background: #fff;
            color: #000;
        }

        .btn-delete {
            background: #2a0a0a;
            color: #ff5252;
            border-color: #4a0a0a;
        }

        .btn-delete:hover {
            background: #d32f2f;
            color: #fff;
        }

        /* PAGINATION */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
            margin-bottom: 50px;
        }

        .page-link {
            padding: 8px 16px;
            background: #111;
            border: 1px solid #333;
            color: #888;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            transition: 0.3s;
        }

        .page-link:hover {
            background: #222;
            color: #fff;
            border-color: #555;
        }

        .page-link.active {
            background: #ff9800;
            color: #000;
            border-color: #ff9800;
            font-weight: bold;
        }

        .page-link.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        /* --- MODAL (DELETE CONFIRMATION) --- */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            z-index: 2000;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            visibility: hidden;
            transition: 0.3s;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal-box {
            background: #1a1a1a;
            border: 1px solid #333;
            padding: 30px;
            border-radius: 16px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            transform: scale(0.9);
            transition: 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }

        .modal-overlay.active .modal-box {
            transform: scale(1);
        }

        .modal-icon {
            font-size: 40px;
            color: #ff5252;
            margin-bottom: 15px;
        }

        .modal-title {
            color: #fff;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .modal-text {
            color: #888;
            font-size: 14px;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .modal-btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 14px;
            transition: 0.2s;
        }

        .btn-cancel {
            background: #333;
            color: #fff;
        }

        .btn-cancel:hover {
            background: #444;
        }

        .btn-confirm-delete {
            background: #d32f2f;
            color: #fff;
        }

        .btn-confirm-delete:hover {
            background: #b71c1c;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(211, 47, 47, 0.3);
        }

        /* TOAST NOTIFICATION */
        #toast-container {
            visibility: hidden;
            min-width: 300px;
            background-color: #1a1a1a;
            color: #fff;
            text-align: center;
            border-radius: 8px;
            padding: 16px;
            position: fixed;
            z-index: 3000;
            right: 30px;
            bottom: 30px;
            font-size: 14px;
            border-left: 5px solid #ff9800;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            transform: translateY(100px);
            opacity: 0;
            transition: 0.5s;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        #toast-container.show {
            visibility: visible;
            transform: translateY(0);
            opacity: 1;
        }

        .toast-success {
            border-color: #00e676 !important;
        }

        .toast-success i {
            color: #00e676;
        }

        .toast-error {
            border-color: #ff1744 !important;
        }

        .toast-error i {
            color: #ff1744;
        }
    </style>
</head>

<body>

    <nav class="admin-nav">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">Quinn.<span>Admin</span></a>
            <div class="nav-links">
                <a href="dashboard.php" class="active"><i class="fa-solid fa-layer-group"></i> <span>Blog Posts</span></a>
                <a href="subscribers.php"><i class="fa-solid fa-users"></i> <span>Subscribers</span></a>
                <a href="write.php" class="btn-write-nav"><i class="fa-solid fa-pen-nib"></i> <span>Write</span></a>
            </div>
            <div class="nav-actions">
                <a href="../index.php" target="_blank" title="View Website"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                <a href="logout.php" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </div>
    </nav>

    <div class="container">

        <div class="row align-items-center mil-mb-30" style="margin-bottom: 20px;">
            <div class="col-12">
                <h2 style="color:#fff; margin-bottom: 5px;">Blog Management</h2>
                <p style="color:#666; font-size: 14px;">Total Posts: <?php echo $total_rows; ?></p>
            </div>
        </div>

        <div class="mil-table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 70px;">Img</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Views</th>
                        <th>Published</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch posts with limit for pagination
                    $sql = "SELECT posts.*, categories.name as cat_name FROM posts 
                            LEFT JOIN categories ON posts.category_id = categories.id 
                            ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            // Ensure views is a number
                            $views = isset($row['views']) ? number_format($row['views']) : 0;

                            echo "<tr>";
                            echo "<td><img src='../" . $row['image_url'] . "' style='width:40px; height:40px; object-fit:cover; border-radius:6px;'></td>";
                            echo "<td><strong>" . htmlspecialchars($row['title']) . "</strong></td>";
                            echo "<td><span style='background:#222; color:#888; padding:4px 8px; border-radius:4px; font-size:11px; text-transform:uppercase; font-weight:bold;'>" . htmlspecialchars($row['cat_name'] ?? 'Uncategorized') . "</span></td>";
                            echo "<td><span style='color:#ff9800; font-weight:bold; font-size:13px;'><i class='fa-regular fa-eye'></i> " . $views . "</span></td>";
                            echo "<td style='color:#666; font-size:13px;'>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";

                            echo "<td style='text-align:right;'>
                                    <button onclick='copyLink(" . $row['id'] . ")' class='action-btn btn-share'><i class='fa-solid fa-link'></i></button>
                                    <a href='edit.php?id=" . $row['id'] . "' class='action-btn btn-edit'><i class='fa-solid fa-pen'></i> Edit</a>
                                    <button onclick='confirmDelete(" . $row['id'] . ")' class='action-btn btn-delete'><i class='fa-solid fa-trash'></i></button>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' style='text-align:center; padding:40px; color:#555;'>No posts found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <a href="?page=<?php echo max(1, $page - 1); ?>" class="page-link <?php if ($page <= 1) echo 'disabled'; ?>">Prev</a>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>" class="page-link <?php if ($page == $i) echo 'active'; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>

                <a href="?page=<?php echo min($total_pages, $page + 1); ?>" class="page-link <?php if ($page >= $total_pages) echo 'disabled'; ?>">Next</a>
            </div>
        <?php endif; ?>

    </div>

    <div id="deleteModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <h3 class="modal-title">Delete Post?</h3>
            <p class="modal-text">Are you sure you want to delete this post? This action cannot be undone.</p>
            <div class="modal-actions">
                <button class="modal-btn btn-cancel" onclick="closeModal()">Cancel</button>
                <button id="confirmDeleteBtn" class="modal-btn btn-confirm-delete">Yes, Delete</button>
            </div>
        </div>
    </div>

    <div id="toast-container">
        <i class="fa-solid fa-circle-info"></i>
        <span id="toast-message">Notification</span>
    </div>

    <script>
        // --- 1. TOAST NOTIFICATIONS ---
        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            const msg = urlParams.get('msg');

            if (msg) {
                let text = "";
                let type = "success";

                if (msg === 'deleted') {
                    text = "Post deleted successfully!";
                    type = "error";
                } else if (msg === 'updated') {
                    text = "Post updated successfully!";
                } else if (msg === 'created') {
                    text = "New post published!";
                }

                if (text) showToast(text, type);

                // Remove param from URL
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }
        });

        function showToast(message, type = 'normal') {
            var x = document.getElementById("toast-container");
            var msgSpan = document.getElementById("toast-message");
            var icon = x.querySelector("i");

            msgSpan.innerText = message;
            x.className = "show";

            if (type === 'success') {
                x.classList.add("toast-success");
                icon.className = "fa-solid fa-circle-check";
            } else if (type === 'error') {
                x.classList.add("toast-error");
                icon.className = "fa-solid fa-trash-can";
            } else {
                icon.className = "fa-solid fa-circle-info";
            }

            setTimeout(function() {
                x.className = x.className.replace("show", "");
            }, 3500);
        }

        // --- 2. DELETE MODAL LOGIC ---
        function confirmDelete(id) {
            var modal = document.getElementById('deleteModal');
            var confirmBtn = document.getElementById('confirmDeleteBtn');

            // Set the onclick action for the "Yes" button
            confirmBtn.onclick = function() {
                window.location.href = 'delete.php?id=' + id;
            };

            modal.classList.add('active');
        }

        function closeModal() {
            document.getElementById('deleteModal').classList.remove('active');
        }

        // Close modal if clicking outside box
        window.onclick = function(event) {
            var modal = document.getElementById('deleteModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // --- 3. COPY LINK FUNCTION ---
        function copyLink(id) {
            // Adjust this URL to match your live site domain
            var fullUrl = window.location.origin + "/Quinn-Web-Blog/post.php?id=" + id;

            navigator.clipboard.writeText(fullUrl).then(function() {
                showToast("Link copied to clipboard!", "success");
            }, function() {
                showToast("Failed to copy link.", "error");
            });
        }
    </script>

</body>

</html>