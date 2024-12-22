<?php
require_once '../config.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: /auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Ambil ID situs dari URL
$site_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$site_id) {
    header('Location: /user/dashboard.php');
    exit;
}

// Ambil data situs dan pastikan milik user yang sedang login
$stmt = $db->prepare("SELECT * FROM sites WHERE id = ? AND user_id = ?");
$stmt->execute([$site_id, $user_id]);
$site = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$site) {
    header('Location: /user/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_name = trim($_POST['site_name']);
    $subdomain = trim($_POST['subdomain']);

    if (empty($site_name) || empty($subdomain)) {
        $error = 'Nama situs dan subdomain harus diisi';
    } else {
        // Cek apakah subdomain sudah digunakan (kecuali oleh situs ini sendiri)
        $stmt = $db->prepare("SELECT id FROM sites WHERE subdomain = ? AND id != ?");
        $stmt->execute([$subdomain, $site_id]);
        if ($stmt->fetch()) {
            $error = 'Subdomain sudah digunakan';
        } else {
            // Update data situs
            $stmt = $db->prepare("UPDATE sites SET site_name = ?, subdomain = ? WHERE id = ? AND user_id = ?");
            if ($stmt->execute([$site_name, $subdomain, $site_id, $user_id])) {
                header('Location: /user/dashboard.php');
                exit;
            } else {
                $error = 'Gagal mengupdate situs';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Situs</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold mb-6">Edit Situs</h2>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-4">
                    <label for="site_name" class="block text-gray-700 text-sm font-bold mb-2">Nama Situs</label>
                    <input type="text" name="site_name" id="site_name" required
                           value="<?= htmlspecialchars($site['site_name']) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <div class="mb-6">
                    <label for="subdomain" class="block text-gray-700 text-sm font-bold mb-2">Subdomain</label>
                    <div class="flex items-center">
                        <input type="text" name="subdomain" id="subdomain" required
                               value="<?= htmlspecialchars($site['subdomain']) ?>"
                               class="w-full px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <span class="px-3 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-md text-gray-500">
                            .localhost
                        </span>
                    </div>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="/user/dashboard.php"
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 