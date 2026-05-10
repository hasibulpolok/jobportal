<?php
// ============================================================
// TalentBridge - Secure CV Download
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
requireRole('employer');

$pdo    = getDB();
$userId = $_SESSION['user_id'];
$appId  = intval($_GET['app_id'] ?? 0);

if (!$appId) { setFlash('error', 'Invalid request.'); redirect(BASE_URL . '/employer/applicants.php'); }

// Verify ownership + fetch CV
$stmt = $pdo->prepare("
    SELECT a.cv_file, up.cv_file AS profile_cv, u.name AS applicant_name
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN users u ON a.user_id = u.id
    LEFT JOIN user_profiles up ON up.user_id = u.id
    WHERE a.id = ? AND j.employer_id = ?
");
$stmt->execute([$appId, $userId]);
$data = $stmt->fetch();

if (!$data) { setFlash('error', 'Application not found.'); redirect(BASE_URL . '/employer/applicants.php'); }

// Use application CV or fall back to profile CV
$cvFile = $data['cv_file'] ?: $data['profile_cv'];

if (!$cvFile) { setFlash('error', 'No CV available for this applicant.'); redirect(BASE_URL . '/employer/applicants.php'); }

$filePath = UPLOAD_PATH . basename($cvFile); // basename() prevents directory traversal

if (!file_exists($filePath)) {
    setFlash('error', 'CV file not found on server.');
    redirect(BASE_URL . '/employer/applicants.php');
}

// Determine MIME type safely
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($filePath);
$ext      = strtolower(pathinfo($cvFile, PATHINFO_EXTENSION));

$allowedMimes = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
];

if (!isset($allowedMimes[$ext])) {
    setFlash('error', 'Invalid file type.');
    redirect(BASE_URL . '/employer/applicants.php');
}

// Safe download filename
$safeApplicantName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $data['applicant_name']);
$downloadName      = 'CV_' . $safeApplicantName . '.' . $ext;

// Send headers for download
header('Content-Description: File Transfer');
header('Content-Type: ' . $allowedMimes[$ext]);
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));
ob_clean();
flush();
readfile($filePath);
exit;
?>
