<?php
require_once '../config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Ambil comment_id dari URL
$comment_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$comment_id) {
    header('Location: /admin/manage-comments.php?site_id=' . $_GET['site_id']);
    exit;
}

$db = Database::getInstance()->getConnection();

// Ambil data komentar
$stmt = $db->prepare("SELECT comments.*, posts.title AS post_title FROM comments JOIN posts ON comments.post_id = posts.id WHERE comments.id = ?");
$stmt->execute([$comment_id]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comment) {
    header('Location: /admin/manage-comments.php?site_id=' . $_GET['site_id']);
    exit;
}

// Proses form jika ada pengiriman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content']);
    
    if (empty($content)) {
        $error = 'Konten komentar harus diisi';
    } else {
        // Update komentar
        $stmt = $db->prepare("UPDATE comments SET content = ? WHERE id = ?");
        $stmt->execute([$content, $comment_id]);

        // Redirect kembali ke halaman komentar
        header('Location: /admin/manage-comments.php?site_id=' . $_GET['site_id']);
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Komentar - <?= htmlspecialchars($comment['post_title']) ?></title>
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
                            Edit Komentar
                        </h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/admin/manage-comments.php?site_id=<?= $comment['site_id'] ?>" 
                       class="text-gray-600 hover:text-gray-900">
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
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                        Konten Komentar
                    </label>
                    <textarea name="content" id="content" required
                              class="w-full border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($comment['content']) ?></textarea>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="window.location.href='/admin/manage-comments.php?site_id=<?= $comment['site_id'] ?>'"
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