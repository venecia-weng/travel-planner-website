<?php
session_start();

// Admin check
if (!isset($_SESSION['admin_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header("Location: ../admin-login.php");
    exit();
}

require_once '../includes/db_connect.php';
include 'admin-header.php';

// Fetch blog posts
$result = $conn->query("SELECT blog_id, title, author, created_date FROM blogs ORDER BY created_date DESC");
?>

<div class="main-content">
    <div class="page-header">
        <h1>Manage Blogs</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Blogs</li>
            </ol>
        </nav>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Blog Posts</h5>
            <a href="blog-create.php" class="btn btn-sm btn-success">
                <i class="bi bi-plus-circle me-1"></i> Create New Blog
            </a>
        </div>
        <div class="card-body">
            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['title']) ?></td>
                                    <td><?= htmlspecialchars($row['author']) ?></td>
                                    <td><?= htmlspecialchars($row['created_date']) ?></td>
                                    <td>
                                    <a href="blog-edit.php?id=<?= $row['blog_id'] ?>" class="btn btn-outline-primary btn-sm me-1">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                        <a href="blog-delete.php?id=<?= $row['blog_id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Are you sure you want to delete this blog?')">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No blog posts found.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'admin-footer.php'; ?>    