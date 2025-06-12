/* assets/js/profile.js (versi MySQL + PHP) */

const API = "../includes/api.php";

const baseURL =
  window.location.origin + window.location.pathname.replace(/\/pages\/.*$/, "");
const defaultBorders = ["Default_Border.png", "We_Heart_It.gif"];

function showToast(msg, dur = 3000) {
  const t = document.getElementById("toast");
  t.textContent = msg;
  t.classList.add("show");
  setTimeout(() => t.classList.remove("show"), dur);
}

/**
 * Ambil data session user saat ini dari PHP
 * Endpoint: POST api.php?action=get_current_user
 * Response: { status: 'success', data: { id, username, email, role, avatar, border, background, ... } }
 */
async function fetchCurrentUser() {
  try {
    const form = new FormData();
    form.append("action", "get_current_user");
    const res = await fetch(API, {
      method: "POST",
      credentials: "include",
      body: form,
    });
    const json = await res.json();
    if (json.status === "success") {
      return json.data;
    } else {
      throw new Error(json.message || "Gagal mengambil data user.");
    }
  } catch (err) {
    console.error(err);
    return null;
  }
}

/**
 * Ambil daftar custom borders dari database
 * Endpoint (baru) : POST api.php?action=get_borders
 * Response: { status: 'success', data: [ { filename }, ... ] }
 */
async function fetchCustomBorders() {
  try {
    const form = new FormData();
    form.append("action", "get_borders");
    const res = await fetch(API, {
      method: "POST",
      credentials: "include",
      body: form,
    });
    const json = await res.json();
    return json.status === "success" ? json.data : [];
  } catch {
    return [];
  }
}

/**
 * Update profile user (username, email, role, avatar, border, background, password)
 * Kita akan kirim via FormData (karena bisa include file avatar)
 * Endpoint: POST api.php?action=update_user
 * - Jika update password, sertakan old_password & new_password
 * - Jika update avatar utawa border/background, sertakan file
 */
async function updateProfile(payload, files = {}) {
  try {
    const form = new FormData();
    // ✨ Ubah action jadi update_profile
    form.append("action", "update_profile");

    // Kalau mau ganti username, role, password, border, background:
    if (payload.newUsername) form.append("newUsername", payload.newUsername);
    if (payload.email) form.append("email", payload.email);
    if (payload.role) form.append("role", payload.role);
    if (payload.oldPassword && payload.newPassword) {
      form.append("old_password", payload.oldPassword);
      form.append("plainPassword", payload.newPassword);
    }
    if (payload.border) form.append("border", payload.border);
    if (payload.background) form.append("background", payload.background);

    // File avatar
    if (files.avatarFile) {
      form.append("avatar", files.avatarFile);
    }

    const res = await fetch(API, {
      method: "POST",
      credentials: "include",
      body: form,
    });
    return await res.json();
  } catch (err) {
    console.error(err);
    return { status: "error", message: "Gagal koneksi ke server." };
  }
}

/* ----------------------------------------------------
   INISIALISASI UI: Setelah DOM siap, kita fetch data user
----------------------------------------------------- */
document.addEventListener("DOMContentLoaded", async () => {
  // 1. Ambil data user via PHP session
  const user = await fetchCurrentUser();
  if (!user) {
    alert("Silakan login terlebih dahulu!");
    return (window.location.href = "login.php");
  }

  // 2. Inisialisasi variabel
  let currentUser = user; // { id, username, email, role, avatar, border, background, ... }
  const { id: userId, username, role } = currentUser;

  // 3. DOM Elements
  const profileHeader = document.getElementById("profileHeader");
  const avatarImg = document.getElementById("avatarImg");
  const borderOverlay = document.getElementById("borderOverlay");
  const displayUsername = document.getElementById("displayUsername");

  const usernameInput = document.getElementById("usernameInput");
  const saveUsernameBtn = document.getElementById("saveUsernameBtn");

  const oldPassword = document.getElementById("oldPassword");
  const newPassword = document.getElementById("newPassword");
  const confirmNewPassword = document.getElementById("confirmNewPassword");
  const savePasswordBtn = document.getElementById("savePasswordBtn");

  const roleSelect = document.getElementById("roleSelect");
  const saveRoleBtn = document.getElementById("saveRoleBtn");

  const editAvatarBtn = document.getElementById("editAvatarBtn");
  const editBorderBtn = document.getElementById("editBorderBtn");
  const borderModal = document.getElementById("borderModal");
  const borderGallery = document.getElementById("borderGallery");
  const closeBorderModal = document.getElementById("closeBorderModal");
  const selectedBorderName = document.getElementById("selectedBorderName");
  const addBorderBtn = document.getElementById("addBorderBtn");

  // 4. Set data awal ke UI
  function renderProfile() {
    displayUsername.textContent = currentUser.username;
    usernameInput.value = currentUser.username;

    // Background
    if (currentUser.background) {
      profileHeader.style.backgroundImage = `url('../${currentUser.background}')`;
    }

    // Avatar
    if (currentUser.avatar) {
      avatarImg.src = `../${currentUser.avatar}`;
    }

    // Border
    let imgPath;
    if (currentUser.border) {
      if (defaultBorders.includes(currentUser.border)) {
        // pakai folder images untuk default
        imgPath = `${baseURL}/images/borders/${currentUser.border}`;
      } else {
        // pakai uploads untuk custom
        imgPath = `${baseURL}/uploads/borders/${currentUser.border}`;
      }
      borderOverlay.src = imgPath;
      selectedBorderName.textContent = currentUser.border;
    } else {
      // jika kosong, default Default_Border
      selectedBorderName.textContent = "Default_Border.png";
      borderOverlay.src = `${baseURL}/images/borders/Default_Border.png`;
    }

    // Role
    roleSelect.value = currentUser.role;
    if (role !== "admin") {
      document.getElementById("profile-admin-only").style.display = "none";
    }

    const oldPassDiv = document.getElementById("oldPasswordDiv");

    if (currentUser.has_password) {
      oldPassDiv.style.display = "block";
    } else {
      oldPassDiv.style.display = "none";
      const note = document.createElement("p");
      note.innerText =
        "⚠️ Kamu login menggunakan Google dan belum mengatur password. Silakan buat password baru.";
      note.style.color = "red";
      note.style.marginTop = "0.5rem";
      note.style.fontStyle = "italic";
      note.classList = "pesanBelumLogin";

      // Masukkan sebelum oldPassDiv
      oldPassDiv.parentNode.insertBefore(note, oldPassDiv);
    }
  }

  // Render awal
  renderProfile();

  /*** 5. Render Galeri Border (dari database) ***/
  async function renderBorderGallery() {
    borderGallery.innerHTML = "";

    const defaults = defaultBorders.map((filename) => ({
      filename,
      url: `${baseURL}/images/borders/${filename}`,
    }));

    const customsRaw = await fetchCustomBorders();
    const customs = customsRaw
      .filter((b) => !defaultBorders.includes(b.filename))
      .map((b) => ({
        filename: b.filename,
        url: `${baseURL}/uploads/borders/${b.filename}`,
      }));

    const all = [
      ...defaults,
      ...customs.map((b) => ({
        filename: b.filename,
        url: `${baseURL}/uploads/borders/${b.filename}`,
      })),
    ];

    all.forEach((item) => {
      const { filename, url } = item;
      const container = document.createElement("div");
      container.className = "border-container";
      container.style.position = "relative";
      container.style.display = "inline-block";
      container.style.margin = "5px";

      const img = document.createElement("img");
      img.src = url;
      img.className = "border-item";
      img.title = filename;
      if (filename === currentUser.border) img.classList.add("selected");
      container.appendChild(img);

      // Tombol hapus hanya untuk custom border dan admin
      if (customs.some((c) => c.filename === filename) && role === "admin") {
        const delBtn = document.createElement("button");
        delBtn.textContent = "Hapus";
        delBtn.className = "delete-border-btn";
        Object.assign(delBtn.style, {
          position: "absolute",
          top: "2px",
          right: "2px",
          background: "rgba(255,255,255,0.7)",
          border: "none",
          cursor: "pointer",
        });
        delBtn.addEventListener("click", async (e) => {
          e.stopPropagation();
          // Hapus lewat API
          const form = new FormData();
          form.append("action", "delete_border");
          form.append("border", filename);
          const res = await fetch(API, {
            method: "POST",
            credentials: "include",
            body: form,
          });
          const json = await res.json();
          if (json.status === "success") {
            if (currentUser.border === filename) {
              currentUser.border = "";
              renderProfile();
            }
            renderBorderGallery();
            showToast("Border berhasil dihapus!", 2000);
          } else {
            showToast(json.message || "Gagal hapus border.", 2000);
          }
        });
        container.appendChild(delBtn);
      }

      img.addEventListener("click", async () => {
        // Pilih border baru
        const res = await updateProfile({ id: userId, border: filename });
        if (res.status === "success") {
          currentUser.border = filename;
          renderProfile();
          borderModal.style.display = "none";
          renderBorderGallery();
          showToast("Border berhasil dipilih!", 2000);
        } else {
          showToast(res.message || "Gagal ubah border.", 2000);
        }
      });

      borderGallery.appendChild(container);
    });
  }

  // Event: klik “Edit Border”
  editBorderBtn.addEventListener("click", async (e) => {
    e.stopPropagation();
    borderModal.style.display = "flex";
    const customs = await fetchCustomBorders();
    addBorderBtn.style.display = role === "admin" ? "inline-block" : "none";
    renderBorderGallery();
  });

  // Event: tambah border (admin saja)
  addBorderBtn.addEventListener("click", () => {
    if (role !== "admin") {
      return showToast("⚠️ Hanya admin yang bisa menambah border!", 2000);
    }
    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*";
    input.onchange = async () => {
      const file = input.files[0];
      if (!file) return;

      // Upload via endpoint api.php?action=upload_border
      const form = new FormData();
      form.append("action", "upload_border");
      form.append("uploadBorder", file);
      const res = await fetch(API, {
        method: "POST",
        credentials: "include",
        body: form,
      });
      const json = await res.json();
      if (json.status === "success") {
        showToast("Border berhasil diupload!", 2000);
        renderBorderGallery();
      } else {
        showToast(json.message || "Gagal upload border.", 2000);
      }
    };
    input.click();
  });

  // Tutup modal border
  closeBorderModal.addEventListener("click", () => {
    borderModal.style.display = "none";
  });

  // Klik di luar modal (tutup)
  window.addEventListener("click", (e) => {
    if (e.target === borderModal) borderModal.style.display = "none";
  });

  /*** 6. Event Ganti Avatar ***/
  editAvatarBtn.addEventListener("click", (e) => {
    e.stopPropagation();
    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*";
    input.onchange = async () => {
      const file = input.files[0];
      if (!file) return;
      // Preview sementara
      const reader = new FileReader();
      reader.onload = () => {
        avatarImg.src = reader.result;
      };
      reader.readAsDataURL(file);

      // Kirim ke server
      const res = await updateProfile({}, { avatarFile: file });
      if (res.status === "success" && res.data && res.data.avatar) {
        currentUser.avatar = res.data.avatar;
      } else if (res.status === "success") {
        // Fallback: gak ada avatar baru, tetap pakai yang lama
        showToast("Avatar berhasil diubah!", 2000);
      } else {
        showToast(res.message || "Gagal ubah avatar.", 2000);
      }
    };
    input.click();
  });

  // Tambah label saat load kalau belum ada
  if (!document.querySelector(".edit-bg-label")) {
    const editBgLabel = document.createElement("span");
    editBgLabel.textContent = "Edit Background";
    editBgLabel.classList.add("edit-bg-label");
    profileHeader.appendChild(editBgLabel);
  }

  profileHeader.addEventListener("click", (e) => {
    if (e.target.closest(".avatar-container") || e.target === editBorderBtn)
      return;

    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*";

    input.onchange = async () => {
      const file = input.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = () => {
        profileHeader.style.backgroundImage = `url('${reader.result}')`;
      };
      reader.readAsDataURL(file);

      const form = new FormData();
      form.append("action", "update_profile");
      form.append("background", file);

      try {
        const res = await fetch(API, {
          method: "POST",
          credentials: "include",
          body: form,
        });
        const json = await res.json();

        if (json.status === "success") {
          currentUser.background = json.filename;
          await updateProfile({ id: userId, background: json.filename });

          // Hapus label
          const label = document.querySelector(".edit-bg-label");
          if (label) label.remove();

          showToast("Background berhasil diubah!", 2000);
        } else {
          showToast(json.message || "Gagal ubah background.", 2000);
        }
      } catch (err) {
        console.error(err);
        showToast("Error saat menghubungi server.", 2000);
      }
    };

    input.click();
  });

  /*** 8. Event Ganti Username ***/
  saveUsernameBtn.addEventListener("click", async () => {
    const newU = usernameInput.value.trim();
    if (!newU) return showToast("⚠️ Username tidak boleh kosong!", 2000);
    if (newU === currentUser.username) return;
    const res = await updateProfile({ id: userId, newUsername: newU });
    if (res.status === "success") {
      currentUser.username = newU;
      renderProfile();
      showToast(`Username berhasil diubah ke “${newU}”!`, 2000);
    } else {
      showToast(res.message || "Gagal ubah username.", 2000);
    }
  });

  /*** 9. Event Ganti Password ***/
  savePasswordBtn.addEventListener("click", async () => {
    const oldPassword = document.getElementById("oldPassword");
    const newPassword = document.getElementById("newPassword");
    const confirmNewPassword = document.getElementById("confirmNewPassword");

    // Validasi form input
    if (currentUser.has_password && !oldPassword.value) {
      showToast("⚠️ Masukkan password lama!", 2000);
    }

    if (!newPassword.value || !confirmNewPassword.value) {
      return showToast("⚠️ Password baru dan konfirmasi wajib diisi!", 2000);
    }

    if (newPassword.value !== confirmNewPassword.value) {
      return showToast("⚠️ Konfirmasi password tidak cocok!", 2000);
    }

    const payload = {
      id: currentUser.id,
      newPassword: newPassword.value,
      // oldPassword hanya jika has_password
      oldPassword: oldPassword.value, // opsional
    };

    // Kalau user sudah punya password, sertakan oldPassword
    if (currentUser.has_password) {
      payload.oldPassword = oldPassword.value;
    }

    const res = await updateProfile(payload);

    if (res.status === "success") {
      oldPassword.value = "";
      newPassword.value = "";
      confirmNewPassword.value = "";

      const oldPassDiv = document.getElementById("oldPasswordDiv");
      currentUser.has_password = true;
      oldPassDiv.style.display = "block";
const note = document.querySelector(".pesanBelumLogin");
if (note) {
  note.style.display = "none";
}
    }
    if (res.status === "success") {
      showToast("✅ Password berhasil diubah!", 2000);
    } else {
      showToast(res.message || "❌ Gagal ganti password.", 2000);
    }
  });

  /*** 10. Event Ganti Role (Admin Only) ***/
  if (role === "admin") {
    saveRoleBtn.addEventListener("click", async () => {
      const newRole = roleSelect.value;
      const res = await updateProfile({ id: userId, role: newRole });
      if (res.status === "success") {
        currentUser.role = newRole;
        showToast("Role berhasil disimpan!", 2000);
      } else {
        showToast(res.message || "Gagal ubah role.", 2000);
      }
    });
  } else {
    document.getElementById("profile-admin-only").style.display = "none";
  }

  // Logout
  $("#logoutBtn").on("click", function (e) {
    e.preventDefault();
    $.post(
      "../includes/api.php",
      { action: "logout" },
      function (resp) {
        if (resp.status === "success") {
          window.location.href = "../index.php";
        }
      },
      "json"
    );
  });
});
