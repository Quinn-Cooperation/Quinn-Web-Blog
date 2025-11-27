<?php
session_start();
if (!isset($_SESSION['admin'])) { header("Location: index.php"); exit(); }
include '../db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Newsletter Subscribers</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/style.css" />
    <link rel="stylesheet" href="../css/plugins/bootstrap-grid.css" />
    <style>
        body { background: #050505; color: #e0e0e0; font-family: 'Helvetica', sans-serif; }
        .container { padding-top: 50px; }
        table { width: 100%; background: #111; border-collapse: collapse; }
        th, td { padding: 15px; border-bottom: 1px solid #222; text-align: left; }
        th { color: #888; text-transform: uppercase; font-size: 12px; }
        .btn-back { color: #aaa; text-decoration: none; margin-right: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;">
            <h2>📬 Subscribers</h2>
            <div>
                <a href="dashboard.php" class="btn-back">&larr; Dashboard</a>
                <button onclick="window.print()" style="padding:10px; background:#ff9800; border:none; cursor:pointer;">Export / Print</button>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>Date Subscribed</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $result = $conn->query("SELECT * FROM subscribers ORDER BY created_at DESC");
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . $row['email'] . "</td>";
                        echo "<td>" . $row['created_at'] . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='3'>No subscribers yet.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>