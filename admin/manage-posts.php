<?php
require_once '../config.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Ambil site_id dari URL
$site_id = filter_var($_GET['site_id'], FILTER_VALIDATE_INT);
if (!$site_id) {
    header('Location: /admin/dashboard.php');
    exit;
}

// Ambil semua postingan untuk site tertentu
$stmt = $db->prepare("
    SELECT posts.*, users.username 
    FROM posts 
    JOIN users ON posts.user_id = users.id 
    WHERE posts.site_id = ? 
    ORDER BY posts.created_at DESC
");
$stmt->execute([$site_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Posts</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-gray-900">Manage Posts</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/auth/logout.php" class="text-gray-600 hover:text-gray-900">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold mb-4">Daftar Postingan</h2>
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <ul class="divide-y divide-gray-200">
                <?php if (empty($posts)): ?>
                    <li class="p-6 text-center text-gray-500">Belum ada postingan.</li>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <li class="p-6 flex justify-between items-center">
                            <div class="flex-1 min-w-0">
                                <h3 class="text-lg font-medium text-gray-900 truncate">
                                    <?= htmlspecialchars($post['title']) ?>
                                </h3>
                            </div>
                            <div class="flex items-center space-x-3">
                                <a href="/admin/edit-post.php?id=<?= $post['id'] ?>" class="text-yellow-400 hover:text-yellow-600" title="Edit Postingan">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="deletePost(<?= $post['id'] ?>)" class="text-red-400 hover:text-red-600" title="Hapus Postingan">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <script>
    function deletePost(postId) {
        if (confirm('Apakah Anda yakin ingin menghapus postingan ini?')) {
            window.location.href = `/admin/delete-post.php?id=${postId}`;
        }
    }
    </script>
</body>
</html> 