<?php
require_once '../config.php';

// Get the requested URI
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Check for static files
$static_files = [
    '/assets/css/style.css' => '../assets/css/style.css',
    '/assets/css/blog.css' => '../assets/css/blog.css',
    '/assets/js/script.js' => '../assets/js/script.js',
];

if (isset($static_files[$path])) {
    $file = $static_files[$path];
    if (file_exists($file)) {
        include $file;
        exit;
    }
}

// Get the host and check for subdomain
$host = $_SERVER['HTTP_HOST'];
$subdomain = null;

// Check if using subdomain
if (strpos($host, DOMAIN) !== false && $host !== DOMAIN) {
    $subdomain = str_replace('.' . DOMAIN, '', $host);
    
    // Jika subdomain adalah 'www', redirect ke domain utama
    if ($subdomain === 'www') {
        header('Location: http://' . DOMAIN . $_SERVER['REQUEST_URI']);
        exit;
    }
}

// Basic routing
if ($subdomain) {
    // Subdomain routing - menampilkan blog pengguna
    $db = Database::getInstance()->getConnection();
    
    // Cek apakah subdomain valid
    $stmt = $db->prepare("
        SELECT s.*, u.username as owner_name 
        FROM sites s 
        JOIN users u ON s.user_id = u.id 
        WHERE s.subdomain = ?
    ");
    $stmt->execute([$subdomain]);
    $current_site = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$current_site) {
        http_response_code(404);
        include '../views/404.php';
        exit;
    }

    // Cek apakah site aktif
    if (!($current_site['is_active'] ?? 1)) {
        http_response_code(403);
        include '../views/site_inactive.php';
        exit;
    }

    // Ambil postingan untuk subdomain ini
    $stmt = $db->prepare("
        SELECT p.*, u.name as author_name, u.username as author_username
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.site_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$current_site['id']]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set data untuk template
    $data = [
        'current_site' => $current_site,
        'posts' => $posts,
        'subdomain' => $subdomain,
        'site' => $current_site
    ];

    // Tampilkan template blog
    
    if (preg_match('#^/post/(\d+)$#', $path, $matches)) {
        $postId = $matches[1];
        $stmt = $db->prepare("
            SELECT p.*, 
                   s.subdomain, 
                   s.site_name, 
                   u.name as author_name,
                   u.username as author_username,
                   u.badge as author_badge,
                   COALESCE(s.is_active, 1) as site_active,
                   GROUP_CONCAT(DISTINCT c.name) as categories,
                   GROUP_CONCAT(DISTINCT t.name) as tags
            FROM posts p
            JOIN sites s ON p.site_id = s.id
            JOIN users u ON p.user_id = u.id
            LEFT JOIN post_categories pc ON p.id = pc.post_id
            LEFT JOIN categories c ON pc.category_id = c.id
            LEFT JOIN post_tags pt ON p.id = pt.post_id
            LEFT JOIN tags t ON pt.tag_id = t.id
            WHERE p.id = ? AND p.site_id = ?
            GROUP BY p.id
        ");
        $stmt->execute([$postId, $current_site['id']]);
        $post = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($post && ($post['site_active'] ?? 1)) {
            // Catat view
            $ip = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            
            // Cek apakah sudah pernah view dalam 24 jam terakhir
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM post_views 
                WHERE post_id = ? 
                AND ip_address = ? 
                AND viewed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute([$postId, $ip]);
            $has_viewed = $stmt->fetchColumn();

            // Jika belum pernah view dalam 24 jam
            if (!$has_viewed) {
                $stmt = $db->prepare("
                    INSERT INTO post_views (post_id, ip_address, user_agent)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$postId, $ip, $user_agent]);
            }

            // Ambil total views untuk post ini
            $stmt = $db->prepare("
                SELECT COUNT(DISTINCT ip_address) as total_views
                FROM post_views
                WHERE post_id = ?
            ");
            $stmt->execute([$postId]);
            $views = $stmt->fetch(PDO::FETCH_ASSOC);
            $post['total_views'] = $views['total_views'];

            // Ambil komentar untuk post ini
            $stmt = $db->prepare("
                SELECT c.*, 
                       CASE 
                           WHEN c.user_id IS NOT NULL THEN u.username 
                           ELSE c.name 
                       END as commenter_name,
                       u.badge
                FROM comments c
                LEFT JOIN users u ON u.id = c.user_id
                WHERE c.post_id = ?
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$postId]);
            $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Teruskan data komentar ke view
            $post['comments'] = $comments;
            $data['post'] = $post;
            include '../views/blog/post.php';
            exit;
        }
    }
    include '../views/blog/home.php';
    exit;
}

// Handle direct file access for auth and admin directories
if (strpos($path, '/auth/') === 0) {
    $file = '../auth/' . basename($path);
    if (file_exists($file)) {
        include $file;
        exit;
    }
} elseif (strpos($path, '/admin/') === 0) {
    // Cek login untuk akses admin
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        header('Location: /auth/login.php');
        exit;
    }
    $file = '../admin/' . basename($path);
    if (file_exists($file)) {
        include $file;
        exit;
    }
} elseif (strpos($path, '/user/') === 0) {
    // Cek login untuk akses user
    if (!isset($_SESSION['user_id'])) {
        header('Location: /auth/login.php');
        exit;
    }
    $file = '../user/' . basename($path);
    if (file_exists($file)) {
        include $file;
        exit;
    }
} 

// Main site routing
switch ($path) {
    case '/':
        // Homepage
        $db = Database::getInstance()->getConnection();
        
        // Ambil postingan populer dengan jumlah view
        $stmt = $db->prepare("
            SELECT p.*, 
                   s.subdomain, 
                   s.site_name,
                   u.username as author,
                   COUNT(DISTINCT pv.ip_address) as total_views
            FROM posts p
            JOIN sites s ON p.site_id = s.id
            JOIN users u ON p.user_id = u.id
            LEFT JOIN post_views pv ON p.id = pv.post_id
            WHERE s.is_active = 1
            GROUP BY p.id
            ORDER BY total_views DESC, p.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $popular_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ambil semua site
        $stmt = $db->prepare("SELECT * FROM sites WHERE is_active = 1");
        $stmt->execute();
        $sites = $stmt->fetchAll(PDO::FETCH_ASSOC);

        include '../views/main.php';
        break;
    case '/auth/login.php':
    case '/login':
        // Login page
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }
        include '../auth/login.php';
        break;

    case '/auth/register.php':
    case '/register':
        // Register page
        if (isset($_SESSION['user_id'])) {
            header('Location: /');
            exit;
        }
        include '../auth/register.php';
        break;

    case '/auth/logout.php':
    case '/logout':
        // Logout
        session_destroy();
        header('Location: /');
        exit;
        break;

    case '/dashboard':
        // Dashboard routing berdasarkan role
        if (!isset($_SESSION['user_id'])) {
            header('Location: /auth/login.php');
            exit;
        }

        switch ($_SESSION['role']) {
            case 'admin':
                header('Location: /admin/dashboard.php');
                break;
            case 'user':
                header('Location: /user/dashboard.php');
                break;
            default:
                header('Location: /');
        }
        exit;
        break;
    default:
        // Single post view
        if (preg_match('#^/post/(\d+)$#', $path, $matches)) {
            $postId = $matches[1];
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
                SELECT p.*, 
                       s.subdomain, 
                       s.site_name, 
                       u.name as author_name,
                       u.username as author_username,
                       u.badge as author_badge,
                       COALESCE(s.is_active, 1) as site_active,
                       GROUP_CONCAT(DISTINCT c.name) as categories,
                       GROUP_CONCAT(DISTINCT t.name) as tags
                FROM posts p
                JOIN sites s ON p.site_id = s.id
                JOIN users u ON p.user_id = u.id
                LEFT JOIN post_categories pc ON p.id = pc.post_id
                LEFT JOIN categories c ON pc.category_id = c.id
                LEFT JOIN post_tags pt ON p.id = pt.post_id
                LEFT JOIN tags t ON pt.tag_id = t.id
                WHERE p.id = ?
                GROUP BY p.id
            ");
            $stmt->execute([$postId]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($post && ($post['site_active'] ?? 1)) {
                // Catat view
                $ip = $_SERVER['REMOTE_ADDR'];
                $user_agent = $_SERVER['HTTP_USER_AGENT'];
                
                // Cek apakah sudah pernah view dalam 24 jam terakhir
                $stmt = $db->prepare("
                    SELECT COUNT(*) 
                    FROM post_views 
                    WHERE post_id = ? 
                    AND ip_address = ? 
                    AND viewed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ");
                $stmt->execute([$postId, $ip]);
                $has_viewed = $stmt->fetchColumn();

                // Jika belum pernah view dalam 24 jam
                if (!$has_viewed) {
                    $stmt = $db->prepare("
                        INSERT INTO post_views (post_id, ip_address, user_agent)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$postId, $ip, $user_agent]);
                }

                // Ambil total views untuk post ini
                $stmt = $db->prepare("
                    SELECT COUNT(DISTINCT ip_address) as total_views
                    FROM post_views
                    WHERE post_id = ?
                ");
                $stmt->execute([$postId]);
                $views = $stmt->fetch(PDO::FETCH_ASSOC);
                $post['total_views'] = $views['total_views'];

                // Ambil komentar untuk post ini
                $stmt = $db->prepare("
                    SELECT c.*, 
                           CASE 
                               WHEN c.user_id IS NOT NULL THEN u.username 
                               ELSE c.name 
                           END as commenter_name,
                           u.badge
                    FROM comments c
                    LEFT JOIN users u ON u.id = c.user_id
                    WHERE c.post_id = ?
                    ORDER BY c.created_at DESC
                ");
                $stmt->execute([$postId]);
                $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Teruskan data komentar ke view
                $post['comments'] = $comments;
                include '../views/post.php';
                exit;
            }
        }
        // 404 Not Found
        http_response_code(404);
        include '../views/404.php';
        break;
}