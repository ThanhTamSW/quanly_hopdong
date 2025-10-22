<?php
$page_title = 'Quản lý Blog AI';
$requires_login = true;
include 'includes/header.php';
include 'includes/db.php';
include 'includes/ai_helper.php';

$message = '';
$error = '';

// Xử lý tạo bài viết mới với AI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_ai'])) {
    $topic = trim($_POST['topic']);
    $length = $_POST['length'] ?? 'medium';
    $tone = $_POST['tone'] ?? 'professional';
    
    if (empty($topic)) {
        $error = 'Vui lòng nhập chủ đề bài viết!';
    } else {
        // Generate content với AI
        $result = generateBlogPost($topic, ['length' => $length, 'tone' => $tone]);
        
        if ($result['success']) {
            $title = $topic;
            $slug = generateSlug($title);
            $content = $result['content'];
            $excerpt = extractExcerpt($content);
            $ai_prompt = $result['prompt'];
            
            // Check slug unique
            $check_slug = $conn->prepare("SELECT id FROM blog_posts WHERE slug = ?");
            $check_slug->bind_param("s", $slug);
            $check_slug->execute();
            if ($check_slug->get_result()->num_rows > 0) {
                $slug .= '-' . time();
            }
            $check_slug->close();
            
            // Insert vào database
            $stmt = $conn->prepare("INSERT INTO blog_posts (title, slug, topic, content, excerpt, author_id, status, ai_generated, ai_prompt) VALUES (?, ?, ?, ?, ?, ?, 'draft', TRUE, ?)");
            $stmt->bind_param("sssssis", $title, $slug, $topic, $content, $excerpt, $_SESSION['user_id'], $ai_prompt);
            
            if ($stmt->execute()) {
                $new_post_id = $stmt->insert_id;
                $message = "✅ Đã tạo bài viết thành công! <a href='blog_edit.php?id=$new_post_id' class='alert-link'>Chỉnh sửa ngay</a>";
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
                        <option value="short">Ngắn (500-700 từ)</option>
                        <option value="medium" selected>Trung bình (1000-1500 từ)</option>
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
                    <button type="submit" name="generate_ai" class="btn btn-primary btn-lg">
                        ✨ Tạo bài viết với AI
                    </button>
                    <small class="text-muted ms-3">⏱️ Quá trình có thể mất 10-30 giây</small>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Danh sách bài viết -->
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
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
                                <th>Tác giả</th>
                                <th>Trạng thái</th>
                                <th>AI</th>
                                <th>Lượt xem</th>
                                <th>Ngày tạo</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($post = $posts->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($post['title']) ?></strong></td>
                                    <td><?= htmlspecialchars($post['author_name']) ?></td>
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
                                    <td><?= $post['ai_generated'] ? '🤖 AI' : '✍️ Thủ công' ?></td>
                                    <td><?= number_format($post['views']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($post['created_at'])) ?></td>
                                    <td>
                                        <a href="blog_edit.php?id=<?= $post['id'] ?>" class="btn btn-sm btn-warning">Sửa</a>
                                        <a href="blog_post.php?slug=<?= $post['slug'] ?>" class="btn btn-sm btn-info" target="_blank">Xem</a>
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
    
    <!-- Hướng dẫn sử dụng AI -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">💡 Hướng dẫn sử dụng</h5>
        </div>
        <div class="card-body">
            <h6><strong>Bước 1: Cấu hình API</strong></h6>
            <ol>
                <li>Copy file <code>config.ai.example.php</code> thành <code>config.ai.php</code></li>
                <li>Chọn provider (khuyến nghị: <strong>Gemini</strong> - miễn phí)</li>
                <li>Lấy API key:
                    <ul>
                        <li><strong>Google Gemini:</strong> <a href="https://makersuite.google.com/app/apikey" target="_blank">https://makersuite.google.com/app/apikey</a> (Miễn phí)</li>
                        <li><strong>OpenAI:</strong> <a href="https://platform.openai.com/api-keys" target="_blank">https://platform.openai.com/api-keys</a> (Có phí)</li>
                    </ul>
                </li>
                <li>Dán API key vào file <code>config.ai.php</code></li>
            </ol>
            
            <h6 class="mt-3"><strong>Bước 2: Tạo nội dung</strong></h6>
            <ul>
                <li>Nhập chủ đề cụ thể (càng chi tiết càng tốt)</li>
                <li>Chọn độ dài và phong cách phù hợp</li>
                <li>Click "Tạo bài viết với AI" và chờ</li>
                <li>AI sẽ tự động tạo bài viết hoàn chỉnh</li>
            </ul>
            
            <h6 class="mt-3"><strong>Ví dụ chủ đề tốt:</strong></h6>
            <ul>
                <li>✅ "10 bài tập tăng cơ ngực hiệu quả cho người mới bắt đầu"</li>
                <li>✅ "Chế độ dinh dưỡng tăng cơ giảm mỡ cho nam giới"</li>
                <li>✅ "Cách sử dụng máy Smith an toàn và đúng kỹ thuật"</li>
                <li>❌ "Gym" (quá chung chung)</li>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

