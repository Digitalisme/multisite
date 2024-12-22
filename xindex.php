<?php


require_once 'config.php';

class MultisiteRouter {
    private $db;
    private $subdomain;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->subdomain = $this->getSubdomain();
    }

    private function getSubdomain() {
        $host = $_SERVER['HTTP_HOST'];
        $mainDomain = MAIN_DOMAIN;
        
        if (strpos($host, $mainDomain) !== false) {
            $subdomain = str_replace('.'.$mainDomain, '', $host);
            return $subdomain;
        }
        
        return '';
    }

    public function route() {
        if (empty($this->subdomain)) {
            // Tampilkan halaman utama
            include 'views/main.php';
            return;
        }

        // Cek subdomain di database
        $stmt = $this->db->prepare("SELECT * FROM sites WHERE subdomain = ?");
        $stmt->execute([$this->subdomain]);
        $site = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($site) {
            // Load site yang sesuai
            $_SESSION['current_site'] = $site;
            include 'views/blog.php';
        } else {
            // Site tidak ditemukan
            header("HTTP/1.0 404 Not Found");
            include 'views/404.php';
        }
    }
}

$router = new MultisiteRouter();
$router->route(); 