<?php
// includes/delete_background.php
require_once __DIR__ . '/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['status'=>'error','message'=>'Akses ditolak.']);
    exit;
}

$bg = $_POST['background'] ?? '';
if (!$bg) {
    echo json_encode(['status'=>'error','message'=>'Nama background tidak diberikan.']);
    exit;
}

$path = __DIR__ . '/../uploads/backgrounds/' . $bg;
if (!file_exists($path)) {
    echo json_encode(['status'=>'error','message'=>'File background tidak ditemukan.']);
    exit;
}

if (unlink($path)) {
    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error','message'=>'Gagal menghapus file background.']);
}
