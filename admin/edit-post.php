<?php
require_once '../config.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /auth/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Ambil post_id dari URL
$post_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$post_id) {
    header('Location: /admin/manage-posts.php');
    exit;
}

// Ambil data postingan berdasarkan post_id
$stmt = $db->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header('Location: /admin/manage-posts.php');
    exit;
}

// Ambil site_id dari postingan
$site_id = $post['site_id'];

// Ambil daftar kategori
$stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil kategori yang sudah dipilih
$stmt = $db->prepare("SELECT category_id FROM post_categories WHERE post_id = ?");
$stmt->execute([$post_id]);
$selected_categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Proses form jika ada pengiriman
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $selected_categories = $_POST['categories'] ?? [];
    $other_category = trim($_POST['other_category']);
    $tags = !empty($_POST['tags']) ? array_map('trim', explode(',', $_POST['tags'])) : [];

    if (empty($title) || empty($content)) {
        $error = 'Judul dan konten harus diisi';
    } else {
        // Update data postingan
        $stmt = $db->prepare("UPDATE posts SET title = ?, content = ? WHERE id = ?");
        $stmt->execute([$title, $content, $post_id]);

        // Hapus kategori lama
        $stmt = $db->prepare("DELETE FROM post_categories WHERE post_id = ?");
        $stmt->execute([$post_id]);

        // Simpan kategori baru
        foreach ($selected_categories as $category_id) {
            $stmt = $db->prepare("INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)");
            $stmt->execute([$post_id, $category_id]);
        }

        // Jika ada kategori lainnya, simpan ke database
        if (!empty($other_category)) {
            $stmt = $db->prepare("INSERT INTO categories (name, site_id) VALUES (?, ?)");
            $stmt->execute([$other_category, $site_id]); // Menyertakan site_id
            $new_category_id = $db->lastInsertId();
            $stmt = $db->prepare("INSERT INTO post_categories (post_id, category_id) VALUES (?, ?)");
            $stmt->execute([$post_id, $new_category_id]);
        }

        // Bagian penanganan tags
        if (!empty($tags)) {
            $db->beginTransaction();
            
            try {
                // Hapus tag lama
                $stmt = $db->prepare("DELETE pt FROM post_tags pt 
                                     JOIN tags t ON pt.tag_id = t.id 
                                     WHERE pt.post_id = ? AND t.site_id = ?");
                $stmt->execute([$post_id, $site_id]);

                // Proses setiap tag
                foreach ($tags as $tag_name) {
                    $tag_name = trim($tag_name);
                    if (empty($tag_name)) continue;

                    // Cek apakah tag sudah ada untuk site ini
                    $stmt = $db->prepare("SELECT id FROM tags WHERE name = ? AND site_id = ?");
                    $stmt->execute([$tag_name, $site_id]);
                    $tag_id = $stmt->fetchColumn();

                    // Jika tag belum ada, buat baru
                    if (!$tag_id) {
                        $stmt = $db->prepare("INSERT INTO tags (name, site_id) VALUES (?, ?)");
                        $stmt->execute([$tag_name, $site_id]);
                        $tag_id = $db->lastInsertId();
                    }

                    // Hubungkan tag dengan post
                    $stmt = $db->prepare("INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)");
                    $stmt->execute([$post_id, $tag_id]);
                }

                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
        }

        header('Location: /admin/manage-posts.php?site_id=' . $site_id); // Redirect ke halaman manage posts setelah berhasil
        exit;
    }
}

// Ambil tags yang sudah ada untuk ditampilkan di form
$stmt = $db->prepare("
    SELECT GROUP_CONCAT(t.name) as tags
    FROM tags t
    JOIN post_tags pt ON t.id = pt.tag_id
    WHERE pt.post_id = ?
    GROUP BY pt.post_id
");
$stmt->execute([$post_id]);
$existing_tags = $stmt->fetchColumn() ?: '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Postingan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.tiny.cloud/1/z9ctuotyi3xjxerb76re78lky801uqerqnm3cyksre4r7bc5/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#content',
            plugins: 'link image code lists table',
            toolbar: 'undo redo | styleselect | bold italic | link image | bullist numlist | table | code',
            height: 300,
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
                        <h1 class="text-xl font-bold text-gray-900">Edit Postingan</h1>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/admin/manage-posts.php?site_id=<?= $post['site_id'] ?>" class="text-gray-600 hover:text-gray-900">Kembali</a>
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
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Judul</label>
                    <input type="text" name="title" id="title" value="<?= htmlspecialchars($post['title']) ?>" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Konten</label>
                    <textarea name="content" id="content" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"><?= htmlspecialchars($post['content']) ?></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Kategori</label>
                    <div class="space-y-2">
                        <?php foreach ($categories as $category): ?>
                            <label class="inline-flex items-center mr-4">
                                <input type="checkbox" name="categories[]" 
                                       value="<?= $category['id'] ?>"
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                                       <?= in_array($category['id'], $selected_categories) ? 'checked' : '' ?>>
                                <span class="ml-2 text-gray-700">
                                    <?= htmlspecialchars($category['name']) ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                        <div>
                            <label for="other_category" class="block text-sm font-medium text-gray-700 mb-2">Kategori Lainnya</label>
                            <input type="text" name="other_category" id="other_category" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" 
                                   placeholder="Masukkan kategori baru jika tidak ada di atas">
                        </div>
                        <?php if (empty($categories)): ?>
                            <p class="text-gray-500 text-sm">
                                Belum ada kategori. 
                                <a href="/admin/create-category.php?site_id=<?= $post['site_id'] ?>" 
                                   class="text-blue-600 hover:text-blue-800">
                                   Buat kategori baru
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <label for="tags" class="block text-sm font-medium text-gray-700 mb-2">Tags (pisahkan dengan koma)</label>
                    <input type="text" name="tags" id="tags" 
                           value="<?= htmlspecialchars($existing_tags) ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" 
                           placeholder="Tag1, Tag2, Tag3">
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="window.location.href='/admin/manage-posts.php?site_id=<?= $post['site_id'] ?>'"
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