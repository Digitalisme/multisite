<?php
require_once '../config.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: /auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Ambil ID situs dari URL
$site_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$site_id) {
    header('Location: /user/dashboard.php');
    exit;
}

// Verifikasi bahwa situs milik user yang sedang login
$stmt = $db->prepare("SELECT id FROM sites WHERE id = ? AND user_id = ?");
$stmt->execute([$site_id, $user_id]);
if (!$stmt->fetch()) {
    header('Location: /user/dashboard.php');
    exit;
}

try {
    $db->beginTransaction();

    // Ambil semua post_id dari situs ini
    $stmt = $db->prepare("SELECT id FROM posts WHERE site_id = ?");
    $stmt->execute([$site_id]);
    $post_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($post_ids)) {
        // Hapus semua tags dari postingan
        $placeholders = str_repeat('?,', count($post_ids) - 1) . '?';
        $stmt = $db->prepare("DELETE FROM post_tags WHERE post_id IN ($placeholders)");
        $stmt->execute($post_ids);

        // Hapus semua kategori dari postingan
        $stmt = $db->prepare("DELETE FROM post_categories WHERE post_id IN ($placeholders)");
        $stmt->execute($post_ids);

        // Hapus semua komentar dari postingan
        $stmt = $db->prepare("DELETE FROM comments WHERE post_id IN ($placeholders)");
        $stmt->execute($post_ids);

        // Hapus semua postingan
        $stmt = $db->prepare("DELETE FROM posts WHERE site_id = ?");
        $stmt->execute([$site_id]);
    }

    // Hapus semua kategori dari situs
    $stmt = $db->prepare("DELETE FROM categories WHERE site_id = ?");
    $stmt->execute([$site_id]);

    // Hapus situs
    $stmt = $db->prepare("DELETE FROM sites WHERE id = ?");
    $stmt->execute([$site_id]);

    $db->commit();
    header('Location: /user/dashboard.php');
} catch (Exception $e) {
    $db->rollBack();
    die('Gagal menghapus situs: ' . $e->getMessage());
} 