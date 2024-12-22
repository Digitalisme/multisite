<?php
require_once '../config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}

// Ambil site_id dari URL
$site_id = $_GET['site_id'] ?? null;
if (!$site_id) {
    header('Location: /admin/dashboard.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Verifikasi kepemilikan site
$stmt = $db->prepare("SELECT * FROM sites WHERE id = ? AND user_id = ?");
$stmt->execute([$site_id, $_SESSION['user_id']]);
$site = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$site) {
    header('Location: /admin/dashboard.php');
    exit;
}

// Ambil daftar kategori
$stmt = $db->prepare("SELECT * FROM categories WHERE site_id = ? ORDER BY name");
$stmt->execute([$site_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = $_POST['content']; // Ambil konten dari TinyMCE
    $selected_categories = $_POST['categories'] ?? [];

    if (empty($title) || empty($content)) {
        $error = 'Judul dan konten harus diisi';
    } else {
        // Simpan posting
        $stmt = $db->prepare("INSERT INTO posts (title, content, site_id) VALUES (?, ?, ?)");
        $stmt->execute([$title, $content, $site_id]);
        $post_id = $db->lastInsertId();

        // Simpan kategori
        foreach ($selected_categories as $category_id) {
            $stmt = $db->prepare("INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)");
            $stmt->execute([$post_id, $category_id]);
        }

        header('Location: /admin/manage-site.php?id=' . $site_id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tulis Posting Baru - <?= htmlspecialchars($site['site_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- TinyMCE -->
    <script src="https://cdn.tiny.cloud/1/z9ctuotyi3xjxerb76re78lky801uqerqnm3cyksre4r7bc5/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#content',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            height: 500,
            menubar: false,
            setup: function (editor) {
                editor.on('change', function () {
                    editor.save(); // Simpan konten ke textarea asli
                });
            }
        });
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <h1 class="text-xl font-bold text-gray-900">
                            Tulis Posting Baru
                        </h1>
                        <span class="ml-3 text-sm text-gray-500">
                            <?= htmlspecialchars($site['site_name']) ?>
                        </span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/admin/manage-site.php?id=<?= $site_id ?>" 
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
                <input type="hidden" name="site_id" value="<?= $site_id ?>">
                
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                        Judul Posting
                    </label>
                    <input type="text" name="title" id="title" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                           value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Kategori
                    </label>
                    <div class="space-y-2">
                        <?php foreach ($categories as $category): ?>
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="categories[]" 
                                       value="<?= $category['id'] ?>"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-gray-700">
                                    <?= htmlspecialchars($category['name']) ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                        <?php if (empty($categories)): ?>
                            <p class="text-gray-500 text-sm">
                                Belum ada kategori. 
                                <a href="/admin/manage-categories.php?site_id=<?= $site_id ?>" 
                                   class="text-blue-600 hover:text-blue-800">
                                    Buat kategori baru
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                        Konten
                    </label>
                    <textarea name="content" id="content" required
                              class="w-full"><?= isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '' ?></textarea>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="window.location.href='/admin/manage-site.php?id=<?= $site_id ?>'"
                            class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Simpan Posting
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 