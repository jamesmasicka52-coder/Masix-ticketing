<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
ensure_logged_in();

$currentUser = auth_get_current_user();
// Role checks removed so all logged-in users can access admin UI (buttons still work via existing logic).

$message = isset($_GET['message']) ? trim($_GET['message']) : '';
$error = isset($_GET['error']) ? trim($_GET['error']) : '';

// Get list of all users (always reloaded after create/delete so the table updates immediately)

// Get list of users based on admin scope
$users = [];
if (isset($pdo) && $pdo) {
    try {
        if (is_default_admin($currentUser)) {
            $stmt = $pdo->query('SELECT id, username, email, role, added_by_admin_id, created_at FROM users ORDER BY created_at DESC');
        } else {
            $stmt = $pdo->prepare('SELECT id, username, email, role, added_by_admin_id, created_at FROM users WHERE added_by_admin_id = :aid OR id = :aid_dummy ORDER BY created_at DESC');
            // NOTE: OR condition is just to keep syntax valid; scope is enforced by added_by_admin_id filter
            $stmt->execute([':aid' => (int)$currentUser['id'], ':aid_dummy' => -1]);
        }
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $users = [];
    }
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - User Management</title>
<style>
        :root{
            --bg: #0b1220;
            --primary: #2563eb;
            --primary-2: #1d4ed8;
            --text: rgba(255,255,255,.92);
            --muted: rgba(255,255,255,.70);
            --border: rgba(255,255,255,.12);
        }
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: radial-gradient(1200px 800px at 15% 10%, rgba(37,99,235,.35), transparent 55%), radial-gradient(900px 600px at 80% 0%, rgba(29,78,216,.25), transparent 45%), var(--bg); color: var(--text); }

        .admin-container { max-width: clamp(900px, 92vw, 1800px); margin: 24px auto; padding: 0 20px; }
        .admin-card { background: rgba(255,255,255,.06); border-radius: 12px; padding: 24px; box-shadow: 0 2px 16px rgba(0,0,0,0.08); margin-bottom: 20px; border: 1px solid rgba(255,255,255,.12); }
.admin-card *{ color: inherit; }
        .admin-card input,
        .admin-card select,
        .admin-card textarea{
            background: rgba(10, 20, 40, .55);
            color: rgba(116, 124, 141, 0.92);
        }
        .admin-card table th, .admin-card table td{ color: var(--text); }
        .message.success { background: rgba(16,185,129,.12) !important; color: rgba(209,250,229,1) !important; border: 1px solid rgba(16,185,129,.25); }
        .message.error { background: rgba(239,68,68,.12) !important; color: rgba(254,226,226,1) !important; border: 1px solid rgba(239,68,68,.25); }
        table{ background: rgba(255,255,255,.03); border-radius: 12px;}
        th, td{ border-color: rgba(255,255,255,.18) !important; }
        tbody tr:nth-child(even) { background-color: rgba(255,255,255,.03); }
        tbody tr:hover { background-color: rgba(46, 44, 44, 0.42); }
        h1, h2 { margin-top: 0; }
        .button { display: inline-flex; align-items: center; justify-content: center; padding: 10px 16px; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; font-weight: 600; }
        .button.primary { background: #2563eb; color: #fff; }
        .button.secondary { background: rgba(37,99,235,.18);border-color: rgba(38, 40, 43, 0.09); }
        .button.danger { background: #ef4444; color: #fff; }
        .button:hover { opacity: 0.9; }
        .header-actions { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
        form label { display: block; margin: 14px 0 6px; font-weight: 600; }
        form input[type="text"], form input[type="password"], form select { 
            width: 100%; padding: 10px 12px;
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 8px;
            box-sizing: border-box;
            background: rgba(10, 20, 40, .55);
            /* slightly softer text color so it doesn't look "white" */
            color: rgba(226, 235, 255, .92);
        }
        form select option{
            background: rgba(10, 20, 40, .98);
            color: rgba(255,255,255,.95);
        }
        .message { padding: 14px 16px; border-radius: 8px; margin-bottom: 14px; }
        .message.success { background: #ecfdf5; color: #166534; }
        .message.error { background: #f8d7da; color: #842029; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; align-items: center; }
        .hint { font-size: 13px; color: #6b7280; margin-top: 6px; line-height: 1.35; }
        table { width: 100%; border: 1px solid #0000; border-collapse: collapse; border-radius:20px; margin-top: 14px; }
        th, td { border: 1px solid #e5e7eb; padding: 12px 14px; text-align: left; vertical-align: top; }
        th { background: #0f172a; color: #fff; font-weight: 600; }
        tbody tr:nth-child(even) { background-color: #707880; }
        tbody tr:hover { background-color: #23252748; }
        .small { font-size: 12px; color: #6b7280; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 600; }
        .badge.admin { background: #dbeafe; color: #1e40af; }
        .badge.user { background: #dcfce7; color: #166534; }
        .table-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .table-actions form { margin: 0; }
        .table-actions button { padding: 6px 12px; font-size: 12px; }
    </style>
</head>
<body>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const toggleBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    toggleBtn.addEventListener('click', function () {
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleBtn.textContent = '🙈';
        } else {
            passwordInput.type = 'password';
            toggleBtn.textContent = '👁️';
        }
    });
});
</script>

<div class="admin-container">

    <div class="admin-card">
        <div class="header-actions">
            <div>
                <h1 style="margin:0;">Admin Panel</h1>
                <p class="small" style="margin:6px 0 0 0;">Logged in as: <strong><?php echo htmlspecialchars($currentUser['username']); ?></strong></p>
            </div>
<div style="display: flex; gap: 10px;">
<a class="button secondary" href="index.php">Back to Home</a>
                <a class="button secondary" href="admin_profile.php">My Security</a>
                <a class="button secondary" href="logout.php">Logout</a>
            </div>
        </div>
    </div>

    <?php if ($message): ?><div class="admin-card"><div class="message success"><?php echo htmlspecialchars($message); ?></div></div><?php endif; ?>
    <?php if ($error): ?><div class="admin-card"><div class="message error"><?php echo htmlspecialchars($error); ?></div></div><?php endif; ?>

    <div class="grid">
        <div class="admin-card">
            <h2>Create New User</h2>
            <form method="post" action="admin_save_user.php" id="createUserForm">
                <label for="username">Username</label>
                <input
    id="username"
    name="username"
    type="text"
    required
    placeholder="Enter new username"
    autocomplete="off">
                <div class="hint">Must be unique. No spaces allowed.</div>

                <label for="role" style="margin-top: 14px;">Role</label>
                <select id="role" name="role" required>
                    <option value="user">Regular User</option>
                    <option value="admin">Admin</option>
                </select>
                <div class="hint">Regular users can only view/edit their own tickets. Admins can manage everything.</div>

                <label for="password" style="margin-top: 14px;">Password</label>
                <div class="password-container" style="position:relative;display:flex;align-items:center;">
                    <input
    id="password"
    name="password"
    type="password"
    required
    placeholder="Enter password"
    autocomplete="new-password"
    style="width:100%;padding-right:48px;">
                    <button type="button" class="toggle-password" id="togglePassword" aria-label="Toggle password visibility" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:transparent;border:none;color: rgba(255,255,255,.65);cursor:pointer;font-size:18px;padding:8px;line-height:1;">👁️</button>
                </div>
                <div class="hint">Passwords are hashed and cannot be viewed after creation.</div>

                <div class="actions">
                    <button class="button primary" type="submit">Create User</button>
                    <button class="button secondary" type="button" onclick="document.getElementById('createUserForm').reset();">Clear</button>
                </div>
            </form>
        </div>

        <div class="admin-card">
            <h2>Delete User</h2>
            <div class="hint">
                Enter the username to delete. <strong>You cannot delete the currently logged-in admin account</strong> (this protects your access).
            </div>

            <form method="post" action="admin_delete_user.php" style="margin-top: 14px;">
                <label for="delete_username">Username to Delete</label>
                <input id="delete_username" name="username" type="text" required placeholder="Enter username">
                <div class="hint">This action cannot be undone.</div>
                <div class="actions">
                    <button class="button danger" type="submit" onclick="return confirm('Are you sure? This will permanently delete the user and all associated data.');">Delete User</button>
                </div>
            </form>
        </div>
    </div>

    <div class="admin-card">
        <h2>Existing Users</h2>
        <?php if (count($users) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($u['id']); ?></td>
                        <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                        <td><?php echo htmlspecialchars($u['email'] ?? '-'); ?></td>
                        <td><span class="badge <?php echo ($u['role'] === 'admin') ? 'admin' : 'user'; ?>"><?php echo htmlspecialchars($u['role']); ?></span></td>
                        <td class="small"><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <div class="table-actions">
                                <?php if ((int)$u['id'] !== (int)$currentUser['id']): ?>
                                    <form method="post" action="admin_delete_user.php" style="margin:0;" onsubmit="return confirm('Delete user: <?php echo htmlspecialchars($u['username']); ?>?');">
                                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($u['username']); ?>">
                                        <button type="submit" class="button danger" style="font-size:12px; padding:6px 10px;">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <span class="small" style="color:#6b7280;">(Your account)</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color:#6b7280; text-align:center; padding:20px;">No users found.</p>
        <?php endif; ?>
    </div>
</div>
<script>
window.addEventListener('load', function () {
    const form = document.getElementById('createUserForm');

    if (form) {
        form.reset();

        document.getElementById('username').value = '';
        document.getElementById('password').value = '';
    }
});
</script>
</body>
</html>
