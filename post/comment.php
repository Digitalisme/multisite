<?php
require_once '../config.php';

// Inisialisasi koneksi database di awal
$db = Database::getInstance()->getConnection();

// Validasi captcha
if (!isset($_SESSION['captcha_result']) || 
    !isset($_POST['captcha']) || 
    (int)$_POST['captcha'] !== $_SESSION['captcha_result']) {
    $_SESSION['error'] = "Verifikasi captcha salah.";
    header('Location: /post?id=' . $_POST['post_id']);
    exit;
}

// Hapus hasil captcha dari session
unset($_SESSION['captcha_result']);

// Cek method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

// Validasi input
$post_id = filter_var($_POST['post_id'], FILTER_VALIDATE_INT);
$content = trim($_POST['content']);

if (!$post_id || empty($content)) {
    $_SESSION['error'] = "Data tidak valid.";
    header('Location: /post?id=' . $_POST['post_id']);
    exit;
}

// Set user data
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("SELECT username as name, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $name = $user['name'];
    $email = $user['email'];
} else {
    $user_id = null;
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);

    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Format email tidak valid.";
        header('Location: /post?id=' . $_POST['post_id']);
        exit;
    }

    // Validasi nama
    if (empty($name)) {
        $_SESSION['error'] = "Nama harus diisi.";
        header('Location: /post?id=' . $_POST['post_id']);
        exit;
    }
}

try {
    // Cek apakah post masih ada dan aktif
    $stmt = $db->prepare("
        SELECT p.id, s.is_active 
        FROM posts p
        JOIN sites s ON p.site_id = s.id
        WHERE p.id = ? AND (s.is_active = 1 OR s.is_active IS NULL)
    ");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if (!$post) {
        $_SESSION['error'] = "Post tidak ditemukan atau tidak aktif.";
        header('Location: /');
        exit;
    }

    // Simpan komentar
    $stmt = $db->prepare("
        INSERT INTO comments (post_id, user_id, name, email, content, status, created_at)
        VALUES (?, ?, ?, ?, ?, 1, NOW())
    ");
    $stmt->execute([
        $post_id,
        ($user_id ?: NULL),  // Gunakan NULL jika user_id kosong
        $name,
        $email,
        $content
    ]);

    $_SESSION['success'] = "Komentar berhasil ditambahkan.";
} catch (Exception $e) {
    $_SESSION['error'] = "Gagal menambahkan komentar: " . $e->getMessage();
}

// Kembali ke halaman post
header('Location: /post?id=' . $post_id); 