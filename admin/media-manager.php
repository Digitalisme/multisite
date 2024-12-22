<?php
require_once '../config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

$site_id = filter_var($_GET['site_id'], FILTER_VALIDATE_INT);
if (!$site_id) {
    header('Location: /admin/dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Cek kepemilikan blog
$stmt = $db->prepare("SELECT * FROM sites WHERE id = ? AND user_id = ?");
$stmt->execute([$site_id, $_SESSION['user_id']]);
$site = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$site) {
    header('Location: /admin/dashboard.php');
    exit;
}

// Handle upload file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['media'])) {
    $file = $_FILES['media'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (!in_array($file['type'], $allowed_types)) {
        $error = 'Tipe file tidak didukung. Hanya gambar JPG, PNG, dan GIF yang diizinkan.';
    } elseif ($file['size'] > 5242880) { // 5MB
        $error = 'Ukuran file terlalu besar. Maksimal 5MB.';
    } else {
        $upload_dir = '../uploads/' . $site_id . '/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $filename = uniqid() . '_' . $file['name'];
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            // Simpan info file ke database
            $stmt = $db->prepare("INSERT INTO media (site_id, filename, filepath) VALUES (?, ?, ?)");
            $stmt->execute([$site_id, $filename, '/uploads/' . $site_id . '/' . $filename]);
            
            $_SESSION['success'] = 'File berhasil diupload';
            header('Location: /admin/media-manager.php?site_id=' . $site_id);
            exit;
        } else {
            $error = 'Gagal mengupload file';
        }
    }
}

// Ambil daftar media
$stmt = $db->prepare("SELECT * FROM media WHERE site_id = ? ORDER BY created_at DESC");
$stmt->execute([$site_id]);
$media_files = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Media Manager - <?= htmlspecialchars($site['site_name']) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div>
                <h1>Media Manager</h1>
                <p class="site-url"><?= htmlspecialchars($site['subdomain']) ?>.<?= MAIN_DOMAIN ?></p>
            </div>
            <a href="/admin/manage-site.php?id=<?= $site_id ?>" class="btn">Kembali</a>
        </header>

        <main class="admin-content">
            <div class="media-uploader">
                <?php if (isset($error)): ?>
                    <div class="error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <div class="form-group">
                        <label>Upload File:</label>
                        <input type="file" name="media" accept="image/*" required>
                    </div>
                    <button type="submit" class="btn">Upload</button>
                </form>
            </div>

            <div class="media-grid">
                <?php foreach ($media_files as $media): ?>
                    <div class="media-item">
                        <img src="<?= htmlspecialchars($media['filepath']) ?>" 
                             alt="<?= htmlspecialchars($media['filename']) ?>">
                        <div class="media-actions">
                            <button class="btn btn-sm" onclick="copyUrl('<?= htmlspecialchars($media['filepath']) ?>')">
                                Copy URL
                            </button>
                            <form method="POST" action="/admin/delete-media.php" class="inline-form"
                                  onsubmit="return confirm('Yakin ingin menghapus file ini?')">
                                <input type="hidden" name="media_id" value="<?= $media['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <script>
    function copyUrl(url) {
        navigator.clipboard.writeText(window.location.origin + url)
            .then(() => alert('URL berhasil disalin!'))
            .catch(err => console.error('Gagal menyalin URL:', err));
    }
    </script>
</body>
</html> 