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

// Ambil daftar media
$stmt = $db->prepare("SELECT * FROM media WHERE site_id = ? ORDER BY created_at DESC");
$stmt->execute([$site_id]);
$media_files = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pilih Media</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body { padding: 20px; }
        .media-grid { margin-top: 0; }
        .media-item { cursor: pointer; }
        .media-item:hover { opacity: 0.8; }
    </style>
</head>
<body>
    <div class="media-picker">
        <h2>Pilih Media</h2>
        
        <div class="media-grid">
            <?php foreach ($media_files as $media): ?>
                <div class="media-item" onclick="selectMedia('<?= htmlspecialchars($media['filepath']) ?>')">
                    <img src="<?= htmlspecialchars($media['filepath']) ?>" 
                         alt="<?= htmlspecialchars($media['filename']) ?>">
                    <div class="media-info">
                        <?= htmlspecialchars($media['filename']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
    function selectMedia(url) {
        window.opener.mediaPickerCallback(url);
        window.close();
    }
    </script>
</body>
</html> 