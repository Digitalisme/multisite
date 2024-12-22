<?php
require_once '../config.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: /auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Ambil ID postingan dari URL
$post_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$post_id) {
    header('Location: /user/dashboard.php');
    exit;
}

// Verifikasi bahwa postingan milik user yang sedang login
$stmt = $db->prepare("
    SELECT p.id 
    FROM posts p 
    JOIN sites s ON p.site_id = s.id 
    WHERE p.id = ? AND s.user_id = ?
");
$stmt->execute([$post_id, $user_id]);
if (!$stmt->fetch()) {
    header('Location: /user/dashboard.php');
    exit;
}

try {
    $db->beginTransaction();

    // Hapus tags
    $stmt = $db->prepare("DELETE FROM post_tags WHERE post_id = ?");
    $stmt->execute([$post_id]);

    // Hapus kategori
    $stmt = $db->prepare("DELETE FROM post_categories WHERE post_id = ?");
    $stmt->execute([$post_id]);

    // Hapus komentar
    $stmt = $db->prepare("DELETE FROM comments WHERE post_id = ?");
    $stmt->execute([$post_id]);

    // Hapus postingan
    $stmt = $db->prepare("DELETE FROM posts WHERE id = ?");
    $stmt->execute([$post_id]);

    $db->commit();
    header('Location: /user/dashboard.php');
} catch (Exception $e) {
    $db->rollBack();
    die('Gagal menghapus postingan: ' . $e->getMessage());
} 