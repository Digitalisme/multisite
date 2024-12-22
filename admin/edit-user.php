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

// Ambil data user
$stmt = $db->prepare("
    SELECT u.*, 
           COUNT(DISTINCT s.id) as site_count,
           COUNT(DISTINCT p.id) as post_count
    FROM users u
    LEFT JOIN sites s ON u.id = s.user_id
    LEFT JOIN posts p ON s.id = p.site_id
    WHERE u.id = ? AND u.role = 'user'
    GROUP BY u.id
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: /admin/manage-users.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $new_password = trim($_POST['new_password']);
    $badge = $_POST['badge'];
    if (!in_array($badge, ['registered', 'verified', 'subscriber', 'staff'])) {
        $badge = 'registered';
    }
    
    $errors = [];

    // Validasi username
    if (empty($username)) {
        $errors[] = "Username harus diisi";
    } elseif ($username !== $user['username']) {
        // Cek apakah username sudah digunakan
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = "Username sudah digunakan";
        }
    }

    // Validasi email
    if (empty($email)) {
        $errors[] = "Email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    } elseif ($email !== $user['email']) {
        // Cek apakah email sudah digunakan
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $errors[] = "Email sudah digunakan";
        }
    }

    // Jika tidak ada error, update data
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Update data dasar
            $sql = "UPDATE users SET username = ?, email = ?, is_active = ? WHERE id = ?";
            $params = [$username, $email, $is_active, $user_id];

            // Jika ada password baru
            if (!empty($new_password)) {
                $sql = "UPDATE users SET username = ?, email = ?, is_active = ?, password = ? WHERE id = ?";
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $params = [$username, $email, $is_active, $hashed_password, $user_id];
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            $stmt = $db->prepare("UPDATE users SET badge = ? WHERE id = ?");
            $stmt->execute([$badge, $user_id]);

            $db->commit();
            $success = "Data user berhasil diupdate";
            
            // Refresh data user
            $stmt = $db->prepare("
                SELECT u.*, 
                       COUNT(DISTINCT s.id) as site_count,
                       COUNT(DISTINCT p.id) as post_count
                FROM users u
                LEFT JOIN sites s ON u.id = s.user_id
                LEFT JOIN posts p ON s.id = p.site_id
                WHERE u.id = ? AND u.role = 'user'
                GROUP BY u.id
            ");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = "Gagal mengupdate data: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-900">
                    Edit User
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
        <div class="bg-white shadow rounded-lg p-6">
            <!-- User Stats -->
            <div class="mb-6 grid grid-cols-2 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm text-blue-600">Total Sites</div>
                    <div class="text-2xl font-bold text-blue-800"><?= $user['site_count'] ?></div>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <div class="text-sm text-green-600">Total Posts</div>
                    <div class="text-2xl font-bold text-green-800"><?= $user['post_count'] ?></div>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="mb-4 bg-red-50 text-red-500 p-4 rounded-lg">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="mb-4 bg-green-50 text-green-500 p-4 rounded-lg">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="space-y-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700">Username</label>
                        <input type="text" 
                               name="username" 
                               id="username"
                               value="<?= htmlspecialchars($user['username']) ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" 
                               name="email" 
                               id="email"
                               value="<?= htmlspecialchars($user['email']) ?>"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700">
                            Password Baru (kosongkan jika tidak ingin mengubah)
                        </label>
                        <input type="password" 
                               name="new_password" 
                               id="new_password"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" 
                               name="is_active" 
                               id="is_active"
                               <?= ($user['is_active'] ?? 1) ? 'checked' : '' ?>
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">
                            User Aktif
                        </label>
                    </div>

                    <div class="mb-4">
                        <label for="badge" class="block text-sm font-medium text-gray-700">Badge</label>
                        <select name="badge" id="badge" 
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="registered" <?= $user['badge'] === 'registered' ? 'selected' : '' ?>>Registered</option>
                            <option value="verified" <?= $user['badge'] === 'verified' ? 'selected' : '' ?>>Verified</option>
                            <option value="subscriber" <?= $user['badge'] === 'subscriber' ? 'selected' : '' ?>>Subscriber</option>
                            <option value="staff" <?= $user['badge'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-4">
                        <a href="/admin/manage-users.php"
                           class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Batal
                        </a>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>
</body>
</html> 