// assets/js/login.js
$(document).ready(function () {
  // ============================================================
  // 1. Deklarasi variabel global sebelum digunakan
  // ============================================================
  let isRegisterMode = false;
  let bypassValidation = false;

  // ============================================================
  // 2. Fungsi untuk menghasilkan captcha sederhana (penjumlahan 2 angka)
  // ============================================================
  function generateCaptcha() {
    const a = Math.floor(Math.random() * 10) + 1;
    const b = Math.floor(Math.random() * 10) + 1;
    $("#captchaQuestion").text(`${a} + ${b}`);
    $("#captchaSolution").val(a + b);
  }

  // ============================================================
  // 3. Fungsi toggle antara mode Login dan Register
  // ============================================================
  function toggleMode() {
    isRegisterMode = !isRegisterMode;
    if (isRegisterMode) {
      $("#googleLoginBtn").hide();
      $("#formTitle").text("Register");
      $("#submitBtn").text("Register");
      $("#confirmDiv").removeClass("hidden");
      $("#emailDiv").removeClass("hidden");
      $("#toggleBtn").text("Sudah punya akun? Login");
    } else {
      $("#googleLoginBtn").show();
      $("#formTitle").text("Login");
      $("#submitBtn").text("Login");
      $("#confirmDiv").addClass("hidden");
      $("#emailDiv").addClass("hidden");
      $("#toggleBtn").text("Belum punya akun? Register");
    }
    // Segarkan captcha setiap kali pindah mode
    generateCaptcha();
    // Kosongkan input captcha
    $("#captchaInput").val("");
  }

  // Bind click:
  $("#googleLoginBtn").on("click", (e) => {
    e.preventDefault();
    window.location.href = "../includes/google-login.php";
  });

  // ============================================================
  // 4. Fungsi skip login: “Login Nanti”
  // ============================================================
  function loginLater() {
    bypassValidation = true;
    const form = document.querySelector(".form-container");
    const portal = document.getElementById("portalTransition");

    form.classList.add("form-break");

    setTimeout(() => {
      portal.classList.remove("hidden");
      portal.classList.add("show");
    }, 2000);

    setTimeout(() => {
      window.location.href = "../index.php";
    }, 3000);
    // Misalnya redirect ke homepage
  }

  // ============================================================
  // 5. Fungsi menampilkan toast (pesan singkat)
  // ============================================================
  function showToast(msg) {
    const t = $("#toast");
    t.text(`⚠️ ${msg}`);
    t.addClass("show");
    setTimeout(() => t.removeClass("show"), 3000);
  }

  // ============================================================
  // 6. Bind event listener:
  //    - Tombol toggle mode
  //    - Tombol Login Nanti
  //    - Submit form
  // ============================================================
  $("#toggleBtn").on("click", function (e) {
    e.preventDefault();
    toggleMode();
  });

  $("#loginLaterBtn").on("click", function (e) {
    e.preventDefault();
    loginLater();
  });

  // Generate captcha pertama kali saat halaman dibuka
  generateCaptcha();

  // ============================================================
  // 7. Handler untuk submit form Login/Register
  // ============================================================
  $("#authForm").on("submit", function (e) {
    e.preventDefault();

    // Jika user memilih skip login, kita abaikan submit
    if (bypassValidation) {
      return;
    }

    const username = $("#username").val().trim();
    const password = $("#password").val();
    const captchaAnswer = $("#captchaInput").val().trim();
    const captchaSolution = $("#captchaSolution").val();

    if (!username || !password) {
      showToast("Username dan password wajib diisi.");
      return;
    }

    // ========================================================
    // MODE REGISTER
    // ========================================================
    if (isRegisterMode) {
      const confirmPassword = $("#confirmPassword").val();
      const email = $("#email").val().trim();
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

      if (email === "") {
        showToast("Email tidak boleh kosong, ya!");
        return;
      }

      if (!emailRegex.test(email)) {
        showToast("Format email tidak valid. Gunakan email yang benar, seperti example@mail.com.");
        return;
      }

      if (password !== confirmPassword) {
        showToast("Password konfirmasi tidak cocok.");
        return;
      }
      if (captchaAnswer === "" || captchaAnswer !== captchaSolution) {
        showToast("Jawaban captcha salah.");
        generateCaptcha();
        return;
      }

      // Kirim data register ke API
      $.ajax({
        url: "../includes/api.php",
        type: "POST",
        data: {
          action: "register",
          username: username,
          password: password,
          confirmPassword: confirmPassword,
          email: email,
          captchaAnswer: captchaAnswer,
          captchaSolution: captchaSolution,
        },
        dataType: "json",
        success: function (resp) {
          if (resp.status === "success") {
            showToast(resp.message);
            // Setelah berhasil register, kembali ke mode Login
            toggleMode();
          } else {
            showToast(resp.message || "Registrasi gagal.");
            generateCaptcha();
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error (register):", status, error);
          console.error("Response Text:", xhr.responseText);
          showToast("Kesalahan server. Coba lagi.");
          generateCaptcha();
        },
      });

      // ========================================================
      // MODE LOGIN
      // ========================================================
    } else {
      if (captchaAnswer === "" || captchaAnswer !== captchaSolution) {
        showToast("Jawaban captcha salah.");
        generateCaptcha();
        return;
      }
      // Kirim data login ke API
      $.ajax({
        url: "../includes/api.php",
        type: "POST",
        data: {
          action: "login",
          username: username,
          password: password,
          captchaAnswer: captchaAnswer,
          captchaSolution: captchaSolution,
        },
        dataType: "json",
        success: function (resp) {
          if (resp.status === "success") {
            showToast(resp.message);
            const form = document.querySelector(".form-container");
            const portal = document.getElementById("portalTransition");

            form.classList.add("form-break");

            setTimeout(() => {
              portal.classList.remove("hidden");
              portal.classList.add("show");
            }, 2000);

            setTimeout(() => {
              window.location.href = "forum.php";
            }, 3000);
          } else {
            showToast(resp.message || "Login gagal.");
            generateCaptcha();
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error (login):", status, error);
          console.error("Response Text:", xhr.responseText);
          showToast("Kesalahan server. Coba lagi.");
          generateCaptcha();
        },
      });
    }
  });
});
