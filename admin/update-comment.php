<?php
require_once '../config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment_id = filter_var($_POST['comment_id'], FILTER_VALIDATE_INT);
    $status = $_POST['status'];
    
    if (!$comment_id || !in_array($status, ['approved', 'spam'])) {
        header('Location: /admin/dashboard.php');
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Cek kepemilikan komentar melalui post dan site
    $stmt = $db->prepare("
        SELECT comments.*, posts.site_id, sites.user_id 
        FROM comments 
        JOIN posts ON comments.post_id = posts.id 
        JOIN sites ON posts.site_id = sites.id 
        WHERE comments.id = ?
    ");
    $stmt->execute([$comment_id]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($comment && $comment['user_id'] === $_SESSION['user_id']) {
        $stmt = $db->prepare("UPDATE comments SET status = ? WHERE id = ?");
        $stmt->execute([$status, $comment_id]);
        
        $_SESSION['success'] = 'Status komentar berhasil diperbarui';
        header('Location: /admin/manage-comments.php?site_id=' . $comment['site_id']);
        exit;
    }
}

header('Location: /admin/dashboard.php'); 