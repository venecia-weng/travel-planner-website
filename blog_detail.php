<?php
session_start();
require_once 'includes/db_connect.php';

// Check if blog ID is provided
if (!isset($_GET['id'])) {
    header("Location: blogs.php");
    exit();
}

$blog_id = intval($_GET['id']);

// Get blog details with author info
$sql = "SELECT b.*, u.username, u.profile_image, u.bio
        FROM Blogs b 
        LEFT JOIN Users u ON b.user_id = u.user_id 
        WHERE b.blog_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $blog_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: blogs.php");
    exit();
}

$blog = $result->fetch_assoc();

// Get additional blog images
$image_sql = "SELECT image_path FROM blog_images WHERE blog_id = ? ORDER BY image_order ASC";
$image_stmt = $conn->prepare($image_sql);
$image_stmt->bind_param("i", $blog_id);
$image_stmt->execute();
$image_result = $image_stmt->get_result();
$additional_images = $image_result->fetch_all(MYSQLI_ASSOC);

include 'header.php'; 

// Update view count
$update_sql = "UPDATE Blogs SET views = views + 1 WHERE blog_id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("i", $blog_id);
$update_stmt->execute();

// Get SQL-based comments
$comment_sql = "SELECT c.*, u.username, u.profile_image 
                FROM Comments c 
                JOIN Users u ON c.user_id = u.user_id 
                WHERE c.blog_id = ? 
                ORDER BY c.created_at DESC";
$comment_stmt = $conn->prepare($comment_sql);
$comment_stmt->bind_param("i", $blog_id);
$comment_stmt->execute();
$comment_result = $comment_stmt->get_result();

$comments = [];
while ($row = $comment_result->fetch_assoc()) {
    
    // Get replies for each comment
    $reply_sql = "SELECT r.*, u.username, u.profile_image 
                  FROM Replies r 
                  JOIN Users u ON r.user_id = u.user_id 
                  WHERE r.comment_id = ? 
                  ORDER BY r.created_at ASC";
    $reply_stmt = $conn->prepare($reply_sql);
    $reply_stmt->bind_param("i", $row['comment_id']);
    $reply_stmt->execute();
    $reply_result = $reply_stmt->get_result();
    $replies = $reply_result->fetch_all(MYSQLI_ASSOC);

    $row['replies'] = $replies;

    // Get like count
    $like_sql = "SELECT COUNT(*) AS like_count FROM Likes WHERE comment_id = ?";
    $like_stmt = $conn->prepare($like_sql);
    $like_stmt->bind_param("i", $row['comment_id']);
    $like_stmt->execute();
    $like_result = $like_stmt->get_result();
    $like_data = $like_result->fetch_assoc();

    $row['like_count'] = $like_data['like_count'];

    $comments[] = $row;
}
$comment_count = count($comments);

// Generate table of contents from markdown headers
$toc_items = [];
if (!empty($blog['content'])) {
    preg_match_all('/^## (.+)$/m', $blog['content'], $matches);
    if (!empty($matches[1])) {
        foreach ($matches[1] as $index => $title) {
            $anchor = 'section-' . ($index + 1);
            $toc_items[] = [
                'title' => $title,
                'anchor' => $anchor
            ];
        }
    }
}

// Process content with markdown-style headings
function processContent($content) {
    // Add anchors to headings for table of contents
    $processed = preg_replace_callback('/^## (.+)$/m', function($matches) {
        static $counter = 0;
        $counter++;
        $anchor = 'section-' . $counter;
        return '<h2 id="' . $anchor . '" class="mt-4 mb-3">' . $matches[1] . '</h2>';
    }, $content);
    
    // Process other markdown elements
    $processed = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $processed);
    $processed = preg_replace('/^- (.+)$/m', '<li>$1</li>', $processed);
    $processed = preg_replace('/<li>(.+)<\/li>\s+<li>/s', '<li>$1</li><li>', $processed);
    $processed = preg_replace('/(<li>.+<\/li>)+/s', '<ul>$0</ul>', $processed);
    
    // Convert paragraphs (lines with content that aren't headings or list items)
    $processed = preg_replace('/^(?!<h|<ul|<li)(.+)$/m', '<p>$1</p>', $processed);
    
    // Remove empty paragraphs
    $processed = str_replace('<p></p>', '', $processed);
    
    return $processed;
}

// Get related blogs (by category, destination, or tags)
$related_where_clauses = [];
$related_params = [];
$related_types = "";

if (!empty($blog['category'])) {
    $related_where_clauses[] = "b.category = ?";
    $related_params[] = $blog['category'];
    $related_types .= "s";
}

if (!empty($blog['destination'])) {
    $related_where_clauses[] = "b.destination = ?";
    $related_params[] = $blog['destination'];
    $related_types .= "s";
}

// Extract first two tags for related content matching
if (!empty($blog['tags'])) {
    $tags = explode(',', $blog['tags']);
    if (count($tags) > 0) {
        $tag_conditions = [];
        foreach (array_slice($tags, 0, 2) as $tag) {
            $tag = trim($tag);
            if (!empty($tag)) {
                $tag_conditions[] = "b.tags LIKE ?";
                $related_params[] = '%' . $tag . '%';
                $related_types .= "s";
            }
        }
        if (!empty($tag_conditions)) {
            $related_where_clauses[] = "(" . implode(" OR ", $tag_conditions) . ")";
        }
    }
}

// Add blog_id to prevent current blog from appearing in related
$related_where_clauses[] = "b.blog_id != ?";
$related_params[] = $blog_id;
$related_types .= "i";

$related_sql = "SELECT b.blog_id, b.title, b.subtitle, b.image_path, b.created_at, b.created_date, 
                b.destination, b.category, b.reading_time, b.views
                FROM Blogs b WHERE " . implode(" OR ", $related_where_clauses) . 
                " ORDER BY b.created_at DESC, b.views DESC LIMIT 3";

$related_stmt = $conn->prepare($related_sql);
$related_stmt->bind_param($related_types, ...$related_params);
$related_stmt->execute();
$related_result = $related_stmt->get_result();

$related_blogs = [];
if ($related_result->num_rows > 0) {
    while ($row = $related_result->fetch_assoc()) {
        $related_blogs[] = $row;
    }
}

// Get posts by same author
$author_sql = "SELECT b.blog_id, b.title, b.image_path, b.created_at, b.created_date
              FROM Blogs b 
              WHERE b.user_id = ? AND b.blog_id != ?
              ORDER BY b.created_at DESC, b.created_date DESC 
              LIMIT 3";
$author_stmt = $conn->prepare($author_sql);
$author_stmt->bind_param("ii", $blog['user_id'], $blog_id);
$author_stmt->execute();
$author_result = $author_stmt->get_result();

$author_blogs = [];
if ($author_result->num_rows > 0) {
    while ($row = $author_result->fetch_assoc()) {
        $author_blogs[] = $row;
    }
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Get messages from session if available
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['success']);
unset($_SESSION['error']);

// Get estimated reading time
$reading_time = $blog['reading_time'] ?? 5;

// Prepare meta tags for social sharing
$meta_description = !empty($blog['meta_description']) ? 
                   htmlspecialchars($blog['meta_description']) : 
                   htmlspecialchars(substr(strip_tags($blog['content']), 0, 160));
                   
$share_image = !empty($blog['image_path']) ? 
              "https://" . $_SERVER['HTTP_HOST'] . "/" . $blog['image_path'] : 
              "https://" . $_SERVER['HTTP_HOST'] . "/assets/images/default-share.jpg";
?>

<!-- Add Open Graph and Twitter meta tags for better social sharing -->
<meta property="og:title" content="<?= htmlspecialchars($blog['title']) ?>">
<meta property="og:description" content="<?= $meta_description ?>">
<meta property="og:image" content="<?= $share_image ?>">
<meta property="og:url" content="<?= "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
<meta property="og:type" content="article">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars($blog['title']) ?>">
<meta name="twitter:description" content="<?= $meta_description ?>">
<meta name="twitter:image" content="<?= $share_image ?>">

<div class="container mt-5 mb-5">
    <!-- Success/Error Messages -->
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Blog Content -->
        <div class="col-lg-8">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item"><a href="blogs.php">Blogs</a></li>
                    <?php if (!empty($blog['category'])): ?>
                        <li class="breadcrumb-item"><a href="blogs.php?category=<?= urlencode($blog['category']) ?>"><?= htmlspecialchars($blog['category']) ?></a></li>
                    <?php endif; ?>
                    <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($blog['title']) ?></li>
                </ol>
            </nav>
            
            <!-- Blog Header -->
            <div class="blog-header mb-4">
                <!-- Featured Badge if applicable -->
                <?php if ($blog['featured'] == 1): ?>
                    <div class="position-relative mb-3">
                    <span class="badge bg-danger px-3 py-2 rounded-pill">
                        <i class="fas fa-star me-1"></i> Featured
                        </span>
                    </div>
                <?php endif; ?>
                
                <h1 class="display-4 mb-2"><?= htmlspecialchars($blog['title']) ?></h1>
                
                <?php if (!empty($blog['subtitle'])): ?>
                    <h2 class="h4 text-muted mb-3"><?= htmlspecialchars($blog['subtitle']) ?></h2>
                <?php endif; ?>
                
                <div class="d-flex flex-wrap align-items-center mb-4">
                    <!-- Author Info -->
                    <div class="d-flex align-items-center me-4 mb-2">
                        <?php if (!empty($blog['profile_image'])): ?>
                            <img src="<?= htmlspecialchars($blog['profile_image']) ?>" 
                                 class="rounded-circle me-2" width="50" height="50" alt="Author">
                        <?php else: ?>
                            <div class="bg-secondary rounded-circle text-white d-flex align-items-center justify-content-center me-2" 
                                 style="width: 50px; height: 50px;">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <p class="fw-bold mb-0"><?= htmlspecialchars($blog['username'] ?? $blog['author']) ?></p>
                            <p class="text-muted small mb-0">
                                <i class="fas fa-calendar-alt me-1"></i> 
                                <?= date('F d, Y', strtotime($blog['created_at'] ?? $blog['created_date'])) ?>
                                
                                <?php if (!empty($blog['last_updated']) && 
                                          strtotime($blog['last_updated']) > strtotime($blog['created_at'] ?? $blog['created_date'])): ?>
                                    <span class="ms-2">
                                        <i class="fas fa-edit me-1"></i> Updated
                                    </span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Meta Info -->
                    <div class="d-flex flex-wrap mb-2">
                        <div class="me-3">
                            <span class="badge bg-secondary">
                                <i class="fas fa-clock me-1"></i> <?= $reading_time ?> min read
                            </span>
                        </div>
                        
                        <?php if (!empty($blog['category'])): ?>
                            <div class="me-3">
                                <a href="blogs.php?category=<?= urlencode($blog['category']) ?>" 
                                   class="badge bg-primary text-decoration-none">
                                    <i class="fas fa-tag me-1"></i> <?= htmlspecialchars($blog['category']) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($blog['destination'])): ?>
                            <div class="me-3">
                                <a href="blogs.php?destination=<?= urlencode($blog['destination']) ?>" 
                                   class="badge bg-dark text-decoration-none text-white">
                                    <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($blog['destination']) ?>
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($blog['trip_date'])): ?>
                            <div class="me-3">
                                <span class="badge bg-success">
                                    <i class="fas fa-plane-departure me-1"></i> <?= date('M Y', strtotime($blog['trip_date'])) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Views and Comments Counter -->
                    <div class="ms-auto mb-2">
                        <span class="text-muted me-3">
                            <i class="fas fa-eye me-1"></i> <?= $blog['views'] + 1 ?> views
                        </span>
                        <a href="#comments" class="text-decoration-none text-muted">
                            <i class="fas fa-comment me-1"></i> <?= $comment_count ?> comments
                        </a>
                    </div>
                </div>
                
                <!-- Tags Display -->
                <?php if (!empty($blog['tags'])): ?>
                    <div class="mb-4">
                        <div class="d-flex flex-wrap gap-2">
                            <?php 
                            $tags = explode(',', $blog['tags']);
                            foreach ($tags as $tag): 
                                $tag = trim($tag);
                                if (!empty($tag)):
                            ?>
                                <a href="blogs.php?tag=<?= urlencode($tag) ?>" class="text-decoration-none">
                                    <span class="badge bg-light text-dark p-2">#<?= htmlspecialchars($tag) ?></span>
                                </a>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Featured Image -->
                <?php if (!empty($blog['image_path'])): ?>
                    <div class="featured-image mb-4">
                        <img src="<?= htmlspecialchars($blog['image_path']) ?>" 
                             class="img-fluid rounded shadow" 
                             alt="<?= htmlspecialchars($blog['title']) ?>">
                        <?php if (!empty($blog['image_credit'])): ?>
                            <div class="small text-muted mt-1">
                                Photo: <?= htmlspecialchars($blog['image_credit']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Table of Contents -->
            <?php if (count($toc_items) > 2): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-list me-2"></i> Table of Contents
                        </h5>
                        <div class="row">
                            <div class="col-md-6">
                                <ol class="mb-0">
                                    <?php foreach (array_slice($toc_items, 0, ceil(count($toc_items) / 2)) as $item): ?>
                                        <li class="mb-2">
                                            <a href="#<?= $item['anchor'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($item['title']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                            <div class="col-md-6">
                                <ol start="<?= ceil(count($toc_items) / 2) + 1 ?>" class="mb-0">
                                    <?php foreach (array_slice($toc_items, ceil(count($toc_items) / 2)) as $item): ?>
                                        <li class="mb-2">
                                            <a href="#<?= $item['anchor'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($item['title']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Blog Content -->
            <div class="blog-content mb-5">
                <div class="bg-white p-4 rounded shadow-sm">
                    <article class="fs-5 lh-lg">
                        <?= processContent(htmlspecialchars($blog['content'])) ?>
                    </article>
                </div>
            </div>
            
            <!-- Aditional Images-->
            <?php if (!empty($additional_images)): ?>
            <div class="mt-5">
                <h4 class="mb-3"><i class="fas fa-images me-2"></i>Gallery</h4>
                <div class="row g-3">
                    <?php foreach ($additional_images as $index => $img): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <img src="<?= htmlspecialchars($img['image_path']) ?>"
                                class="img-fluid rounded shadow-sm gallery-thumb"
                                style="object-fit: cover; width: 100%; height: 180px; cursor: pointer;"
                                data-bs-toggle="modal"
                                data-bs-target="#galleryModal"
                                data-index="<?= $index ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Gallery Modal with Carousel -->
            <div class="modal fade" id="galleryModal" tabindex="-1" aria-labelledby="galleryModalLabel" aria-hidden="true" data-bs-backdrop="false">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content bg-white border-0">
                        <div class="modal-body p-0 position-relative">

                            <!-- Carousel -->
                            <div id="galleryCarousel" class="carousel slide" data-bs-ride="false">
                                <div class="carousel-inner">
                                    <?php foreach ($additional_images as $index => $img): ?>
                                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                        <img src="<?= htmlspecialchars($img['image_path']) ?>" 
                                        class="d-block w-100"
                                        style="max-height: 90vh; object-fit: contain; cursor: pointer;" 
                                        alt="Gallery Image"
                                        data-bs-dismiss="modal">
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Prev/Next Buttons -->
                                <button class="carousel-control-prev" type="button" data-bs-target="#galleryCarousel" data-bs-slide="prev">
                                    <span class="carousel-control-prev-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                                    <span class="visually-hidden">Previous</span>
                                </button>
                                <button class="carousel-control-next" type="button" data-bs-target="#galleryCarousel" data-bs-slide="next">
                                    <span class="carousel-control-next-icon bg-dark rounded-circle p-3" aria-hidden="true"></span>
                                    <span class="visually-hidden">Next</span>
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Carousel JS -->
            <script>
            document.addEventListener('DOMContentLoaded', function () {
                const thumbnails = document.querySelectorAll('.gallery-thumb');
                const carousel = document.querySelector('#galleryCarousel');
                const closeBtn = document.getElementById('modalCloseBtn');
                const images = document.querySelectorAll('#galleryCarousel img');

                // Initialize carousel
                const carouselInstance = new bootstrap.Carousel(carousel, {
                    interval: false,
                    touch: true,
                    wrap: false
                });

                // Go to selected image
                thumbnails.forEach((thumb, index) => {
                    thumb.addEventListener('click', function () {
                        carouselInstance.to(index);
                    });
                });

                // Prevent close button click from triggering carousel behavior
                closeBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                });

                // Prevent image clicks from advancing carousel
                images.forEach(img => {
                    img.addEventListener('click', function (e) {
                        e.stopPropagation();
                    });
                });
            });
            </script>

            <!-- Trip Details Card (if applicable) -->
            <?php if (!empty($blog['trip_date']) || !empty($blog['destination'])): ?>
                <div class="card border-0 shadow-sm mb-5">
                    <div class="card-header bg-light border-0">
                        <h5 class="mb-0">
                            <i class="fas fa-suitcase me-2"></i> Trip Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if (!empty($blog['destination'])): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="small text-muted">Destination</div>
                                    <div class="fw-bold">
                                        <i class="fas fa-map-marker-alt me-1 text-danger"></i>
                                        <?= htmlspecialchars($blog['destination']) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($blog['trip_date'])): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="small text-muted">When I Traveled</div>
                                    <div class="fw-bold">
                                        <i class="fas fa-calendar-alt me-1 text-primary"></i>
                                        <?= date('F Y', strtotime($blog['trip_date'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($blog['category'])): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="small text-muted">Type of Trip</div>
                                    <div class="fw-bold">
                                        <i class="fas fa-tag me-1 text-success"></i>
                                        <?= htmlspecialchars($blog['category']) ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Author Bio Card -->
            <div class="card border-0 shadow-sm mb-5">
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-md-3 text-center mb-3 mb-md-0">
                            <?php if (!empty($blog['profile_image'])): ?>
                                <img src="<?= htmlspecialchars($blog['profile_image']) ?>" 
                                     class="rounded-circle img-fluid mx-auto d-block" style="max-width: 120px;" alt="Author">
                            <?php else: ?>
                                <div class="bg-secondary rounded-circle text-white d-flex align-items-center justify-content-center mx-auto" 
                                     style="width: 120px; height: 120px;">
                                    <i class="fas fa-user fa-4x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-9">
                            <h4 class="mb-2">About the Author</h4>
                            <h5 class="h5 mb-3"><?= htmlspecialchars($blog['username'] ?? $blog['author']) ?></h5>
                            
                            <p class="mb-3"><?= !empty($blog['bio']) ? htmlspecialchars($blog['bio']) : 'Travel enthusiast and content creator. Sharing experiences and tips from around the world.' ?></p>
                            
                            <div class="d-flex gap-2">
                                <a href="blogs.php?author=<?= urlencode($blog['username'] ?? $blog['author']) ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-book me-1"></i> More from this Author
                                </a>
                                
                                <a href="#" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-envelope me-1"></i> Contact
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Social Sharing (Bottom) -->
            <div class="sharing mb-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="mb-3"><i class="fas fa-share-alt me-2"></i> Share this article</h5>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $encodedUrl ?>" 
                            target="_blank" rel="noopener noreferrer" class="btn btn-primary">
                                <i class="fab fa-facebook-f me-2"></i> Facebook
                            </a>
                            
                            <a href="https://twitter.com/intent/tweet?url=<?= $encodedUrl ?>&text=<?= $encodedTitle ?>" 
                            target="_blank" rel="noopener noreferrer" class="btn btn-dark text-white">
                                <i class="fab fa-twitter me-2"></i> Twitter
                            </a>
                            
                            <a href="https://api.whatsapp.com/send?text=<?= $encodedTitle ?>%20-%20<?= $encodedUrl ?>" 
                            target="_blank" rel="noopener noreferrer" class="btn btn-success">
                                <i class="fab fa-whatsapp me-2"></i> WhatsApp
                            </a>
                            
                            <a href="https://www.pinterest.com/pin/create/button/?url=<?= $encodedUrl ?>&media=<?= urlencode($share_image) ?>&description=<?= $encodedTitle ?>" 
                            target="_blank" rel="noopener noreferrer" class="btn btn-danger">
                                <i class="fab fa-pinterest me-2"></i> Pinterest
                            </a>
                            
                            <a href="mailto:?subject=<?= $encodedTitle ?>&body=I thought you might enjoy this article: <?= $encodedUrl ?>" 
                            class="btn btn-secondary">
                                <i class="fas fa-envelope me-2"></i> Email
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Comments Section -->
            <div id="comments" class="comments mb-5">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light py-3 border-0">
                        <h3 class="h5 mb-0">
                            <i class="fas fa-comments me-2"></i> Comments (<?= $comment_count ?>)
                        </h3>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($is_logged_in): ?>
                            <div class="comment-form mb-4">
                                <form action="post_blog_comment.php" method="POST">
                                    <input type="hidden" name="blog_id" value="<?= $blog_id ?>">
                                    <div class="mb-3">
                                        <textarea class="form-control" name="comment" rows="3" placeholder="Share your thoughts or questions about this article..." required></textarea>
                                    </div>
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="submit" class="btn btn-primary px-4">
                                            <i class="fas fa-paper-plane me-2"></i> Post Comment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-4" role="alert">
                                <i class="fas fa-info-circle me-2"></i> Please <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI'] . '#comments') ?>" class="alert-link">log in</a> to leave a comment.
                            </div>
                        <?php endif; ?>
                        
                        <!-- Display Comments -->
                        <div class="comments-list">
                            <?php if (count($comments) > 0): ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment mb-4 pb-4 border-bottom">
                                        <div class="d-flex">
                                            <?php if (!empty($comment['profile_image'])): ?>
                                                <img src="<?= htmlspecialchars($comment['profile_image']) ?>" 
                                                     class="rounded-circle me-3" width="50" height="50" alt="User">
                                            <?php else: ?>
                                                <div class="bg-secondary rounded-circle text-white d-flex align-items-center justify-content-center me-3" 
                                                     style="width: 50px; height: 50px;">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div class="flex-grow-1">
                                                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                                                    <h6 class="mb-0 me-2">
                                                        <?php if (!empty($comment['user_id'])): ?>
                                                            <a href="profile.php?id=<?= $comment['user_id'] ?>" style="text-decoration-none; color: darkblue">
                                                                <?= htmlspecialchars($comment['username']) ?>
                                                            </a>
                                                        <?php else: ?>
                                                            <?= htmlspecialchars($comment['username']) ?>
                                                        <?php endif; ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <?= date('M d, Y, g:i a', strtotime($comment['created_at'])) ?>
                                                    </small>
                                                </div>
                                                <div class="comment-content">
                                                    <?= nl2br(htmlspecialchars($comment['content'])) ?>
                                                </div>
                                                <!-- Show replies -->
                                                <?php if (!empty($comment['replies'])): ?>
                                                    <div class="mt-3 ps-4 border-start border-2">
                                                        <?php foreach ($comment['replies'] as $reply): ?>
                                                            <div class="mb-3">
                                                                <div class="d-flex align-items-start">
                                                                    <?php if (!empty($reply['profile_image'])): ?>
                                                                        <img src="<?= htmlspecialchars($reply['profile_image']) ?>" 
                                                                            class="rounded-circle me-2" width="40" height="40" alt="User">
                                                                    <?php else: ?>
                                                                        <div class="bg-secondary rounded-circle text-white d-flex align-items-center justify-content-center me-2" 
                                                                            style="width: 40px; height: 40px;">
                                                                            <i class="fas fa-user"></i>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div>
                                                                        <strong><?= htmlspecialchars($reply['username']) ?></strong>
                                                                        <small class="text-muted ms-2"><?= date('M d, Y, g:i a', strtotime($reply['created_at'])) ?></small>
                                                                        <p class="mb-0"><?= nl2br(htmlspecialchars($reply['content'])) ?></p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="mt-2">
                                                <?php if ($is_logged_in): ?>
                                                    <form action="post_blog_likes.php" method="POST" style="display:inline;">
                                                        <input type="hidden" name="comment_id" value="<?= $comment['comment_id'] ?>">
                                                        <input type="hidden" name="blog_id" value="<?= $blog_id ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                            <i class="far fa-thumbs-up me-1"></i> Like (<?= $comment['like_count'] ?>)
                                                        </button>
                                                    </form>
                                                    <form action="post_blog_replies.php" method="POST" class="d-inline-block">
                                                        <input type="hidden" name="comment_id" value="<?= $comment['comment_id'] ?>">
                                                        <input type="hidden" name="blog_id" value="<?= $blog_id ?>">
                                                        <input type="text" name="reply_content" class="form-control d-inline-block w-auto" placeholder="Reply..." required>
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary ms-2">
                                                            <i class="far fa-comment me-1"></i> Reply
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI'] . '#comments') ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="far fa-thumbs-up me-1"></i> Like
                                                    </a>
                                                    <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI'] . '#comments') ?>" class="btn btn-sm btn-outline-secondary ms-2">
                                                        <i class="far fa-comment me-1"></i> Reply
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="comment-placeholder text-center py-4">
                                    <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Be the first to share your thoughts on this article!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Related Articles -->
            <?php if (count($related_blogs) > 0): ?>
                <div class="related-articles mb-5">
                    <h3 class="h4 mb-4"><i class="fas fa-bookmark me-2"></i>You might also like</h3>
                    <div class="row row-cols-1 row-cols-md-3 g-4">
                        <?php foreach ($related_blogs as $related): ?>
                            <div class="col">
                                <div class="card h-100 border-0 shadow-hover position-relative">
                                    <a href="blog_detail.php?id=<?= $related['blog_id'] ?>" class="text-decoration-none">
                                        <?php if (!empty($related['image_path'])): ?>
                                            <img src="<?= htmlspecialchars($related['image_path']) ?>" 
                                                class="card-img-top" style="height: 160px; object-fit: cover;" alt="">
                                        <?php else: ?>
                                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 160px;">
                                                <i class="fas fa-image fa-2x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="card-body">
                                            <div class="small mb-2">
                                                <?php if (!empty($related['category'])): ?>
                                                    <span class="badge bg-primary me-2"><?= htmlspecialchars($related['category']) ?></span>
                                                <?php endif; ?>
                                                
                                                <?php if (!empty($related['destination'])): ?>
                                                    <span class="badge bg-dark text-white"><?= htmlspecialchars($related['destination']) ?></span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <h5 class="card-title" style="line-height: 1.4; color: darkblue"><?= htmlspecialchars($related['title']) ?></h5>
                                            
                                            <?php if (!empty($related['subtitle'])): ?>
                                                <p class="card-text text-muted small">
                                                    <?= htmlspecialchars(substr($related['subtitle'], 0, 70)) . (strlen($related['subtitle']) > 70 ? '...' : '') ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                <div>
                                                    <small class="text-muted">
                                                        <?= date('M d, Y', strtotime($related['created_at'] ?? $related['created_date'])) ?>
                                                    </small>
                                                </div>
                                                <div>
                                                    <?php if (!empty($related['reading_time'])): ?>
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock me-1"></i> <?= $related['reading_time'] ?> min
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Author's Other Posts (Mobile Only) -->
            <?php if (count($author_blogs) > 0): ?>
                <div class="d-block d-lg-none mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light py-3 border-0">
                            <h3 class="h5 mb-0">
                                <i class="fas fa-user me-2 text-primary"></i>More from <?= htmlspecialchars($blog['username'] ?? $blog['author']) ?>
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($author_blogs as $post): ?>
                                    <a href="blog_detail.php?id=<?= $post['blog_id'] ?>" class="list-group-item list-group-item-action border-0 d-flex align-items-center p-3">
                                        <?php if (!empty($post['image_path'])): ?>
                                            <img src="<?= htmlspecialchars($post['image_path']) ?>" 
                                                 class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;" alt="">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 60px; height: 60px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="mb-0 text-truncate"><?= htmlspecialchars($post['title']) ?></h6>
                                            <small class="text-muted">
                                                <?= date('M d, Y', strtotime($post['created_at'] ?? $post['created_date'])) ?>
                                            </small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Table of Contents (Sticky on Desktop) -->
            <?php if (count($toc_items) > 2): ?>
                <div class="d-none d-lg-block mb-4 sticky-top" style="top: 100px; z-index: 99;">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light py-3 border-0">
                            <h3 class="h5 mb-0">
                                <i class="fas fa-list me-2 text-primary"></i>In This Article
                            </h3>
                        </div>
                        <div class="card-body">
                            <ol class="mb-0 ps-3 small">
                                <?php foreach ($toc_items as $item): ?>
                                    <li class="mb-2">
                                        <a href="#<?= $item['anchor'] ?>" class="text-decoration-none text-secondary">
                                            <?= htmlspecialchars($item['title']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Author's Other Posts (Desktop) -->
            <?php if (count($author_blogs) > 0): ?>
                <div class="d-none d-lg-block mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light py-3 border-0">
                            <h3 class="h5 mb-0">
                                <i class="fas fa-user me-2 text-primary"></i>More from <?= htmlspecialchars($blog['username'] ?? $blog['author']) ?>
                            </h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($author_blogs as $post): ?>
                                    <a href="blog_detail.php?id=<?= $post['blog_id'] ?>" class="list-group-item list-group-item-action border-0 d-flex align-items-center p-3">
                                        <?php if (!empty($post['image_path'])): ?>
                                            <img src="<?= htmlspecialchars($post['image_path']) ?>" 
                                                 class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;" alt="">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center me-3" 
                                                 style="width: 60px; height: 60px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h6 class="mb-0 text-truncate"><?= htmlspecialchars($post['title']) ?></h6>
                                            <small class="text-muted">
                                                <?= date('M d, Y', strtotime($post['created_at'] ?? $post['created_date'])) ?>
                                            </small>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Popular Categories -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light py-3 border-0">
                    <h3 class="h5 mb-0"><i class="fas fa-tags me-2 text-primary"></i>Popular Categories</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <?php
                        $cat_sql = "SELECT category, COUNT(*) as count FROM Blogs GROUP BY category ORDER BY count DESC LIMIT 10";
                        $cat_result = $conn->query($cat_sql);
                        
                        if ($cat_result && $cat_result->num_rows > 0) {
                            while ($cat = $cat_result->fetch_assoc()) {
                                echo '<a href="blogs.php?category=' . urlencode($cat['category']) . '" ' .
                                     'class="btn btn-outline-primary btn-sm">' . 
                                     htmlspecialchars($cat['category']) . ' <span class="badge bg-primary ms-1">' . $cat['count'] . '</span></a>';
                            }
                        } else {
                            echo '<p class="text-muted mb-0">No categories found</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Popular Destinations -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light py-3 border-0">
                    <h3 class="h5 mb-0"><i class="fas fa-map-marker-alt me-2 text-danger"></i>Popular Destinations</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php
                        $dest_sql = "SELECT destination, COUNT(*) as count FROM Blogs WHERE destination IS NOT NULL 
                                    GROUP BY destination ORDER BY count DESC LIMIT 5";
                        $dest_result = $conn->query($dest_sql);
                        
                        if ($dest_result && $dest_result->num_rows > 0) {
                            while ($dest = $dest_result->fetch_assoc()) {
                                if (!empty($dest['destination'])) {
                                    echo '<a href="blogs.php?destination=' . urlencode($dest['destination']) . '" ' .
                                         'class="list-group-item list-group-item-action d-flex justify-content-between align-items-center border-0 px-3 py-2">' . 
                                         htmlspecialchars($dest['destination']) . 
                                         '<span class="badge bg-primary rounded-pill">' . $dest['count'] . '</span></a>';
                                }
                            }
                        } else {
                            echo '<p class="text-muted p-3 mb-0">No destinations found</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Popular Tags -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light py-3 border-0">
                    <h3 class="h5 mb-0"><i class="fas fa-hashtag me-2 text-success"></i>Popular Tags</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <?php
                        $tag_sql = "SELECT SUBSTRING_INDEX(SUBSTRING_INDEX(t.tags, ',', n.n), ',', -1) tag,
                                  COUNT(*) as count
                                  FROM blogs t CROSS JOIN (
                                      SELECT a.N + b.N * 10 + 1 n
                                      FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a,
                                          (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
                                      ORDER BY n
                                  ) n
                                  WHERE n.n <= 1 + (LENGTH(t.tags) - LENGTH(REPLACE(t.tags, ',', '')))
                                  AND TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(t.tags, ',', n.n), ',', -1)) != ''
                                  GROUP BY tag
                                  ORDER BY count DESC, tag
                                  LIMIT 15";
                        
                        $tag_result = $conn->query($tag_sql);
                        
                        if ($tag_result && $tag_result->num_rows > 0) {
                            while ($tag = $tag_result->fetch_assoc()) {
                                echo '<a href="blogs.php?tag=' . urlencode(trim($tag['tag'])) . '" ' .
                                     'class="btn btn-outline-success btn-sm">' . 
                                     '#' . htmlspecialchars(trim($tag['tag'])) . ' <span class="badge bg-success ms-1">' . $tag['count'] . '</span></a>';
                            }
                        } else {
                            echo '<p class="text-muted mb-0">No tags found</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Newsletter Signup -->
            <div class="card border-0 shadow-sm bg-primary text-white mb-4">
                <div class="card-body p-4 text-center">
                    <h3 class="h5 mb-3">Subscribe to Our Newsletter</h3>
                    <p class="mb-3">Get the latest travel tips and stories delivered to your inbox.</p>
                    <form>
                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="Your email address" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-light">
                                <i class="fas fa-paper-plane me-2"></i> Subscribe
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Featured Posts Widget -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-light py-3 border-0">
                    <h3 class="h5 mb-0"><i class="fas fa-star me-2 text-warning"></i>Featured Posts</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php
                        $featured_sql = "SELECT blog_id, title, image_path, created_at, created_date 
                                       FROM Blogs 
                                       WHERE featured = 1 AND blog_id != ? 
                                       ORDER BY created_at DESC, created_date DESC 
                                       LIMIT 3";
                        $featured_stmt = $conn->prepare($featured_sql);
                        $featured_stmt->bind_param("i", $blog_id);
                        $featured_stmt->execute();
                        $featured_result = $featured_stmt->get_result();
                        
                        if ($featured_result && $featured_result->num_rows > 0) {
                            while ($featured = $featured_result->fetch_assoc()) {
                                echo '<a href="blog_detail.php?id=' . $featured['blog_id'] . '" ' .
                                     'class="list-group-item list-group-item-action border-0 d-flex align-items-center p-3">';
                                
                                if (!empty($featured['image_path'])) {
                                    echo '<img src="' . htmlspecialchars($featured['image_path']) . '" ' .
                                         'class="rounded me-3" style="width: 60px; height: 60px; object-fit: cover;" alt="">';
                                } else {
                                    echo '<div class="bg-light d-flex align-items-center justify-content-center me-3" ' .
                                         'style="width: 60px; height: 60px;">' .
                                         '<i class="fas fa-image text-muted"></i>' .
                                         '</div>';
                                }
                                
                                echo '<div>' .
                                     '<h6 class="mb-0 text-truncate" style="max-width: 220px;">' . htmlspecialchars($featured['title']) . '</h6>' .
                                     '<small class="text-muted">' .
                                     date('M d, Y', strtotime($featured['created_at'] ?? $featured['created_date'])) .
                                     '</small>' .
                                     '</div>' .
                                     '</a>';
                            }
                        } else {
                            echo '<p class="text-muted p-3 mb-0">No featured posts available</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Comment Reply Functionality -->
<script>
function replyToComment(username) {
    const commentTextarea = document.querySelector('textarea[name="comment"]');
    if (commentTextarea) {
        commentTextarea.value = `@${username} `;
        commentTextarea.focus();
        
        // Scroll to comment form
        document.querySelector('.comment-form').scrollIntoView({ behavior: 'smooth' });
    }
}

// Add this script to the bottom of your blog_detail.php file, just before the closing </body> tag
document.addEventListener('DOMContentLoaded', function() {
  // Explicitly initialize Bootstrap dropdowns
  var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'))
  var dropdownList = dropdownElementList.map(function(dropdownToggleEl) {
    return new bootstrap.Dropdown(dropdownToggleEl)
  })
  
  // Fix for the user dropdown specifically
  const userDropdown = document.getElementById('userDropdown');
  if (userDropdown) {
    userDropdown.addEventListener('click', function(e) {
      e.preventDefault();
      const dropdown = bootstrap.Dropdown.getInstance(userDropdown);
      if (dropdown) {
        dropdown.toggle();
      } else {
        new bootstrap.Dropdown(userDropdown).toggle();
      }
    });
  }
  
  // Fix z-index issues by adjusting the table of contents z-index
  const tableOfContents = document.querySelector('.sticky-top');
  if (tableOfContents) {
    tableOfContents.style.zIndex = "999"; // Lower z-index for the TOC
  }
  
  // Ensure z-index is properly set for dropdown menus
  const userDropdownMenu = document.querySelector('.user-dropdown-menu');
  if (userDropdownMenu) {
    userDropdownMenu.style.zIndex = "1050"; // Higher z-index to ensure it appears above other elements
  }
  
  // Add additional style for fixing the header positioning
  const headerElement = document.querySelector('.header');
  if (headerElement) {
    headerElement.style.position = "relative";
    headerElement.style.zIndex = "1060"; // Highest z-index for the header
  }
});
</script>
<style>
/* Add this to the <head> section */
.head-wrapper {
    position: relative;
    z-index: 9999 !important;
}

.header {
    position: relative;
    z-index: 9999 !important;
}

.navbar {
    position: relative;
    z-index: 9999 !important;
}

#navbarSupportedContent {
    position: relative;
    z-index: 9999 !important;
}

.dropdown-menu {
    z-index: 10000 !important;
}

.user-dropdown-menu {
    z-index: 10000 !important;
}

.sticky-top {
    z-index: 99 !important;
}
</style>
<?php 
$conn->close();
include 'footer.php'; 
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>