<?php
$requires_login = false;
include 'includes/db.php';

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header('Location: blog.php');
    exit;
}

// Lấy bài viết
$stmt = $conn->prepare("
    SELECT 
        bp.*,
        u.full_name AS author_name
    FROM blog_posts bp
    JOIN users u ON bp.author_id = u.id
    WHERE bp.slug = ? AND bp.status = 'published'
");
$stmt->bind_param("s", $slug);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    header('Location: blog.php');
    exit;
}

// Tăng lượt xem
$conn->query("UPDATE blog_posts SET views = views + 1 WHERE id = {$post['id']}");

$page_title = $post['title'];
include 'includes/header.php';

// Parse markdown basic
function parseMarkdown($text) {
    // Headers
    $text = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $text);
    $text = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $text);
    $text = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $text);
    
    // Bold
    $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
    
    // Lists
    $text = preg_replace('/^\* (.+)$/m', '<li>$1</li>', $text);
    $text = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $text);
    
    // Paragraphs
    $text = '<p>' . preg_replace('/\n\n/', '</p><p>', $text) . '</p>';
    
    return $text;
}
?>

<article class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="mb-4">
                <h1 class="display-5 fw-bold mb-3"><?= htmlspecialchars($post['title']) ?></h1>
                
                <div class="d-flex align-items-center text-muted mb-3">
                    <span class="me-3">
                        <strong>👤 <?= htmlspecialchars($post['author_name']) ?></strong>
                    </span>
                    <span class="me-3">
                        📅 <?= date('d/m/Y', strtotime($post['published_at'] ?? $post['created_at'])) ?>
                    </span>
                    <span class="me-3">
                        👁️ <?= number_format($post['views']) ?> lượt xem
                    </span>
                    <?php if ($post['ai_generated']): ?>
                        <span class="badge bg-info">🤖 AI Generated</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Featured Image -->
            <?php if ($post['featured_image']): ?>
                <img src="<?= htmlspecialchars($post['featured_image']) ?>" class="img-fluid rounded mb-4" alt="<?= htmlspecialchars($post['title']) ?>">
            <?php endif; ?>
            
            <!-- Content -->
            <div class="blog-content" style="font-size: 1.1rem; line-height: 1.8;">
                <?= parseMarkdown($post['content']) ?>
            </div>
            
            <!-- Footer -->
            <hr class="my-5">
            
            <div class="d-flex justify-content-between align-items-center">
                <a href="blog.php" class="btn btn-outline-primary">
                    ← Quay lại Blog
                </a>
                
                <div>
                    <button class="btn btn-outline-secondary" onclick="window.print()">
                        🖨️ In bài viết
                    </button>
                </div>
            </div>
            
            <!-- Related Posts -->
            <?php
            $related_query = "
                SELECT id, title, slug, excerpt
                FROM blog_posts
                WHERE status = 'published' AND id != {$post['id']}
                ORDER BY RAND()
                LIMIT 3
            ";
            $related = $conn->query($related_query);
            
            if ($related && $related->num_rows > 0):
            ?>
                <div class="mt-5">
                    <h3 class="mb-4">📖 Bài viết liên quan</h3>
                    <div class="row g-3">
                        <?php while ($rel = $related->fetch_assoc()): ?>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title"><?= htmlspecialchars($rel['title']) ?></h6>
                                        <p class="card-text small text-muted"><?= htmlspecialchars(mb_substr($rel['excerpt'], 0, 100)) ?>...</p>
                                        <a href="blog_post.php?slug=<?= $rel['slug'] ?>" class="btn btn-sm btn-primary">Đọc →</a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</article>

<style>
.blog-content h2 {
    margin-top: 2rem;
    margin-bottom: 1rem;
    color: #2c3e50;
}

.blog-content h3 {
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    color: #34495e;
}

.blog-content p {
    margin-bottom: 1rem;
}

.blog-content ul {
    margin-bottom: 1rem;
    padding-left: 2rem;
}

.blog-content li {
    margin-bottom: 0.5rem;
}

@media print {
    .btn, nav, .related {
        display: none !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>

