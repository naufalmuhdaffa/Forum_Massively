<?php
// pages/kelolaUser.php
require_once __DIR__ . '/../includes/auth.php';
if (!isAdmin()) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HertaHub - Kelola User</title>
    <link rel="stylesheet" href="../assets/css/kelolaUser.css" />
</head>

<body>
    <!-- Popup -->
    <div id="toast" class="toast"></div>

    <div class="container">
        <header>
            <h1>Kelola User</h1>
        </header>

        <button id="logoutBtn">Logout</button>
        <hr />

        <table id="userTable" border="1" cellpadding="5" cellspacing="0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                <!-- Konten akan di‐generate oleh kelolaUser.js -->
            </tbody>
        </table>
    </div>
    <div class="berandaBtn">
        <a href="../index.php"><button class="backBtn">Beranda</button></a>
    </div>

    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/kelolaUser.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/live2d-widget@3.1.4/lib/L2Dwidget.min.js"></script>
    <script src="../assets/js/miku.js"></script>
</body>

</html>