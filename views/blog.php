<?php
if (!isset($data['current_site'])) exit;
$current_site = $data['current_site'];
$posts = $data['posts'];
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($current_site['site_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 py-6 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold text-gray-900">
                <?= htmlspecialchars($current_site['site_name']) ?>
            </h1>
            <p class="mt-2 text-sm text-gray-600">
                By <?= htmlspecialchars($current_site['owner_name']) ?>
            </p>
        </div>
    </header>

    <!-- Di bawah header, tambahkan ini -->
    <?php if (isset($data['filter_type'])): ?>
        <div class="max-w-7xl mx-auto px-4 py-3 sm:px-6 lg:px-8">
            <div class="text-lg text-gray-600">
                <?php if ($data['filter_type'] === 'category'): ?>
                    Posts in category: 
                    <span class="font-semibold"><?= htmlspecialchars($data['filter_value']) ?></span>
                <?php else: ?>
                    Posts tagged with: 
                    <span class="font-semibold">#<?= htmlspecialchars($data['filter_value']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if (empty($posts)): ?>
            <div class="text-center text-gray-500 py-12">
                Belum ada postingan.
            </div>
        <?php else: ?>
            <div class="grid gap-6 lg:grid-cols-2">
                <?php foreach ($posts as $post): ?>
                    <article class="bg-white shadow rounded-lg overflow-hidden">
                        <div class="p-6">
                            <h2 class="text-xl font-semibold text-gray-900">
                                <?= htmlspecialchars($post['title']) ?>
                            </h2>
                            <p class="mt-2 text-sm text-gray-500">
                                By <?= htmlspecialchars($post['author']) ?> • 
                                <?= date('d M Y', strtotime($post['created_at'])) ?> •
                                <i class="fas fa-eye"></i> <?= number_format($post['total_views'] ?? 0) ?>
                            </p>
                            <div class="mt-4 text-gray-600">
                                <?= nl2br(htmlspecialchars(substr(strip_tags($post['content']), 0, 200))) ?>...
                            </div>
                            <div class="mt-4">
                                <?php if ($subdomain): ?>
                                    <!-- Jika mengakses dari subdomain -->
                                    <a href="//<?= DOMAIN ?>/post?id=<?= $post['id'] ?>" 
                                       class="text-blue-600 hover:text-blue-800">
                                        Baca selengkapnya <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                <?php else: ?>
                                    <!-- Jika mengakses dari domain utama -->
                                    <a href="/post?id=<?= $post['id'] ?>" 
                                       class="text-blue-600 hover:text-blue-800">
                                        Baca selengkapnya <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html> 