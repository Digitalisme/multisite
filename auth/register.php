<?php
require_once '../config.php';

// Proses form jika ada pengiriman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $name = trim($_POST['name']);
    $password = trim($_POST['password']);
    
    if (empty($username) || empty($email) || empty($name) || empty($password)) {
        $error = 'Semua field harus diisi';
    } else {
        // Simpan pengguna baru
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("INSERT INTO users (username, email, name, password) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $name, password_hash($password, PASSWORD_DEFAULT)]);
        
        header('Location: /auth/login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Registrasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white shadow-md rounded-lg p-8 w-full max-w-md">
            <h2 class="text-2xl font-bold mb-6 text-center">Registrasi</h2>
            <?php if (isset($error)): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="mb-4">
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                    <input type="text" name="username" id="username" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="email" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" id="name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" id="password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="flex justify-end">
                    <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Daftar
                    </button>
                </div>
            </form>
            <p class="mt-4 text-center text-sm text-gray-600">
                Sudah punya akun? <a href="/auth/login.php" class="text-blue-600 hover:text-blue-800">Masuk di sini</a>
            </p>
        </div>
    </div>
</body>
</html> 