<?php
session_start();
?>

<!DOCTYPE html>
<!--
	Massively by HTML5 UP
	html5up.net | @ajlkn
	Free for personal and commercial use under the CCA 3.0 license (html5up.net/license)
-->
<html>

<head>
    <title>HertaHub - Beranda</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <noscript>
        <link rel="stylesheet" href="assets/css/noscript.css" />
    </noscript>
    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="stylesheet" href="assets/css/homeSlide.css">

</head>

<body>
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
            <li class="active"><a href="index.php">Home</a></li>
            <li><a href="pages/profile.php">Profile</a></li>
            <li><a href="pages/forum.php">Forum</a></li>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li><a href="pages/kelolaUser.php">Kelola User</a></li>
            <?php endif; ?>
            <li><a href="#" id="logoutHomeBtn">Logout</a></li>
            <?php else: ?>
            <li><a href="pages/login.php">Login / Register</a></li>
            <?php endif; ?>
        </ul>
        <ul class="icons">
            <li>
                <a href="https://twitter.com/" class="icon brands fa-twitter"><span class="label">Twitter</span></a>
            </li>
            <li>
                <a href="https://facebook.com/" class="icon brands fa-facebook-f"><span class="label">Facebook</span></a>
            </li>
            <li>
                <a href="https://instagram.com/" class="icon brands fa-instagram"><span class="label">Instagram</span></a>
            </li>
            <li>
                <a href="https://github.com/" class="icon brands fa-github"><span class="label">GitHub</span></a>
            </li>
        </ul>
    </nav>

    <!-- Main -->
    <div id="main">
        <div class="slider">
            <div class="slides">
                <div class="slide landscape">
                    <img src="images/ads/Benefit_Landscape.png" alt="Benefit_Landscape.png" width="700" height="auto">
                </div>
                <div class="slide portrait">
                    <img src="images/ads/Benefit_Portrait.png" alt="Benefit_Portrait.png" width="auto" height="600">
                </div>
                <div class="slide landscape">
                    <img src="images/ads/Benefit_0_Landscape.png" alt="Benefit_0_Landscape.png" width="700"
                        height="auto">
                </div>
                <div class="slide portrait">
                    <img src="images/ads/Benefit_0_Portrait.png" alt="Benefit_0_Portrait.png" width="auto" height="600">
                </div>
            </div>
            <button class="navg prev">&#10094;</button>
            <button class="navg next">&#10095;</button>
        </div>

        <div>
            <h2 class="title">Tentang HertaHub</h2>
            <p class="desc">
                Lorem ipsum dolor sit amet consectetur, adipisicing elit. In reiciendis iure laborum, veritatis aliquid
                architecto. Facere fugit dolorum minima nobis repellat? Minus aperiam voluptas reprehenderit deserunt
                possimus, harum veritatis excepturi?
            </p>
            <p class="desc">
                Lorem ipsum dolor, sit amet consectetur adipisicing elit. Architecto eius, non inventore nemo dolor
                nesciunt placeat eaque consequuntur vel distinctio enim harum, sed atque quidem temporibus eos!
                Officiis, excepturi impedit?
            </p>
        </div>
    </div>

    <!-- Footer -->
    <footer id="footer">
        <section class="split contact">
            <section class="alt">
                <h3>Address</h3>
                <p>
                    1234 Somewhere Road #87257<br />
                    Nashville, TN 00000-0000
                </p>
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
                    <li>
                        <a href="https://twitter.com/" class="icon brands alt fa-twitter"><span class="label">Twitter</span></a>
                    </li>
                    <li>
                        <a href="https://facebook.com/" class="icon brands alt fa-facebook-f"><span class="label">Facebook</span></a>
                    </li>
                    <li>
                        <a href="https://instagram.com/" class="icon brands alt fa-instagram"><span class="label">Instagram</span></a>
                    </li>
                    <li>
                        <a href="https://github.com/" class="icon brands alt fa-github"><span class="label">GitHub</span></a>
                    </li>
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

    <!-- Scripts -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/jquery.scrollex.min.js"></script>
    <script src="assets/js/jquery.scrolly.min.js"></script>
    <script src="assets/js/browser.min.js"></script>
    <script src="assets/js/breakpoints.min.js"></script>
    <script src="assets/js/util.js"></script>
    <script src="assets/js/main.js"></script>

    <script src="assets/js/homeSlide.js"></script>
    <script src="assets/js/index.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/live2d-widget@3.1.4/lib/L2Dwidget.min.js"></script>
    <script src="assets/js/miku.js"></script>

    <script>
    $('#logoutHomeBtn').on('click', function(e) {
        e.preventDefault();
        $.post('includes/api.php', {
            action: 'logout'
        }, function(resp) {
            if (resp.status === 'success') {
                window.location.href = 'index.php';
            }
        }, 'json');
    });
    </script>
</body>

</html>