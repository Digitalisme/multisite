<?php
require_once '../config.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit;
}

// Ambil post_id dari URL
$post_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
$site_id = $_GET['site_id'] ?? null;

if ($post_id) {
    $db = Database::getInstance()->getConnection();
    
    // Hapus postingan
    $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);
}

// Redirect kembali ke halaman daftar postingan
header('Location: /admin/manage-posts.php?site_id=' . $site_id);
exit;
?> 