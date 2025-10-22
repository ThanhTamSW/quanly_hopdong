<?php
$page_title = 'Blog - Kiến thức Fitness & Gym';
$requires_login = false;
include 'includes/header.php';
include 'includes/db.php';

// Lấy danh sách bài viết published
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 9;
$offset = ($page - 1) * $per_page;

$total_query = "SELECT COUNT(*) as total FROM blog_posts WHERE status = 'published'";
$total_result = $conn->query($total_query);
$total_posts = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_posts / $per_page);

$posts_query = "
    SELECT 
        bp.*,
        u.full_name AS author_name
    FROM blog_posts bp
    JOIN users u ON bp.author_id = u.id
    WHERE bp.status = 'published'
    ORDER BY bp.published_at DESC, bp.created_at DESC
    LIMIT $per_page OFFSET $offset
";
$posts = $conn->query($posts_query);
?>

<div class="container my-5">
    <!-- Hero Section -->
    <div class="text-center mb-5">
        <h1 class="display-4 fw-bold mb-3">📚 Blog Fitness & Gym</h1>
        <p class="lead text-muted">Kiến thức chuyên sâu về tập luyện, dinh dưỡng và sức khỏe</p>
    </div>
    
    <?php if ($posts && $posts->num_rows > 0): ?>
        <div class="row g-4">
            <?php while ($post = $posts->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card h-100 shadow-sm hover-lift" style="transition: transform 0.3s;">
                        <?php if ($post['featured_image']): ?>
                            <img src="<?= htmlspecialchars($post['featured_image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($post['title']) ?>">
                        <?php else: ?>
                            <div class="card-img-top bg-gradient text-white d-flex align-items-center justify-content-center" style="height: 200px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <h3 class="text-center px-3"><?= htmlspecialchars(mb_substr($post['title'], 0, 50)) ?></h3>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?= htmlspecialchars($post['title']) ?></h5>
                            <p class="card-text text-muted flex-grow-1"><?= htmlspecialchars($post['excerpt']) ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <small class="text-muted">
                                    👤 <?= htmlspecialchars($post['author_name']) ?><br>
                                    📅 <?= date('d/m/Y', strtotime($post['published_at'] ?? $post['created_at'])) ?>
                                </small>
                                <a href="blog_post.php?slug=<?= $post['slug'] ?>" class="btn btn-primary btn-sm">Đọc tiếp →</a>
                            </div>
                            
                            <?php if ($post['ai_generated']): ?>
                                <div class="mt-2">
                                    <span class="badge bg-info">🤖 AI Generated</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-5">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page - 1 ?>">← Trước</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $page + 1 ?>">Sau →</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="text-center py-5">
            <div class="alert alert-info d-inline-block">
                <h4>📝 Chưa có bài viết nào</h4>
                <p class="mb-0">Hãy quay lại sau để đọc những bài viết mới nhất!</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.hover-lift:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}
</style>

<?php include 'includes/footer.php'; ?>

