<?php
// pages/forum.php
require_once __DIR__ . '/../includes/auth.php';
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE HTML>
<!--
	Massively by HTML5 UP
	html5up.net | @ajlkn
	Free for personal and commercial use under the CCA 3.0 license (html5up.net/license)
-->
<html>

<head>
    <title>HertaHub - Forum</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="../assets/css/main.css" />
    <link rel="stylesheet" href="../assets/css/style.css">
    <noscript>
        <link rel="stylesheet" href="../assets/css/noscript.css" />
    </noscript>
    <link rel="stylesheet" href="../assets/css/forum.css">
</head>

<body class="is-preload">
    <!-- Popup -->
    <div id="toast" class="toast"></div>

    <!-- Wrapper -->
    <div id="wrapper" class="fade-in">
        <div id="box">
            <!-- Intro -->
            <div id="intro">
                <div class="planet-border">
                    <div class="planet"></div>
                    <h1 class="introAnime">
                        Herta<br />
                        Hub
                    </h1>
                    <p style="font-size: x-small; line-height: 1px;">Pusat Forum & Diskusi</p>
                </div>
            </div>
            <p class="introDesc">
                <span class="text kuru">Kuru Kuru</span>
                <span class="text kururin">Kururin</span>
            </p>
            <ul class="actions">
                <li>
                    <a href="#header" class="button icon solid solo fa-arrow-down scrolly"></a>
                </li>
            </ul>
        </div>
    </div>

    <!-- Header -->
    <div id="boxes">
        <header id="header">
            <a href="#main" class="logo">HertaHub</a>
        </header>
    </div>

    <!-- Nav -->
    <nav id="nav">
        <ul class="links">
            <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="../index.php">Home</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li class="active"><a href="forum.php">Forum</a></li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li><a href="kelolaUser.php">Kelola User</a></li>
            <?php endif; ?>
            <li><a href="#" id="logoutBtn">Logout</a></li>
            <?php else: ?>
            <li><a href="login.php">Login / Register</a></li>
            <?php endif; ?>
        </ul>
        <ul class="icons">
            <li><a href="https://twitter.com/" class="icon brands fa-twitter"><span class="label">Twitter</span></a>
            </li>
            <li><a href="https://facebook.com/" class="icon brands fa-facebook-f"><span
                        class="label">Facebook</span></a></li>
            <li><a href="https://instagram.com/" class="icon brands fa-instagram"><span
                        class="label">Instagram</span></a></li>
            <li><a href="https://github.com/" class="icon brands fa-github"><span class="label">GitHub</span></a></li>
        </ul>
    </nav>

    <!-- Main -->
    <section id="main" class="posts">
        <div class="inner">
            <header class="major">
                <h1>Forum Diskusi</h1>
            </header>

            <section class="post" id="createThreadSection">
                <!-- Form Buat Thread Baru -->
                <form id="threadForm" enctype="multipart/form-data">
                    <div class="fields">
                        <div class="field">
                            <input type="text" id="title" name="title" required maxlength="100"
                                placeholder="Judul thread (maks. 100 karakter)" style="width:100%; padding:8px;" /><br>
                        </div>
                        <div class="field">
                            <textarea id="content" name="content" required placeholder="Tulis konten thread..."
                                style="width:100%; padding:8px; height:100px;"></textarea><br>
                        </div>
                        <div class="field">
                            <input type="file" id="media" name="media" accept="*/*" style="display:none;" />
                            <label for="media" class="button small">Pilih File (opsional)</label>
                        </div>

                        <div class="field">
                            <span id="fileInfo" style="font-size:0.9em; color:#555; white-space: pre-wrap; word-wrap: break-word;"></span>
                            <div id="mediaPreview" style="margin-bottom:10px;"></div>
                        </div>
                    </div>

                    <ul class="actions">
                        <button type="submit" class="button primary" id="submitThreadBtn">Posting</button>
                    </ul>
            </section>

            <section>
                <!-- Search Thread -->
                <input type="text" id="searchInput" placeholder="Cari thread..."
                    style="width:100%; padding:8px; margin-bottom:20px;" />
            </section>

            <!-- Daftar Thread -->
            <div id="threadList"></div>
        </div>
    </section>



    <!-- Footer -->
    <footer id="footer">
        <section class="split contact">
            <section class="alt">
                <h3>Address</h3>
                <p>1234 Somewhere Road #87257<br />
                    Nashville, TN 00000-0000</p>
            </section>
            <section>
                <h3>Phone</h3>
                <p><a href="#">(000) 000-0000</a></p>
            </section>
            <section>
                <h3>Email</h3>
                <p><a href="#">info@untitled.tld</a></p>
            </section>
            <section>
                <h3>Social</h3>
                <ul class="icons alt">
                    <li><a href="https://twitter.com/" class="icon brands alt fa-twitter"><span
                                class="label">Twitter</span></a></li>
                    <li><a href="https://facebook.com/" class="icon brands alt fa-facebook-f"><span
                                class="label">Facebook</span></a>
                    </li>
                    <li><a href="https://instagram.com/" class="icon brands alt fa-instagram"><span
                                class="label">Instagram</span></a>
                    </li>
                    <li><a href="https://github.com/" class="icon brands alt fa-github"><span
                                class="label">GitHub</span></a></li>
                </ul>
            </section>
        </section>
    </footer>

    <!-- Copyright -->
    <div id="copyright">
        <ul>
            <li>&copy; Untitled</li>
            <li>Design: <a href="https://html5up.net">HTML5 UP</a></li>
        </ul>
    </div>

    </div>

    <!-- Scripts -->
    <script src="../assets/js/jquery.min.js"></script>
    <script src="../assets/js/jquery.scrollex.min.js"></script>
    <script src="../assets/js/jquery.scrolly.min.js"></script>
    <script src="../assets/js/browser.min.js"></script>
    <script src="../assets/js/breakpoints.min.js"></script>
    <script src="../assets/js/util.js"></script>
    <script src="../assets/js/main.js"></script>
    <script src="../assets/js/forum.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/live2d-widget@3.1.4/lib/L2Dwidget.min.js"></script>
    <script src="../assets/js/miku.js"></script>

</body>

</html>