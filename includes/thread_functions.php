<?php
// includes/thread_functions.php
// ---------------------------------

require_once __DIR__ . '/db.php';

/**
 * Ambil semua thread (bisa nanti di‐order by tanggal DESC)
 *
 * @return array
 */
function getAllThreads(): array {
    global $pdo;
    $stmt = $pdo->query(
        "SELECT t.id, t.user_id, u.username, u.avatar, u.border, t.title, t.content, t.media, t.created_at
        FROM threads t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC"
    );
    return $stmt->fetchAll();
}

/**
 * Ambil satu thread berdasarkan ID
 *
 * @param int $threadId
 * @return array|null
 */
function getThreadById(int $threadId): ?array {
    global $pdo;
    $stmt = $pdo->prepare(
        "SELECT t.id, t.user_id, u.username, u.avatar, u.border, t.title, t.content, t.media, t.created_at
        FROM threads t
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ?"
    );
    $stmt->execute([$threadId]);
    return $stmt->fetch() ?: [];
}

/**
 * Buat thread baru
 *
 * @param int $userId
 * @param string $title
 * @param string $content
 * @param string|null $mediaPath  (relatif path di uploads/threads/...)
 * @return array ['status'=>'success'|'error', 'message'=>string]
 */
function createThread(int $userId, string $title, string $content, ?string $mediaPath): array {
    global $pdo;
    $stmt = $pdo->prepare("
        INSERT INTO threads (user_id, title, content, media)
        VALUES (?, ?, ?, ?)
    ");
    try {
        $stmt->execute([$userId, $title, $content, $mediaPath]);
        return ['status' => 'success', 'message' => 'Thread berhasil dibuat.'];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Gagal membuat thread: ' . $e->getMessage()];
    }
}

/**
 * Update thread (hanya owner atau admin)
 *
 * @param int $threadId
 * @param int $userId  (untuk cek kepemilikan atau admin)
 * @param string $title
 * @param string $content
 * @param string|null $mediaPath
 * @param bool $isAdmin
 * @return array
 */
function updateThread(int $threadId, int $userId, string $title, string $content, ?string $mediaPath, bool $isAdmin = false): array {
    global $pdo;
    // Cek ownership dulu
    $stmt = $pdo->prepare("SELECT user_id FROM threads WHERE id = ?");
    $stmt->execute([$threadId]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['status' => 'error', 'message' => 'Thread tidak ditemukan.'];
    }
    if (!$isAdmin && intval($row['user_id']) !== $userId) {
        return ['status' => 'error', 'message' => 'Anda tidak berhak mengedit thread ini.'];
    }
    // Lakukan update
    $sql = "UPDATE threads SET title = ?, content = ?";
    $params = [$title, $content];
    if ($mediaPath !== null) {
        $sql .= ", media = ?";
        $params[] = $mediaPath;
    }
    $sql .= " WHERE id = ?";
    $params[] = $threadId;
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute($params);
        return ['status' => 'success', 'message' => 'Thread berhasil diperbarui.'];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Gagal memperbarui thread: ' . $e->getMessage()];
    }
}

/**
 * Hapus thread (hanya owner atau admin)
 *
 * @param int $threadId
 * @param int $userId
 * @param bool $isAdmin
 * @return array
 */
function deleteThread(int $threadId, int $userId, bool $isAdmin = false): array {
    global $pdo;
    // Cek ownership
    $stmt = $pdo->prepare("SELECT user_id, media FROM threads WHERE id = ?");
    $stmt->execute([$threadId]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['status' => 'error', 'message' => 'Thread tidak ditemukan.'];
    }
    if (!$isAdmin && intval($row['user_id']) !== $userId) {
        return ['status' => 'error', 'message' => 'Anda tidak berhak menghapus thread ini.'];
    }
    // Kalau ada file media, hapus file server
    if (!empty($row['media']) && file_exists(__DIR__ . '/../' . $row['media'])) {
        @unlink(__DIR__ . '/../' . $row['media']);
    }
    // 3. Ambil semua replies milik thread ini
    $stmt = $pdo->prepare("SELECT id, media FROM replies WHERE thread_id = ?");
    $stmt->execute([$threadId]);
    $replies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Loop untuk hapus file media tiap reply
    foreach ($replies as $r) {
        if (!empty($r['media']) && file_exists(__DIR__ . '/../' . $r['media'])) {
            @unlink(__DIR__ . '/../' . $r['media']);
        }
    }
    // Hapus dari DB
    $stmt = $pdo->prepare("DELETE FROM threads WHERE id = ?");
    try {
        $stmt->execute([$threadId]);
        return ['status' => 'success', 'message' => 'Thread berhasil dihapus.'];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Gagal menghapus thread: ' . $e->getMessage()];
    }
}

/**
 * Ambil semua reply untuk suatu thread
 *
 * @param int $threadId
 * @return array
 */
function getRepliesByThreadId(int $threadId): array {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT r.id, r.thread_id, r.user_id, u.username, u.avatar, r.content, r.media, r.created_at
        FROM replies r
        JOIN users u ON r.user_id = u.id
        WHERE r.thread_id = ?
        ORDER BY r.created_at ASC
    ");
    $stmt->execute([$threadId]);
    return $stmt->fetchAll();
}

/**
 * Buat reply baru
 *
 * @param int $threadId
 * @param int $userId
 * @param string $content
 * @param string|null $mediaPath
 * @return array
 */
function createReply(int $threadId, int $userId, string $content, ?string $mediaPath): array {
    global $pdo;
    // Pastikan threadId valid
    $stmt = $pdo->prepare("SELECT id FROM threads WHERE id = ?");
    $stmt->execute([$threadId]);
    if (!$stmt->fetch()) {
        return ['status' => 'error', 'message' => 'Thread tidak ditemukan.'];
    }
    // Insert
    $stmt = $pdo->prepare("
        INSERT INTO replies (thread_id, user_id, content, media)
        VALUES (?, ?, ?, ?)
    ");
    try {
        $stmt->execute([$threadId, $userId, $content, $mediaPath]);
        return ['status' => 'success', 'message' => 'Reply berhasil dikirim.'];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Gagal membuat reply: ' . $e->getMessage()];
    }
}

/**
 * Update reply (hanya owner atau admin)
 *
 * @param int $replyId
 * @param int $userId
 * @param string $content
 * @param string|null $mediaPath
 * @param bool $isAdmin
 * @return array
 */
function updateReply(int $replyId, int $userId, string $content, ?string $mediaPath, bool $isAdmin = false): array {
    global $pdo;
    // Cek kepemilikan
    $stmt = $pdo->prepare("SELECT user_id, media FROM replies WHERE id = ?");
    $stmt->execute([$replyId]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['status' => 'error', 'message' => 'Reply tidak ditemukan.'];
    }
    if (!$isAdmin && intval($row['user_id']) !== $userId) {
        return ['status' => 'error', 'message' => 'Anda tidak berhak mengedit reply ini.'];
    }
    // Hapus file lama (jika ada)
    if (!empty($row['media']) && file_exists(__DIR__ . '/../' . $row['media'])) {
        @unlink(__DIR__ . '/../' . $row['media']);
    }
    // Update
    $sql = "UPDATE replies SET content = ?";
    $params = [$content];
    if ($mediaPath !== null) {
        $sql .= ", media = ?";
        $params[] = $mediaPath;
    }
    $sql .= " WHERE id = ?";
    $params[] = $replyId;

    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute($params);
        return ['status' => 'success', 'message' => 'Reply berhasil diperbarui.'];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Gagal memperbarui reply: ' . $e->getMessage()];
    }
}

/**
 * Hapus reply (hanya owner atau admin)
 *
 * @param int $replyId
 * @param int $userId
 * @param bool $isAdmin
 * @return array
 */
function deleteReply(int $replyId, int $userId, bool $isAdmin = false): array {
    global $pdo;
    // Cek kepemilikan
    $stmt = $pdo->prepare("SELECT user_id, media FROM replies WHERE id = ?");
    $stmt->execute([$replyId]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['status' => 'error', 'message' => 'Reply tidak ditemukan.'];
    }
    if (!$isAdmin && intval($row['user_id']) !== $userId) {
        return ['status' => 'error', 'message' => 'Anda tidak berhak menghapus reply ini.'];
    }
    // Hapus file lama
    if (!empty($row['media']) && file_exists(__DIR__ . '/../' . $row['media'])) {
        @unlink(__DIR__ . '/../' . $row['media']);
    }
    // Delete dari DB
    $stmt = $pdo->prepare("DELETE FROM replies WHERE id = ?");
    try {
        $stmt->execute([$replyId]);
        return ['status' => 'success', 'message' => 'Reply berhasil dihapus.'];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Gagal menghapus reply: ' . $e->getMessage()];
    }
}
