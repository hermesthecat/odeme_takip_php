<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bütçe Takip Sistemi Kurulum</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
        }

        .success {
            color: green;
            padding: 10px;
            border: 1px solid green;
            margin: 10px 0;
        }

        .error {
            color: red;
            padding: 10px;
            border: 1px solid red;
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <h1>Bütçe Takip Sistemi Kurulum</h1>
    <?php
    /**
     * Bütçe Takip Sistemi Kurulum Dosyası
     * @author A. Kerem Gök
     */

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $host = $_POST['host'] ?? 'localhost';
        $dbname = $_POST['dbname'] ?? 'odeme_takip';
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        try {
            // Veritabanı bağlantısını test et
            $pdo = new PDO("mysql:host=$host", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // SQL dosyasını oku
            $sql = file_get_contents('database.sql');

            // SQL komutlarını çalıştır
            $pdo->exec($sql);

            // db.php dosyasını oluştur
            $config = "<?php
/**
 * Veritabanı Yapılandırma Dosyası
 * @author A. Kerem Gök
 */

define('DB_HOST', '" . addslashes($host) . "');
define('DB_NAME', '" . addslashes($dbname) . "');
define('DB_USER', '" . addslashes($username) . "');
define('DB_PASS', '" . addslashes($password) . "');

try {
    \$pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_turkish_ci'
        ]
    );
} catch (PDOException \$e) {
    die('Veritabanı bağlantı hatası: ' . \$e->getMessage());
}
";
            file_put_contents('includes/db.php', $config);

            echo '<div class="success">
                <p>✅ Kurulum başarıyla tamamlandı!</p>
                <p>Lütfen güvenlik için bu dosyayı (install.php) sunucunuzdan silin.</p>
                <p><a href="index.php">Ana sayfaya git</a></p>
            </div>';
        } catch (PDOException $e) {
            echo '<div class="error">Hata: ' . $e->getMessage() . '</div>';
        }
    } else {
    ?>
        <form method="post">
            <div class="form-group">
                <label for="host">Veritabanı Sunucusu:</label>
                <input type="text" id="host" name="host" value="localhost" required>
            </div>

            <div class="form-group">
                <label for="dbname">Veritabanı Adı:</label>
                <input type="text" id="dbname" name="dbname" value="odeme_takip" required>
            </div>

            <div class="form-group">
                <label for="username">Veritabanı Kullanıcı Adı:</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Veritabanı Şifresi:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit">Kurulumu Başlat</button>
        </form>
    <?php } ?>
</body>

</html>