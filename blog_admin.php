<?php
$page_title = 'Quản lý Blog AI';
$requires_login = true;
include 'includes/header.php';
include 'includes/db.php';
include 'includes/ai_helper.php';

$message = '';
$error = '';

// Xử lý xóa bài viết
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $post_id = (int)$_POST['post_id'];
    
    // Xóa bài viết
    $stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
    $stmt->bind_param("i", $post_id);
    
    if ($stmt->execute()) {
        $message = 'Đã xóa bài viết thành công!';
    } else {
        $error = 'Có lỗi khi xóa bài viết: ' . $conn->error;
    }
    $stmt->close();
}

// Xử lý tạo bài viết mới với AI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_ai'])) {
    $topic = trim($_POST['topic']);
    $length = $_POST['length'] ?? 'medium';
    $tone = $_POST['tone'] ?? 'professional';
    $generate_image = isset($_POST['generate_image']);
    
    if (empty($topic)) {
        $error = 'Vui lòng nhập chủ đề bài viết!';
    } else {
        // Lấy thông tin user để thêm vào bài viết
        $user_id = $_SESSION['user_id'];
        $user_query = $conn->query("SELECT phone_number, full_name FROM users WHERE id = $user_id");
        $user_info = $user_query->fetch_assoc();
        
        // Generate content với AI (truyền thông tin user)
        $result = generateBlogPost($topic, [
            'length' => $length, 
            'tone' => $tone,
            'author_phone' => $user_info['phone_number'],
            'author_name' => $user_info['full_name']
        ]);
        
        if ($result['success']) {
            $title = $topic;
            $slug = generateSlug($title);
            
            // Clean content - xóa markdown syntax
            $content = $result['content'];
            $content = str_replace('**', '', $content); // Xóa bold
            $content = str_replace('__', '', $content); // Xóa bold alternative
            $content = preg_replace('/\*([^*]+)\*/', '$1', $content); // Xóa italic
            $content = preg_replace('/^#+\s+/m', '', $content); // Xóa heading markdown
            
            $excerpt = extractExcerpt($content);
            $ai_prompt = $result['prompt'];
            $featured_image = null;
            
            // Extract hashtags from content for image generation
            preg_match_all('/#\w+/', $content, $matches);
            $hashtags = $matches[0] ?? [];
            
            // Generate featured image nếu được chọn
            if ($generate_image) {
                $image_result = generateFeaturedImage($topic, ['hashtags' => $hashtags]);
                if ($image_result['success']) {
                    $featured_image = $image_result['image_url'];
                    
                    // Add text overlay nếu được chọn
                    if (isset($_POST['add_text_overlay'])) {
                        $featured_image = addTextOverlay($featured_image, $title);
                    }
                    
                    $message = "✅ Đã tạo bài viết + hình ảnh";
                    $message .= isset($_POST['add_text_overlay']) ? " + logo" : "";
                    $message .= " thành công! ";
                } else {
                    $message = "⚠️ Đã tạo bài viết nhưng không tạo được ảnh: " . $image_result['error'] . ". ";
                }
            }
            
            // Check slug unique
            $check_slug = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ?");
            $check_slug->bind_param("s", $slug);
            $check_slug->execute();
            if ($check_slug->get_result()->num_rows > 0) {
                $slug .= '-' . time();
            }
            $check_slug->close();
            
            // Insert vào database
            $stmt = $conn->prepare("INSERT INTO blog_posts (title, slug, topic, content, excerpt, featured_image, author_id, status, ai_generated, ai_prompt) VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', TRUE, ?)");
            $stmt->bind_param("ssssssis", $title, $slug, $topic, $content, $excerpt, $featured_image, $_SESSION['user_id'], $ai_prompt);
            
            if ($stmt->execute()) {
                $new_post_id = $stmt->insert_id;
                if (!isset($message)) {
                    $message = "✅ Đã tạo bài viết thành công! ";
                }
                $message .= "<a href='blog_edit.php?id=$new_post_id' class='alert-link'>Chỉnh sửa ngay</a>";
            } else {
                $error = 'Lỗi khi lưu bài viết: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = $result['error'];
        }
    }
}

// Lấy danh sách bài viết
$posts_query = "
    SELECT 
        bp.*,
        u.full_name AS author_name
    FROM blog_posts bp
    JOIN users u ON bp.author_id = u.id
    ORDER BY bp.created_at DESC
";
$posts = $conn->query($posts_query);
?>

<div class="container my-4">
    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['flash_success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['flash_error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Form tạo bài viết với AI -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <h4 class="mb-0">🤖 Tạo bài viết với AI</h4>
        </div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-12">
                    <label class="form-label"><strong>Chủ đề bài viết</strong></label>
                    <input type="text" name="topic" class="form-control" placeholder="VD: 10 bài tập tăng cơ ngực hiệu quả cho người mới" required>
                    <div class="form-text">Nhập chủ đề cụ thể, AI sẽ tự động tạo nội dung đầy đủ</div>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label"><strong>Độ dài bài viết</strong></label>
                    <select name="length" class="form-select">
                        <option value="very-short" selected>Rất ngắn (200-300 từ) + 5 hashtags</option>
                        <option value="short">Ngắn (500-700 từ)</option>
                        <option value="medium">Trung bình (1000-1500 từ)</option>
                        <option value="long">Dài (2000-3000 từ)</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label"><strong>Phong cách</strong></label>
                    <select name="tone" class="form-select">
                        <option value="professional" selected>Chuyên nghiệp</option>
                        <option value="casual">Thân mật</option>
                        <option value="friendly">Thân thiện</option>
                    </select>
                </div>
                
                <div class="col-12">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="generate_image" id="generate_image" checked onchange="toggleTextOverlay()">
                        <label class="form-check-label" for="generate_image">
                            <strong>🎨 Tự động tạo hình ảnh đại diện với AI</strong>
                            <div class="form-text">Sử dụng AI để tạo hình ảnh chất lượng cao phù hợp với nội dung bài viết</div>
                        </label>
                    </div>
                    
                    <div class="form-check mb-3 ms-4" id="text_overlay_option" style="display: block;">
                        <input class="form-check-input" type="checkbox" name="add_text_overlay" id="add_text_overlay" checked>
                        <label class="form-check-label" for="add_text_overlay">
                            <strong>✍️ Thêm logo Transform lên hình (góc trên trái)</strong>
                            <div class="form-text">Tự động chèn logo Transform Fitness vào góc trên bên trái hình ảnh</div>
                        </label>
                    </div>
                </div>
                
                <script>
                function toggleTextOverlay() {
                    const imageCheckbox = document.getElementById('generate_image');
                    const overlayOption = document.getElementById('text_overlay_option');
                    const overlayCheckbox = document.getElementById('add_text_overlay');
                    
                    if (imageCheckbox.checked) {
                        overlayOption.style.display = 'block';
                    } else {
                        overlayOption.style.display = 'none';
                        overlayCheckbox.checked = false;
                    }
                }
                </script>
                
                <div class="col-12">
                    <button type="submit" name="generate_ai" class="btn btn-primary btn-lg">
                        ✨ Tạo bài viết với AI
                    </button>
                    <small class="text-muted ms-3">⏱️ Quá trình có thể mất 20-60 giây (có ảnh)</small>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Danh sách bài viết -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2">
            <h4 class="mb-0">📝 Danh sách bài viết</h4>
            <a href="blog.php" class="btn btn-light btn-sm">👁️ Xem Blog công khai</a>
        </div>
        <div class="card-body">
            <?php if ($posts && $posts->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tiêu đề</th>
                                <th class="d-none d-md-table-cell">Tác giả</th>
                                <th>Trạng thái</th>
                                <th class="d-none d-lg-table-cell">AI</th>
                                <th class="d-none d-lg-table-cell">Lượt xem</th>
                                <th class="d-none d-md-table-cell">Ngày tạo</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($post = $posts->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($post['title']) ?></strong></td>
                                    <td class="d-none d-md-table-cell"><?= htmlspecialchars($post['author_name']) ?></td>
                                    <td>
                                        <?php
                                        $status_badges = [
                                            'draft' => '<span class="badge bg-secondary">Nháp</span>',
                                            'published' => '<span class="badge bg-success">Đã xuất bản</span>',
                                            'archived' => '<span class="badge bg-dark">Lưu trữ</span>'
                                        ];
                                        echo $status_badges[$post['status']];
                                        ?>
                                    </td>
                                    <td class="d-none d-lg-table-cell"><?= $post['ai_generated'] ? '🤖 AI' : '✍️ Thủ công' ?></td>
                                    <td class="d-none d-lg-table-cell"><?= number_format($post['views']) ?></td>
                                    <td class="d-none d-md-table-cell"><?= date('d/m/Y', strtotime($post['created_at'])) ?></td>
                                    <td>
                                        <div class="btn-group-vertical btn-group-sm d-md-none" role="group">
                                            <a href="blog_edit.php?id=<?= $post['id'] ?>" class="btn btn-warning btn-sm">✏️</a>
                                            <a href="blog_post.php?slug=<?= $post['slug'] ?>" class="btn btn-info btn-sm" target="_blank">👁️</a>
                                            <button onclick="copyPostContent(<?= $post['id'] ?>, '<?= htmlspecialchars(addslashes($post['content'])) ?>')" class="btn btn-success btn-sm">📋</button>
                                            <button onclick="deletePost(<?= $post['id'] ?>, '<?= htmlspecialchars(addslashes($post['title'])) ?>')" class="btn btn-danger btn-sm">🗑️</button>
                                        </div>
                                        <div class="d-none d-md-block">
                                            <a href="blog_edit.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-warning">✏️ Sửa</a>
                                            <a href="blog_post.php?slug=<?= $post['slug'] ?>" class="btn btn-sm btn-info" target="_blank">👁️ Xem</a>
                                            <button onclick="copyPostContent(<?= $post['id'] ?>, '<?= htmlspecialchars(addslashes($post['content'])) ?>')" class="btn btn-sm btn-success">📋 Copy</button>
                                            <button onclick="deletePost(<?= $post['id'] ?>, '<?= htmlspecialchars(addslashes($post['title'])) ?>')" class="btn btn-sm btn-danger">🗑️ Xóa</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    ℹ️ Chưa có bài viết nào. Hãy tạo bài viết đầu tiên với AI!
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deletePost(postId, postTitle) {
    if (confirm('Bạn có chắc chắn muốn xóa bài viết "' + postTitle + '"?\n\nHành động này không thể hoàn tác!')) {
        // Tạo form ẩn để submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        
        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'post_id';
        inputId.value = postId;
        
        const inputDelete = document.createElement('input');
        inputDelete.type = 'hidden';
        inputDelete.name = 'delete_post';
        inputDelete.value = '1';
        
        form.appendChild(inputId);
        form.appendChild(inputDelete);
        document.body.appendChild(form);
        form.submit();
    }
}

function copyPostContent(postId, content) {
    // Decode HTML entities
    const textarea = document.createElement('textarea');
    textarea.innerHTML = content;
    const decodedContent = textarea.value;
    
    // Copy to clipboard
    navigator.clipboard.writeText(decodedContent).then(function() {
        // Show success message
        alert('✅ Đã copy nội dung!\n\nBây giờ bạn có thể paste (Ctrl+V) vào Facebook hoặc bất kỳ đâu.');
    }).catch(function(err) {
        // Fallback for older browsers
        const tempTextarea = document.createElement('textarea');
        tempTextarea.value = decodedContent;
        tempTextarea.style.position = 'fixed';
        tempTextarea.style.opacity = '0';
        document.body.appendChild(tempTextarea);
        tempTextarea.select();
        document.execCommand('copy');
        document.body.removeChild(tempTextarea);
        
        alert('✅ Đã copy nội dung!\n\nBây giờ bạn có thể paste (Ctrl+V) vào Facebook hoặc bất kỳ đâu.');
    });
}
</script>

<?php include 'includes/footer.php'; ?>
