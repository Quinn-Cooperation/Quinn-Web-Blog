<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: index.php"); exit(); }
include '../db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard - Blog Posts</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/plugins/bootstrap-grid.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <style>
        /* --- GLOBAL ADMIN STYLES --- */
        body { background-color: #050505; color: #e0e0e0; font-family: 'Helvetica', sans-serif; padding-top: 80px; }
        
        /* --- RESPONSIVE NAVBAR --- */
        .admin-nav {
            background: rgba(17, 17, 17, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #222;
            padding: 15px 0;
            position: fixed;
            top: 0; left: 0; width: 100%;
            z-index: 1000;
        }
        .nav-container { display: flex; justify-content: space-between; align-items: center; }
        
        .logo { font-weight: 900; font-size: 20px; color: #fff; text-decoration: none; letter-spacing: -0.5px; }
        .logo span { color: #ff9800; }

        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-links a { 
            color: #aaa; text-decoration: none; font-size: 14px; font-weight: 600; 
            transition: 0.3s; display: flex; align-items: center; gap: 8px;
        }
        .nav-links a:hover, .nav-links a.active { color: #fff; }
        .nav-links a.active i { color: #ff9800; }

        .btn-write-nav {
            background: #ff9800; color: #000 !important; padding: 8px 16px; border-radius: 6px;
        }
        .btn-write-nav:hover { background: #ffb74d; }

        .nav-actions a { color: #666; font-size: 18px; margin-left: 15px; transition: 0.3s; }
        .nav-actions a:hover { color: #ef5350; }

        /* --- TABLE STYLES --- */
        .mil-table-responsive { overflow-x: auto; background: #111; border-radius: 12px; border: 1px solid #222; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 18px 25px; text-align: left; border-bottom: 1px solid #222; }
        
        /* UPDATED: Removed text-transform: uppercase */
        th { background: #181818; color: #666; font-size: 12px; letter-spacing: 0.5px; font-weight: 700; }
        
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #161616; }
        td { color: #ccc; font-size: 14px; vertical-align: middle; }
        
        /* Buttons */
        .action-btn { 
            padding: 6px 12px; border-radius: 6px; text-decoration: none; 
            font-size: 12px; margin-left: 5px; display: inline-flex; align-items: center; gap: 5px;
            border: 1px solid transparent; cursor: pointer; transition: 0.2s; font-weight: 600;
        }
        .btn-share { background: #004d40; color: #80cbc4; border-color: #00695c; }
        .btn-share:hover { background: #00695c; color: #fff; }
        .btn-edit { background: #222; color: #fff; border-color: #333; }
        .btn-edit:hover { background: #fff; color: #000; }
        .btn-delete { background: #2a0a0a; color: #ff5252; border-color: #4a0a0a; }
        .btn-delete:hover { background: #d32f2f; color: #fff; }

        .status-msg { padding: 15px; border-radius: 8px; margin-bottom: 30px; text-align: center; font-weight: bold; font-size: 14px; }
        .msg-success { background: rgba(27, 94, 32, 0.2); border: 1px solid #1b5e20; color: #66bb6a; }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .nav-links span { display: none; } /* Hide text on mobile, show icons */
            .nav-links { gap: 15px; }
            .btn-write-nav span { display: inline-block; } /* Keep "Write" text */
        }
    </style>
</head>
<body>

    <nav class="admin-nav">
        <div class="container nav-container">
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
        
        <div class="row align-items-center mil-mb-30">
            <div class="col-12">
                <h2 style="color:#fff; margin-bottom: 5px;">Blog Management</h2>
                <p style="color:#666; font-size: 14px;">Manage your articles and content.</p>
            </div>
        </div>

        <?php if(isset($_GET['msg'])) {
            if($_GET['msg'] == 'deleted') echo "<div class='status-msg msg-success'><i class='fa-solid fa-trash'></i> Post Deleted Successfully!</div>";
            if($_GET['msg'] == 'updated') echo "<div class='status-msg msg-success'><i class='fa-solid fa-check'></i> Post Updated Successfully!</div>";
            if($_GET['msg'] == 'created') echo "<div class='status-msg msg-success'><i class='fa-solid fa-rocket'></i> New Post Published!</div>";
        } ?>

        <div class="mil-table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 70px;">Img</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Published</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT posts.*, categories.name as cat_name FROM posts 
                            LEFT JOIN categories ON posts.category_id = categories.id 
                            ORDER BY created_at DESC";
                    $result = $conn->query($sql);

                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td><img src='../" . $row['image_url'] . "' style='width:40px; height:40px; object-fit:cover; border-radius:6px;'></td>";
                            echo "<td><strong>" . $row['title'] . "</strong></td>";
                            // UPDATED: Removed text-transform:uppercase from span style below
                            echo "<td><span style='background:#222; color:#888; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:bold;'>" . $row['cat_name'] . "</span></td>";
                            echo "<td style='color:#666; font-size:13px;'>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                            
                            echo "<td style='text-align:right;'>
                                    <button onclick='copyLink(" . $row['id'] . ")' class='action-btn btn-share'><i class='fa-solid fa-link'></i></button>
                                    <a href='edit.php?id=" . $row['id'] . "' class='action-btn btn-edit'><i class='fa-solid fa-pen'></i> Edit</a>
                                    <a href='delete.php?id=" . $row['id'] . "' class='action-btn btn-delete' onclick='return confirm(\"Are you sure?\")'><i class='fa-solid fa-trash'></i></a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding:40px; color:#555;'>No posts yet. Click 'Write' to start!</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function copyLink(id) {
            var fullUrl = "https://blog.quinncoop.org/post.php?id=" + id;
            navigator.clipboard.writeText(fullUrl).then(function() {
                alert("✅ Link Copied: " + fullUrl);
            }, function() {
                alert("❌ Copy failed. Link: " + fullUrl);
            });
        }
    </script>

</body>
</html>