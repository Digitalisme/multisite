<?php
require_once '../config.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Ambil ID user dan action dari URL
$user_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
$action = $_GET['action'];

if (!$user_id || !in_array($action, ['activate', 'deactivate'])) {
    header('Location: /admin/manage-users.php');
    exit;
}

// Cek apakah user valid dan bukan admin
$stmt = $db->prepare("SELECT id, username FROM users WHERE id = ? AND role = 'user'");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error'] = "User tidak ditemukan";
    header('Location: /admin/manage-users.php');
    exit;
}

try {
    // Update status user
    $is_active = ($action === 'activate') ? 1 : 0;
    $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->execute([$is_active, $user_id]);

    // Set pesan sukses
    $_SESSION['success'] = sprintf(
        "User %s berhasil %s",
        $user['username'],
        $action === 'activate' ? 'diaktifkan' : 'dinonaktifkan'
    );

    // Redirect ke halaman sebelumnya jika ada
    if (isset($_SERVER['HTTP_REFERER'])) {
        $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
        // Cek apakah referer adalah halaman view user
        if (strpos($referer, '/admin/view-user.php') === 0) {
            header("Location: /admin/view-user.php?id=" . $user_id);
            exit;
        }
    }

    // Default redirect ke manage users
    header('Location: /admin/manage-users.php');
} catch (Exception $e) {
    $_SESSION['error'] = "Gagal mengubah status user: " . $e->getMessage();
    header('Location: /admin/manage-users.php');
} 