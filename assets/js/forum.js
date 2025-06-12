// assets/js/forum.js

// Daftar nama file border bawaan
const defaultBorders = ["Default_Border.png", "We_Heart_It.gif"];
// Base URL otomatis (include port & path project)
const baseURL =
  window.location.origin + window.location.pathname.replace(/\/pages\/.*$/, "");

$(document).ready(function () {
  let currentUser = null;

  // =============================================================
  // 1. Fungsi untuk menampilkan toast
  // =============================================================
  function showToast(msg) {
    const t = $("#toast");
    t.text(`✔️ ${msg}`);
    t.addClass("show");
    setTimeout(() => t.removeClass("show"), 3000);
  }

  // =============================================================
  // 2. Fetch data user saat ini (untuk id, role, dll.)
  // =============================================================
  function fetchCurrentUser(callback) {
    $.post(
      "../includes/api.php",
      { action: "get_current_user" },
      function (resp) {
        if (resp.status === "success") {
          currentUser = resp.data;
          if (typeof callback === "function") callback();
        } else {
          alert("Silakan login dahulu.");
          window.location.href = "login.php";
        }
      },
      "json"
    );
  }

  // function fetchCurrentUser(callback) {
  //     apiUtils.fetchCurrentUser(function (data) {
  //         currentUser = data;
  //         if (typeof callback === 'function') callback();
  //     });
  // }

  // =============================================================
  // 3. Logout
  // =============================================================
  $("#logoutBtn").on("click", function (e) {
    e.preventDefault();
    $.post(
      "../includes/api.php",
      { action: "logout" },
      function (resp) {
        if (resp.status === "success") {
          window.location.href = "login.php";
        }
      },
      "json"
    );
  });

  // =============================================================
  // 4. Load semua thread dan render
  // =============================================================
  function loadThreads() {
    $.post(
      "../includes/api.php",
      { action: "get_all_threads" },
      function (resp) {
        if (resp.status === "success") {
          displayThreads(resp.data);
        } else {
          console.error("Gagal memuat thread:", resp.message);
        }
      },
      "json"
    );
  }

  // =============================================================
  // 5. Render daftar thread di dalam #threadList
  // =============================================================
  function displayThreads(threads) {
    const container = $("#threadList");
    container.empty();

    if (!threads.length) {
      container.html("<p>Belum ada thread.</p>");
      return;
    }

    threads.forEach((thread) => {
      // Build avatar + border HTML
              // Avatar (tetap sama)
        const avatarSrc = thread.avatar
            ? `../${thread.avatar}`
            : `${baseURL}/images/herta-kurukuru.gif`;

        // Border: cek default vs custom
        let borderSrc;
        if (thread.border) {
            if (defaultBorders.includes(thread.border)) {
                // pakai folder images
                borderSrc = `${baseURL}/images/borders/${thread.border}`;
            } else {
                // pakai folder uploads
                borderSrc = `${baseURL}/uploads/borders/${thread.border}`;
            }
        } else {
            // tidak punya border → default
            borderSrc = `${baseURL}/images/borders/Default_Border.png`;
        }
      const authorHtml = `
            <div class="thread-author" style="display:flex; align-items:center; margin-bottom:10px;">
                <div class="avatar-thumb" style="position:relative; margin-right:10px;">
                    <img src="${avatarSrc}" alt="Avatar ${
        thread.username
      }" style="width:80%; height:80%; top: 10%; left: 10%; border-radius:50%; position: absolute; object-fit:cover;" />
                    <img src="${borderSrc}" alt="Border" style="position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none;" />
                </div>
                <div>
                    <strong>${escapeHtml(thread.username)}</nstrong><br />
                    <small style="color:#777;">${thread.created_at}</small>
                </div>
            </div>`;

      const threadDiv = $(
        `<div class="thread-container" data-id="${
          thread.id
        }" style="border: 2px solid black;margin-top: 15px; padding: 15px;">
                ${authorHtml}
                <h3>${escapeHtml(thread.title)}</h3>
                <pre>${escapeHtml(thread.content)}</pre>
            </div>`
      );

      // Jika ada media, tampilkan pratinjau kecil jika image, atau link jika bukan image
      if (thread.media) {
        const ext = thread.media.split(".").pop().toLowerCase();
        if (["png", "jpg", "jpeg", "gif", "webp"].includes(ext)) {
          threadDiv.append(
            `<img src="../${thread.media}" style="max-width:150px; display:block; margin-top:10px; border-radius:4px;" />`
          );
        } else {
          threadDiv.append(
            `<a href="../${thread.media}" target="_blank" style="display:block; margin-top:10px;">Unduh File</a>`
          );
        }
      }

      // Tombol "Lihat & Beri Reply"
      const viewBtn = $(
        `<button class="button small" style="margin-top:10px;">Lihat & Beri Reply</button>`
      );
      viewBtn.on("click", function () {
        showThreadDetail(thread.id);
      });
      threadDiv.append(viewBtn);

      // Tombol Edit & Delete (jika owner atau admin)
      if (currentUser.role === "admin" || currentUser.id === thread.user_id) {
        const editBtn = $(
          `<button class="button warning small" style="margin-left:10px; margin-top:10px;">Edit</button>`
        );
        editBtn.on("click", function () {
          populateThreadForm(thread);
        });
        const deleteBtn = $(
          `<button class="button danger small" style="margin-left:5px; margin-top:10px;">Hapus</button>`
        );
        deleteBtn.on("click", function () {
          if (confirm("Yakin hapus thread ini?")) {
            deleteThread(thread.id);
          }
        });
        threadDiv.append(editBtn).append(deleteBtn);
      }

      container.append(threadDiv);
    });
  }

  // =============================================================
  // 6. Escape HTML sederhana untuk mencegah XSS
  // =============================================================
  function escapeHtml(text) {
    return $("<div/>").text(text).html();
  }

  // =============================================================
  // 7. Setup preview saat memilih file untuk thread
  // =============================================================
  $("#media").on("change", function () {
    const file = this.files[0];
    const fileInfoSpan = $("#fileInfo");
    const mediaPreviewDiv = $("#mediaPreview");
    fileInfoSpan.text("");
    mediaPreviewDiv.empty();

    if (file) {
      // Tampilkan nama & ukuran file
      const sizeKB = (file.size / 1024).toFixed(1);
      fileInfoSpan.text(`${file.name} (${sizeKB} KB)`);

      // Jika image, tampilkan preview kecil
      if (file.type.startsWith("image/")) {
        const reader = new FileReader();
        reader.onload = function (e) {
          mediaPreviewDiv.html(
            `<img src="${e.target.result}" style="max-width:200px; margin-top:10px; border-radius:4px;" />`
          );
        };
        reader.readAsDataURL(file);
      }
    }
  });

  // =============================================================
  // 8. Submit Form Buat Thread (create atau update)
  // =============================================================
  $("#threadForm").on("submit", function (e) {
    e.preventDefault();

    const title = $("#title").val().trim();
    const content = $("#content").val().trim();
    const mediaFile = $("#media")[0].files[0];
    const isUpdate = $(this).data("edit") || false;
    const threadId = $(this).data("threadId") || null;

    if (!title || !content) {
      alert("Judul dan konten thread wajib diisi.");
      return;
    }

    const formData = new FormData();
    formData.append("action", isUpdate ? "update_thread" : "create_thread");
    formData.append("title", title);
    formData.append("content", content);

    if (mediaFile) {
      formData.append("media", mediaFile);
    }
    if (isUpdate && threadId) {
      formData.append("thread_id", threadId);
    }

    $.ajax({
      url: "../includes/api.php",
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function (resp) {
        if (resp.status === "success") {
          showToast(
            isUpdate ? "Thread berhasil diperbarui." : "Thread berhasil dibuat."
          );
          // Reset form
          $("#threadForm")[0].reset();
          $("#threadForm").removeData("edit").removeData("threadId");
          $("#fileInfo").text("");
          $("#mediaPreview").empty();
          // Muat ulang thread
          loadThreads();
        } else {
          alert(resp.message || "Gagal memproses.");
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error (create/update thread):", status, error);
        console.error("Response Text:", xhr.responseText);
        alert("Kesalahan server saat membuat thread.");
      },
    });
  });

  // =============================================================
  // 9. Populate form untuk edit thread
  // =============================================================
  function populateThreadForm(thread) {
    $("#title").val(thread.title);
    $("#content").val(thread.content);
    $("#threadForm").data("edit", true).data("threadId", thread.id);

    // Jika ada media sebelumnya, kita bisa menampilkan link / preview kecil di sini jika diinginkan
    // Untuk kesederhanaan, kita hapus preview lama agar user bisa pilih file baru
    $("#fileInfo").text("");
    $("#mediaPreview").empty();

    // Scroll ke atas form agar user melihat form edit
    $("html, body").animate(
      { scrollTop: $("#threadForm").offset().top - 20 },
      300
    );
  }

  // =============================================================
  // 10. Delete thread
  // =============================================================
  function deleteThread(threadId) {
    $.post(
      "../includes/api.php",
      { action: "delete_thread", thread_id: threadId },
      function (resp) {
        if (resp.status === "success") {
          showToast("Thread berhasil dihapus.");
          loadThreads();
        } else {
          alert(resp.message || "Gagal menghapus thread.");
        }
      },
      "json"
    );
  }

  // =============================================================
  // 11. Lihat detail thread + reply
  // =============================================================
  function showThreadDetail(threadId) {
    // Hide daftar & form
    $("#createThreadSection").hide();
    $(".thread-container").hide();
    $("#threadList").hide();
    $("#threadForm").hide();
    $("#searchInput").hide();

    // Ambil detail thread dari API (idealnya ada get_thread_by_id, tapi kita ambil semua lalu filter)
    $.post(
      "../includes/api.php",
      { action: "get_all_threads" },
      function (respThreads) {
        if (respThreads.status === "success") {
          const thread = respThreads.data.find((t) => t.id === threadId);
          if (!thread) {
            alert("Thread tidak ditemukan.");
            $("#createThreadSection").show();
            $(".thread-container").show();
            $("#threadList").show();
            $("#threadForm").show();
            $("#searchInput").show();
            return;
          }

          // Ambil replies-nya
          $.post(
            "../includes/api.php",
            { action: "get_replies", thread_id: threadId },
            function (respReplies) {
              if (respReplies.status === "success") {
                renderThreadDetail(thread, respReplies.data);
              } else {
                alert("Gagal memuat replies.");
                $("#createThreadSection").show();
                $(".thread-container").show();
                $("#threadList").show();
                $("#threadForm").show();
                $("#searchInput").show();
              }
            },
            "json"
          );
        } else {
          alert("Gagal memuat thread.");
          $("#createThreadSection").show();
          $(".thread-container").show();
          $("#threadList").show();
          $("#threadForm").show();
          $("#searchInput").show();
        }
      },
      "json"
    );
  }

  // =============================================================
  // 12. Render detail thread + replies
  // =============================================================
  function renderThreadDetail(thread, replies) {
    const wrapper = $('<div id="threadDetailWrapper"></div>');

    // Detail thread
    const detailDiv = $(`
            <div id="threadDetail" style="margin-bottom:30px; line-height: 30px; border: 2px solid black; padding: 15px;">
                <h3>${escapeHtml(thread.title)}</h3>
                <small>oleh <b>${escapeHtml(thread.username)}</b> pada ${
      thread.created_at
    }</small>
                <pre>${escapeHtml(thread.content)}</pre>
            </div>
        `);
    // Preview media jika ada
    if (thread.media) {
      const ext = thread.media.split(".").pop().toLowerCase();
      if (["png", "jpg", "jpeg", "gif", "webp"].includes(ext)) {
        detailDiv.append(
          `<img src="../${thread.media}" style="max-width:300px; display:block; margin-top:10px; border-radius:4px;" />`
        );
      } else {
        detailDiv.append(
          `<a href="../${thread.media}" target="_blank" style="display:block; margin-top:10px;">Unduh File</a>`
        );
      }
    }
    // Tombol Edit & Delete di detail
    if (currentUser.role === "admin" || currentUser.id === thread.user_id) {
      const editThreadBtn = $(
        `<button class="button warning small" style="margin-top:10px;">Edit Thread</button>`
      );
      editThreadBtn.on("click", function () {
        // Kembalikan tampilan daftar & form, lalu populate form
        $("#threadDetailWrapper").remove();
        $(".thread-container").show();
        $("#createThreadSection").show();
        $("#threadList").show();
        $("#threadForm").show();
        populateThreadForm(thread);
      });
      const deleteThreadBtn = $(
        `<button class="button danger small" style="margin-left:5px; margin-top:10px;">Hapus Thread</button>`
      );
      deleteThreadBtn.on("click", function () {
        if (confirm("Yakin hapus thread ini?")) {
          deleteThread(thread.id);
          $("#threadDetailWrapper").remove();
          $(".thread-container").show();
          $("#createThreadSection").show();
          $("#threadList").show();
          $("#threadForm").show();
        }
      });
      detailDiv.append(editThreadBtn).append(deleteThreadBtn);
    }
    wrapper.append(detailDiv);

    // Tombol Kembali
    const backBtn = $(
      `<button id="backBtn" class="button small">Kembali ke Thread</button>`
    );
    backBtn.on("click", function () {
      $("#threadDetailWrapper").remove();
      $(".thread-container").show();
      $("#createThreadSection").show();
      $("#threadList").show();
      $("#threadForm").show();
    });
    wrapper.append(backBtn);

    // Bagian reply
    const replyDiv = $(
      '<div id="replySection" style="margin: 10px auto;"></div>'
    );
    replyDiv.append(
      '<h3 style=" border: 2px solid red; border-bottom: 0; padding: 15px;">Replies</h3>'
    );

    // Form kirim reply
    const replyForm = $(`
            <form id="replyForm" enctype="multipart/form-data" style="margin-bottom:20px; border: 2px solid red; border-top: 0; padding: 15px;">
                <input type="hidden" id="replyThreadId" name="thread_id" value="${thread.id}" />
                <textarea id="replyContent" name="content" required placeholder="Tulis reply..." style="width:100%; padding:8px; height:80px;"></textarea><br><br>
                <input type="file" id="replyMedia" name="media" accept="*/*" style="display:none;" />
                <label for="replyMedia" class="button small">Pilih File (opsional)</label>
                <span id="replyFileInfo" style="margin-left:10px; font-size:0.9em; color:#555;"></span><br><br>
                <div id="replyMediaPreview" style="margin-bottom:10px;"></div>
                <button type="submit" class="button primary small">Kirim Reply</button>
            </form>
        `);
    replyDiv.append(replyForm);

    // Daftar reply
    const repliesContainer = $('<div id="repliesContainer"></div>');
    replies.forEach((r) => {
      const rDiv = $(`
                <div class="thread-container" style="background:#f5f5f5; padding: 15px; line-height: 30px; border: 2px solid blue;" data-id="${
                  r.id
                }">
                    <small><b>${escapeHtml(r.username)}</b> pada ${
        r.created_at
      }</small>
                    <p>${escapeHtml(r.content)}</p>
                </div>
            `);
      if (r.media) {
        const ext = r.media.split(".").pop().toLowerCase();
        if (["png", "jpg", "jpeg", "gif", "webp"].includes(ext)) {
          rDiv.append(
            `<img src="../${r.media}" style="max-width:150px; display:block; margin-top:10px; border-radius:4px;" />`
          );
        } else {
          rDiv.append(
            `<a href="../${r.media}" target="_blank" style="display:block; margin-top:10px;">Unduh File</a>`
          );
        }
      }
      // Tombol Edit/Hapus reply jika owner atau admin
      if (currentUser.role === "admin" || currentUser.id === r.user_id) {
        const editRBtn = $(
          `<button class="button warning xsmall" style="margin-top:10px;">Edit</button>`
        );
        editRBtn.on("click", function () {
          $("#createThreadSection").show();
          $(".thread-container").show();
          $("#threadList").show();
          $("#threadForm").show();
          populateReplyForm(r);
        });
        const delRBtn = $(
          `<button class="button danger xsmall" style="margin-left:5px; margin-top:10px;">Hapus</button>`
        );
        delRBtn.on("click", function () {
          if (confirm("Yakin hapus reply ini?")) {
            deleteReply(r.id, thread.id);
          }
        });
        rDiv.append(editRBtn).append(delRBtn);
      }
      repliesContainer.append(rDiv).append("<hr>");
    });
    replyDiv.append(repliesContainer);
    wrapper.append(replyDiv);

    // Tambahkan ke dalam main
    $("#main .inner").append(wrapper);

    // =============================================================
    // 13. Setup preview saat memilih file untuk reply
    // =============================================================
    $("#replyMedia").on("change", function () {
      const file = this.files[0];
      const fileInfoSpan = $("#replyFileInfo");
      const mediaPreviewDiv = $("#replyMediaPreview");
      fileInfoSpan.text("");
      mediaPreviewDiv.empty();

      if (file) {
        const sizeKB = (file.size / 1024).toFixed(1);
        fileInfoSpan.text(`${file.name} (${sizeKB} KB)`);

        if (file.type.startsWith("image/")) {
          const reader = new FileReader();
          reader.onload = function (e) {
            mediaPreviewDiv.html(
              `<img src="${e.target.result}" style="max-width:150px; margin-top:10px; border-radius:4px;" />`
            );
          };
          reader.readAsDataURL(file);
        }
      }
    });

    // =============================================================
    // 14. Handler submit reply (create/update)
    // =============================================================
    $("#replyForm").on("submit", function (e) {
      e.preventDefault();

      const content = $("#replyContent").val().trim();
      const mediaFile = $("#replyMedia")[0].files[0];
      const isReplyUpdate = $(this).data("edit") || false;
      const replyId = $(this).data("replyId") || null;

      if (!content) {
        alert("Isi reply tidak boleh kosong.");
        return;
      }

      const formData = new FormData();
      formData.append(
        "action",
        isReplyUpdate ? "update_reply" : "create_reply"
      );
      formData.append("thread_id", thread.id);
      formData.append("content", content);
      if (mediaFile) {
        formData.append("media", mediaFile);
      }
      if (isReplyUpdate && replyId) {
        formData.append("reply_id", replyId);
      }

      $.ajax({
        url: "../includes/api.php",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        dataType: "json",
        success: function (res) {
          if (res.status === "success") {
            showToast(
              isReplyUpdate
                ? "Reply berhasil diperbarui."
                : "Reply berhasil dikirim."
            );
            // Reload replies
            $.post(
              "../includes/api.php",
              { action: "get_replies", thread_id: thread.id },
              function (rp) {
                if (rp.status === "success") {
                  $("#threadDetailWrapper").remove();
                  renderThreadDetail(thread, rp.data);
                }
              },
              "json"
            );
          } else {
            alert(res.message || "Gagal memproses reply.");
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error (create/update reply):", status, error);
          console.error("Response Text:", xhr.responseText);
          alert("Kesalahan server saat memproses reply.");
        },
      });
    });

    // =============================================================
    // 15. Populate form untuk edit reply
    // =============================================================
    function populateReplyForm(r) {
      $("#replyContent").val(r.content);
      $("#replyForm").data("edit", true).data("replyId", r.id);

      // Hapus preview lama
      $("#replyFileInfo").text("");
      $("#replyMediaPreview").empty();

      // Scroll ke form reply
      $("html, body").animate(
        { scrollTop: $("#replyForm").offset().top - 20 },
        300
      );
    }

    // =============================================================
    // 16. Delete reply
    // =============================================================
    function deleteReply(replyId, tid) {
      $.post(
        "../includes/api.php",
        { action: "delete_reply", reply_id: replyId },
        function (rp) {
          if (rp.status === "success") {
            showToast("Reply berhasil dihapus.");
            // Reload replies
            $.post(
              "../includes/api.php",
              { action: "get_replies", thread_id: tid },
              function (rp2) {
                if (rp2.status === "success") {
                  $("#threadDetailWrapper").remove();
                  renderThreadDetail(thread, rp2.data);
                }
              },
              "json"
            );
          } else {
            alert(rp.message || "Gagal menghapus reply.");
          }
        },
        "json"
      );
    }
  }

  // =============================================================
  // 17. Search Thread on Input
  // =============================================================
  $("#searchInput").on("input", function () {
    const kw = $(this).val().trim();
    if (!kw) {
      loadThreads();
      return;
    }
    $.post(
      "../includes/api.php",
      { action: "search_threads", keyword: kw },
      function (resp) {
        if (resp.status === "success") {
          displayThreads(resp.data);
        } else {
          console.error("Gagal search:", resp.message);
        }
      },
      "json"
    );
  });

  // =============================================================
  // 18. Initial flow: ambil user → load threads
  // =============================================================
  fetchCurrentUser(loadThreads);
});
