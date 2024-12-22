<?php
require_once '../config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Proses form jika ada pengiriman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = trim($_POST['site_name']);
    $subdomain = trim($_POST['subdomain']);
    
    if (empty($site_name) || empty($subdomain)) {
        $error = 'Nama situs dan subdomain harus diisi';
    } else {
        // Simpan situs baru
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO sites (site_name, subdomain, user_id) VALUES (?, ?, ?)");
        $stmt->execute([$site_name, $subdomain, $_SESSION['user_id']]);
        
        header('Location: /admin/manage-site.php?id=' . $db->lastInsertId());
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Buat Situs Baru</title>
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
                            Buat Situs Baru
                        </h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/admin/dashboard.php" class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-arrow-left mr-2"></i> Kembali
                    </a>
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
                    <label for="site_name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nama Situs
                    </label>
                    <input type="text" name="site_name" id="site_name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="subdomain" class="block text-sm font-medium text-gray-700 mb-2">
                        Subdomain
                    </label>
                    <input type="text" name="subdomain" id="subdomain" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="window.location.href='/admin/dashboard.php'"
                            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Buat Situs
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 