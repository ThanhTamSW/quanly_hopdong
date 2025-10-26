<?php
session_start();
$requires_login = false;
include 'includes/db.php';

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';

if (empty($slug)) {
    header('Location: blog.php');
    exit;
}

// Lấy bài viết - cho phép xem draft nếu đã đăng nhập
$is_logged_in = isset($_SESSION['user_id']);

if ($is_logged_in) {
    // Đã đăng nhập: xem được tất cả bài viết
    $stmt = $conn->prepare("
        SELECT 
            bp.*,
            u.full_name AS author_name
        FROM blog_posts bp
        JOIN users u ON bp.author_id = u.id
        WHERE bp.slug = ?
    ");
} else {
    // Chưa đăng nhập: chỉ xem được bài published
    $stmt = $conn->prepare("
        SELECT 
            bp.*,
            u.full_name AS author_name
        FROM blog_posts bp
        JOIN users u ON bp.author_id = u.id
        WHERE bp.slug = ? AND bp.status = 'published'
    ");
}

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

// Parse markdown với format Facebook-style
function parseMarkdown($text) {
    // Loại bỏ các markdown syntax không mong muốn
    $text = str_replace('**', '', $text); // Xóa bold markdown
    $text = str_replace('__', '', $text); // Xóa bold alternative
    $text = preg_replace('/\*([^*]+)\*/', '$1', $text); // Xóa italic
    $text = preg_replace('/^#+\s+/m', '', $text); // Xóa heading markdown (##, ###)
    
    $lines = explode("\n", $text);
    $result = '';
    $in_list = false;
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        if (empty($line)) {
            if ($in_list) {
                $result .= '</div>';
                $in_list = false;
            }
            continue;
        }
        
        // Tiêu đề VIẾT HOA (dòng đầu tiên hoặc có emoji ở cuối)
        if (preg_match('/^([A-ZÀÁẠẢÃÂẦẤẬẨẪĂẰẮẶẲẴÈÉẸẺẼÊỀẾỆỂỄÌÍỊỈĨÒÓỌỎÕÔỒỐỘỔỖƠỜỚỢỞỠÙÚỤỦŨƯỪỨỰỬỮỲÝỴỶỸĐ0-9\s:,\-]+?)\s*(🔥|💪|⭐|✨|🎯|🏃|🏋️|🛡️|👟|🧠|💯|⚡|🌟|🤸|🧘|🦵|🦶|⚙️|🔄)!?\s*$/u', $line)) {
            if ($in_list) {
                $result .= '</div>';
                $in_list = false;
            }
            $result .= '<h2 class="main-title">' . htmlspecialchars($line) . '</h2>';
        }
        // Tiêu đề section (số + VIẾT HOA + emoji)
        else if (preg_match('/^(\d+)\.\s+([A-ZÀÁẠẢÃÂẦẤẬẨẪĂẰẮẶẲẴÈÉẸẺẼÊỀẾỆỂỄÌÍỊỈĨÒÓỌỎÕÔỒỐỘỔỖƠỜỚỢỞỠÙÚỤỦŨƯỪỨỰỬỮỲÝỴỶỸĐ\s]+)\s*(🔥|💪|⭐|✨|🎯|👟|🧠|💯|⚡|🌟|🤸|🧘|🦵|🦶|⚙️|🔄)\s*$/u', $line)) {
            if ($in_list) {
                $result .= '</div>';
                $in_list = false;
            }
            $result .= '<h3 class="section-title">' . htmlspecialchars($line) . '</h3>';
        }
        // Bullet points với ✅
        else if (preg_match('/^✅\s+(.+)$/u', $line, $matches)) {
            if (!$in_list) {
                $result .= '<div class="bullet-list">';
                $in_list = true;
            }
            // Chỉ lấy nội dung sau ✅ (không lấy ✅ từ $line để tránh trùng)
            $content = $matches[1]; // Nội dung sau dấu ✅
            $result .= '<div class="bullet-item"><span class="bullet-icon">✅</span> ' . htmlspecialchars($content) . '</div>';
        }
        // Call to Action với 👉
        else if (preg_match('/^👉\s+(.+)$/u', $line)) {
            if ($in_list) {
                $result .= '</div>';
                $in_list = false;
            }
            $result .= '<div class="cta-line">' . htmlspecialchars($line) . '</div>';
        }
        // Hashtags
        else if (preg_match('/^#\w+/', $line)) {
            if ($in_list) {
                $result .= '</div>';
                $in_list = false;
            }
            // Parse hashtags
            $line = preg_replace('/(#\w+)/', '<span class="hashtag">$1</span>', $line);
            $result .= '<div class="hashtags">' . $line . '</div>';
        }
        // Dòng thường
        else {
            if ($in_list) {
                $result .= '</div>';
                $in_list = false;
            }
            $result .= '<p>' . htmlspecialchars($line) . '</p>';
        }
    }
    
    if ($in_list) {
        $result .= '</div>';
    }
    
    return $result;
}
?>

<article class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Header -->
            <div class="mb-4">
                <?php if ($post['status'] !== 'published' && $is_logged_in): ?>
                    <div class="alert alert-warning mb-3">
                        <strong>⚠️ Preview Mode:</strong> Bài viết này đang ở trạng thái <strong><?= $post['status'] ?></strong> và chỉ bạn mới thấy được.
                        <?php if ($post['status'] === 'draft'): ?>
                            <a href="blog_edit.php?id=<?= $post['id'] ?>" class="alert-link ms-2">Chỉnh sửa →</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
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
                    <?php if ($post['status'] === 'draft'): ?>
                        <span class="badge bg-secondary">📝 Nháp</span>
                    <?php elseif ($post['status'] === 'archived'): ?>
                        <span class="badge bg-dark">📦 Lưu trữ</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Featured Image -->
            <?php if ($post['featured_image']): ?>
                <div class="position-relative mb-4">
                    <img id="post-image" src="<?= htmlspecialchars($post['featured_image']) ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($post['title']) ?>">
                    <div class="position-absolute top-0 end-0 m-2">
                        <button onclick="downloadImage()" class="btn btn-success btn-sm" title="Tải ảnh về">
                            💾 Tải ảnh về
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="mb-3 d-flex flex-column flex-sm-row gap-2 justify-content-end">
                <button onclick="copyContent()" class="btn btn-primary btn-sm">
                    📋 Copy nội dung
                </button>
                <?php if ($post['featured_image']): ?>
                    <button onclick="downloadImage()" class="btn btn-success btn-sm">
                        💾 Tải ảnh về
                    </button>
                <?php endif; ?>
                <small id="copy-feedback" class="ms-2 text-success align-self-center" style="display: none;">✅ Đã copy!</small>
            </div>
            
            <!-- Hidden original content for copying -->
            <textarea id="original-content" style="display: none;"><?= htmlspecialchars($post['content']) ?></textarea>
            
            <!-- Content -->
            <div class="blog-content facebook-style" style="font-size: 1.1rem; line-height: 1.8;">
                <?= parseMarkdown($post['content']) ?>
            </div>
            
            <!-- Footer -->
            <hr class="my-5">
            
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                <a href="blog.php" class="btn btn-outline-primary w-100 w-sm-auto">
                    ← Quay lại Blog
                </a>
                
                <button class="btn btn-outline-secondary w-100 w-sm-auto" onclick="window.print()">
                    🖨️ In bài viết
                </button>
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
                            <div class="col-12 col-sm-6 col-md-4">
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
/* Facebook-style Blog Content */
.facebook-style {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
    color: #1c1e21;
    line-height: 1.6;
}

/* Tiêu đề chính - VIẾT HOA */
.main-title {
    font-size: 1.8rem;
    font-weight: 800;
    text-align: center;
    color: #1c1e21;
    margin: 2rem 0 1.5rem 0;
    padding: 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white !important;
    border-radius: 16px;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.35);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    animation: fadeInScale 0.6s ease-out;
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Tiêu đề section */
.section-title {
    font-size: 1.3rem;
    font-weight: 700;
    color: #1c1e21;
    margin: 2rem 0 1rem 0;
    padding: 0.75rem 1rem;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white !important;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(240, 147, 251, 0.3);
    text-transform: uppercase;
}

/* Bullet list container */
.bullet-list {
    margin: 1rem 0 2rem 0;
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 12px;
    border-left: 4px solid #1877f2;
}

/* Bullet item */
.bullet-item {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    line-height: 1.7;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.bullet-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.bullet-item:last-child {
    margin-bottom: 0;
}

.bullet-icon {
    font-size: 1.3rem;
    margin-right: 0.5rem;
}

/* Paragraph */
.facebook-style p {
    margin-bottom: 1rem;
    line-height: 1.7;
    font-size: 1.05rem;
}

/* Call to Action */
.cta-line {
    font-weight: 700;
    font-size: 1.15rem;
    color: #1877f2;
    margin: 1.5rem 0;
    padding: 1rem 1.5rem;
    background: #e7f3ff;
    border-left: 5px solid #1877f2;
    border-radius: 8px;
}

/* Hashtags */
.hashtags {
    margin: 2rem 0;
    padding: 1.5rem;
    background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    border-radius: 12px;
    text-align: center;
    font-size: 1.1rem;
}

.hashtag {
    display: inline-block;
    color: #1877f2;
    font-weight: 700;
    margin: 0.3rem;
    padding: 0.5rem 1rem;
    background: white;
    border-radius: 20px;
    box-shadow: 0 2px 8px rgba(24, 119, 242, 0.15);
    transition: all 0.3s ease;
    cursor: pointer;
}

.hashtag:hover {
    color: white;
    background: #1877f2;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(24, 119, 242, 0.3);
}

/* Responsive */
@media (max-width: 768px) {
    .main-title {
        font-size: 1.4rem;
        padding: 1rem;
    }
    
    .section-title {
        font-size: 1.1rem;
        padding: 0.6rem 0.8rem;
    }
    
    .bullet-list {
        padding: 1rem;
    }
    
    .bullet-item {
        padding: 0.75rem;
        font-size: 0.95rem;
    }
    
    .cta-line {
        font-size: 1rem;
        padding: 0.75rem 1rem;
    }
    
    .hashtags {
        padding: 1rem;
    }
    
    .hashtag {
        font-size: 0.9rem;
        padding: 0.4rem 0.8rem;
    }
}

@media print {
    .btn, nav, .related {
        display: none !important;
    }
    
    .main-title, .section-title {
        background: none !important;
        color: #000 !important;
        box-shadow: none !important;
    }
}
</style>

<script>
function copyContent() {
    // Lấy nội dung gốc (text thuần + emoji)
    const contentTextarea = document.getElementById('original-content');
    const content = contentTextarea.value;
    
    // Copy vào clipboard
    navigator.clipboard.writeText(content).then(function() {
        // Hiển thị feedback
        const feedback = document.getElementById('copy-feedback');
        feedback.style.display = 'inline';
        feedback.textContent = '✅ Đã copy!';
        
        // Ẩn feedback sau 3 giây
        setTimeout(function() {
            feedback.style.display = 'none';
        }, 3000);
    }).catch(function(err) {
        // Fallback cho trình duyệt cũ
        contentTextarea.style.display = 'block';
        contentTextarea.select();
        document.execCommand('copy');
        contentTextarea.style.display = 'none';
        
        const feedback = document.getElementById('copy-feedback');
        feedback.style.display = 'inline';
        feedback.textContent = '✅ Đã copy!';
        setTimeout(function() {
            feedback.style.display = 'none';
        }, 3000);
    });
}

function downloadImage() {
    const img = document.getElementById('post-image');
    const imageSrc = img.src;
    
    // Tạo tên file từ tiêu đề bài viết
    const title = document.querySelector('h1').textContent;
    const fileName = title.substring(0, 50).replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.png';
    
    // Download bằng fetch API
    fetch(imageSrc)
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = fileName;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            // Show feedback
            const feedback = document.getElementById('copy-feedback');
            feedback.style.display = 'inline';
            feedback.textContent = '✅ Đã tải ảnh!';
            setTimeout(function() {
                feedback.style.display = 'none';
            }, 3000);
        })
        .catch(err => {
            alert('Lỗi khi tải ảnh. Vui lòng thử lại!');
        });
}
</script>

<?php include 'includes/footer.php'; ?>


