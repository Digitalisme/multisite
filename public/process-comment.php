<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log semua data POST
    error_log('POST Data: ' . print_r($_POST, true));
    
    $post_id = filter_var($_POST['post_id'], FILTER_VALIDATE_INT);
    $name = trim($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $content = trim($_POST['content']);
    
    // Debug: Log data yang sudah difilter
    error_log("Filtered Data:");
    error_log("post_id: $post_id");
    error_log("name: $name");
    error_log("email: $email");
    error_log("content: $content");
    
    if (!$post_id || !$name || !$email || !$content) {
        $_SESSION['error'] = 'Semua field harus diisi dengan benar';
        error_log('Validation failed');
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
    
    try {
        $db = Database::getInstance()->getConnection();
        
        // Debug: Verifikasi post_id ada di tabel posts
        $check = $db->prepare("SELECT id FROM posts WHERE id = ?");
        $check->execute([$post_id]);
        if (!$check->fetch()) {
            error_log("Post ID $post_id tidak ditemukan di database");
            throw new Exception("Invalid post_id");
        }
        
        // Simpan komentar
        $stmt = $db->prepare("
            INSERT INTO comments (post_id, name, email, content, status) 
            VALUES (?, ?, ?, ?, 'pending')
        ");
        
        $result = $stmt->execute([$post_id, $name, $email, $content]);
        
        // Debug: Log hasil eksekusi query
        error_log("Query result: " . ($result ? "success" : "failed"));
        error_log("Last Insert ID: " . $db->lastInsertId());
        
        if ($result) {
            $_SESSION['success'] = 'Komentar Anda sedang menunggu moderasi';
            error_log("Komentar berhasil disimpan");
        } else {
            throw new Exception("Failed to insert comment");
        }
    } catch (Exception $e) {
        error_log("Error saving comment: " . $e->getMessage());
        $_SESSION['error'] = 'Gagal menyimpan komentar: ' . $e->getMessage();
    }
    
    // Debug: Log redirect URL
    error_log("Redirecting to: " . $_SERVER['HTTP_REFERER']);
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

error_log("No POST request received");
header('Location: /'); 