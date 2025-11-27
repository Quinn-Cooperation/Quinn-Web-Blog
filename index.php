<?php
// --- ADD THIS TO DEBUG ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';
include 'header.php';
?>

<section class="mil-banner mil-dark-bg">
    <div class="mi-invert-fix">
        <div class="container">
            <div class="mil-banner-content mil-up">
                <h1 class="mil-muted mil-mb-60">Our <span class="mil-thin">Latest</span><br> Insights</h1>
                <div class="mil-link mil-dark mil-arrow-place mil-down-arrow">
                    <span>Scroll Down</span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mil-soft-bg" id="blog">
    <div class="container mil-p-120-120">
        <div class="row">
            <?php
            $sql = "SELECT posts.*, categories.name as cat_name FROM posts 
                    LEFT JOIN categories ON posts.category_id = categories.id 
                    ORDER BY created_at DESC";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $date = date("M d Y", strtotime($row['created_at']));
            ?>
            <div class="col-lg-12">
                <a href="post.php?id=<?php echo $row['id']; ?>" class="mil-blog-card mil-blog-card-hori mil-more mil-mb-60">
                    <div class="mil-cover-frame mil-up">
                    <img src="<?php echo $row['image_url']; ?>" alt="cover" style="width: 1080px; height: 1080px; object-fit: cover;">
                    </div>
                    <div class="mil-post-descr">
                        <div class="mil-labels mil-up mil-mb-30">
                            <div class="mil-label mil-upper mil-accent"><?php echo $row['cat_name']; ?></div>
                            <div class="mil-label mil-upper"><?php echo $date; ?></div>
                        </div>
                        <h4 class="mil-up mil-mb-30"><?php echo $row['title']; ?></h4>
                        <p class="mil-post-text mil-up mil-mb-30">
                            <?php echo substr(strip_tags($row['content']), 0, 180) . '...'; ?>
                        </p>
                        <div class="mil-link mil-dark mil-arrow-place mil-up">
                            <span>Read more</span>
                        </div>
                    </div>
                </a>
            </div>
            <?php 
                }
            } else {
                echo "<h3 class='mil-up'>No posts found yet.</h3>";
            }
            ?>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>