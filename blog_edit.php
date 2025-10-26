<?php
session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';
$post = null;

// Get post ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: blog_admin.php');
    exit;
}

$post_id = (int)$_GET['id'];

// Fetch post
$stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

if (!$post) {
    header('Location: blog_admin.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    
    if (empty($title) || empty($content)) {
        $error = 'Vui lòng điền tiêu đề và nội dung';
    } else {
        // Generate slug from title if changed
        $slug = $post['slug'];
        if ($title !== $post['title']) {
            require_once 'includes/ai_helper.php';
            $slug = generateSlug($title);
        }
        
        // Update post
        $stmt = $conn->prepare("UPDATE blog_posts SET title = ?, slug = ?, content = ?, excerpt = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $title, $slug, $content, $excerpt, $status, $post_id);
        
        if ($stmt->execute()) {
            $success = 'Đã cập nhật bài viết thành công!';
            // Refresh post data
            $stmt = $conn->prepare("SELECT * FROM blog_posts WHERE id = ?");
            $stmt->bind_param("i", $post_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $post = $result->fetch_assoc();
        } else {
            $error = 'Có lỗi khi cập nhật: ' . $conn->error;
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>✏️ Chỉnh sửa bài viết</h2>
        <a href="blog_admin.php" class="btn btn-secondary">← Quay lại</a>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Nội dung bài viết</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label"><strong>Tiêu đề</strong></label>
                            <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($post['title']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Slug (URL)</strong></label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($post['slug']) ?>" disabled>
                            <small class="text-muted">Slug sẽ tự động cập nhật khi bạn thay đổi tiêu đề</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Tóm tắt (Excerpt)</strong></label>
                            <textarea name="excerpt" class="form-control" rows="3"><?= htmlspecialchars($post['excerpt']) ?></textarea>
                            <small class="text-muted">Để trống nếu muốn tự động tạo từ nội dung</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Nội dung</strong></label>
                            <textarea name="content" class="form-control" rows="20" required><?= htmlspecialchars($post['content']) ?></textarea>
                            <small class="text-muted">Hỗ trợ Markdown format</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Trạng thái</strong></label>
                            <select name="status" class="form-select">
                                <option value="draft" <?= $post['status'] === 'draft' ? 'selected' : '' ?>>Nháp</option>
                                <option value="published" <?= $post['status'] === 'published' ? 'selected' : '' ?>>Đã xuất bản</option>
                                <option value="archived" <?= $post['status'] === 'archived' ? 'selected' : '' ?>>Lưu trữ</option>
                            </select>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
                            <a href="blog_post.php?slug=<?= $post['slug'] ?>" class="btn btn-info" target="_blank">👁️ Xem bài viết</a>
                            <a href="blog_admin.php" class="btn btn-secondary">Hủy</a>
                            <button type="button" class="btn btn-danger ms-auto" onclick="deletePost(<?= $post['id'] ?>, '<?= htmlspecialchars(addslashes($post['title'])) ?>')">🗑️ Xóa bài viết</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">ℹ️ Thông tin</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>ID:</strong> <?= $post['id'] ?></p>
                    <p class="mb-2"><strong>Tác giả:</strong> Coach ID <?= $post['author_id'] ?></p>
                    <p class="mb-2"><strong>Lượt xem:</strong> <?= number_format($post['views']) ?></p>
                    <p class="mb-2"><strong>AI Generated:</strong> <?= $post['ai_generated'] ? '✅ Có' : '❌ Không' ?></p>
                    <p class="mb-2"><strong>Ngày tạo:</strong> <?= date('d/m/Y H:i', strtotime($post['created_at'])) ?></p>
                    <p class="mb-0"><strong>Cập nhật:</strong> <?= date('d/m/Y H:i', strtotime($post['updated_at'])) ?></p>
                </div>
            </div>
            
            <?php if ($post['featured_image']): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">🖼️ Hình ảnh đại diện</h6>
                </div>
                <div class="card-body p-0">
                    <img src="<?= htmlspecialchars($post['featured_image']) ?>" class="img-fluid" alt="Featured image">
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card shadow-sm">
                <div class="card-header bg-warning">
                    <h6 class="mb-0">💡 Tips</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0 ps-3">
                        <li>Dùng ## cho tiêu đề phụ</li>
                        <li>Dùng **text** để in đậm</li>
                        <li>Dùng - để tạo danh sách</li>
                        <li>Hashtags nên để ở cuối bài</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function deletePost(postId, postTitle) {
    if (confirm('Bạn có chắc chắn muốn xóa bài viết "' + postTitle + '"?\n\nHành động này không thể hoàn tác!')) {
        if (confirm('⚠️ XÁC NHẬN LẦN CUỐI!\n\nBài viết sẽ bị xóa vĩnh viễn. Bạn có chắc chắn?')) {
            window.location.href = 'blog_delete.php?id=' + postId;
        }
    }
}
</script>

<?php include 'includes/footer.php'; ?>

