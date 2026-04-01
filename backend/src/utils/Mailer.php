<?php
declare(strict_types=1);

namespace Src\Utils;

class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        // If PHPMailer is available, use SMTP per .env
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $_ENV['SMTP_HOST'] ?? '';
                $mail->Port = (int)($_ENV['SMTP_PORT'] ?? 587);
                $mail->SMTPAuth = true;
                $mail->Username = $_ENV['SMTP_USER'] ?? '';
                $mail->Password = $_ENV['SMTP_PASS'] ?? '';
                $mail->SMTPSecure = $_ENV['SMTP_SECURE'] ?? 'tls';
                $mail->setFrom($_ENV['SMTP_FROM'] ?? 'no-reply@example.com', $_ENV['SMTP_FROM_NAME'] ?? 'MyRA Journey');
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $htmlBody;
                return $mail->send();
            } catch (\Throwable $e) {
                // fallback
            }
        }
        // Fallback to PHP mail()
        $headers = "MIME-Version: 1.0\r\nContent-type:text/html;charset=UTF-8\r\n";
        $from = $_ENV['SMTP_FROM'] ?? 'no-reply@example.com';
        $headers .= 'From: '.$from."\r\n";
        return @mail($to, $subject, $htmlBody, $headers);
    }
}


