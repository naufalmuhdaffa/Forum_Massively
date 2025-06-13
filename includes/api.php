<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/user_functions.php';
require_once __DIR__ . '/thread_functions.php';


$action = $_POST['action'] ?? '';

// Jika action = 'update_profile', alihkan ke 'update_user'
if ($action === 'update_profile') {
    requireLogin();
    $_POST['user_id'] = $_SESSION['user_id'];
    $action = 'update_user';
}

switch ($action) {

    /* =========================
     * REGISTER
     * ========================= */
    case 'register':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';
        $email = trim($_POST['email'] ?? null);
        $captchaAnswer = trim($_POST['captchaAnswer'] ?? '');

        if ($captchaAnswer === '' || $captchaAnswer !== ($_POST['captchaSolution'] ?? '')) {
            echo json_encode(['status' => 'error', 'message' => 'Captcha salah.']);
            exit;
        }
        if ($password !== $confirmPassword) {
            echo json_encode(['status' => 'error', 'message' => 'Password konfirmasi tidak cocok.']);
            exit;
        }
        $res = registerUser($username, $password, $email);
        echo json_encode($res);
        exit;

    /* =========================
     * LOGIN
     * ========================= */
    case 'login':
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $captchaAnswer = trim($_POST['captchaAnswer'] ?? '');

        if ($captchaAnswer === '' || $captchaAnswer !== ($_POST['captchaSolution'] ?? '')) {
            echo json_encode(['status' => 'error', 'message' => 'Captcha salah.']);
            exit;
        }
        $res = loginUser($username, $password);
        if ($res['status'] === 'success') {
            $user = $res['data'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['avatar'] = $user['avatar'] ?? null;
            unset($user);
        }
        echo json_encode($res);
        exit;

    /* =========================
     * LOGOUT
     * ========================= */
    case 'logout':
        session_unset();
        session_destroy();
        echo json_encode(['status' => 'success', 'message' => 'Logout berhasil.']);
        exit;

    /* =========================
     * GET_CURRENT_USER
     * ========================= */
    case 'get_current_user':
        if (!isLoggedIn()) {
            echo json_encode(['status' => 'error', 'message' => 'Belum login.']);
            exit;
        }
        $user = getUserById($_SESSION['user_id']);

        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan']);
            exit;
        }

        // Cek apakah user memiliki password
        $hasPassword = !empty($user['password']); // true jika password sudah diset

        $data = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'border' => $user['border'],
            'has_password' => $hasPassword, // 🚨 Tambahan ini!
        ];


        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;

    /* =========================
     * GET_ALL_USERS (ADMIN)
     * ========================= */
    case 'get_all_users':
        requireAdmin();
        $users = getAllUsers();
        echo json_encode(['status' => 'success', 'data' => $users]);
        exit;

    /* =========================
     * DELETE_USER (ADMIN)
     * ========================= */
    case 'delete_user':
        requireAdmin();
        $userId = intval($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'User ID tidak valid.']);
            exit;
        }
        // Ambil data user
        $user = getUserById($userId);
        if ($user) {
            // Hapus avatar kalau ada
            if (!empty($user['avatar']) && basename($user['avatar']) !== 'herta-kurukuru.gif') {
                $path = __DIR__ . '/../' . $user['avatar'];
                if (file_exists($path))
                    @unlink($path);
            }
            // Hapus background kalau ada
            if (!empty($user['background'])) {
                $pathBg = __DIR__ . '/../' . $user['background'];
                if (file_exists($pathBg))
                    @unlink($pathBg);
            }
            // Hapus media thread milik user
            $threads = getThreadById($userId);
            foreach ($threads as $t) {
                if (!empty($t['media'])) {
                    $threadPath = __DIR__ . '/../' . $t['media'];
                    if (file_exists($threadPath))
                        @unlink($threadPath);
                }
            }
            // Hapus media reply milik user
            $replies = getRepliesByThreadId($userId);
            foreach ($replies as $r) {
                if (!empty($r['media'])) {
                    $replyPath = __DIR__ . '/../' . $r['media'];
                    if (file_exists($replyPath))
                        @unlink($replyPath);
                }
            }
        }
        $res = deleteUser($userId);
        echo json_encode($res);
        exit;

    /* =========================
     * CHECK_PASSWORD
     * ========================= */
    case 'check_password':
        requireLogin();
        $userId = intval($_POST['user_id'] ?? 0);
        $oldPwd = $_POST['old_password'] ?? '';

        if ($userId !== $_SESSION['user_id']) {
            echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
            exit;
        }
        global $pdo;
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if ($row && password_verify($oldPwd, $row['password'])) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Password lama tidak cocok']);
        }
        exit;


    /* =========================
     * update_role (ADMIN)
     * ========================= */

    case 'update_role':
        // Cek session: pastikan yang request adalah admin
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Akses admin diperlukan.'
            ]);
            exit;
        }

        // Validasi input
        $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $newRole = isset($_POST['role']) ? $_POST['role'] : '';

        if (!in_array($newRole, ['admin', 'user'], true) || $userId <= 0) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Data role tidak valid.'
            ]);
            exit;
        }

        // Siapkan query update
        try {
            $stmt = $pdo->prepare("UPDATE users SET role = :role WHERE id = :id");
            $stmt->execute([
                ':role' => $newRole,
                ':id' => $userId
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Role pengguna berhasil diperbarui.'
            ]);

        } catch (PDOException $e) {
            // Kalau error dari DB
            echo json_encode([
                'status' => 'error',
                'message' => 'Gagal mengupdate role: ' . $e->getMessage()
            ]);
        }
        break;


    /* =========================
     * UPDATE_USER (profil)
     * ========================= */
    case 'update_user':
        requireLogin();
        $userId = $_SESSION['user_id'];
        if (!isAdmin() && $userId !== $_SESSION['user_id']) {
            echo json_encode(['status' => 'error', 'message' => 'Akses ditolak.']);
            exit;
        }

        // Ambil data lama user untuk cek conflict username
        $existing = getUserById($userId);
        if (!$existing) {
            echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan.']);
            exit;
        }

        $newUsername = trim($_POST['newUsername'] ?? '');
        $emailInput = trim($_POST['email'] ?? '');
        $oldPassword = $_POST['oldPassword'] ?? '';
        $newPassword = trim($_POST['newPassword'] ?? '');
        $confirmNewPwd = trim($_POST['confirmNewPassword'] ?? '');
        $roleBaru = $_POST['role'] ?? null;
        $border = $_POST['border'] ?? null;
        $background = null;
        $avatarPath = null;

        // Kalau ingin ganti username, cek dulu unique
        if ($newUsername && $newUsername !== $existing['username']) {
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$newUsername]);
            if ($check->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Username sudah dipakai.']);
                exit;
            }
        }

        // 4. Cek unique username jika diubah
        if ($newUsername && $newUsername !== $existing['username']) {
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$newUsername]);
            if ($check->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Username sudah dipakai.']);
                exit;
            }
        }

        // 5. Tentukan email final: pakai input kalau ada, kalau tidak pakai yang lama
        $email = $emailInput !== '' ? $emailInput : $existing['email'];
        // 6. Cek unique email hanya jika diubah
        if ($emailInput !== '' && $emailInput !== $existing['email']) {
            $checkEmail = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $checkEmail->execute([$emailInput]);
            if ($checkEmail->fetch()) {
                echo json_encode(['status' => 'error', 'message' => 'Email sudah dipakai.']);
                exit;
            }
        }

        // 3. Validasi password (hanya jika ada newPassword)
        if ($newPassword !== '') {
            // Jika user sudah punya password, wajib verifikasi old
            if (!empty($existing['password'])) {
                if (!password_verify($oldPassword, $existing['password'])) {
                    echo json_encode(['status' => 'error', 'message' => 'Password lama salah.']);
                    exit;
                }
            }
            // Cek konfirmasi
            if ($newPassword !== $confirmNewPwd) {
                echo json_encode(['status' => 'error', 'message' => 'Konfirmasi password tidak cocok.']);
                exit;
            }
            // Hash baru
            $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        } else {
            // Tidak ganti password
            $passwordHash = $existing['password'];
        }

        // Proses upload avatar
        if (!empty($_FILES['avatar']['name'])) {
            $uploadDir = __DIR__ . '/../uploads/avatars/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                echo json_encode(['status' => 'error', 'message' => 'Format avatar tidak didukung.']);
                exit;
            }

            // Hapus avatar lama jika bukan default
            if (!empty($existing['avatar']) && basename($existing['avatar']) !== 'herta-kurukuru.gif') {
                $oldPath = __DIR__ . '/../' . $existing['avatar'];
                if (file_exists($oldPath))
                    @unlink($oldPath);
            }

            $newName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            $target = $uploadDir . $newName;
            if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal upload avatar.']);
                exit;
            }
            $avatarPath = 'uploads/avatars/' . $newName;
        }

        // Proses upload background baru dan hapus lama
        if (!empty($_FILES['background']['name'])) {
            $uploadDir = __DIR__ . '/../uploads/backgrounds/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);
            $ext = strtolower(pathinfo($_FILES['background']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                echo json_encode(['status' => 'error', 'message' => 'Format background tidak didukung.']);
                exit;
            }
            if (!empty($existing['background'])) {
                $oldBg = __DIR__ . '/../' . $existing['background'];
                if (file_exists($oldBg))
                    @unlink($oldBg);
            }
            $newName = 'background_' . $userId . '_' . time() . '.' . $ext;
            $target = $uploadDir . $newName;
            if (!move_uploaded_file($_FILES['background']['tmp_name'], $target)) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal upload background.']);
                exit;
            }
            $background = 'uploads/backgrounds/' . $newName;
        }

        // Panggil fungsi updateUser di user_functions.php
        $res = updateUser(
            $userId,
            $newUsername ?: $existing['username'], // pass old username jika null
            $email,
            $passwordHash,
            $avatarPath ?: $existing['avatar'],
            $roleBaru ?: $existing['role'],
            $border ?: $existing['border'],
            $background ?: $existing['background']
        );


        // Update juga session (username, role, avatar)
        if ($res['status'] === 'success') {
            if (!empty($newUsername)) {
                $_SESSION['username'] = $newUsername;
            }
            if (!empty($roleBaru)) {
                $_SESSION['role'] = $roleBaru;
            }
            if (!empty($avatarPath)) {
                $_SESSION['avatar'] = $avatarPath;
            }
        }


        echo json_encode($res);
        exit;


    /* =========================
     * THREAD: GET_ALL_THREADS
     * ========================= */
    case 'get_all_threads':
        requireLogin();
        $threads = getAllThreads();
        echo json_encode(['status' => 'success', 'data' => $threads]);
        exit;

    /* =========================
     * THREAD: CREATE_THREAD
     * ========================= */
    case 'create_thread':
        requireLogin();
        $userId = $_SESSION['user_id'];
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $mediaPath = null;

        if (!empty($_FILES['media']['name'])) {
            $uploadDir = __DIR__ . '/../uploads/threads/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
            $newName = 'thread_' . $userId . '_' . time() . "." . $ext;
            $targetFile = $uploadDir . $newName;
            if (!move_uploaded_file($_FILES['media']['tmp_name'], $targetFile)) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal upload media.']);
                exit;
            }
            $mediaPath = 'uploads/threads/' . $newName;
        }

        $res = createThread($userId, $title, $content, $mediaPath);
        echo json_encode($res);
        exit;

    /* =========================
     * THREAD: UPDATE_THREAD
     * ========================= */
    case 'update_thread':
        requireLogin();
        $threadId = intval($_POST['thread_id'] ?? 0);
        $userId = $_SESSION['user_id'];
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $mediaPath = null;
        $isAdmin = isAdmin();

        // Ambil media lama dan hapus jika diganti
        if (!empty($_FILES['media']['name'])) {
            $existing = getThreadById($threadId);
            if (!empty($existing['media']) && file_exists(__DIR__ . '/../' . $existing['media'])) {
                @unlink(__DIR__ . '/../' . $existing['media']);
            }
            $uploadDir = __DIR__ . '/../uploads/threads/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
            $newName = 'thread_' . $userId . '_' . time() . '.' . $ext;
            $targetFile = $uploadDir . $newName;
            if (!move_uploaded_file($_FILES['media']['tmp_name'], $targetFile)) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal upload media.']);
                exit;
            }
            $mediaPath = 'uploads/threads/' . $newName;
        }

        $res = updateThread($threadId, $userId, $title, $content, $mediaPath, $isAdmin);
        echo json_encode($res);
        exit;

    /* =========================
     * THREAD: DELETE_THREAD
     * ========================= */
    case 'delete_thread':
        requireLogin();
        $threadId = intval($_POST['thread_id'] ?? 0);
        $userId = $_SESSION['user_id'];
        $isAdmin = isAdmin();
        // ── Hapus media lama kalau ada
        $existing = getThreadById($threadId);
        if (!empty($existing['media']) && file_exists(__DIR__ . '/../' . $existing['media'])) {
            @unlink(__DIR__ . '/../' . $existing['media']);
        }
        // ── Baru panggil deleteThread
        $res = deleteThread($threadId, $userId, $isAdmin);
        echo json_encode($res);
        exit;

    /* =========================
     * REPLY: GET_REPLIES
     * ========================= */
    case 'get_replies':
        requireLogin();
        $threadId = intval($_POST['thread_id'] ?? 0);
        $replies = getRepliesByThreadId($threadId);
        echo json_encode(['status' => 'success', 'data' => $replies]);
        exit;

    /* =========================
     * REPLY: CREATE_REPLY
     * ========================= */
    case 'create_reply':
        requireLogin();
        $threadId = intval($_POST['thread_id'] ?? 0);
        $userId = $_SESSION['user_id'];
        $content = trim($_POST['content'] ?? '');
        $mediaPath = null;

        if (!empty($_FILES['media']['name'])) {
            $uploadDir = __DIR__ . '/../uploads/replies/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
            $newName = 'reply_' . $userId . '_' . time() . "." . $ext;
            $targetFile = $uploadDir . $newName;
            if (!move_uploaded_file($_FILES['media']['tmp_name'], $targetFile)) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal upload media.']);
                exit;
            }
            $mediaPath = 'uploads/replies/' . $newName;
        }

        $res = createReply($threadId, $userId, $content, $mediaPath);
        echo json_encode($res);
        exit;

    /* =========================
     * REPLY: UPDATE_REPLY
     * ========================= */
    case 'update_reply':
        requireLogin();
        $replyId = intval($_POST['reply_id'] ?? 0);
        $userId = $_SESSION['user_id'];
        $content = trim($_POST['content'] ?? '');
        $mediaPath = null;
        $isAdmin = isAdmin();

        // Ambil media lama dan hapus jika diganti
        if (!empty($_FILES['media']['name'])) {
            $existing = getRepliesByThreadId($replyId);
            if (!empty($existing['media']) && file_exists(__DIR__ . '/../' . $existing['media'])) {
                @unlink(__DIR__ . '/../' . $existing['media']);
            }
            $uploadDir = __DIR__ . '/../uploads/replies/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
            $newName = 'reply_' . $userId . '_' . time() . '.' . $ext;
            $targetFile = $uploadDir . $newName;
            if (!move_uploaded_file($_FILES['media']['tmp_name'], $targetFile)) {
                echo json_encode(['status' => 'error', 'message' => 'Gagal upload media.']);
                exit;
            }
            $mediaPath = 'uploads/replies/' . $newName;
        }

        $res = updateReply($replyId, $userId, $content, $mediaPath, $isAdmin);
        echo json_encode($res);
        exit;

    /* =========================
     * REPLY: DELETE_REPLY
     * ========================= */
    case 'delete_reply':
        requireLogin();
        $replyId = intval($_POST['reply_id'] ?? 0);
        $userId = $_SESSION['user_id'];
        $isAdmin = isAdmin();
        // ── Hapus media lama kalau ada
        $existing = getRepliesByThreadId($replyId);
        if (!empty($existing['media']) && file_exists(__DIR__ . '/../' . $existing['media'])) {
            @unlink(__DIR__ . '/../' . $existing['media']);
        }
        // ── Baru panggil deleteReply
        $res = deleteReply($replyId, $userId, $isAdmin);
        echo json_encode($res);
        exit;

    /* =========================
     * SEARCH_THREADS
     * ========================= */
    case 'search_threads':
        requireLogin();
        $keyword = '%' . ($_POST['keyword'] ?? '') . '%';
        global $pdo;
        $stmt = $pdo->prepare("
            SELECT t.id, t.user_id, u.username, u.avatar, t.title, t.content, t.media, t.created_at, t.updated_at
            FROM threads t
            JOIN users u ON t.user_id = u.id
            WHERE t.title LIKE ? OR t.content LIKE ?
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$keyword, $keyword]);
        $threads = $stmt->fetchAll();
        echo json_encode(['status' => 'success', 'data' => $threads]);
        exit;

    /* =========================
     * GET_BORDERS (ADMIN+USER)
     * ========================= */
    case 'get_borders':
        requireLogin();
        global $pdo;
        $stmt = $pdo->query("SELECT filename FROM custom_borders ORDER BY uploaded_at DESC");
        $borders = [];
        while ($row = $stmt->fetch()) {
            $borders[] = ['filename' => $row['filename']];
        }
        echo json_encode(['status' => 'success', 'data' => $borders]);
        exit;

    /* =========================
     * UPLOAD_BORDER (ADMIN)
     * ========================= */
    case 'upload_border':
        requireLogin();
        requireAdmin();
        if (!isset($_FILES['uploadBorder']) || $_FILES['uploadBorder']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'File tidak ditemukan atau error upload.']);
            exit;
        }
        $file = $_FILES['uploadBorder'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['status' => 'error', 'message' => 'Format file border tidak didukung.']);
            exit;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['status' => 'error', 'message' => 'Ukuran file terlalu besar (maks 5MB).']);
            exit;
        }
        $uploadDir = __DIR__ . '/../uploads/borders/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0755, true);
        $newName = 'border_' . time() . '_' . uniqid() . '.' . $ext;
        $target = $uploadDir . $newName;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal upload border.']);
            exit;
        }
        // Masukkan ke tabel custom_borders
        $stmt = $pdo->prepare("INSERT INTO custom_borders (filename) VALUES (?)");
        $stmt->execute([$newName]);
        echo json_encode(['status' => 'success', 'filename' => $newName]);
        exit;

    /* =========================
     * DELETE_BORDER (ADMIN)
     * ========================= */
    case 'delete_border':
        requireLogin();
        requireAdmin();
        $border = $_POST['border'] ?? '';
        // validasi nama file
        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $border)) {
            echo json_encode(['status' => 'error', 'message' => 'Nama file tidak valid.']);
            exit;
        }
        $path = realpath(__DIR__ . '/../uploads/borders/' . $border);
        $base = realpath(__DIR__ . '/../uploads/borders');
        if (strpos($path, $base) !== 0 || !file_exists($path)) {
            echo json_encode(['status' => 'error', 'message' => 'File tidak ditemukan.']);
            exit;
        }
        // Hapus file
        if (!unlink($path)) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal hapus file.']);
            exit;
        }
        // Hapus record di DB
        $stmt = $pdo->prepare("DELETE FROM custom_borders WHERE filename = ?");
        $stmt->execute([$border]);
        echo json_encode(['status' => 'success']);
        exit;

    /* =========================
     * UPLOAD_BACKGROUND (ADMIN)
     * ========================= */
    case 'upload_background':
        requireLogin();
        if (!isset($_FILES['uploadBackground']) || $_FILES['uploadBackground']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => 'error', 'message' => 'File tidak ditemukan atau error upload.']);
            exit;
        }
        $file = $_FILES['uploadBackground'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            echo json_encode(['status' => 'error', 'message' => 'Format file background tidak didukung.']);
            exit;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            echo json_encode(['status' => 'error', 'message' => 'Ukuran file terlalu besar (maks 5MB).']);
            exit;
        }
        $uploadDir = __DIR__ . '/../uploads/backgrounds/';
        if (!is_dir($uploadDir))
            mkdir($uploadDir, 0755, true);
        $newName = 'background_' . time() . '_' . uniqid() . '.' . $ext;
        $target = $uploadDir . $newName;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            echo json_encode(['status' => 'error', 'message' => 'Gagal upload background.']);
            exit;
        }
        // Catat ke tabel custom_backgrounds (opsional)
        $stmt = $pdo->prepare("INSERT INTO custom_backgrounds (filename) VALUES (?)");
        $stmt->execute([$newName]);

        echo json_encode(['status' => 'success', 'filename' => $newName]);
        exit;


    /* =========================
     * DEFAULT: ACTION TIDAK VALID
     * ========================= */
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Action tidak valid: ' . $action
        ]);
        exit;
}