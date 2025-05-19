<?php 
session_start();
include 'header.php'; 
require_once 'includes/db_connect.php';

// Get query parameters for filtering
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$destination_filter = isset($_GET['destination']) ? $_GET['destination'] : '';

// Build the SQL query based on filters
$sql = "SELECT b.*, u.username, u.profile_image FROM Blogs b 
        LEFT JOIN Users u ON b.user_id = u.user_id ";

$where_clauses = [];
if (!empty($category_filter)) {
    $where_clauses[] = "b.category = '" . $conn->real_escape_string($category_filter) . "'";
}
if (!empty($destination_filter)) {
    $where_clauses[] = "b.destination = '" . $conn->real_escape_string($destination_filter) . "'";
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY b.created_at DESC";
$result = $conn->query($sql);

$blogs = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $blogs[] = $row;
    }
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Get featured blog (latest with image)
$featured_blog = null;
foreach ($blogs as $key => $blog) {
    if (!empty($blog['image_path'])) {
        $featured_blog = $blog;
        unset($blogs[$key]); // Remove from regular listing
        break;
    }
}
?>

<div class="container mt-5">
    <!-- Page Header with Filter Indication -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-4 mb-0">Travel Blogs</h1>
            <p class="lead text-muted">Explore travel stories and tips from our community</p>
            
            <?php if (!empty($category_filter) || !empty($destination_filter)): ?>
                <div class="d-flex align-items-center mt-3">
                    <span class="me-2">Showing:</span>
                    <?php if (!empty($category_filter)): ?>
                        <span class="badge bg-primary me-2 p-2">
                            Category: <?= htmlspecialchars($category_filter) ?>
                            <a href="?<?= !empty($destination_filter) ? 'destination='.urlencode($destination_filter) : '' ?>" 
                               class="text-white ms-2" title="Remove filter">×</a>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($destination_filter)): ?>
                        <span class="badge bg-info me-2 p-2">
                            Destination: <?= htmlspecialchars($destination_filter) ?>
                            <a href="?<?= !empty($category_filter) ? 'category='.urlencode($category_filter) : '' ?>" 
                               class="text-white ms-2" title="Remove filter">×</a>
                        </span>
                    <?php endif; ?>
                    
                    <a href="blogs.php" class="btn btn-sm btn-outline-secondary ms-auto">
                        <i class="fas fa-times me-1"></i> Clear All Filters
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Featured Blog (if available) -->
    <?php if ($featured_blog): ?>
        <div class="row mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm overflow-hidden">
                    <div class="row g-0">
                        <div class="col-md-6">
                            <img src="<?= htmlspecialchars($featured_blog['image_path']) ?>" 
                                 class="w-100 h-100" style="object-fit: cover; max-height: 450px;" 
                                 alt="<?= htmlspecialchars($featured_blog['title']) ?>">
                        </div>
                        <div class="col-md-6 d-flex align-items-center">
                            <div class="card-body p-4 p-md-5">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="badge bg-danger text-white me-2">Featured</div>
                                    <span class="badge bg-primary me-2"><?= htmlspecialchars($featured_blog['category']) ?></span>
                                    <?php if (!empty($featured_blog['destination'])): ?>
                                        <span class="badge bg-info"><?= htmlspecialchars($featured_blog['destination']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <h2 class="card-title fs-1 mb-3"><?= htmlspecialchars($featured_blog['title']) ?></h2>
                                
                                <p class="card-text fs-5 text-muted mb-4">
                                    <?= substr(htmlspecialchars($featured_blog['content']), 0, 180) ?>...
                                </p>
                                
                                <div class="d-flex align-items-center mb-4">
                                    <?php if (!empty($featured_blog['profile_image'])): ?>
                                        <img src="<?= htmlspecialchars($featured_blog['profile_image']) ?>" 
                                             class="rounded-circle me-2" width="40" height="40" alt="Author">
                                    <?php else: ?>
                                        <div class="bg-secondary rounded-circle text-white d-flex align-items-center justify-content-center me-2" 
                                             style="width: 40px; height: 40px;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="m-0 fw-bold"><?= htmlspecialchars($featured_blog['username'] ?? $featured_blog['author']) ?></p>
                                        <small class="text-muted">
                                            <?= date('F d, Y', strtotime($featured_blog['created_at'] ?? $featured_blog['created_date'])) ?>
                                        </small>
                                    </div>
                                </div>
                                
                                <a href="blog_detail.php?id=<?= $featured_blog['blog_id'] ?>" 
                                   class="btn btn-dark btn-lg px-4">Read Article</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- Blog Listings -->
        <div class="col-lg-8">
            <!-- Blog Grid Display -->
            <div class="row row-cols-1 row-cols-md-2 g-4 mb-4">
                <?php if (count($blogs) > 0): ?>
                    <?php foreach ($blogs as $index => $blog): ?>
                        <div class="col">
                            <div class="card h-100 border-0 shadow-sm">
                                <?php if (!empty($blog['image_path'])): ?>
                                    <img src="<?= htmlspecialchars($blog['image_path']) ?>" 
                                         class="card-img-top" style="height: 200px; object-fit: cover;" 
                                         alt="<?= htmlspecialchars($blog['title']) ?>">
                                <?php else: ?>
                                    <div class="card-img-top text-center bg-light pt-5 pb-5">
                                        <i class="fas fa-images fa-3x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <!-- Badges -->
                                    <div class="mb-2">
                                        <span class="badge bg-primary text-white me-2"><?= htmlspecialchars($blog['category']) ?></span>
                                        <?php if (!empty($blog['destination'])): ?>
                                            <span class="badge bg-info text-white"><?= htmlspecialchars($blog['destination']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <h3 class="card-title h5"><?= htmlspecialchars($blog['title']) ?></h3>
                                    <p class="card-text text-muted small">
                                        <?= substr(htmlspecialchars($blog['content']), 0, 130) ?>...
                                    </p>
                                </div>
                                
                                <div class="card-footer bg-white border-0 pt-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($blog['profile_image'])): ?>
                                                <img src="<?= htmlspecialchars($blog['profile_image']) ?>" 
                                                     class="rounded-circle me-2" width="30" height="30" alt="Author">
                                            <?php else: ?>
                                                <div class="bg-secondary rounded-circle text-white d-flex align-items-center justify-content-center me-2" 
                                                     style="width: 30px; height: 30px;">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            <?php endif; ?>
                                            <small><?= htmlspecialchars($blog['username'] ?? $blog['author']) ?></small>
                                        </div>
                                        <small class="text-muted">
                                            <?= date('M d, Y', strtotime($blog['created_at'] ?? $blog['created_date'])) ?>
                                        </small>
                                    </div>
                                    <a href="blog_detail.php?id=<?= $blog['blog_id'] ?>" 
                                       class="btn btn-outline-primary w-100 mt-3">Read More</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info p-4">
                            <h4 class="alert-heading mb-3"><i class="fas fa-info-circle me-2"></i>No blogs found</h4>
                            <?php if (!empty($category_filter) || !empty($destination_filter)): ?>
                                <p>No blog posts match your current filters. Try removing some filters or check back later.</p>
                                <a href="blogs.php" class="btn btn-sm btn-primary mt-2">View All Blogs</a>
                            <?php else: ?>
                                <p>No blog posts have been published yet. Be the first to share your travel experiences!</p>
                                <?php if ($is_logged_in): ?>
                                    <a href="create_blog.php" class="btn btn-sm btn-primary mt-2">
                                        <i class="fas fa-pen me-2"></i> Write a Blog
                                    </a>
                                <?php else: ?>
                                    <a href="login.php" class="btn btn-sm btn-primary mt-2">
                                        Sign in to write a blog
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Categories -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h5 mb-0"><i class="fas fa-tags me-2 text-primary"></i>Categories</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <?php
                        $cat_sql = "SELECT category, COUNT(*) as count FROM Blogs GROUP BY category ORDER BY count DESC";
                        $cat_result = $conn->query($cat_sql);
                        
                        if ($cat_result && $cat_result->num_rows > 0) {
                            while ($cat = $cat_result->fetch_assoc()) {
                                $active = ($category_filter == $cat['category']) ? 'active' : '';
                                echo '<a href="blogs.php?category=' . urlencode($cat['category']) . '" ' .
                                     'class="btn btn-outline-primary btn-sm ' . $active . '">' . 
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
                <div class="card-header bg-white py-3">
                    <h3 class="h5 mb-0"><i class="fas fa-map-marker-alt me-2 text-danger"></i>Popular Destinations</h3>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php
                        $dest_sql = "SELECT destination, COUNT(*) as count FROM Blogs WHERE destination IS NOT NULL 
                                    GROUP BY destination ORDER BY count DESC LIMIT 5";
                        $dest_result = $conn->query($dest_sql);
                        
                        if ($dest_result && $dest_result->num_rows > 0) {
                            while ($dest = $dest_result->fetch_assoc()) {
                                if (!empty($dest['destination'])) {
                                    $active = ($destination_filter == $dest['destination']) ? 'active' : '';
                                    echo '<a href="blogs.php?destination=' . urlencode($dest['destination']) . '" ' .
                                         'class="list-group-item list-group-item-action d-flex justify-content-between align-items-center border-0 px-0 ' . $active . '">' . 
                                         htmlspecialchars($dest['destination']) . 
                                         '<span class="badge bg-primary rounded-pill">' . $dest['count'] . '</span></a>';
                                }
                            }
                        } else {
                            echo '<p class="text-muted mb-0">No destinations found</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Posts -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3">
                    <h3 class="h5 mb-0"><i class="fas fa-clock me-2 text-success"></i>Recent Posts</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php
                        $recent_sql = "SELECT blog_id, title, created_at, created_date FROM Blogs 
                                      ORDER BY created_at DESC, created_date DESC LIMIT 5";
                        $recent_result = $conn->query($recent_sql);
                        
                        if ($recent_result && $recent_result->num_rows > 0) {
                            while ($recent = $recent_result->fetch_assoc()) {
                                echo '<a href="blog_detail.php?id=' . $recent['blog_id'] . '" ' .
                                     'class="list-group-item list-group-item-action border-0 px-3 py-3">' . 
                                     '<p class="mb-1">' . htmlspecialchars($recent['title']) . '</p>' .
                                     '<small class="text-muted">' . 
                                     date('M d, Y', strtotime($recent['created_at'] ?? $recent['created_date'])) . 
                                     '</small></a>';
                            }
                        } else {
                            echo '<p class="text-muted p-3 mb-0">No recent posts found</p>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
include 'footer.php'; 
?>
