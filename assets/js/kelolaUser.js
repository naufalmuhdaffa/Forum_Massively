$(document).ready(function() {
    function loadUsers() {
        $.post('../includes/api.php', { action: 'get_all_users' }, function(resp) {
            if (resp.status === 'success') {
                renderTable(resp.data);
            } else {
                alert(resp.message || 'Gagal memuat data user.');
            }
        }, 'json');
    }

    function renderTable(users) {
        const tbody = $('#userTable tbody');
        tbody.empty();
        users.forEach((u) => {
            const tr = $('<tr></tr>');
            tr.append(`<td>${u.id}</td>`);
            tr.append(`<td>${escapeHtml(u.username)}</td>`);
            tr.append(`<td>${escapeHtml(u.email || '')}</td>`);
            tr.append(`<td>${u.role}</td>`);
            const aksiTd = $('<td></td>');

            const delBtn = $('<button>Hapus</button>');
            delBtn.on('click', function() {
                if (confirm(`Yakin hapus user "${u.username}"?`)) {
                    deleteUser(u.id);
                }
            });
            aksiTd.append(delBtn);

            const toggleRoleBtn = $(`<button>${u.role === 'admin' ? 'Jadikan User' : 'Jadikan Admin'}</button>`);
            toggleRoleBtn.on('click', function() {
                const newRole = u.role === 'admin' ? 'user' : 'admin';
                updateUserRole(u.id, newRole);
            });
            aksiTd.append(toggleRoleBtn);

            tr.append(aksiTd);
            tbody.append(tr);
        });
    }

    function escapeHtml(text) {
        return $('<div/>').text(text).html();
    }

    function deleteUser(userId) {
        $.post('../includes/api.php', { action: 'delete_user', user_id: userId }, function(resp) {
            if (resp.status === 'success') {
                loadUsers();
            } else {
                alert(resp.message || 'Gagal menghapus user.');
            }
        }, 'json');
    }

    function updateUserRole(userId, newRole) {
        $.post('../includes/api.php', { action: 'update_role', user_id: userId, role: newRole }, function(resp) {
            if (resp.status === 'success') {
                loadUsers();
            } else {
                alert(resp.message || 'Gagal memperbarui role.');
            }
        }, 'json');
    }

    loadUsers();

    $('#logoutBtn').on('click', function(e) {
        e.preventDefault();
        $.post('../includes/api.php', { action: 'logout' }, function(resp) {
            if (resp.status === 'success') {
                window.location.href = '../index.php';
            }
        }, 'json');
    });
});