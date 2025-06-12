<?php
// includes/upload_background.php
require_once __DIR__ . '/auth.php';
requireLogin();
if ($_SESSION['role'] !== 'admin') {
    echo json_encode(['status'=>'error','message'=>'Akses ditolak.']);
    exit;
}

if (!isset($_FILES['uploadBackground']['name'])) {
    echo json_encode(['status'=>'error','message'=>'File tidak ditemukan.']);
    exit;
}

$file      = $_FILES['uploadBackground'];
$ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
$allowed   = ['png','jpg','jpeg','gif','webp'];
if (!in_array(strtolower($ext), $allowed)) {
    echo json_encode(['status'=>'error','message'=>'Format file background tidak didukung.']);
    exit;
}

$uploadDir = __DIR__ . '/../uploads/backgrounds/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
$newName   = 'background_' . time() . '.' . $ext;
$target    = $uploadDir . $newName;

if (move_uploaded_file($file['tmp_name'], $target)) {
    echo json_encode(['status'=>'success','filename'=>$newName]);
} else {
    echo json_encode(['status'=>'error','message'=>'Gagal upload background.']);
}
