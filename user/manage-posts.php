<?php
require_once '../config.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: /auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Ambil site_id dari URL
$site_id = filter_var($_GET['site_id'], FILTER_VALIDATE_INT);
if (!$site_id) {
    header('Location: /user/dashboard.php');
    exit;
}

// Verifikasi bahwa situs milik user yang sedang login
$stmt = $db->prepare("SELECT * FROM sites WHERE id = ? AND user_id = ?");
$stmt->execute([$site_id, $user_id]);
$site = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$site) {
    header('Location: /user/dashboard.php');
    exit;
}

// Ambil semua postingan untuk situs ini
$stmt = $db->prepare("
    SELECT p.*, 
           COUNT(DISTINCT c.id) as comment_count,
           GROUP_CONCAT(DISTINCT cat.name) as categories,
           GROUP_CONCAT(DISTINCT t.name) as tags
    FROM posts p
    LEFT JOIN comments c ON p.id = c.post_id
    LEFT JOIN post_categories pc ON p.id = pc.post_id
    LEFT JOIN categories cat ON pc.category_id = cat.id
    LEFT JOIN post_tags pt ON p.id = pt.post_id
    LEFT JOIN tags t ON pt.tag_id = t.id
    WHERE p.site_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$stmt->execute([$site_id]);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kelola Postingan - <?= htmlspecialchars($site['site_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <nav class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-bold">
                            Kelola Postingan - <?= htmlspecialchars($site['site_name']) ?>
                        </h1>
                    </div>
                    <div class="flex items-center">
                        <a href="/user/dashboard.php" class="text-gray-600 hover:text-gray-900">
                            <i class="fas fa-arrow-left mr-2"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0">
                <div class="flex justify-end mb-6">
                    <a href="/user/create-post.php?site_id=<?= $site_id ?>" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> Buat Postingan Baru
                    </a>
                </div>

                <?php if (empty($posts)): ?>
                    <div class="bg-white rounded-lg shadow-sm p-6 text-center text-gray-500">
                        Belum ada postingan untuk situs ini.
                    </div>
                <?php else: ?>
                    <div class="bg-white shadow overflow-hidden sm:rounded-md">
                        <ul class="divide-y divide-gray-200">
                            <?php foreach ($posts as $post): ?>
                                <li class="p-6">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1 min-w-0">
                                            <h3 class="text-lg font-medium text-gray-900">
                                                <?= htmlspecialchars($post['title']) ?>
                                            </h3>
                                            <div class="mt-1 text-sm text-gray-500">
                                                <p>
                                                    <i class="far fa-calendar mr-1"></i>
                                                    <?= date('d M Y', strtotime($post['created_at'])) ?>
                                                    <span class="mx-2">•</span>
                                                    <i class="far fa-comment mr-1"></i>
                                                    <?= $post['comment_count'] ?> komentar
                                                </p>
                                                <?php if ($post['categories']): ?>
                                                    <p class="mt-1">
                                                        <i class="fas fa-folder mr-1"></i>
                                                        <?= htmlspecialchars($post['categories']) ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if ($post['tags']): ?>
                                                    <p class="mt-1">
                                                        <i class="fas fa-tags mr-1"></i>
                                                        <?= htmlspecialchars($post['tags']) ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-4 ml-4">
                                            <a href="/user/edit-post.php?id=<?= $post['id'] ?>" 
                                               class="text-yellow-600 hover:text-yellow-800"
                                               title="Edit Postingan">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="deletePost(<?= $post['id'] ?>)" 
                                                    class="text-red-600 hover:text-red-800"
                                                    title="Hapus Postingan">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    function deletePost(postId) {
        if (confirm('Apakah Anda yakin ingin menghapus postingan ini?')) {
            window.location.href = `/user/delete-post.php?id=${postId}`;
        }
    }
    </script>
</body>
</html> 