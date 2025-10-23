<?php
session_start();
require_once 'includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get post ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['flash_error'] = 'ID bài viết không hợp lệ!';
    header('Location: blog_admin.php');
    exit;
}

$post_id = (int)$_GET['id'];

// Check if post exists
$stmt = $conn->prepare("SELECT id, title, featured_image FROM blog_posts WHERE id = ?");
$stmt->bind_param("i", $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();
$stmt->close();

if (!$post) {
    $_SESSION['flash_error'] = 'Bài viết không tồn tại!';
    header('Location: blog_admin.php');
    exit;
}

// Delete featured image if exists
if ($post['featured_image']) {
    $image_path = __DIR__ . '/' . $post['featured_image'];
    if (file_exists($image_path)) {
        unlink($image_path);
    }
}

// Delete post from database
$stmt = $conn->prepare("DELETE FROM blog_posts WHERE id = ?");
$stmt->bind_param("i", $post_id);

if ($stmt->execute()) {
    $_SESSION['flash_success'] = 'Đã xóa bài viết "' . htmlspecialchars($post['title']) . '" thành công!';
} else {
    $_SESSION['flash_error'] = 'Có lỗi khi xóa bài viết: ' . $conn->error;
}

$stmt->close();
header('Location: blog_admin.php');
exit;
?>

