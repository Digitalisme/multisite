<?php
require_once '../config.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit;
}

// Ambil blog_id dari URL
$blog_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

if ($blog_id) {
    $db = Database::getInstance()->getConnection();
    
    // Hapus blog
    $stmt = $db->prepare("DELETE FROM sites WHERE id = ?");
    $stmt->execute([$blog_id]);
}

// Redirect kembali ke halaman dashboard admin
header('Location: /admin/dashboard.php');
exit;
?> 