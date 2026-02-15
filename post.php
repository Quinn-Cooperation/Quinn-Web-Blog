<?php
include 'db.php';
session_start();

// Force UTF-8
$conn->set_charset("utf8mb4");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// --- VIEW COUNTER LOGIC ---
if (!isset($_SESSION['viewed_post_' . $id])) {
    $conn->query("UPDATE posts SET views = views + 1 WHERE id = $id");
    $_SESSION['viewed_post_' . $id] = true;
}

$sql = "SELECT * FROM posts WHERE id = $id";
$result = $conn->query($sql);
$post = $result->fetch_assoc();

if (!$post) {
    header("Location: index.php");
    exit();
}

// Calculate Reading Time
$wordCount = str_word_count(strip_tags($post['content']));
$readingTime = max(1, ceil($wordCount / 200));

// Get full URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$full_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

include 'header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Charter:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    :root {
        --accent: #ff9800;
        --dark: #111;
        --light-gray: #f4f4f4;
        --text-main: #292929;
        --heading-font: 'Plus Jakarta Sans', sans-serif;
        --body-font: 'Charter', serif;
    }

    body {
        background-color: #fff;
        color: var(--text-main);
    }

    /* Header Design */
    .post-header {
        max-width: 800px;
        margin: 80px auto 40px;
        padding: 0 20px;
        text-align: center;
    }

    .post-title {
        font-family: var(--heading-font);
        font-size: clamp(34px, 5vw, 56px);
        font-weight: 800;
        line-height: 1.1;
        margin-bottom: 25px;
        color: var(--dark);
        letter-spacing: -1.5px;
    }

    .post-info-bar {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
        font-family: var(--heading-font);
        color: #888;
        font-size: 14px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .post-info-bar span i {
        color: var(--accent);
        margin-right: 5px;
    }

    /* --- IMAGE FIX (1080x1080) --- */
    .featured-outer {
        max-width: 1080px;
        margin: 0 auto 60px;
        padding: 0 20px;
    }

    .featured-aspect-container {
        position: relative;
        width: 100%;
        max-width: 1080px;
        aspect-ratio: 1 / 1;
        background: #000;
        border-radius: 24px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 40px 80px rgba(0, 0, 0, 0.15);
    }

    .bg-blur {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-image: url('<?php echo htmlspecialchars($post['image_url']); ?>');
        background-size: cover;
        background-position: center;
        filter: blur(30px) brightness(0.6);
        transform: scale(1.1);
        z-index: 1;
    }

    .main-featured-img {
        position: relative;
        z-index: 2;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Layout */
    .post-layout {
        display: grid;
        grid-template-columns: 100px 1fr 100px;
        max-width: 1100px;
        margin: 0 auto;
        gap: 20px;
    }

    .share-sidebar {
        position: sticky;
        top: 100px;
        height: fit-content;
        display: flex;
        flex-direction: column;
        gap: 15px;
        align-items: center;
    }

    .share-btn {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: var(--light-gray);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #555;
        text-decoration: none;
        transition: 0.3s;
        font-size: 18px;
        border: none;
        cursor: pointer;
    }

    .share-btn:hover {
        background: var(--accent);
        color: #fff;
        transform: translateY(-3px);
    }

    .post-main-content {
        font-family: var(--body-font);
        font-size: 21px;
        line-height: 1.8;
        color: var(--text-main);
        padding-bottom: 100px;
    }

    .post-main-content a {
        color: var(--dark);
        box-shadow: inset 0 -2px 0 var(--accent);
        text-decoration: none;
    }

    /* Footer / Thanks Section */
    .post-footer-area {
        margin-top: 80px;
        padding-top: 40px;
        border-top: 1px solid #eee;
        text-align: center;
    }

    .thanks-box {
        background: var(--light-gray);
        padding: 40px;
        border-radius: 20px;
        border: 1px dashed #ddd;
        transition: border-color 0.3s;
    }

    .thanks-box:hover {
        border-color: var(--accent);
    }

    .back-to-blog {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: var(--dark);
        color: #fff !important;
        padding: 12px 30px;
        border-radius: 50px;
        text-decoration: none;
        font-family: var(--heading-font);
        font-weight: 600;
        font-size: 14px;
        transition: all 0.3s ease;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        margin-top: 10px;
    }

    .back-to-blog:hover {
        background: var(--accent) !important;
        transform: translateX(-5px);
        box-shadow: 0 15px 30px rgba(255, 152, 0, 0.3) !important;
    }

    /* --- PREMIUM TOAST STYLING --- */
    #toast-container {
        visibility: hidden;
        min-width: 300px;
        background-color: rgba(20, 20, 20, 0.95);
        backdrop-filter: blur(10px);
        color: #fff;
        text-align: left;
        border-radius: 12px;
        padding: 16px 20px;
        position: fixed;
        z-index: 9999;
        right: 30px;
        bottom: 30px;
        font-family: var(--heading-font);
        font-size: 14px;
        font-weight: 500;
        border-left: 5px solid var(--accent);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        transform: translateY(100px);
        opacity: 0;
        transition: all 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        display: flex;
        align-items: center;
        gap: 15px;
    }

    #toast-container.show {
        visibility: visible;
        transform: translateY(0);
        opacity: 1;
    }

    #toast-container i {
        font-size: 20px;
        color: #4caf50;
    }

    @media (max-width: 800px) {
        .post-layout {
            grid-template-columns: 1fr;
            padding: 0 20px;
        }

        .share-sidebar {
            flex-direction: row;
            position: relative;
            top: 0;
            justify-content: center;
            margin-bottom: 40px;
        }

        /* Mobile Toast Position */
        #toast-container {
            left: 50%;
            right: auto;
            transform: translate(-50%, 100px);
            bottom: 20px;
            width: 90%;
            justify-content: center;
        }

        #toast-container.show {
            transform: translate(-50%, 0);
        }
    }
</style>

<article>
    <header class="post-header">
        <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>
        <div class="post-info-bar">
            <span><i class="fa-regular fa-calendar"></i> <?php echo date("M d, Y", strtotime($post['created_at'] ?? 'now')); ?></span>
            <span><i class="fa-regular fa-clock"></i> <?php echo $readingTime; ?> MIN</span>
            <span><i class="fa-regular fa-eye"></i> <?php echo number_format($post['views']); ?> VIEWS</span>
        </div>
    </header>

    <div class="featured-outer">
        <div class="featured-aspect-container">
            <div class="bg-blur"></div>
            <img src="<?php echo htmlspecialchars($post['image_url']); ?>" class="main-featured-img" alt="Blog Image">
        </div>
    </div>

    <div class="post-layout">
        <aside class="share-sidebar">
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($full_url); ?>" 
               target="_blank" 
               class="share-btn" 
               title="Share on Facebook"
               onclick="triggerToast('Sharing to Facebook...')">
               <i class="fa-brands fa-facebook-f"></i>
            </a>

            <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($post['title'] . " - " . $full_url); ?>" 
               target="_blank" 
               class="share-btn" 
               title="Share on WhatsApp"
               onclick="triggerToast('Opening WhatsApp...')">
               <i class="fa-brands fa-whatsapp"></i>
            </a>

            <button onclick="copyPageLink()" class="share-btn" title="Copy Link"><i class="fa-solid fa-link"></i></button>
        </aside>

        <div class="post-main-content" id="blogContent">
            <?php echo html_entity_decode($post['content']); ?>

            <div class="post-footer-area">
                <div class="thanks-box">
                    <h4 style="font-family: var(--heading-font); margin-bottom: 10px; color: var(--dark); font-weight: 700;">Thanks For Reading!</h4>
                    <p style="font-size: 16px; color: #666; margin-bottom: 25px;">If you found this article helpful, feel free to share it with your friends or colleagues.</p>

                    <a href="./index.php" class="back-to-blog">
                        <i class="fa-solid fa-arrow-left"></i> Back To All Stories
                    </a>
                </div>
            </div>
        </div>
        <div></div>
    </div>
</article>

<div id="toast-container">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toast-text">Notification</span>
</div>

<script>
    // 1. GENERIC TOAST FUNCTION
    function triggerToast(message) {
        var x = document.getElementById("toast-container");
        var textSpan = document.getElementById("toast-text");
        
        textSpan.innerText = message;
        x.classList.add("show");

        // Hide after 3.5 seconds
        setTimeout(function() {
            x.classList.remove("show");
        }, 3500);
    }

    // 2. COPY LINK LOGIC
    function copyPageLink() {
        const el = document.createElement('textarea');
        el.value = window.location.href;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);

        triggerToast('Link Copied Successfully');
    }

    // 3. LINK FIXER
    document.addEventListener("DOMContentLoaded", function() {
        var links = document.getElementById('blogContent').getElementsByTagName('a');
        for (var i = 0; i < links.length; i++) {
            var href = links[i].getAttribute('href');
            if (href === '/' || href.includes('localhost/dashboard')) {
                links[i].setAttribute('href', 'javascript:void(0)');
            } else if (href && !href.startsWith('http') && href.includes('.')) {
                links[i].setAttribute('href', 'https://' + href);
            }
            if (href && href.startsWith('http')) links[i].setAttribute('target', '_blank');
        }
    });
</script>

<?php include 'footer.php'; ?>