<?php
require_once '../config.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = "WHERE 1=1";
$params = [];

if ($search) {
    $where_clause .= " AND (s.site_name LIKE ? OR s.subdomain LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Get total sites for pagination
$stmt = $db->prepare("
    SELECT COUNT(*)
    FROM sites s
    JOIN users u ON s.user_id = u.id
    " . $where_clause
);
$stmt->execute($params);
$total_sites = $stmt->fetchColumn();
$total_pages = ceil($total_sites / $limit);

// Get sites with statistics
$query = "
    SELECT s.*,
           u.username,
           u.email,
           COUNT(DISTINCT p.id) as post_count,
           COUNT(DISTINCT c.id) as comment_count,
           MAX(p.created_at) as last_post_date
    FROM sites s
    JOIN users u ON s.user_id = u.id
    LEFT JOIN posts p ON s.id = p.site_id
    LEFT JOIN comments c ON p.id = c.post_id
    {$where_clause}
    GROUP BY s.id
    ORDER BY s.created_at DESC
    LIMIT {$limit} OFFSET {$offset}
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$sites = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Kelola Sites - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900">
                    Kelola Sites
                </h1>
                <div class="flex items-center space-x-4">
                    <a href="/admin/dashboard.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-2"></i>Kembali ke Dashboard
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Search -->
        <div class="mb-6">
            <form action="" method="GET" class="flex gap-4">
                <div class="flex-1">
                    <input type="text" 
                           name="search" 
                           value="<?= htmlspecialchars($search) ?>"
                           placeholder="Cari nama situs, subdomain, atau username..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                </div>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    <i class="fas fa-search mr-2"></i>Cari
                </button>
            </form>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-4 bg-green-50 text-green-500 p-4 rounded-lg">
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-4 bg-red-50 text-red-500 p-4 rounded-lg">
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Sites Table -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Site Info
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Owner
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Statistik
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Aksi
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($sites as $site): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($site['site_name']) ?>
                                    </div>
                                    <div class="text-sm text-blue-600 hover:text-blue-800">
                                        <a href="http://<?= htmlspecialchars($site['subdomain']) ?>.<?= DOMAIN ?>" 
                                           target="_blank"
                                           class="flex items-center">
                                            <?= htmlspecialchars($site['subdomain']) ?>.<?= DOMAIN ?>
                                            <i class="fas fa-external-link-alt ml-1 text-xs"></i>
                                        </a>
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Created: <?= date('d M Y', strtotime($site['created_at'])) ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <?= htmlspecialchars($site['username']) ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?= htmlspecialchars($site['email']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm">
                                    <div class="text-gray-900"><?= $site['post_count'] ?> posts</div>
                                    <div class="text-gray-500"><?= $site['comment_count'] ?> comments</div>
                                    <?php if ($site['last_post_date']): ?>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Last post: <?= date('d M Y', strtotime($site['last_post_date'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= ($site['is_active'] ?? 1) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                    <?= ($site['is_active'] ?? 1) ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-3">
                                    <a href="/admin/view-site.php?id=<?= $site['id'] ?>" 
                                       class="text-blue-600 hover:text-blue-900"
                                       title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="/admin/edit-site.php?id=<?= $site['id'] ?>" 
                                       class="text-yellow-600 hover:text-yellow-900"
                                       title="Edit Site">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($site['is_active'] ?? 1): ?>
                                        <button onclick="deactivateSite(<?= $site['id'] ?>)"
                                                class="text-orange-600 hover:text-orange-900"
                                                title="Deactivate Site">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    <?php else: ?>
                                        <button onclick="activateSite(<?= $site['id'] ?>)"
                                                class="text-green-600 hover:text-green-900"
                                                title="Activate Site">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    <?php endif; ?>
                                    <button onclick="deleteSite(<?= $site['id'] ?>)"
                                            class="text-red-600 hover:text-red-900"
                                            title="Delete Site">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-6 flex justify-center">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page-1 ?><?= $search ? '&search='.urlencode($search) : '' ?>" 
                           class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?><?= $search ? '&search='.urlencode($search) : '' ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium <?= $i === $page ? 'text-blue-600 bg-blue-50' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page+1 ?><?= $search ? '&search='.urlencode($search) : '' ?>" 
                           class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </nav>
            </div>
        <?php endif; ?>
    </main>

    <script>
    function deleteSite(siteId) {
        if (confirm('Apakah Anda yakin ingin menghapus site ini? Semua postingan dan komentar akan ikut terhapus.')) {
            window.location.href = `/admin/delete-site.php?id=${siteId}`;
        }
    }

    function deactivateSite(siteId) {
        if (confirm('Apakah Anda yakin ingin menonaktifkan site ini?')) {
            window.location.href = `/admin/toggle-site.php?id=${siteId}&action=deactivate`;
        }
    }

    function activateSite(siteId) {
        if (confirm('Apakah Anda yakin ingin mengaktifkan kembali site ini?')) {
            window.location.href = `/admin/toggle-site.php?id=${siteId}&action=activate`;
        }
    }
    </script>
</body>
</html> 