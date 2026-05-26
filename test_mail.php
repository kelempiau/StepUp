<?php
require_once __DIR__ . '/src/helpers/mailer.php';

// Try sending an email to a test address
$to = 'kgianandra@gmail.com'; // user's email
$subject = 'Test Email SMTP LMS';
$message = '<h1>Halo!</h1><p>Ini adalah email test untuk memastikan SMTP berjalan.</p>';

$result = smtp_mailer($to, $subject, $message, 'kgianandra@gmail.com', 'qocj jfwo hgnm bqcy');

if ($result) {
    echo "BERHASIL: Email terkirim ke $to\n";
} else {
    echo "GAGAL: Email tidak dapat dikirim.\n";
}
