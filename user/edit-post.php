<?php
require_once '../config.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: /auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$user_id = $_SESSION['user_id'];

// Ambil ID postingan dari URL
$post_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$post_id) {
    header('Location: /user/dashboard.php');
    exit;
}

// Ambil data postingan dan pastikan milik user yang sedang login
$stmt = $db->prepare("
    SELECT p.*, s.site_name 
    FROM posts p 
    JOIN sites s ON p.site_id = s.id 
    WHERE p.id = ? AND s.user_id = ?
");
$stmt->execute([$post_id, $user_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header('Location: /user/dashboard.php');
    exit;
}

// Ambil daftar kategori untuk site ini
$stmt = $db->prepare("SELECT * FROM categories WHERE site_id = ?");
$stmt->execute([$post['site_id']]);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil kategori yang sudah dipilih
$stmt = $db->prepare("SELECT category_id FROM post_categories WHERE post_id = ?");
$stmt->execute([$post_id]);
$selected_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Ambil tags yang sudah ada
$stmt = $db->prepare("
    SELECT t.name 
    FROM tags t 
    JOIN post_tags pt ON t.id = pt.tag_id 
    WHERE pt.post_id = ?
");
$stmt->execute([$post_id]);
$tags = implode(', ', $stmt->fetchAll(PDO::FETCH_COLUMN));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $new_categories = $_POST['categories'] ?? [];
    $new_tags = trim($_POST['tags']);

    if (empty($title) || empty($content)) {
        $error = 'Judul dan konten harus diisi';
    } else {
        try {
            $db->beginTransaction();

            // Update postingan
            $stmt = $db->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ?");
            $stmt->execute([$title, $content, $post_id]);

            // Hapus kategori lama
            $stmt = $db->prepare("DELETE FROM post_categories WHERE post_id = ?");
            $stmt->execute([$post_id]);

            // Simpan kategori baru
            foreach ($new_categories as $category_id) {
                $stmt = $db->prepare("INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)");
                $stmt->execute([$post_id, $category_id]);
            }

            // Hapus tag lama
            $stmt = $db->prepare("DELETE FROM post_tags WHERE post_id = ?");
            $stmt->execute([$post_id]);

            // Simpan tag baru
            if (!empty($new_tags)) {
                $tagsArray = array_map('trim', explode(',', $new_tags));
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
            $error = 'Gagal mengupdate postingan: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Postingan</title>
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
            <h2 class="text-2xl font-bold mb-6">Edit Postingan</h2>
            
            <?php if (isset($error)): ?>
                <div class="bg-red-50 text-red-500 p-4 rounded-lg mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Situs</label>
                    <div class="text-gray-600">
                        <?= htmlspecialchars($post['site_name']) ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="title" class="block text-gray-700 text-sm font-bold mb-2">Judul</label>
                    <input type="text" name="title" id="title" required
                           value="<?= htmlspecialchars($post['title']) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="mb-4">
                    <label for="content" class="block text-gray-700 text-sm font-bold mb-2">Konten</label>
                    <textarea name="content" id="content" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                              rows="10"><?= htmlspecialchars($post['content']) ?></textarea>
                </div>

                <?php if (!empty($categories)): ?>
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">Kategori</label>
                        <div class="space-y-2">
                            <?php foreach ($categories as $category): ?>
                                <label class="inline-flex items-center mr-4">
                                    <input type="checkbox" name="categories[]" 
                                           value="<?= $category['id'] ?>"
                                           <?= in_array($category['id'], $selected_categories) ? 'checked' : '' ?>
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
                           value="<?= htmlspecialchars($tags) ?>"
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
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 