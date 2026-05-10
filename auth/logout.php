<?php
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();
setFlash('success', 'You have been logged out successfully.');
redirect(BASE_URL . '/auth/login.php');
?>
