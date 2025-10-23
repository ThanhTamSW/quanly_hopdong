<?php
/**
 * Script để làm sạch markdown syntax trong các bài viết cũ
 * Chạy file này 1 lần để xóa tất cả dấu **, *, ## trong content
 */

require_once 'includes/db.php';

echo "<h2>🧹 Làm sạch Markdown Syntax trong Blog Posts</h2>";

// Lấy tất cả bài viết
$result = $conn->query("SELECT id, title, content FROM blog_posts");

if (!$result) {
    die("Error: " . $conn->error);
}

$updated_count = 0;

while ($post = $result->fetch_assoc()) {
    $original_content = $post['content'];
    $cleaned_content = $original_content;
    
    // Loại bỏ markdown syntax
    $cleaned_content = str_replace('**', '', $cleaned_content); // Bold
    $cleaned_content = str_replace('__', '', $cleaned_content); // Bold alternative
    $cleaned_content = preg_replace('/\*([^*]+)\*/', '$1', $cleaned_content); // Italic
    $cleaned_content = preg_replace('/^#+\s+/m', '', $cleaned_content); // Headers (##, ###)
    
    // Chỉ update nếu có thay đổi
    if ($cleaned_content !== $original_content) {
        $stmt = $conn->prepare("UPDATE blog_posts SET content = ? WHERE id = ?");
        $stmt->bind_param("si", $cleaned_content, $post['id']);
        
        if ($stmt->execute()) {
            echo "✅ Updated: <strong>" . htmlspecialchars($post['title']) . "</strong><br>";
            $updated_count++;
        } else {
            echo "❌ Failed: " . htmlspecialchars($post['title']) . " - " . $stmt->error . "<br>";
        }
        
        $stmt->close();
    } else {
        echo "⏭️ Skipped (no changes): " . htmlspecialchars($post['title']) . "<br>";
    }
}

echo "<hr>";
echo "<h3>🎉 Hoàn thành!</h3>";
echo "<p>Đã cập nhật <strong>$updated_count</strong> bài viết.</p>";
echo "<p><a href='blog_admin.php'>← Quay lại Blog Admin</a></p>";

$conn->close();
?>

