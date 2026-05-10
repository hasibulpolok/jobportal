<?php
// ============================================================
// TalentBridge - Save/Unsave Job (AJAX)
// Developer: Hasibul Polok
// ============================================================
require_once '../config/db.php';
require_once '../includes/functions.php';

startSecureSession();
header('Content-Type: application/json');

if (!isLoggedIn() || userRole() !== 'jobseeker') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
    exit;
}

verifyCSRF($_POST['csrf_token'] ?? '');

$jobId  = intval($_POST['job_id'] ?? 0);
$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];

if (!$jobId || !in_array($action, ['save', 'unsave'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$pdo = getDB();

// Verify job exists
$stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND status = 'approved'");
$stmt->execute([$jobId]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Job not found']);
    exit;
}

if ($action === 'save') {
    try {
        $pdo->prepare("INSERT IGNORE INTO saved_jobs (user_id, job_id) VALUES (?, ?)")->execute([$userId, $jobId]);
        echo json_encode(['success' => true, 'message' => 'Job saved']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error saving job']);
    }
} else {
    $pdo->prepare("DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?")->execute([$userId, $jobId]);
    echo json_encode(['success' => true, 'message' => 'Job unsaved']);
}
?>
