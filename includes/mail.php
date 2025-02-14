<?php

/**
 * @author A. Kerem Gök
 * Email işlemleri için yardımcı fonksiyonlar
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

/**
 * Email gönderme fonksiyonu
 * 
 * @param string $to Alıcı email adresi
 * @param string $subject Email konusu
 * @param string $body Email içeriği (HTML)
 * @param string $altBody Alternatif metin içeriği
 * @return bool Gönderim başarılı ise true, değilse false
 */
function sendEmail($to, $subject, $body, $altBody = '')
{
    try {
        $mail = new PHPMailer(true);

        // SMTP ayarları
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Gmail SMTP sunucusu
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Gmail adresiniz
        $mail->Password = 'your-app-password'; // Gmail uygulama şifreniz
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Gönderici bilgileri
        $mail->setFrom('your-email@gmail.com', 'Ödeme Takip Sistemi');
        $mail->addAddress($to);

        // Email içeriği
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $altBody ?: strip_tags($body);

        return $mail->send();
    } catch (Exception $e) {
        error_log("Email gönderme hatası: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Şifre sıfırlama emaili gönder
 * 
 * @param string $email Kullanıcı email adresi
 * @param string $username Kullanıcı adı
 * @param string $firstName Kullanıcı adı
 * @param string $token Sıfırlama token'ı
 * @return bool Gönderim başarılı ise true, değilse false
 */
function sendPasswordResetEmail($email, $username, $firstName, $token)
{
    $resetLink = "https://" . $_SERVER['HTTP_HOST'] . "/reset-password?token=" . $token;

    $subject = "Şifre Sıfırlama Talebi";

    $body = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .button { 
                display: inline-block; 
                padding: 10px 20px; 
                background-color: #4CAF50; 
                color: white; 
                text-decoration: none; 
                border-radius: 5px; 
                margin: 20px 0;
            }
            .footer { font-size: 12px; color: #666; margin-top: 30px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h2>Merhaba " . htmlspecialchars($firstName ?: $username) . ",</h2>
            
            <p>Hesabınız için şifre sıfırlama talebinde bulundunuz.</p>
            
            <p>Şifrenizi sıfırlamak için aşağıdaki bağlantıya tıklayın:</p>
            
            <p><a href='" . $resetLink . "' class='button'>Şifremi Sıfırla</a></p>
            
            <p>Veya bu bağlantıyı tarayıcınıza kopyalayın:</p>
            <p>" . $resetLink . "</p>
            
            <p>Bu bağlantı 1 saat süreyle geçerlidir.</p>
            
            <p>Eğer bu talebi siz yapmadıysanız, bu emaili görmezden gelebilirsiniz.</p>
            
            <div class='footer'>
                <p>Bu otomatik bir emaildir, lütfen yanıtlamayınız.</p>
                <p>Ödeme Takip Sistemi</p>
            </div>
        </div>
    </body>
    </html>";

    return sendEmail($email, $subject, $body);
}
