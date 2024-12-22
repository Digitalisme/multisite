<?php
require_once '../config.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Ambil data admin
$stmt = $db->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Statistik
$stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'user'");
$stmt->execute();
$total_users = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM sites");
$stmt->execute();
$total_sites = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM posts");
$stmt->execute();
$total_posts = $stmt->fetchColumn();

// Ambil daftar user terbaru
$stmt = $db->prepare("
    SELECT u.*, COUNT(s.id) as site_count 
    FROM users u 
    LEFT JOIN sites s ON u.id = s.user_id 
    WHERE u.role = 'user' 
    GROUP BY u.id 
    ORDER BY u.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil postingan terbaru dari semua situs
$stmt = $db->prepare("
    SELECT p.*, s.site_name, s.subdomain, u.username as author
    FROM posts p 
    JOIN sites s ON p.site_id = s.id 
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900">
                    Admin Dashboard
                </h1>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">
                        <i class="fas fa-user-shield mr-2"></i>
                        <?= htmlspecialchars($admin['username']) ?>
                    </span>
                    <a href="/auth/logout.php" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Statistics -->
        <div class="mb-8 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Total Users</p>
                        <p class="text-2xl font-semibold text-gray-800"><?= $total_users ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i class="fas fa-globe text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Total Sites</p>
                        <p class="text-2xl font-semibold text-gray-800"><?= $total_sites ?></p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i class="fas fa-file-alt text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Total Posts</p>
                        <p class="text-2xl font-semibold text-gray-800"><?= $total_posts ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <a href="/admin/manage-users.php" class="bg-blue-600 text-white p-6 rounded-lg shadow hover:bg-blue-700">
                    <i class="fas fa-users-cog text-3xl mb-2"></i>
                    <h3 class="text-lg font-semibold">Kelola Users</h3>
                    <p class="text-sm opacity-90">Lihat dan kelola semua pengguna</p>
                </a>
                <a href="/admin/manage-sites.php" class="bg-green-600 text-white p-6 rounded-lg shadow hover:bg-green-700">
                    <i class="fas fa-globe text-3xl mb-2"></i>
                    <h3 class="text-lg font-semibold">Kelola Sites</h3>
                    <p class="text-sm opacity-90">Lihat dan kelola semua situs</p>
                </a>
                <a href="/admin/manage-posts.php" class="bg-purple-600 text-white p-6 rounded-lg shadow hover:bg-purple-700">
                    <i class="fas fa-file-alt text-3xl mb-2"></i>
                    <h3 class="text-lg font-semibold">Kelola Posts</h3>
                    <p class="text-sm opacity-90">Lihat dan kelola semua postingan</p>
                </a>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">User Terbaru</h2>
                <a href="/admin/manage-users.php" class="text-blue-600 hover:text-blue-800">
                    Lihat Semua <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            <div class="divide-y divide-gray-200">
                <?php foreach ($recent_users as $user): ?>
                    <div class="p-6 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                <?= htmlspecialchars($user['username']) ?>
                            </h3>
                            <p class="text-sm text-gray-500">
                                <?= htmlspecialchars($user['email']) ?> • 
                                <?= $user['site_count'] ?> sites
                            </p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <a href="/admin/edit-user.php?id=<?= $user['id'] ?>" 
                               class="text-yellow-600 hover:text-yellow-800"
                               title="Edit User">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button onclick="deleteUser(<?= $user['id'] ?>)" 
                                    class="text-red-600 hover:text-red-800"
                                    title="Delete User">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Recent Posts -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-xl font-semibold text-gray-800">Postingan Terbaru</h2>
                <a href="/admin/manage-posts.php" class="text-blue-600 hover:text-blue-800">
                    Lihat Semua <i class="fas fa-arrow-right ml-2"></i>
                </a>
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
                                    By <?= htmlspecialchars($post['author']) ?> • 
                                    Di <?= htmlspecialchars($post['site_name']) ?> • 
                                    <?= date('d M Y', strtotime($post['created_at'])) ?>
                                </p>
                            </div>
                            <div class="flex space-x-3">
                                <a href="/admin/edit-post.php?id=<?= $post['id'] ?>" 
                                   class="text-yellow-600 hover:text-yellow-800"
                                   title="Edit Post">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="deletePost(<?= $post['id'] ?>)" 
                                        class="text-red-600 hover:text-red-800"
                                        title="Delete Post">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <script>
    function deleteUser(userId) {
        if (confirm('Apakah Anda yakin ingin menghapus user ini? Semua situs, postingan, dan komentar user ini akan ikut terhapus.')) {
            window.location.href = `/admin/delete-user.php?id=${userId}`;
        }
    }

    function deletePost(postId) {
        if (confirm('Apakah Anda yakin ingin menghapus postingan ini?')) {
            window.location.href = `/admin/delete-post.php?id=${postId}`;
        }
    }
    </script>
</body>
</html> 