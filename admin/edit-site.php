<?php
require_once '../config.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Ambil site_id dari URL
$site_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$site_id) {
    header('Location: /admin/dashboard.php');
    exit;
}

// Ambil data blog berdasarkan site_id
$stmt = $db->prepare("SELECT * FROM sites WHERE id = ?");
$stmt->execute([$site_id]);
$site = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$site) {
    header('Location: /admin/dashboard.php');
    exit;
}

// Proses form jika ada pengiriman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = trim($_POST['site_name']);
    $subdomain = trim($_POST['subdomain']);

    if (empty($site_name) || empty($subdomain)) {
        $error = 'Nama blog dan subdomain harus diisi';
    } else {
        // Update data blog
        $stmt = $db->prepare("UPDATE sites SET site_name = ?, subdomain = ? WHERE id = ?");
        $stmt->execute([$site_name, $subdomain, $site_id]);

        header('Location: /admin/dashboard.php'); // Redirect ke dashboard setelah berhasil
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Blog</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-gray-900">Edit Blog</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/admin/dashboard.php" class="text-gray-600 hover:text-gray-900">Kembali</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if (isset($error)): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <form method="POST" action="" class="p-6 space-y-6">
                <div>
                    <label for="site_name" class="block text-sm font-medium text-gray-700 mb-2">Nama Blog</label>
                    <input type="text" name="site_name" id="site_name" value="<?= htmlspecialchars($site['site_name']) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="subdomain" class="block text-sm font-medium text-gray-700 mb-2">Subdomain</label>
                    <input type="text" name="subdomain" id="subdomain" value="<?= htmlspecialchars($site['subdomain']) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="window.location.href='/admin/dashboard.php'"
                            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 