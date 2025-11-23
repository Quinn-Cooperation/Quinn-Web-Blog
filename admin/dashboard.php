<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: index.php"); exit(); }
include '../db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/plugins/bootstrap-grid.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <style>
        body { background-color: #050505; color: #e0e0e0; font-family: 'Helvetica', sans-serif; }
        .admin-nav { background: #111; padding: 20px 0; border-bottom: 1px solid #222; margin-bottom: 40px; }
        .nav-flex { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-weight: 900; font-size: 24px; color: #fff; }
        
        /* Table Styles */
        .mil-table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; background: #111; border-radius: 8px; overflow: hidden; }
        th, td { padding: 15px 20px; text-align: left; border-bottom: 1px solid #222; }
        th { background: #1a1a1a; color: #888; text-transform: uppercase; font-size: 12px; letter-spacing: 1px; }
        tr:hover { background: #151515; }
        td { color: #ccc; font-size: 15px; vertical-align: middle; }

        /* Buttons */
        .btn-create { background: #ff9800; color: #000; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block; }
        .btn-create:hover { background: #ffb74d; }
        
        .action-btn { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 13px; margin-right: 5px; display: inline-block; border: none; cursor: pointer; transition: 0.2s; }
        
        /* Edit Button */
        .btn-edit { background: #222; color: #fff; border: 1px solid #333; }
        .btn-edit:hover { background: #fff; color: #000; }
        
        /* Share Button (NEW) */
        .btn-share { background: #004d40; color: #80cbc4; border: 1px solid #00695c; }
        .btn-share:hover { background: #00695c; color: #fff; }

        /* Delete Button */
        .btn-delete { background: #3a0000; color: #ff5252; border: 1px solid #500000; }
        .btn-delete:hover { background: #ff0000; color: #fff; }

        .status-msg { padding: 15px; border-radius: 6px; margin-bottom: 20px; text-align: center; font-weight: bold; }
        .msg-success { background: #1b5e20; color: #fff; }
    </style>
</head>
<body>

    <div class="admin-nav">
        <div class="container">
            <div class="nav-flex">
                <div class="logo">Quinn.<span style="color:#ff9800;">Admin</span></div>
                <div>
                    <a href="../index.php" target="_blank" style="color:#aaa; margin-right:20px; text-decoration:none;">Visit Site</a>
                    <a href="logout.php" style="color:#ef5350; text-decoration:none;">Logout</a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if(isset($_GET['msg'])) {
            if($_GET['msg'] == 'deleted') echo "<div class='status-msg msg-success'>Post Deleted Successfully! 🗑️</div>";
            if($_GET['msg'] == 'updated') echo "<div class='status-msg msg-success'>Post Updated Successfully! ✅</div>";
            if($_GET['msg'] == 'created') echo "<div class='status-msg msg-success'>New Post Published! 🚀</div>";
        } ?>

        <div class="row align-items-center mil-mb-30">
            <div class="col-6"><h2 style="color:#fff; margin:0;">All Posts</h2></div>
            <div class="col-6" style="text-align:right;">
                <a href="write.php" class="btn-create">+ Write New Post</a>
            </div>
        </div>

        <div class="mil-table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">Image</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Date</th>
                        <th style="text-align:right; width: 260px;">Actions</th>
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
                            echo "<td><img src='../" . $row['image_url'] . "' style='width:50px; height:50px; object-fit:cover; border-radius:4px;'></td>";
                            echo "<td><strong>" . $row['title'] . "</strong></td>";
                            echo "<td><span style='background:#222; padding:3px 8px; border-radius:4px; font-size:12px;'>" . $row['cat_name'] . "</span></td>";
                            echo "<td style='color:#666; font-size:13px;'>" . date('M d, Y', strtotime($row['created_at'])) . "</td>";
                            
                            // ACTION BUTTONS
                            echo "<td style='text-align:right;'>
                                    <button onclick='copyLink(" . $row['id'] . ")' class='action-btn btn-share'><i class='fa-solid fa-share-from-square'></i> Share</button>
                                    <a href='edit.php?id=" . $row['id'] . "' class='action-btn btn-edit'><i class='fa-solid fa-pen'></i> Edit</a>
                                    <a href='delete.php?id=" . $row['id'] . "' class='action-btn btn-delete' onclick='return confirm(\"Are you sure you want to delete this?\")'><i class='fa-solid fa-trash'></i></a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' style='text-align:center; padding:30px; color:#666;'>No posts found. Start writing!</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function copyLink(id) {
            // 1. Construct the URL
            // NOTE: Change 'blog.quinncoop.org' if you change domains later.
            var baseUrl = "https://blog.quinncoop.org/post.php?id=";
            var fullUrl = baseUrl + id;

            // 2. Copy to Clipboard
            navigator.clipboard.writeText(fullUrl).then(function() {
                // 3. Success Feedback
                alert("✅ Link Copied to Clipboard!\n\n" + fullUrl);
            }, function(err) {
                alert("❌ Could not copy link. Manual copy:\n" + fullUrl);
            });
        }
    </script>

</body>
</html>