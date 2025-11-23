<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: index.php"); exit(); }
include '../db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard - Subscribers</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/plugins/bootstrap-grid.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <style>
        /* REUSING GLOBAL STYLES FROM DASHBOARD.PHP FOR CONSISTENCY */
        body { background-color: #050505; color: #e0e0e0; font-family: 'Helvetica', sans-serif; padding-top: 80px; }
        .admin-nav { background: rgba(17, 17, 17, 0.95); backdrop-filter: blur(10px); border-bottom: 1px solid #222; padding: 15px 0; position: fixed; top: 0; left: 0; width: 100%; z-index: 1000; }
        .nav-container { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-weight: 900; font-size: 20px; color: #fff; text-decoration: none; }
        .logo span { color: #ff9800; }
        .nav-links { display: flex; gap: 20px; align-items: center; }
        .nav-links a { color: #aaa; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .nav-links a:hover, .nav-links a.active { color: #fff; }
        .nav-links a.active i { color: #ff9800; }
        .btn-write-nav { background: #ff9800; color: #000 !important; padding: 8px 16px; border-radius: 6px; }
        .btn-write-nav:hover { background: #ffb74d; }
        .nav-actions a { color: #666; font-size: 18px; margin-left: 15px; }
        .nav-actions a:hover { color: #ef5350; }

        /* Table & Export */
        .mil-table-responsive { overflow-x: auto; background: #111; border-radius: 12px; border: 1px solid #222; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 18px 25px; text-align: left; border-bottom: 1px solid #222; }
        th { background: #181818; color: #666; text-transform: uppercase; font-size: 11px; font-weight: 700; }
        tr:hover { background: #161616; }
        
        .btn-export { background: #222; color: #fff; border: 1px solid #333; padding: 8px 15px; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; transition: 0.2s; }
        .btn-export:hover { background: #fff; color: #000; }
        
        @media (max-width: 768px) { .nav-links span { display: none; } .btn-write-nav span { display: inline-block; } }
    </style>
</head>
<body>

    <nav class="admin-nav">
        <div class="container nav-container">
            <a href="dashboard.php" class="logo">Quinn.<span>Admin</span></a>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fa-solid fa-layer-group"></i> <span>Blog Posts</span></a>
                <a href="subscribers.php" class="active"><i class="fa-solid fa-users"></i> <span>Subscribers</span></a>
                <a href="write.php" class="btn-write-nav"><i class="fa-solid fa-pen-nib"></i> <span>Write</span></a>
            </div>
            <div class="nav-actions">
                <a href="../index.php" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i></a>
                <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i></a>
            </div>
        </div>
    </nav>

    <div class="container">
        
        <div class="row align-items-center mil-mb-30">
            <div class="col-6">
                <h2 style="color:#fff; margin-bottom: 5px;">Newsletter Subscribers</h2>
                <p style="color:#666; font-size: 14px;">List of users subscribed to updates.</p>
            </div>
            <div class="col-6" style="text-align:right;">
                <button onclick="window.print()" class="btn-export" style="display:inline-flex;">
                    <i class="fa-solid fa-print"></i> Print / Save PDF
                </button>
            </div>
        </div>

        <div class="mil-table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Email Address</th>
                        <th>Joined Date</th>
                        <th style="text-align:right;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM subscribers ORDER BY created_at DESC");

                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td style='color:#555;'>#" . $row['id'] . "</td>";
                            echo "<td style='color:#fff; font-weight:500;'>" . $row['email'] . "</td>";
                            echo "<td style='color:#888;'>" . date('M d, Y - h:i A', strtotime($row['created_at'])) . "</td>";
                            echo "<td style='text-align:right;'><span style='color:#66bb6a; background:rgba(27,94,32,0.2); padding:4px 8px; border-radius:4px; font-size:11px;'>Active</span></td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center; padding:40px; color:#555;'>No subscribers yet.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>