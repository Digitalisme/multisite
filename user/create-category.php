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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = 'Nama kategori harus diisi';
    } else {
        // Cek apakah kategori sudah ada untuk situs ini
        $stmt = $db->prepare("SELECT id FROM categories WHERE name = ? AND site_id = ?");
        $stmt->execute([$name, $site_id]);
        if ($stmt->fetch()) {
            $error = 'Kategori dengan nama tersebut sudah ada';
        } else {
            $stmt = $db->prepare("INSERT INTO categories (name, description, site_id) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $description, $site_id])) {
                header('Location: /user/manage-posts.php?site_id=' . $site_id);
                exit;
            } else {
                $error = 'Gagal membuat kategori';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Buat Kategori Baru - <?= htmlspecialchars($site['site_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold mb-6">Buat Kategori Baru</h2>
            <p class="text-gray-600 mb-6">
                Untuk situs: <?= htmlspecialchars($site['site_name']) ?>
            </p>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-4">
                    <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nama Kategori</label>
                    <input type="text" name="name" id="name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Masukkan nama kategori">
                </div>
                
                <div class="mb-6">
                    <label for="description" class="block text-gray-700 text-sm font-bold mb-2">Deskripsi (opsional)</label>
                    <textarea name="description" id="description"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                              rows="3"
                              placeholder="Masukkan deskripsi kategori"></textarea>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="/user/manage-posts.php?site_id=<?= $site_id ?>"
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Buat Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 