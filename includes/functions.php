<?php
// ============================================================
// TalentBridge - Helper Functions
// Developer: Hasibul Polok
// ============================================================

// Start session securely
function startSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        session_start();
    }
}

// XSS protection - escape output
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user
function currentUser() {
    return $_SESSION['user'] ?? null;
}

// Get current user role
function userRole() {
    return $_SESSION['role'] ?? null;
}

// Require login - redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        setFlash('error', 'Please login to continue.');
        redirect(BASE_URL . '/auth/login.php');
    }
}

// Require specific role
function requireRole($role) {
    requireLogin();
    if (userRole() !== $role) {
        setFlash('error', 'Access denied. You do not have permission.');
        redirect(BASE_URL . '/index.php');
    }
}

// Redirect helper
function redirect($url) {
    header("Location: $url");
    exit();
}

// Flash messages
function setFlash($type, $message) {
    startSecureSession();
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function displayFlash() {
    $flash = getFlash();
    if ($flash) {
        $type = $flash['type']; // success, error, warning, info
        $msg = e($flash['message']);
        $icons = ['success' => '✓', 'error' => '✕', 'warning' => '⚠', 'info' => 'ℹ'];
        $icon = $icons[$type] ?? 'ℹ';
        echo "<div class='flash flash-{$type}'><span class='flash-icon'>{$icon}</span>{$msg}</div>";
    }
}

// Generate URL-friendly slug
function createSlug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// Unique slug generator
function uniqueSlug($pdo, $table, $title, $excludeId = null) {
    $slug = createSlug($title);
    $base = $slug;
    $i = 1;
    while (true) {
        $sql = "SELECT id FROM $table WHERE slug = ?";
        $params = [$slug];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if (!$stmt->fetch()) break;
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

// Format date
function formatDate($date, $format = 'd M Y') {
    if (!$date) return 'N/A';
    return date($format, strtotime($date));
}

// Time ago
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    if ($time < 60) return 'Just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 604800) return floor($time/86400) . ' days ago';
    return formatDate($datetime);
}

// Format salary
function formatSalary($min, $max, $type) {
    if ($type === 'negotiable') return 'Negotiable';
    $currency = '৳';
    if ($min && $max) {
        return $currency . number_format($min) . ' - ' . $currency . number_format($max) . '/' . $type;
    } elseif ($min) {
        return 'From ' . $currency . number_format($min) . '/' . $type;
    } elseif ($max) {
        return 'Up to ' . $currency . number_format($max) . '/' . $type;
    }
    return 'Not disclosed';
}

// Upload CV file
function uploadCV($file) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $file['error']);
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('File too large. Maximum size is 5MB.');
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, ALLOWED_CV_TYPES)) {
        throw new Exception('Invalid file type. Only PDF and DOCX allowed.');
    }
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'cv_' . uniqid() . '_' . time() . '.' . strtolower($ext);
    $dest = UPLOAD_PATH . $filename;
    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new Exception('Failed to save file.');
    }
    return $filename;
}


// Upload Profile Photo
function uploadPhoto($file, $oldFile = null) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Photo upload error: ' . $file['error']);
    }
    if ($file['size'] > MAX_PHOTO_SIZE) {
        throw new Exception('Photo too large. Maximum size is 3MB.');
    }
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, ALLOWED_PHOTO_TYPES)) {
        throw new Exception('Invalid file type. Only JPG, PNG, GIF, WEBP allowed.');
    }
    if ($oldFile && file_exists(PHOTO_UPLOAD_PATH . basename($oldFile))) {
        unlink(PHOTO_UPLOAD_PATH . basename($oldFile));
    }
    $extMap   = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
    $ext      = $extMap[$mimeType] ?? 'jpg';
    $filename = 'photo_' . uniqid() . '_' . time() . '.' . $ext;
    $dest     = PHOTO_UPLOAD_PATH . $filename;
    if (!is_dir(PHOTO_UPLOAD_PATH)) mkdir(PHOTO_UPLOAD_PATH, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new Exception('Failed to save photo.');
    }
    return $filename;
}

// Upload Company Logo
function uploadLogo($file, $oldFile = null) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Logo upload error: ' . $file['error']);
    }
    if ($file['size'] > MAX_PHOTO_SIZE) {
        throw new Exception('Logo too large. Maximum size is 3MB.');
    }
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, ALLOWED_PHOTO_TYPES)) {
        throw new Exception('Invalid file type. Only JPG, PNG, GIF, WEBP allowed.');
    }
    if ($oldFile && file_exists(LOGO_UPLOAD_PATH . basename($oldFile))) {
        unlink(LOGO_UPLOAD_PATH . basename($oldFile));
    }
    $extMap   = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'];
    $ext      = $extMap[$mimeType] ?? 'jpg';
    $filename = 'logo_' . uniqid() . '_' . time() . '.' . $ext;
    $dest     = LOGO_UPLOAD_PATH . $filename;
    if (!is_dir(LOGO_UPLOAD_PATH)) mkdir(LOGO_UPLOAD_PATH, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new Exception('Failed to save logo.');
    }
    return $filename;
}

// Get profile photo full URL (returns null if no photo)
function getPhotoUrl($photoFile) {
    if ($photoFile && file_exists(PHOTO_UPLOAD_PATH . basename($photoFile))) {
        return PHOTO_UPLOAD_URL . $photoFile;
    }
    return null;
}

// Get company logo full URL
function getLogoUrl($logoFile) {
    if ($logoFile && file_exists(LOGO_UPLOAD_PATH . basename($logoFile))) {
        return LOGO_UPLOAD_URL . $logoFile;
    }
    return null;
}

// Render avatar: shows photo if exists, else colored initials circle
function renderAvatar($name, $photoFile = null, $size = 48, $extraStyle = '') {
    $photoUrl = getPhotoUrl($photoFile);
    $initials  = strtoupper(substr(trim($name ?: 'U'), 0, 1));
    $palette   = ['#1a56db','#7c3aed','#db2777','#059669','#d97706','#dc2626','#0891b2','#9333ea'];
    $bg        = $palette[abs(crc32($name)) % count($palette)];
    if ($photoUrl) {
        return '<img src="' . htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') . '"
                     alt="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"
                     style="width:' . $size . 'px;height:' . $size . 'px;border-radius:50%;
                            object-fit:cover;flex-shrink:0;' . $extraStyle . '">';
    }
    return '<div style="width:' . $size . 'px;height:' . $size . 'px;border-radius:50%;
                background:' . $bg . ';color:white;display:flex;align-items:center;
                justify-content:center;font-family:var(--font-display);font-weight:800;
                font-size:' . round($size * 0.38) . 'px;flex-shrink:0;' . $extraStyle . '">'
           . $initials . '</div>';
}

// Paginate query
function paginate($pdo, $sql, $params, $page, $perPage = 10) {
    $countSql = "SELECT COUNT(*) FROM ($sql) AS t";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, max(1, $totalPages)));
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare("$sql LIMIT $perPage OFFSET $offset");
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    return [
        'data'        => $results,
        'total'       => $total,
        'page'        => $page,
        'per_page'    => $perPage,
        'total_pages' => $totalPages,
    ];
}

// Pagination HTML
function paginationLinks($baseUrl, $currentPage, $totalPages) {
    if ($totalPages <= 1) return '';
    $html = '<div class="pagination">';
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($currentPage - 1) . '" class="page-btn">← Prev</a>';
    }
    $start = max(1, $currentPage - 2);
    $end   = min($totalPages, $currentPage + 2);
    if ($start > 1) $html .= '<a href="' . $baseUrl . '&page=1" class="page-btn">1</a>' . ($start > 2 ? '<span class="page-dots">…</span>' : '');
    for ($i = $start; $i <= $end; $i++) {
        $active = $i == $currentPage ? ' active' : '';
        $html .= '<a href="' . $baseUrl . '&page=' . $i . '" class="page-btn' . $active . '">' . $i . '</a>';
    }
    if ($end < $totalPages) $html .= ($end < $totalPages - 1 ? '<span class="page-dots">…</span>' : '') . '<a href="' . $baseUrl . '&page=' . $totalPages . '" class="page-btn">' . $totalPages . '</a>';
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($currentPage + 1) . '" class="page-btn">Next →</a>';
    }
    $html .= '</div>';
    return $html;
}

// CSRF Token
function generateCSRF() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRF($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        die('Invalid CSRF token.');
    }
}

function csrfInput() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRF() . '">';
}

// Get status badge HTML
function statusBadge($status) {
    $colors = [
        'pending'     => 'badge-warning',
        'approved'    => 'badge-success',
        'rejected'    => 'badge-danger',
        'active'      => 'badge-success',
        'closed'      => 'badge-secondary',
        'draft'       => 'badge-secondary',
        'reviewed'    => 'badge-info',
        'shortlisted' => 'badge-primary',
        'interviewed' => 'badge-info',
        'hired'       => 'badge-success',
    ];
    $class = $colors[$status] ?? 'badge-secondary';
    return '<span class="badge ' . $class . '">' . ucfirst($status) . '</span>';
}

// Truncate text
function truncate($text, $length = 150) {
    $text = strip_tags($text);
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '…';
}

// Get job type label
function jobTypeLabel($type) {
    $labels = [
        'full-time'  => 'Full Time',
        'part-time'  => 'Part Time',
        'remote'     => 'Remote',
        'contract'   => 'Contract',
        'internship' => 'Internship',
        'freelance'  => 'Freelance',
    ];
    return $labels[$type] ?? ucfirst($type);
}

// Check if deadline passed
function isDeadlinePassed($deadline) {
    return strtotime($deadline) < time();
}
?>
