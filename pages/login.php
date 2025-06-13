<?php
// pages/login.php
session_start();
// Jika sudah login, langsung redirect ke forum.php
if (isset($_SESSION['user_id'])) {
    header("Location: forum.php");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>HertaHub - Login / Register</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="../assets/css/fontawesome-all.min.css">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>

<body>
    <!-- Popup -->
    <div id="toast" class="toast"></div>

    <div class="form-container">
        <h2 id="formTitle">Login</h2>

        <form id="authForm" enctype="multipart/form-data">
            <input type="text" id="username" name="username" placeholder="Username" required /><br><br>
            <input type="password" id="password" name="password" placeholder="Password" required /><br><br>

            <!-- Hanya muncul di mode Register -->
            <div id="confirmDiv" class="hidden">
                <input type="password" id="confirmPassword" name="confirmPassword"
                    placeholder="Konfirmasi Password" /><br><br>
            </div>
            <div id="emailDiv" class="hidden">
                <input type="email" id="email" name="email" placeholder="Email (opsional)" /><br><br>
            </div>

            <!-- Captcha -->
            <div id="captchaDiv">
                <label id="captchaLabel">Berapa hasil dari <span id="captchaQuestion"></span> ?</label><br>
                <input type="text" id="captchaInput" placeholder="Jawaban captcha" required /><br><br>
                <input type="hidden" id="captchaSolution" />
            </div>

            <button type="submit" id="submitBtn">Login</button>

            <button type="button" id="googleLoginBtn" class="google-btn">
                <i class="fab fa-google"></i> Login dengan Google
            </button>
        </form>

        <br>
        <button id="toggleBtn">Belum punya akun? Register</button>
        <br><br>
        <!-- Tombol Skip Login (Login Nanti) -->
        <button id="loginLaterBtn">Login Nanti</button>
    </div>

    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/login.js"></script>
    <div id="portalTransition" class="hidden"></div>

    <script src="https://cdn.jsdelivr.net/npm/live2d-widget@3.1.4/lib/L2Dwidget.min.js"></script>
    <script src="../assets/js/miku.js"></script>
</body>

</html>