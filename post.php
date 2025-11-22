<?php
include 'db.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$sql = "SELECT * FROM posts WHERE id = $id";
$result = $conn->query($sql);
$post = $result->fetch_assoc();

if(!$post) { header("Location: index.php"); exit(); }

include 'header.php';
?>

<div class="mil-inner-banner mil-p-0-120">
    <div class="mil-banner-content mil-up">
        <div class="container">
            <ul class="mil-breadcrumbs mil-mb-60">
                <li><a href="index.php">Blog</a></li>
                <li><a href="#"><?php echo $post['title']; ?></a></li>
            </ul>
            <h1 class="mil-mb-60"><?php echo $post['title']; ?></h1>
        </div>
    </div>
</div>

<section id="blog">
    <div class="container mil-p-120-120">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="mil-image-frame mil-horizontal mil-up mil-mb-60">
                    <img src="<?php echo $post['image_url']; ?>" alt="Cover Image">
                </div>
                <div class="mil-text-lg mil-up mil-mb-60">
                <?php echo $post['content']; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>