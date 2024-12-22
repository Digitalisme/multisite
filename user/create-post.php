<?php
require_once '../config.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: /auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Ambil daftar situs milik user
$stmt = $db->prepare("SELECT id, site_name FROM sites WHERE user_id = ?");
$stmt->execute([$user_id]);
$sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($sites)) {
    header('Location: /user/create-site.php');
    exit;
}

// Ambil daftar kategori
$stmt = $db->prepare("SELECT * FROM categories WHERE site_id IN (SELECT id FROM sites WHERE user_id = ?)");
$stmt->execute([$user_id]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $site_id = filter_var($_POST['site_id'], FILTER_VALIDATE_INT);
    $selected_categories = $_POST['categories'] ?? [];
    $tags = trim($_POST['tags']);

    if (empty($title) || empty($content) || !$site_id) {
        $error = 'Semua field harus diisi';
    } else {
        // Verifikasi bahwa site_id adalah milik user
        $stmt = $db->prepare("SELECT id FROM sites WHERE id = ? AND user_id = ?");
        $stmt->execute([$site_id, $user_id]);
        if (!$stmt->fetch()) {
            $error = 'Invalid site selection';
        } else {
            try {
                $db->beginTransaction();

                // Simpan postingan
                $stmt = $db->prepare("INSERT INTO posts (title, content, site_id, user_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$title, $content, $site_id, $user_id]);
                $post_id = $db->lastInsertId();

                // Simpan kategori
                foreach ($selected_categories as $category_id) {
                    $stmt = $db->prepare("INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)");
                    $stmt->execute([$post_id, $category_id]);
                }

                // Simpan tags
                if (!empty($tags)) {
                    $tagsArray = array_map('trim', explode(',', $tags));
                    foreach ($tagsArray as $tag) {
                        if (!empty($tag)) {
                            $stmt = $db->prepare("INSERT INTO tags (name) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
                            $stmt->execute([$tag]);
                            $tag_id = $db->lastInsertId();
                            
                            $stmt = $db->prepare("INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                            $stmt->execute([$post_id, $tag_id]);
                        }
                    }
                }

                $db->commit();
                header('Location: /user/dashboard.php');
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Gagal membuat postingan: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Buat Postingan Baru</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#content',
            plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
            toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
            height: 500
        });
    </script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen p-6">
        <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-8">
            <h2 class="text-2xl font-bold mb-6">Buat Postingan Baru</h2>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-4">
                    <label for="site_id" class="block text-gray-700 text-sm font-bold mb-2">Pilih Situs</label>
                    <select name="site_id" id="site_id" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <?php foreach ($sites as $site): ?>
                            <option value="<?= $site['id'] ?>"><?= htmlspecialchars($site['site_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="title" class="block text-gray-700 text-sm font-bold mb-2">Judul</label>
                    <input type="text" name="title" id="title" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Masukkan judul postingan">
                </div>

                <div class="mb-4">
                    <label for="content" class="block text-gray-700 text-sm font-bold mb-2">Konten</label>
                    <textarea name="content" id="content" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                              rows="10"></textarea>
                </div>

                <?php if (!empty($categories)): ?>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Kategori</label>
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
                        </div>
                    </div>
                <?php endif; ?>

                <div class="mb-6">
                    <label for="tags" class="block text-gray-700 text-sm font-bold mb-2">Tags (pisahkan dengan koma)</label>
                    <input type="text" name="tags" id="tags"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="tag1, tag2, tag3">
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="/user/dashboard.php"
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Batal
                    </a>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Publish
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 