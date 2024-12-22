<?php
$site = $current_site;
$pageTitle = $site['site_name'];

// Ambil posting untuk blog ini
$db = Database::getInstance()->getConnection();

// Cek apakah ada filter kategori
$category_slug = $_GET['category'] ?? null;
$category = null;

if ($category_slug) {
    // Ambil informasi kategori
    $stmt = $db->prepare("SELECT * FROM categories WHERE slug = ? AND site_id = ?");
    $stmt->execute([$category_slug, $site['id']]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($category) {
        $pageTitle = htmlspecialchars($category['name']) . ' - ' . $pageTitle;
        
        // Query untuk posting dalam kategori tertentu
        $stmt = $db->prepare("
            SELECT posts.*, 
                   GROUP_CONCAT(c.name) as category_names,
                   GROUP_CONCAT(c.slug) as category_slugs
            FROM posts 
            JOIN post_categories pc ON posts.id = pc.post_id
            JOIN categories c ON pc.category_id = c.id
            WHERE posts.site_id = ? 
            AND EXISTS (
                SELECT 1 FROM post_categories pc2 
                WHERE pc2.post_id = posts.id 
                AND pc2.category_id = ?
            )
            GROUP BY posts.id
            ORDER BY posts.created_at DESC
        ");
        $stmt->execute([$site['id'], $category['id']]);
    } else {
        // Kategori tidak ditemukan
        header("HTTP/1.0 404 Not Found");
        include '../views/404.php';
        exit;
    }
} else {
    // Query untuk semua posting
    $stmt = $db->prepare("
        SELECT posts.*, 
               GROUP_CONCAT(categories.name) as category_names,
               GROUP_CONCAT(categories.slug) as category_slugs
        FROM posts 
        LEFT JOIN post_categories ON posts.id = post_categories.post_id
        LEFT JOIN categories ON post_categories.category_id = categories.id
        WHERE posts.site_id = ? 
        GROUP BY posts.id
        ORDER BY posts.created_at DESC
    ");
    $stmt->execute([$site['id']]);
}

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<div class="max-w-4xl mx-auto">
    <?php if ($category): ?>
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                Kategori: <?= htmlspecialchars($category['name']) ?>
            </h1>
            <p class="text-gray-600">
                Menampilkan <?= count($posts) ?> posting dalam kategori ini
            </p>
        </div>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
        <div class="bg-white rounded-lg shadow-sm p-8 text-center">
            <div class="text-gray-500">
                <i class="fas fa-file-alt text-4xl mb-4"></i>
                <p class="text-lg">
                    <?= $category 
                        ? 'Belum ada posting dalam kategori ini.' 
                        : 'Belum ada posting.' 
                    ?>
                </p>
            </div>
        </div>
    <?php else: ?>
        <div class="space-y-8">
            <?php foreach ($posts as $post): ?>
                <article class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">
                    <div class="p-6">
                        <header class="mb-4">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                                <a href="/post/<?= $post['id'] ?>" class="hover:text-blue-600 transition-colors">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                            </h2>
                            <div class="flex items-center text-sm text-gray-600 space-x-4">
                                <time datetime="<?= $post['created_at'] ?>" class="flex items-center">
                                    <i class="far fa-calendar mr-2"></i>
                                    <?= date('d M Y', strtotime($post['created_at'])) ?>
                                </time>
                                <?php if ($post['category_names']): ?>
                                    <div class="flex items-center">
                                        <i class="far fa-folder mr-2"></i>
                                        <?php 
                                        $categories = explode(',', $post['category_names']);
                                        $slugs = explode(',', $post['category_slugs']);
                                        foreach (array_combine($slugs, $categories) as $slug => $name): 
                                        ?>
                                            <a href="/category/<?= $slug ?>" 
                                               class="text-blue-600 hover:text-blue-800 transition-colors
                                                      <?= $category_slug === $slug ? 'font-bold' : '' ?>">
                                                <?= htmlspecialchars($name) ?>
                                            </a>
                                            <?= $slug !== end($slugs) ? ', ' : '' ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </header>

                        <div class="prose prose-sm max-w-none mb-4 text-gray-600">
                            <?php
                            $excerpt = strip_tags($post['content']);
                            $excerpt = substr($excerpt, 0, 300);
                            $excerpt = substr($excerpt, 0, strrpos($excerpt, ' '));
                            echo $excerpt . '...';
                            ?>
                        </div>

                        <footer class="flex items-center justify-between">
                            <a href="/post/<?= $post['id'] ?>" 
                               class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium transition-colors">
                                Baca selengkapnya 
                                <i class="fas fa-arrow-right ml-2"></i>
                            </a>
                            
                            <div class="flex space-x-3 text-gray-400">
                                <?php
                                // Ambil jumlah komentar
                                $stmt = $db->prepare("SELECT COUNT(*) FROM comments WHERE post_id = ? AND status = 'approved'");
                                $stmt->execute([$post['id']]);
                                $commentCount = $stmt->fetchColumn();
                                ?>
                                <span class="flex items-center">
                                    <i class="far fa-comment mr-2"></i>
                                    <?= $commentCount ?>
                                </span>
                            </div>
                        </footer>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if (isset($totalPages) && $totalPages > 1): ?>
            <div class="mt-8 flex justify-center">
                <nav class="inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?><?= $category ? '&category=' . urlencode($category_slug) : '' ?>" 
                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium 
                                  <?= $currentPage === $i ? 'text-blue-600 border-blue-500' : 'text-gray-700 hover:bg-gray-50' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </nav>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?> 