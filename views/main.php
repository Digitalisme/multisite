<?php
require_once '../config.php';

$pageTitle = 'Homepage';
ob_start();

// Definisi badge di bagian atas file atau sebelum digunakan
$badge_colors = [
    'verified' => 'text-blue-500',
    'staff' => 'text-yellow-500', 
    'subscriber' => 'text-purple-500',
    'registered' => 'text-gray-400'
];

$badge_icons = [
    'verified' => '<svg viewBox="0 0 22 22" class="w-4 h-4 inline-block" fill="currentColor"><path d="M20.396 11c-.018-.646-.215-1.275-.57-1.816-.354-.54-.852-.972-1.438-1.246.223-.607.27-1.264.14-1.897-.131-.634-.437-1.218-.882-1.687-.47-.445-1.053-.75-1.687-.882-.633-.13-1.29-.083-1.897.14-.273-.587-.704-1.086-1.245-1.44S11.647 1.62 11 1.604c-.646.017-1.273.213-1.813.568s-.969.854-1.24 1.44c-.608-.223-1.267-.272-1.902-.14-.635.13-1.22.436-1.69.882-.445.47-.749 1.055-.878 1.688-.13.633-.08 1.29.144 1.896-.587.274-1.087.705-1.443 1.245-.356.54-.555 1.17-.574 1.817.02.647.218 1.276.574 1.817.356.54.856.972 1.443 1.245-.224.606-.274 1.263-.144 1.896.13.634.433 1.218.877 1.688.47.443 1.054.747 1.687.878.633.132 1.29.084 1.897-.136.274.586.705 1.084 1.246 1.439.54.354.117.551 1.816.569.647-.016 1.276-.213 1.817-.567s.972-.854 1.245-1.44c.604.239 1.266.296 1.903.164.636-.132 1.22-.447 1.68-.907.46-.46.776-1.044.908-1.681s.075-1.299-.165-1.903c.586-.274 1.084-.705 1.439-1.246.354-.54.551-1.17.569-1.816zM9.662 14.85l-3.429-3.428 1.293-1.302 2.072 2.072 4.4-4.794 1.347 1.246z"/></svg>',
    'staff' => '<svg viewBox="0 0 22 22" class="w-4 h-4 inline-block" fill="currentColor"><path d="M20.396 11c-.018-.646-.215-1.275-.57-1.816-.354-.54-.852-.972-1.438-1.246.223-.607.27-1.264.14-1.897-.131-.634-.437-1.218-.882-1.687-.47-.445-1.053-.75-1.687-.882-.633-.13-1.29-.083-1.897.14-.273-.587-.704-1.086-1.245-1.44S11.647 1.62 11 1.604c-.646.017-1.273.213-1.813.568s-.969.854-1.24 1.44c-.608-.223-1.267-.272-1.902-.14-.635.13-1.22.436-1.69.882-.445.47-.749 1.055-.878 1.688-.13.633-.08 1.29.144 1.896-.587.274-1.087.705-1.443 1.245-.356.54-.555 1.17-.574 1.817.02.647.218 1.276.574 1.817.356.54.856.972 1.443 1.245-.224.606-.274 1.263-.144 1.896.13.634.433 1.218.877 1.688.47.443 1.054.747 1.687.878.633.132 1.29.084 1.897-.136.274.586.705 1.084 1.246 1.439.54.354.117.551 1.816.569.647-.016 1.276-.213 1.817-.567s.972-.854 1.245-1.44c.604.239 1.266.296 1.903.164.636-.132 1.22-.447 1.68-.907.46-.46.776-1.044.908-1.681s.075-1.299-.165-1.903c.586-.274 1.084-.705 1.439-1.246.354-.54.551-1.17.569-1.816zM9.662 14.85l-3.429-3.428 1.293-1.302 2.072 2.072 4.4-4.794 1.347 1.246z"/></svg>',
    'subscriber' => '<svg viewBox="0 0 22 22" class="w-4 h-4 inline-block" fill="currentColor"><path d="M20.396 11c-.018-.646-.215-1.275-.57-1.816-.354-.54-.852-.972-1.438-1.246.223-.607.27-1.264.14-1.897-.131-.634-.437-1.218-.882-1.687-.47-.445-1.053-.75-1.687-.882-.633-.13-1.29-.083-1.897.14-.273-.587-.704-1.086-1.245-1.44S11.647 1.62 11 1.604c-.646.017-1.273.213-1.813.568s-.969.854-1.24 1.44c-.608-.223-1.267-.272-1.902-.14-.635.13-1.22.436-1.69.882-.445.47-.749 1.055-.878 1.688-.13.633-.08 1.29.144 1.896-.587.274-1.087.705-1.443 1.245-.356.54-.555 1.17-.574 1.817.02.647.218 1.276.574 1.817.356.54.856.972 1.443 1.245-.224.606-.274 1.263-.144 1.896.13.634.433 1.218.877 1.688.47.443 1.054.747 1.687.878.633.132 1.29.084 1.897-.136.274.586.705 1.084 1.246 1.439.54.354.117.551 1.816.569.647-.016 1.276-.213 1.817-.567s.972-.854 1.245-1.44c.604.239 1.266.296 1.903.164.636-.132 1.22-.447 1.68-.907.46-.46.776-1.044.908-1.681s.075-1.299-.165-1.903c.586-.274 1.084-.705 1.439-1.246.354-.54.551-1.17.569-1.816zM9.662 14.85l-3.429-3.428 1.293-1.302 2.072 2.072 4.4-4.794 1.347 1.246z"/></svg>',
    'registered' => '<svg viewBox="0 0 22 22" class="w-4 h-4 inline-block" fill="currentColor"><path d="M20.396 11c-.018-.646-.215-1.275-.57-1.816-.354-.54-.852-.972-1.438-1.246.223-.607.27-1.264.14-1.897-.131-.634-.437-1.218-.882-1.687-.47-.445-1.053-.75-1.687-.882-.633-.13-1.29-.083-1.897.14-.273-.587-.704-1.086-1.245-1.44S11.647 1.62 11 1.604c-.646.017-1.273.213-1.813.568s-.969.854-1.24 1.44c-.608-.223-1.267-.272-1.902-.14-.635.13-1.22.436-1.69.882-.445.47-.749 1.055-.878 1.688-.13.633-.08 1.29.144 1.896-.587.274-1.087.705-1.443 1.245-.356.54-.555 1.17-.574 1.817.02.647.218 1.276.574 1.817.356.54.856.972 1.443 1.245-.224.606-.274 1.263-.144 1.896.13.634.433 1.218.877 1.688.47.443 1.054.747 1.687.878.633.132 1.29.084 1.897-.136.274.586.705 1.084 1.246 1.439.54.354.117.551 1.816.569.647-.016 1.276-.213 1.817-.567s.972-.854 1.245-1.44c.604.239 1.266.296 1.903.164.636-.132 1.22-.447 1.68-.907.46-.46.776-1.044.908-1.681s.075-1.299-.165-1.903c.586-.274 1.084-.705 1.439-1.246.354-.54.551-1.17.569-1.816zM9.662 14.85l-3.429-3.428 1.293-1.302 2.072 2.072 4.4-4.794 1.347 1.246z"/></svg>'
];

$badge_tooltips = [
    'verified' => 'Verified User',
    'staff' => 'Staff Member',
    'subscriber' => 'Premium Subscriber',
    'registered' => 'Registered User'
];
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold text-gray-900 mb-6">
        Postingan Terbaru
    </h1>

    <?php if (empty($popular_posts)): ?>
        <div class="bg-white rounded-lg shadow-sm p-8 text-center">
            <div class="text-gray-500">
                <i class="fas fa-file-alt text-4xl mb-4"></i>
                <p class="text-lg">Belum ada postingan populer.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="space-y-8">
            <?php foreach ($popular_posts as $post): ?>
                <article class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow duration-200">
                    <div class="p-6">
                        <header class="mb-4">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2">
                                <a href="/post/<?= $post['id'] ?>" class="hover:text-blue-600 transition-colors">
                                    <?= htmlspecialchars($post['title']) ?>
                                </a>
                            </h2>
                            <div class="flex items-center text-sm text-gray-600 space-x-4">
                                <span class="flex items-center">
                                    By <?= htmlspecialchars($post['author_name'] ?? $post['author']) ?>
                                    <?php if (isset($post['author_badge'])): ?>
                                        <span class="relative ml-1 group">
                                            <span class="<?= $badge_colors[$post['author_badge']] ?? $badge_colors['registered'] ?> cursor-help">
                                                <?= $badge_icons[$post['author_badge']] ?? $badge_icons['registered'] ?>
                                            </span>
                                            <!-- Tooltip -->
                                            <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 hidden group-hover:block 
                                                       bg-gray-900 text-white text-xs px-2 py-1 rounded whitespace-nowrap">
                                                <?= $badge_tooltips[$post['author_badge']] ?? $badge_tooltips['registered'] ?>
                                                <span class="block text-gray-300 text-xs">@<?= htmlspecialchars($post['author_username'] ?? $post['author']) ?></span>
                                            </span>
                                        </span>
                                    <?php endif; ?>
                                </span>
                                <time datetime="<?= $post['created_at'] ?>" class="flex items-center">
                                    <i class="far fa-calendar mr-2"></i>
                                    <?= date('d M Y', strtotime($post['created_at'])) ?>
                                </time>
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
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
// include __DIR__ . '/layout.php';
include __DIR__ . '/blog/layout.php';
?>