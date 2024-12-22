# Masih banyak bug nya bro wwkwokwoawokw

# Sistem Blog Multi-User

Sistem blog multi-user dengan fitur subdomain untuk setiap pengguna. Dibuat menggunakan PHP native.

## Fitur

- Multi-user dengan role admin dan user
- Subdomain untuk setiap blog
- Manajemen posting
- Sistem komentar
- Manajemen kategori dan tag
- Upload media
- Dashboard admin

## Instalasi

1. Clone repository ini
2. Import database dari file `database.sql`
3. Copy `config.example.php` ke `config.php`
4. Sesuaikan konfigurasi database di `config.php`
5. Pastikan mod_rewrite Apache aktif
6. Atur virtual host untuk subdomain wildcard

## Persyaratan Sistem

- PHP 7.4+
- MySQL 5.7+
- Apache dengan mod_rewrite
- Ekstensi PHP: PDO, GD

## Penggunaan

1. Login sebagai admin: admin@example.com / password
2. Buat blog baru
3. Kelola posting, kategori, dan komentar
