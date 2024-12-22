<?php
$pageTitle = htmlspecialchars($post['title']) . ' - ' . htmlspecialchars($site['site_name']);

// Ambil semua komentar
$stmt = $db->prepare("SELECT * FROM comments WHERE post_id = ? ORDER BY created_at DESC");
$stmt->execute([$post['id']]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<script>
tailwind.config = {
    theme: {
        extend: {
            typography: {
                DEFAULT: {
                    css: {
                        maxWidth: 'none',
                        color: '#1a202c',
                        a: {
                            color: '#3182ce',
                            '&:hover': {
                                color: '#2c5282',
                            },
                        },
                        h1: {
                            color: '#1a202c',
                            fontWeight: '800',
                        },
                        h2: {
                            color: '#1a202c',
                            fontWeight: '700',
                        },
                        h3: {
                            color: '#1a202c',
                            fontWeight: '600',
                        },
                        h4: {
                            color: '#1a202c',
                            fontWeight: '600',
                        },
                        img: {
                            borderRadius: '0.375rem',
                            marginTop: '2rem',
                            marginBottom: '2rem',
                        },
                        code: {
                            color: '#805ad5',
                            backgroundColor: '#f7fafc',
                            padding: '0.25rem',
                            borderRadius: '0.25rem',
                            fontWeight: '600',
                        },
                        'code::before': {
                            content: '""',
                        },
                        'code::after': {
                            content: '""',
                        },
                        pre: {
                            backgroundColor: '#2d3748',
                            color: '#e2e8f0',
                            padding: '1rem',
                            borderRadius: '0.5rem',
                            marginTop: '1.5rem',
                            marginBottom: '1.5rem',
                        },
                        blockquote: {
                            borderLeftColor: '#4299e1',
                            borderLeftWidth: '4px',
                            paddingLeft: '1rem',
                            fontStyle: 'italic',
                            color: '#4a5568',
                        },
                    },
                },
            },
        },
    },
};
</script>

<article class="max-w-4xl mx-auto bg-white rounded-lg shadow-md overflow-hidden">
    <header class="p-8 border-b border-gray-200">
        <h1 class="text-3xl font-bold text-gray-900 mb-4"><?= htmlspecialchars($post['title']) ?></h1>
        <div class="flex items-center text-sm text-gray-600">
            <time datetime="<?= $post['created_at'] ?>" class="flex items-center">
                <i class="far fa-calendar mr-2"></i>
                <?= date('d M Y', strtotime($post['created_at'])) ?>
            </time>
            <?php
            // Ambil kategori posting
            $stmt = $db->prepare("
                SELECT c.name, c.slug 
                FROM categories c 
                JOIN post_categories pc ON c.id = pc.category_id 
                WHERE pc.post_id = ?
            ");
            $stmt->execute([$post['id']]);
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($categories)): ?>
                <span class="mx-2">•</span>
                <div class="flex items-center">
                    <i class="far fa-folder mr-2"></i>
                    <?php foreach ($categories as $index => $category): ?>
                        <a href="/category/<?= $category['slug'] ?>" 
                           class="text-blue-600 hover:text-blue-800">
                            <?= htmlspecialchars($category['name']) ?>
                        </a>
                        <?= $index < count($categories) - 1 ? ', ' : '' ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <div class="p-8">
        <div class="prose prose-lg max-w-none">
            <?= $post['content'] ?>
        </div>
    </div>

    <footer class="p-8 bg-gray-50 border-t border-gray-200">
        <div class="flex justify-between items-center">
            <a href="/" class="text-blue-600 hover:text-blue-800 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Kembali ke Beranda
            </a>
            <div class="flex space-x-4">
                <a href="#" class="text-gray-600 hover:text-blue-600">
                    <i class="fab fa-facebook"></i>
                </a>
                <a href="#" class="text-gray-600 hover:text-blue-600">
                    <i class="fab fa-twitter"></i>
                </a>
                <a href="#" class="text-gray-600 hover:text-blue-600">
                    <i class="fab fa-linkedin"></i>
                </a>
            </div>
        </div>
    </footer>
</article>

<section class="max-w-4xl mx-auto mt-8">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Komentar (<?= count($comments) ?>)</h2>
    
    <?php if (!empty($comments)): ?>
        <div class="space-y-6">
            <?php foreach ($comments as $comment): ?>
                <div class="bg-white rounded-lg shadow-sm p-6 <?= $comment['status'] ?>">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <strong class="text-gray-900"><?= htmlspecialchars($comment['name']) ?></strong>
                            <time datetime="<?= $comment['created_at'] ?>" class="text-sm text-gray-600 ml-2">
                                <?= date('d M Y H:i', strtotime($comment['created_at'])) ?>
                            </time>
                        </div>
                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                            <?= $comment['status'] === 'approved' ? 'bg-green-100 text-green-800' : 
                               ($comment['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                'bg-red-100 text-red-800') ?>">
                            <?= ucfirst($comment['status']) ?>
                        </span>
                    </div>
                    <div class="text-gray-700 prose-sm">
                        <?= nl2br(htmlspecialchars($comment['content'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-gray-500 text-center py-8">Belum ada komentar. Jadilah yang pertama berkomentar!</p>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-md p-6 mt-8">
        <h3 class="text-xl font-semibold text-gray-900 mb-6">Tinggalkan Komentar</h3>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded relative">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded relative">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <form action="/process-comment.php" method="POST" class="space-y-6">
            <input type="hidden" name="post_id" value="<?= htmlspecialchars($post['id']) ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Nama</label>
                <input type="text" name="name" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                       value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input type="email" name="email" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Komentar</label>
                <textarea name="content" required rows="4"
                          class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                ><?= isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '' ?></textarea>
            </div>
            
            <button type="submit" 
                    class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Kirim Komentar
            </button>
        </form>
    </div>
</section>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
?> 