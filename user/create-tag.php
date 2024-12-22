<?php
require_once '../config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Proses form jika ada pengiriman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tag_name = trim($_POST['tag_name']);
    
    if (empty($tag_name)) {
        $error = 'Nama tag harus diisi';
    } else {
        // Simpan tag baru
        $stmt = $db->prepare("INSERT INTO tags (name, user_id) VALUES (?, ?)");
        $stmt->execute([$tag_name, $_SESSION['user_id']]);
        
        header('Location: /user/dashboard.php'); // Redirect ke dashboard setelah berhasil
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Buat Tag Baru</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-gray-900">Buat Tag Baru</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/user/dashboard.php" class="text-gray-600 hover:text-gray-900">Kembali</a>
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
                    <label for="tag_name" class="block text-sm font-medium text-gray-700 mb-2">Nama Tag</label>
                    <input type="text" name="tag_name" id="tag_name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="window.location.href='/user/dashboard.php'"
                            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Buat Tag
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 