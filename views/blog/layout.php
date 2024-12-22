<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/blog.css">
</head>
<body class="bg-gray-50">
    <header class="bg-white shadow">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 flex justify-between items-center">
            <h1 class="text-2xl font-bold text-gray-900">
                <a href="//<?= isset($site['subdomain']) ? htmlspecialchars($site['subdomain']) : '' ?>.<?= DOMAIN ?>" class="hover:text-blue-600">
                    <?= isset($site['site_name']) ? htmlspecialchars($site['site_name']) : '' ?>
                </a>
            </h1>
            <nav>
                <a href="/" class="text-gray-700 hover:text-blue-600 mr-4">
                    <i class="fas fa-home mr-1"></i>
                    Home
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/dashboard" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-user mr-1"></i>
                        Dashboard
                    </a>
                <?php else: ?>
                    <a href="/login" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-sign-in-alt mr-1"></i>
                        Login
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main>
        <?= $content ?>
    </main>

    <footer class="bg-gray-100 py-4 text-center text-gray-500">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p>&copy; <?= date('Y') ?> <?= isset($site['site_name']) ? htmlspecialchars($site['site_name']) : '' ?></p>
        </div>
    </footer>
</body>
</html> 