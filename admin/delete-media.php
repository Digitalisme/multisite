<?php
require_once '../config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $media_id = filter_var($_POST['media_id'], FILTER_VALIDATE_INT);
    if (!$media_id) {
        header('Location: /admin/dashboard.php');
        exit;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Cek kepemilikan media
    $stmt = $db->prepare("
        SELECT media.*, sites.user_id, sites.id as site_id 
        FROM media 
        JOIN sites ON media.site_id = sites.id 
        WHERE media.id = ?
    ");
    $stmt->execute([$media_id]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($media && $media['user_id'] === $_SESSION['user_id']) {
        // Hapus file fisik
        $file_path = $_SERVER['DOCUMENT_ROOT'] . $media['filepath'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        // Hapus record dari database
        $stmt = $db->prepare("DELETE FROM media WHERE id = ?");
        $stmt->execute([$media_id]);
        
        $_SESSION['success'] = 'Media berhasil dihapus';
        header('Location: /admin/media-manager.php?site_id=' . $media['site_id']);
        exit;
    }
}

header('Location: /admin/dashboard.php'); 