<?php
require_once '../config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Ambil site_id dari URL
$site_id = $_GET['site_id'] ?? null;
if (!$site_id) {
    header('Location: /admin/dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Verifikasi kepemilikan site
$stmt = $db->prepare("SELECT * FROM sites WHERE id = ? AND user_id = ?");
$stmt->execute([$site_id, $_SESSION['user_id']]);
$site = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$site) {
    header('Location: /admin/dashboard.php');
    exit;
}

// Ambil daftar komentar dengan nama dan email
$stmt = $db->prepare("
    SELECT comments.*, posts.title AS post_title 
    FROM comments 
    JOIN posts ON comments.post_id = posts.id 
    WHERE posts.site_id = ? 
    ORDER BY comments.created_at DESC
");
$stmt->execute([$site_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kelola Komentar - <?= htmlspecialchars($site['site_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-gray-900">
                            Komentar untuk <?= htmlspecialchars($site['site_name']) ?>
                        </h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/admin/manage-site.php?id=<?= $site_id ?>" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900">Daftar Komentar</h2>
        </div>

        <?php if (empty($comments)): ?>
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <div class="text-gray-500">
                    <i class="fas fa-comments text-4xl mb-4"></i>
                    <p class="text-lg">Belum ada komentar.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($comments as $comment): ?>
                        <li class="p-6 hover:bg-gray-50 flex justify-between items-center">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-medium text-gray-900 truncate">
                                    <?= htmlspecialchars($comment['content']) ?>
                                </h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    <strong>Nama:</strong> <?= htmlspecialchars($comment['name']) ?> <br>
                                    <strong>Email:</strong> <?= htmlspecialchars($comment['email']) ?> <br>
                                    <strong>Postingan:</strong> <?= htmlspecialchars($comment['post_title']) ?>
                                </p>
                                <p class="mt-1 text-sm text-gray-500">
                                    <strong>Dikirim pada:</strong> <?= date('d M Y H:i', strtotime($comment['created_at'])) ?>
                                </p>
                            </div>
                            <div class="flex items-center space-x-3">
                                <a href="/admin/edit-comment.php?id=<?= $comment['id'] ?>" 
                                   class="text-blue-400 hover:text-blue-600" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="deleteComment(<?= $comment['id'] ?>)" 
                                        class="text-red-400 hover:text-red-600" title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function deleteComment(commentId) {
        if (confirm('Apakah Anda yakin ingin menghapus komentar ini?')) {
            window.location.href = `/admin/delete-comment.php?id=${commentId}&site_id=<?= $site_id ?>`;
        }
    }
    </script>
</body>
</html> 