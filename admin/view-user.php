<?php
require_once '../config.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Ambil ID user dari URL
$user_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$user_id) {
    header('Location: /admin/manage-users.php');
    exit;
}

// Ambil data user dan statistik
$stmt = $db->prepare("
    SELECT u.*, 
           COUNT(DISTINCT s.id) as site_count,
           COUNT(DISTINCT p.id) as post_count,
           COUNT(DISTINCT c.id) as comment_count
    FROM users u
    LEFT JOIN sites s ON u.id = s.user_id
    LEFT JOIN posts p ON s.id = p.site_id
    LEFT JOIN comments c ON p.id = c.post_id
    WHERE u.id = ? AND u.role = 'user'
    GROUP BY u.id
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: /admin/manage-users.php');
    exit;
}

// Ambil daftar situs user
$stmt = $db->prepare("
    SELECT s.*, 
           COUNT(DISTINCT p.id) as post_count,
           COUNT(DISTINCT c.id) as comment_count
    FROM sites s
    LEFT JOIN posts p ON s.id = p.site_id
    LEFT JOIN comments c ON p.id = c.post_id
    WHERE s.user_id = ?
    GROUP BY s.id
    ORDER BY s.created_at DESC
");
$stmt->execute([$user_id]);
$sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil postingan terbaru user
$stmt = $db->prepare("
    SELECT p.*, s.site_name
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
    <title>Detail User - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900">
                    Detail User
                </h1>
                <div class="flex items-center space-x-4">
                    <a href="/admin/manage-users.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- User Info Card -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">
                        <?= htmlspecialchars($user['username']) ?>
                    </h2>
                    <p class="text-gray-600"><?= htmlspecialchars($user['email']) ?></p>
                    <p class="text-sm text-gray-500 mt-1">
                        Bergabung: <?= date('d M Y', strtotime($user['created_at'])) ?>
                    </p>
                </div>
                <div class="flex space-x-2">
                    <a href="/admin/edit-user.php?id=<?= $user['id'] ?>" 
                       class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">
                        <i class="fas fa-edit mr-2"></i>Edit
                    </a>
                    <?php if ($user['is_active'] ?? 1): ?>
                        <button onclick="deactivateUser(<?= $user['id'] ?>)"
                                class="px-4 py-2 bg-orange-600 text-white rounded-md hover:bg-orange-700">
                            <i class="fas fa-ban mr-2"></i>Nonaktifkan
                        </button>
                    <?php else: ?>
                        <button onclick="activateUser(<?= $user['id'] ?>)"
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            <i class="fas fa-check-circle mr-2"></i>Aktifkan
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- User Stats -->
            <div class="grid grid-cols-3 gap-4 mt-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm text-blue-600">Total Sites</div>
                    <div class="text-2xl font-bold text-blue-800"><?= $user['site_count'] ?></div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm text-green-600">Total Posts</div>
                    <div class="text-2xl font-bold text-green-800"><?= $user['post_count'] ?></div>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <div class="text-sm text-purple-600">Total Comments</div>
                    <div class="text-2xl font-bold text-purple-800"><?= $user['comment_count'] ?></div>
                </div>
            </div>
        </div>

        <!-- Sites List -->
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Daftar Blog</h3>
            <?php if (empty($sites)): ?>
                <p class="text-gray-500">User belum memiliki blog.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($sites as $site): ?>
                        <div class="border rounded-lg p-4">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900">
                                        <?= htmlspecialchars($site['site_name']) ?>
                                    </h4>
                                    <p class="text-sm text-gray-500">
                                        <?= htmlspecialchars($site['subdomain']) ?>.<?= DOMAIN ?>
                                    </p>
                                    <div class="mt-2 text-sm text-gray-600">
                                        <?= $site['post_count'] ?> posts • 
                                        <?= $site['comment_count'] ?> comments
                                    </div>
                                </div>
                                <a href="http://<?= htmlspecialchars($site['subdomain']) ?>.<?= DOMAIN ?>" 
                                   target="_blank"
                                   class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Posts -->
        <div class="bg-white shadow rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Postingan Terbaru</h3>
            <?php if (empty($recent_posts)): ?>
                <p class="text-gray-500">User belum memiliki postingan.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($recent_posts as $post): ?>
                        <div class="border rounded-lg p-4">
                            <h4 class="text-lg font-medium text-gray-900">
                                <?= htmlspecialchars($post['title']) ?>
                            </h4>
                            <p class="text-sm text-gray-500 mt-1">
                                Di <?= htmlspecialchars($post['site_name']) ?> • 
                                <?= date('d M Y', strtotime($post['created_at'])) ?>
                            </p>
                            <p class="text-gray-600 mt-2">
                                <?= substr(strip_tags($post['content']), 0, 150) ?>...
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
    function deactivateUser(userId) {
        if (confirm('Apakah Anda yakin ingin menonaktifkan user ini?')) {
            window.location.href = `/admin/toggle-user.php?id=${userId}&action=deactivate`;
        }
    }

    function activateUser(userId) {
        if (confirm('Apakah Anda yakin ingin mengaktifkan kembali user ini?')) {
            window.location.href = `/admin/toggle-user.php?id=${userId}&action=activate`;
        }
    }
    </script>
</body>
</html> 