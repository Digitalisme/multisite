<?php
class Mailer {
    private static function sendMail($to, $subject, $message) {
        $headers = [
            'From' => 'noreply@' . MAIN_DOMAIN,
            'Reply-To' => 'noreply@' . MAIN_DOMAIN,
            'X-Mailer' => 'PHP/' . phpversion(),
            'Content-Type' => 'text/html; charset=UTF-8'
        ];
        
        return mail($to, $subject, $message, $headers);
    }
    
    public static function sendCommentNotification($comment, $post, $site) {
        $subject = "Komentar Baru di " . $site['site_name'];
        
        $message = "
        <html>
        <body>
            <h2>Komentar Baru</h2>
            <p>Ada komentar baru di blog Anda yang memerlukan moderasi.</p>
            
            <h3>Detail Komentar:</h3>
            <ul>
                <li><strong>Nama:</strong> {$comment['name']}</li>
                <li><strong>Email:</strong> {$comment['email']}</li>
                <li><strong>Artikel:</strong> {$post['title']}</li>
                <li><strong>Waktu:</strong> " . date('d M Y H:i', strtotime($comment['created_at'])) . "</li>
            </ul>
            
            <h3>Isi Komentar:</h3>
            <p>" . nl2br(htmlspecialchars($comment['content'])) . "</p>
            
            <p>
                <a href='http://{$site['subdomain']}." . MAIN_DOMAIN . "/admin/manage-comments.php?site_id={$site['id']}'>
                    Klik di sini untuk moderasi komentar
                </a>
            </p>
        </body>
        </html>
        ";
        
        return self::sendMail($site['email'], $subject, $message);
    }
} 