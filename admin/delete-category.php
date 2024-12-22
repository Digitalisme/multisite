<?php
require_once '../config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = filter_var($_POST['category_id'], FILTER_VALIDATE_INT);
    if (!$category_id) {
        header('Location: /admin/dashboard.php');
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Cek kepemilikan kategori melalui site
    $stmt = $db->prepare("
        SELECT categories.*, sites.user_id, sites.id as site_id 
        FROM categories 
        JOIN sites ON categories.site_id = sites.id 
        WHERE categories.id = ?
    ");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($category && $category['user_id'] === $_SESSION['user_id']) {
        // Hapus kategori
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$category_id]);
        
        $_SESSION['success'] = 'Kategori berhasil dihapus';
        header('Location: /admin/manage-categories.php?site_id=' . $category['site_id']);
        exit;
    }
}

header('Location: /admin/dashboard.php'); 