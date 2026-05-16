<?php
// ============================================================
// TalentBridge - Database Configuration
// Developer: Hasibul Polok
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change to your MySQL username
define('DB_PASS', '');           // Change to your MySQL password
define('DB_NAME', 'talentbridge');

define('BASE_URL', 'http://localhost/talentbridge');

// CV Upload
define('UPLOAD_PATH', __DIR__ . '/../uploads/cvs/');
define('UPLOAD_URL', BASE_URL . '/uploads/cvs/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_CV_TYPES', ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);

// Photo Upload
define('PHOTO_UPLOAD_PATH', __DIR__ . '/../uploads/photos/');
define('PHOTO_UPLOAD_URL', BASE_URL . '/uploads/photos/');
define('MAX_PHOTO_SIZE', 3 * 1024 * 1024); // 3MB
define('ALLOWED_PHOTO_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Logo Upload
define('LOGO_UPLOAD_PATH', __DIR__ . '/../uploads/logos/');
define('LOGO_UPLOAD_URL', BASE_URL . '/uploads/logos/');

// Create PDO connection
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('<div style="font-family:sans-serif;padding:20px;background:#fee;border:1px solid #f00;margin:20px;">
                <h3>Database Connection Error</h3>
                <p>Could not connect to the database. Please check your configuration in <code>config/db.php</code></p>
                <p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>
            </div>');
        }
    }
    return $pdo;
}
?>
