<?php
// src/helpers/mailer.php

/**
 * Simple SMTP Mailer for StepUp LMS
 */
function sendVerificationEmail($to, $name, $token) {
    $from = 'kgianandra@gmail.com';
    $password = 'qocj jfwo hgnm bqcy'; // Gmail App Password
    $subject = 'Verifikasi Akun StepUp LMS';
    
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $protocol = $is_https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
    $base_url = $protocol . '://' . $host;
    $link = $base_url . "/src/auth/verify.php?token=" . $token;
    
    $message = "
    <html>
    <head>
        <style>
            .container { font-family: sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 20px; }
            .btn { display: inline-block; padding: 15px 30px; background: #2563eb; color: white !important; text-decoration: none; border-radius: 15px; font-weight: bold; margin-top: 20px; }
            h1 { color: #0f172a; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Halo $name!</h1>
            <p>Terima kasih telah bergabung di <b>StepUp LMS</b>. Silakan klik tombol di bawah ini untuk memverifikasi akun kamu:</p>
            <a href='$link' class='btn'>Verifikasi Akun Saya</a>
            <p style='margin-top: 30px; color: #64748b; font-size: 12px;'>Jika tombol tidak berfungsi, kamu bisa salin link berikut:<br>$link</p>
        </div>
    </body>
    </html>";

    return smtp_mailer($to, $subject, $message, $from, $password);
}

function smtp_mailer($to, $subject, $message, $from, $password) {
    $mail_server = 'ssl://smtp.gmail.com';
    $port = 465;
    $timeout = 30;

    $socket = fsockopen($mail_server, $port, $errno, $errstr, $timeout);
    if (!$socket) return false;

    function get_response($socket) {
        $response = "";
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == " ") break;
        }
        return $response;
    }

    get_response($socket);
    fwrite($socket, "EHLO localhost\r\n");
    get_response($socket);
    fwrite($socket, "AUTH LOGIN\r\n");
    get_response($socket);
    fwrite($socket, base64_encode($from) . "\r\n");
    get_response($socket);
    fwrite($socket, base64_encode(str_replace(' ', '', $password)) . "\r\n");
    $auth_res = get_response($socket);
    
    if (strpos($auth_res, '235') === false) return false;

    fwrite($socket, "MAIL FROM: <$from>\r\n");
    get_response($socket);
    fwrite($socket, "RCPT TO: <$to>\r\n");
    get_response($socket);
    fwrite($socket, "DATA\r\n");
    get_response($socket);

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "To: $to\r\n";
    $headers .= "From: StepUp LMS <$from>\r\n";
    $headers .= "Subject: $subject\r\n";

    fwrite($socket, $headers . "\r\n" . $message . "\r\n.\r\n");
    get_response($socket);
    fwrite($socket, "QUIT\r\n");
    fclose($socket);
    return true;
}
