<?php
// Prevent caching to ensure fresh data on every click
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include 'db.php';
include 'header.php';

$conn->set_charset("utf8mb4");

// Capture Search & Category Inputs
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$cat_filter = (isset($_GET['category']) && is_numeric($_GET['category'])) ? (int)$_GET['category'] : 0;

// Greeting Logic
$hour = date('H');
$greeting = ($hour < 12) ? "Good Morning" : (($hour < 18) ? "Good Afternoon" : "Good Evening");
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    :root {
        --accent: #ff9800;
        --dark-bg: #0b0b0b;
        --card-bg: #ffffff;
        --text-muted: #666;
        --heading-font: 'Plus Jakarta Sans', sans-serif;
    }

    body {
        background-color: #f8f9fa;
        font-family: var(--heading-font);
    }

    /* --- HERO SECTION --- */
    .hero-banner {
        background: var(--dark-bg);
        padding: 120px 0 80px;
        position: relative;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .hero-banner h1 {
        font-size: clamp(36px, 6vw, 64px);
        font-weight: 800;
        color: #fff;
        line-height: 1.1;
        margin-bottom: 15px;
    }

    /* --- SEARCH BAR --- */
    .search-container {
        max-width: 600px;
        margin: 0 auto;
        position: relative;
        z-index: 10;
    }

    .search-input {
        width: 100%;
        padding: 18px 30px;
        padding-left: 60px;
        padding-right: 100px;
        border-radius: 50px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(10px);
        color: #fff;
        font-size: 16px;
        transition: 0.3s;
    }

    .search-input:focus {
        outline: none;
        border-color: var(--accent);
        background: rgba(255, 255, 255, 0.1);
    }

    .search-icon {
        position: absolute;
        left: 25px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--accent);
    }

    .clear-search-btn {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255, 255, 255, 0.1);
        border: none;
        color: #fff;
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 11px;
        cursor: pointer;
    }

    /* --- CATEGORY NAV WRAPPER (NEW) --- */
    .cat-nav-wrapper {
        position: relative;
        max-width: 800px;
        margin: 40px auto 0;
        display: flex;
        align-items: center;
        padding: 0 40px;
        /* Space for arrows */
    }

    /* Scroll Buttons */
    .scroll-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 5;
        transition: 0.3s;
        font-size: 12px;
    }

    .scroll-btn:hover {
        background: var(--accent);
        color: #000;
        border-color: var(--accent);
    }

    .scroll-btn.left {
        left: 0;
    }

    .scroll-btn.right {
        right: 0;
    }

    /* Category Nav Scroll Area */
    .category-nav {
        display: flex;
        gap: 10px;
        overflow-x: auto;
        padding: 5px 0;
        scrollbar-width: none;
        -webkit-overflow-scrolling: touch;
        scroll-behavior: smooth;
        width: 100%;
    }

    .category-nav::-webkit-scrollbar {
        display: none;
    }

    .cat-item {
        padding: 10px 22px;
        border-radius: 50px;
        background: rgba(255, 255, 255, 0.08);
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        font-size: 13px;
        font-weight: 600;
        transition: 0.2s;
        border: 1px solid transparent;
        white-space: nowrap;
        flex-shrink: 0;
    }

    .cat-item:hover {
        background: var(--accent);
        color: #000;
    }

    .cat-item.active {
        background: var(--accent);
        color: #000;
        border-color: var(--accent);
    }

    /* --- POST GRID & CARDS --- */
    .blog-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 30px;
        padding: 60px 0 100px;
    }

    @keyframes fadeUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modern-card,
    .featured-card {
        background: var(--card-bg);
        border-radius: 24px;
        overflow: hidden;
        border: 1px solid #eee;
        display: flex;
        flex-direction: column;
        text-decoration: none;
        color: inherit;
        height: 100%;
        animation: fadeUp 0.6s ease-out forwards;
    }

    .modern-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.08);
        border-color: var(--accent);
        transition: 0.3s;
    }

    /* Featured Layout */
    .featured-card {
        grid-column: 1 / -1;
        display: grid;
        grid-template-columns: 1.2fr 1fr;
    }

    .card-image {
        width: 100%;
        height: 240px;
        overflow: hidden;
    }

    .featured-card .card-image {
        height: 450px;
    }

    .card-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .card-content {
        padding: 25px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .featured-card .card-content {
        padding: 50px;
        justify-content: center;
    }

    .category-pill {
        display: inline-block;
        padding: 4px 12px;
        background: rgba(255, 152, 0, 0.1);
        color: var(--accent);
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        border-radius: 50px;
        margin-bottom: 15px;
        width: fit-content;
    }

    .card-title {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 12px;
        color: var(--dark-bg);
    }

    .featured-card .card-title {
        font-size: 36px;
    }

    .card-footer {
        margin-top: auto;
        padding-top: 20px;
        border-top: 1px solid #f8f8f8;
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        color: #aaa;
    }

    @media (max-width: 991px) {
        .featured-card {
            grid-template-columns: 1fr;
            grid-column: auto;
        }

        .featured-card .card-image {
            height: 240px;
        }

        /* On Mobile, move arrows closer to edge */
        .cat-nav-wrapper {
            padding: 0 35px;
        }

        .scroll-btn {
            width: 28px;
            height: 28px;
        }
    }
</style>

<section class="hero-banner">
    <div class="container">
        <div style="color: var(--accent); font-weight: 700; font-size: 14px; text-transform: uppercase; margin-bottom: 10px; letter-spacing: 2px;">
            <?php echo $greeting; ?>
        </div>
        <h1>Our Latest Insights</h1>

        <div class="search-container">
            <form action="index.php" method="GET">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" name="search" class="search-input" placeholder="Search articles..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                <?php if (!empty($search) || $cat_filter > 0): ?>
                    <button type="button" class="clear-search-btn" onclick="window.location.href='index.php'">Clear</button>
                <?php endif; ?>
                <input type="hidden" name="category" value="<?php echo $cat_filter; ?>">
            </form>
        </div>

        <div class="cat-nav-wrapper">
            <button class="scroll-btn left" onclick="scrollCategories(-1)"><i class="fa-solid fa-chevron-left"></i></button>

            <nav class="category-nav" id="categoryScroll">
                <a href="index.php" class="cat-item <?php echo ($cat_filter === 0) ? 'active' : ''; ?>">All Stories</a>
                <?php
                $cat_res = $conn->query("SELECT * FROM categories ORDER BY name ASC");
                if ($cat_res) {
                    while ($c = $cat_res->fetch_assoc()) {
                        $isActive = ($cat_filter === (int)$c['id']) ? 'active' : '';
                        echo '<a href="index.php?category=' . $c['id'] . '" class="cat-item ' . $isActive . '">' . htmlspecialchars($c['name']) . '</a>';
                    }
                }
                ?>
            </nav>

            <button class="scroll-btn right" onclick="scrollCategories(1)"><i class="fa-solid fa-chevron-right"></i></button>
        </div>

    </div>
</section>

<div class="container">

    <?php if ($cat_filter > 0): ?>
        <div style="margin-top: 30px; color: #888; font-size: 14px;">
            Filtering by Category ID: <strong><?php echo $cat_filter; ?></strong>
        </div>
    <?php endif; ?>

    <div class="blog-grid">
        <?php
        $sql = "SELECT posts.*, categories.name as cat_name FROM posts 
                LEFT JOIN categories ON posts.category_id = categories.id";

        $where_clauses = [];

        if (!empty($search)) {
            $s = $conn->real_escape_string($search);
            $where_clauses[] = "(posts.title LIKE '%$s%' OR posts.content LIKE '%$s%')";
        }

        if ($cat_filter > 0) {
            $where_clauses[] = "posts.category_id = $cat_filter";
        }

        if (count($where_clauses) > 0) {
            $sql .= " WHERE " . implode(" AND ", $where_clauses);
        }

        $sql .= " ORDER BY created_at DESC";

        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $count = 0;
            while ($row = $result->fetch_assoc()) {
                $date = date("M d, Y", strtotime($row['created_at']));
                $wordCount = str_word_count(strip_tags($row['content']));
                $readTime = max(1, ceil($wordCount / 200));

                $is_featured = ($count == 0 && empty($search) && $cat_filter == 0);
                $cardClass = $is_featured ? 'featured-card' : 'modern-card';
        ?>
                <a href="post.php?id=<?php echo $row['id']; ?>" class="<?php echo $cardClass; ?>">
                    <div class="card-image">
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="cover">
                    </div>
                    <div class="card-content">
                        <span class="category-pill"><?php echo htmlspecialchars($row['cat_name'] ?? 'General'); ?></span>
                        <h3 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                        <p style="color: var(--text-muted); font-size: 15px; margin-bottom: 20px; line-height: 1.6;">
                            <?php echo substr(strip_tags($row['content']), 0, ($is_featured ? 220 : 100)) . '...'; ?>
                        </p>
                        <div class="card-footer">
                            <span><i class="fa-regular fa-clock"></i> <?php echo $readTime; ?> min read</span>
                            <span><i class="fa-regular fa-eye"></i> <?php echo number_format($row['views']); ?></span>
                            <span><?php echo $date; ?></span>
                        </div>
                    </div>
                </a>
        <?php
                $count++;
            }
        } else {
            echo "<div style='grid-column: 1/-1; text-align:center; padding: 100px 0;'>
                    <div style='font-size: 40px; color: #ddd; margin-bottom: 20px;'><i class='fa-regular fa-folder-open'></i></div>
                    <h2 style='color:#ccc; font-weight: 400; margin-bottom: 15px;'>No stories found here.</h2>
                    <a href='index.php' style='color:var(--accent); text-decoration:underline; font-weight:700;'>Clear filters</a>
                  </div>";
        }
        ?>
    </div>
</div>

<script>
    // SCROLL LOGIC FOR ARROWS
    function scrollCategories(direction) {
        const container = document.getElementById('categoryScroll');
        const scrollAmount = 150; // Distance to scroll
        container.scrollBy({
            left: direction * scrollAmount,
            behavior: 'smooth'
        });
    }
</script>

<?php include 'footer.php'; ?>