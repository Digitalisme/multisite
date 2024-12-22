<?php
require_once '../config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_id = filter_var($_POST['site_id'], FILTER_VALIDATE_INT);
    $site_name = trim($_POST['site_name']);
    $status = $_POST['status'];
    
    if (!$site_id || empty($site_name) || !in_array($status, ['active', 'inactive'])) {
        $_SESSION['error'] = 'Data tidak valid';
        header('Location: /admin/manage-site.php?id=' . $site_id);
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Cek kepemilikan blog
    $stmt = $db->prepare("SELECT id FROM sites WHERE id = ? AND user_id = ?");
    $stmt->execute([$site_id, $_SESSION['user_id']]);
    if (!$stmt->fetch()) {
        header('Location: /admin/dashboard.php');
        exit;
    }
    
    // Update pengaturan blog
    $stmt = $db->prepare("UPDATE sites SET site_name = ?, status = ? WHERE id = ?");
    $stmt->execute([$site_name, $status, $site_id]);
    
    $_SESSION['success'] = 'Pengaturan blog berhasil diperbarui';
    header('Location: /admin/manage-site.php?id=' . $site_id);
    exit;
}

header('Location: /admin/dashboard.php'); 