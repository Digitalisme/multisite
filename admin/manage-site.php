<?php
require_once '../config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Ambil ID site dari URL
$site_id = $_GET['id'] ?? null;
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

// Ambil daftar posting
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT * FROM posts WHERE site_id = ?";
$params = [$site_id];

if (!empty($search)) {
    $query .= " AND (title LIKE ? OR content LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$query .= " ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kelola Blog - <?= htmlspecialchars($site['site_name']) ?></title>
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
                            <?= htmlspecialchars($site['site_name']) ?>
                        </h1>
                        <span class="ml-3 text-sm text-gray-500">
                            <?= htmlspecialchars($site['subdomain']) ?>.<?= MAIN_DOMAIN ?>
                        </span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/admin/dashboard.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Search dan Action Buttons -->
        <div class="mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
            <form method="GET" action="" class="flex-1 flex w-full sm:w-auto sm:max-w-xs">
                <input type="hidden" name="id" value="<?= $site_id ?>">
                <div class="relative flex-1">
                    <input type="text" name="search" 
                           placeholder="Cari posting..." 
                           value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-l-md focus:ring-blue-500 focus:border-blue-500">
                </div>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-r-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-search"></i>
                </button>
            </form>

            <div class="flex space-x-4">
                <a href="/admin/create-post.php?site_id=<?= $site_id ?>" 
                   class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i> Tulis Posting
                </a>
                <a href="/admin/manage-categories.php?site_id=<?= $site_id ?>" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-folder mr-2"></i> Kategori
                </a>
                <a href="/admin/manage-comments.php?site_id=<?= $site_id ?>" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-comments mr-2"></i> Komentar
                </a>
            </div>
        </div>

        <!-- Daftar Posting -->
        <?php if (empty($posts)): ?>
            <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                <div class="text-gray-500">
                    <i class="fas fa-file-alt text-4xl mb-4"></i>
                    <p class="text-lg">Belum ada posting. Mulai menulis sekarang!</p>
                    <a href="/admin/create-post.php?site_id=<?= $site_id ?>" 
                       class="mt-4 inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i> Tulis Posting Pertama
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($posts as $post): ?>
                        <li class="p-6 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-lg font-medium text-gray-900 truncate">
                                        <?= htmlspecialchars($post['title']) ?>
                                    </h3>
                                    <div class="mt-1 flex items-center text-sm text-gray-500">
                                        <i class="far fa-calendar mr-2"></i>
                                        <?= date('d M Y H:i', strtotime($post['created_at'])) ?>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <a href="//<?= $site['subdomain'] ?>.<?= MAIN_DOMAIN ?>/post/<?= $post['id'] ?>" 
                                       target="_blank"
                                       class="text-gray-400 hover:text-gray-600" 
                                       title="Lihat">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                    <a href="/admin/edit-post.php?id=<?= $post['id'] ?>" 
                                       class="text-blue-400 hover:text-blue-600"
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="deletePost(<?= $post['id'] ?>)" 
                                            class="text-red-400 hover:text-red-600"
                                            title="Hapus">
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

    <script>
    function deletePost(postId) {
        if (confirm('Apakah Anda yakin ingin menghapus posting ini?')) {
            window.location.href = `/admin/delete-post.php?id=${postId}`;
        }
    }
    </script>
</body>
</html> 