<?php
require_once '../config.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: /auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Ambil ID komentar dari URL
$comment_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$comment_id) {
    header('Location: /user/dashboard.php');
    exit;
}

// Verifikasi bahwa komentar ada di postingan milik user yang sedang login
$stmt = $db->prepare("
    SELECT c.id 
    FROM comments c
    JOIN posts p ON c.post_id = p.id
    JOIN sites s ON p.site_id = s.id
    WHERE c.id = ? AND s.user_id = ?
");
$stmt->execute([$comment_id, $user_id]);
if (!$stmt->fetch()) {
    header('Location: /user/dashboard.php');
    exit;
}

try {
    // Hapus komentar
    $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$comment_id]);
    
    header('Location: ' . $_SERVER['HTTP_REFERER'] ?? '/user/dashboard.php');
} catch (Exception $e) {
    die('Gagal menghapus komentar: ' . $e->getMessage());
} 