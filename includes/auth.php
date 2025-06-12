<?php
// includes/auth.php
// ---------------------------------

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Periksa apakah user sudah login
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Periksa apakah user saat ini adalah admin
 * @return bool
 */
function isAdmin(): bool {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
}

/**
 * Fungsi untuk redirect ke login jika belum login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Akses ditolak. Silakan login terlebih dahulu.'
        ]);
        exit;
    }
}

/**
 * Fungsi untuk cek role admin jika dibutuhkan
 */
function requireAdmin() {
    if (!isLoggedIn() || !isAdmin()) {
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Akses admin diperlukan.'
        ]);
        exit;
    }
}
