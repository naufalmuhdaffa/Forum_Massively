<?php
// includes/user_functions.php
// ---------------------------------

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/user_functions.php';

/**
 * Mendaftarkan user baru
 *
 * @param string $username
 * @param string $plainPassword
 * @param string|null $email
 * @return array ['status'=>'success'|'error', 'message'=>string]
 */
function registerUser(string $username, string $plainPassword, ?string $email): array
{
    global $pdo;

    // Cek apakah username sudah dipakai
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return ['status' => 'error', 'message' => 'Username sudah terdaftar.'];
    }

    // Hash password
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    $role = 'user'; // default

    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");
    try {
        $stmt->execute([$username, $hash, $email, $role]);
        return ['status' => 'success', 'message' => 'Registrasi berhasil. Silakan login.'];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Gagal mendaftar: ' . $e->getMessage()];
    }
}

/**
 * Melakukan login: cek username & password
 *
 * @param string $username
 * @param string $plainPassword
 * @return array ['status'=>'success'|'error', 'message'=>string, 'data'=>[user data jika success]]
 */
function loginUser(string $username, string $plainPassword): array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if (!$user) {
        return ['status' => 'error', 'message' => 'Username tidak ditemukan.'];
    }
    if (!password_verify($plainPassword, $user['password'])) {
        return ['status' => 'error', 'message' => 'Password salah.'];
    }
    // Hapus kolom password sebelum dikembalikan
    unset($user['password']);
    return ['status' => 'success', 'message' => 'Login berhasil.', 'data' => $user];
}

/**
 * Mengambil data user berdasarkan ID
 *
 * @param int $id
 * @return array|null
 */
function getUserById(int $id): ?array
{
    global $pdo;
    $stmt = $pdo->prepare("
    SELECT id, username, email, password, role, border, avatar, background
    FROM users WHERE id = ?
");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Mengambil data user berdasarkan username
 *
 * @param string $username
 * @return array|null
 */
function getUserByUsername(string $username): ?array
{
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT id, username, email, role, avatar, border, background, created_at, updated_at
        FROM users
        WHERE username = ?
    ");
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Mengambil semua user (for admin kelola user)
 *
 * @return array
 */
function getAllUsers(): array
{
    global $pdo;
    $stmt = $pdo->query("
        SELECT id, username, email, role, avatar, border, background, created_at, updated_at
        FROM users
    ");
    return $stmt->fetchAll();
}

/**
 * Mengupdate data user (untuk admin atau user mengubah profil)
 *
 * @param int $id
 * @param string $username
 * @param string|null $email
 * @param string|null $plainPassword
 * @param string|null $avatarPath
 * @param string $role
 * @param string|null $border
 * @param string|null $background
 * @return array ['status'=>'success'|'error','message'=>string]
 */
function updateUser(
    int $id,
    string $username,
    ?string $email,
    ?string $plainPassword,
    ?string $avatarPath,
    string $role,
    ?string $border,
    ?string $background
): array {
    global $pdo;
    $params = [];
    $sqlParts = [];

    // Ganti username
    if ($username !== '') {
        $sqlParts[] = "username = ?";
        $params[] = $username;
    }
    // Ganti email
    if ($email !== null) {
        $sqlParts[] = "email = ?";
        $params[] = $email;
    }
    // Ganti password (hash)
    if ($plainPassword !== null) {
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $sqlParts[] = "password = ?";
        $params[] = $hash;
    }
    // Ganti avatar
    if ($avatarPath !== null) {
        $sqlParts[] = "avatar = ?";
        $params[] = $avatarPath;
    }
    // Ganti role
    if ($role !== '') {
        $sqlParts[] = "role = ?";
        $params[] = $role;
    }
    // Ganti border
    if ($border !== null) {
        $sqlParts[] = "border = ?";
        $params[] = $border;
    }
    // Ganti background
    if ($background !== null) {
        $sqlParts[] = "background = ?";
        $params[] = $background;
    }

    if (empty($sqlParts)) {
        return ['status' => 'error', 'message' => 'Tidak ada data untuk diupdate.'];
    }

    $params[] = $id;
    $sql = "UPDATE users SET " . implode(", ", $sqlParts) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute($params);
        return ['status' => 'success', 'message' => 'Profil berhasil diperbarui.'];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Gagal memperbarui user: ' . $e->getMessage()];
    }
}

/**
 * Menghapus user (hanya admin)
 *
 * @param int $id
 * @return array
 */
function deleteUser(int $id): array
{
    global $pdo;

    try {
        // 1. Ambil data user untuk file avatar & background
        $stmtUser = $pdo->prepare("SELECT avatar, background FROM users WHERE id = ?");
        $stmtUser->execute([$id]);
        $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            // Hapus avatar (kecuali default)
            if (!empty($user['avatar']) && basename($user['avatar']) !== 'herta-kurukuru.gif') {
                $avatarPath = __DIR__ . '/../' . $user['avatar'];
                if (file_exists($avatarPath)) {
                    @unlink($avatarPath);
                }
            }
            // Hapus background (kecuali default)
            if (!empty($user['background'])) {
                $background = __DIR__ . '/../' . $user['background'];
                if (file_exists($background)) {
                    @unlink($background);
                }
            }
        }

        // 2. Ambil dan hapus semua thread + media-nya
        $stmtTh = $pdo->prepare("SELECT id, media FROM threads WHERE user_id = ?");
        $stmtTh->execute([$id]);
        $threads = $stmtTh->fetchAll(PDO::FETCH_ASSOC);
        foreach ($threads as $t) {
            if (!empty($t['media'])) {
                $threadMedia = __DIR__ . '/../' . $t['media'];
                if (file_exists($threadMedia)) {
                    @unlink($threadMedia);
                }
            }
            // Hapus replies pada thread itu + media-nya
            $stmtRp = $pdo->prepare("SELECT media FROM replies WHERE thread_id = ?");
            $stmtRp->execute([$t['id']]);
            $replies = $stmtRp->fetchAll(PDO::FETCH_ASSOC);
            foreach ($replies as $r) {
                if (!empty($r['media'])) {
                    $replyMedia = __DIR__ . '/../' . $r['media'];
                    if (file_exists($replyMedia)) {
                        @unlink($replyMedia);
                    }
                }
            }
            // Hapus reply & thread di database
            $pdo->prepare("DELETE FROM replies WHERE thread_id = ?")->execute([$t['id']]);
            $pdo->prepare("DELETE FROM threads WHERE id = ?")->execute([$t['id']]);
        }

        // 3. Akhirnya hapus user-nya
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        return ['status' => 'success', 'message' => 'User dan semua datanya berhasil dihapus.'];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Gagal menghapus user: ' . $e->getMessage()];
    }
}

// Login Google
function getUserByEmail($email)
{
    global $pdo;  // gunakan $pdo, bukan $conn atau $mysqli
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    return $stmt->fetch(PDO::FETCH_ASSOC); // false kalau gak ada
}

function insertUser($data)
{
    global $pdo;
    $stmt = $pdo->prepare("
      INSERT INTO users (username, password, email, google_id, role)
      VALUES (:username, :password, :email, :google_id, :role)
    ");
    $stmt->execute([
        'username' => $data['username'],
        'password' => $data['password'],    // kosong string untuk Google user
        'email' => $data['email'],
        'google_id' => $data['google_id'],
        'role' => $data['role']
    ]);
    return $pdo->lastInsertId();
}