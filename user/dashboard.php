<?php
require_once '../config.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: /auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Ambil data user
$stmt = $db->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Ambil semua subdomain yang dimiliki oleh pengguna
$stmt = $db->prepare("SELECT * FROM sites WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil postingan terbaru dari semua subdomain pengguna
$stmt = $db->prepare("
    SELECT p.*, s.site_name, s.subdomain 
    FROM posts p 
    JOIN sites s ON p.site_id = s.id 
    WHERE s.user_id = ? 
    ORDER BY p.created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - <?= htmlspecialchars($user['username']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900">
                    Selamat datang, <?= htmlspecialchars($user['username']) ?>!
                </h1>
                <div class="flex items-center space-x-4">
                    <a href="/auth/logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Quick Actions -->
        <div class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="/user/create-site.php" class="bg-blue-600 text-white p-6 rounded-lg shadow hover:bg-blue-700">
                    <i class="fas fa-plus-circle text-3xl mb-2"></i>
                    <h3 class="text-lg font-semibold">Buat Subdomain Baru</h3>
                    <p class="text-sm opacity-90">Buat blog baru dengan subdomain Anda sendiri</p>
                </a>
                <?php if (!empty($sites)): ?>
                    <a href="/user/create-post.php" class="bg-green-600 text-white p-6 rounded-lg shadow hover:bg-green-700">
                        <i class="fas fa-edit text-3xl mb-2"></i>
                        <h3 class="text-lg font-semibold">Buat Postingan</h3>
                        <p class="text-sm opacity-90">Tulis postingan baru di blog Anda</p>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sites Section -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Blog Saya</h2>
            </div>
            <?php if (empty($sites)): ?>
                <div class="p-6 text-center text-gray-500">
                    Anda belum memiliki blog. Silakan buat blog baru dengan mengklik tombol "Buat Subdomain Baru".
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($sites as $site): ?>
                        <div class="p-6 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">
                                    <?= htmlspecialchars($site['site_name']) ?>
                                </h3>
                                <p class="text-sm text-gray-500">
                                    <?= htmlspecialchars($site['subdomain']) ?>.<?= DOMAIN ?>
                                </p>
                            </div>
                            <div class="flex items-center space-x-4">
                                <a href="http://<?= htmlspecialchars($site['subdomain']) ?>.<?= DOMAIN ?>" 
                                   target="_blank"
                                   class="text-blue-600 hover:text-blue-800"
                                   title="Kunjungi Blog">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                                <a href="/user/manage-posts.php?site_id=<?= $site['id'] ?>" 
                                   class="text-green-600 hover:text-green-800"
                                   title="Kelola Postingan">
                                    <i class="fas fa-file-alt"></i>
                                </a>
                                <a href="/user/edit-site.php?id=<?= $site['id'] ?>" 
                                   class="text-yellow-600 hover:text-yellow-800"
                                   title="Edit Blog">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="deleteSite(<?= $site['id'] ?>)" 
                                        class="text-red-600 hover:text-red-800"
                                        title="Hapus Blog">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Posts -->
        <?php if (!empty($recent_posts)): ?>
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800">Postingan Terbaru</h2>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($recent_posts as $post): ?>
                        <div class="p-6">
                            <div class="flex justify-between">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">
                                        <?= htmlspecialchars($post['title']) ?>
                                    </h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Di <?= htmlspecialchars($post['site_name']) ?> • 
                                        <?= date('d M Y', strtotime($post['created_at'])) ?>
                                    </p>
                                </div>
                                <div class="flex space-x-3">
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
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
    function deleteSite(siteId) {
        if (confirm('Apakah Anda yakin ingin menghapus blog ini? Semua postingan dan komentar akan ikut terhapus.')) {
            window.location.href = `/user/delete-site.php?id=${siteId}`;
        }
    }

    function deletePost(postId) {
        if (confirm('Apakah Anda yakin ingin menghapus postingan ini?')) {
            window.location.href = `/user/delete-post.php?id=${postId}`;
        }
    }
    </script>
</body>
</html> 