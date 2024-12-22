<?php
require_once '../config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Ambil comment_id dari URL
$comment_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
$site_id = $_GET['site_id'] ?? null;

if ($comment_id) {
    $db = Database::getInstance()->getConnection();
    
    // Hapus komentar
    $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
}

// Redirect kembali ke halaman komentar
header('Location: /admin/manage-comments.php?site_id=' . $site_id);
exit;
?> 