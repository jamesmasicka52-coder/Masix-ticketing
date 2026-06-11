<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
ensure_logged_in();

$currentUser = auth_get_current_user();
if (($currentUser['role'] ?? 'user') !== 'admin') {
    header('Location: index.php');
    exit;
}

$message = isset($_GET['message']) ? trim($_GET['message']) : '';
$error = isset($_GET['error']) ? trim($_GET['error']) : '';

// Get list of all users
$users = [];
if (isset($pdo) && $pdo) {
    try {
        $stmt = $pdo->query('SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC');
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
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f4f5f7; }
        .admin-container { max-width: clamp(900px, 92vw, 1800px); margin: 24px auto; padding: 0 20px; }
        .admin-card { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 2px 16px rgba(0,0,0,0.08); margin-bottom: 20px; }
        h1, h2 { margin-top: 0; }
        .button { display: inline-flex; align-items: center; justify-content: center; padding: 10px 16px; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; font-weight: 600; }
        .button.primary { background: #2563eb; color: #fff; }
        .button.secondary { background: #e5e7eb; color: #111827; }
        .button.danger { background: #ef4444; color: #fff; }
        .button:hover { opacity: 0.9; }
        .header-actions { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 18px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
        form label { display: block; margin: 14px 0 6px; font-weight: 600; }
        form input[type="text"], form input[type="password"], form select { 
            width: 100%;
            padding: 10px 12px;
            border: 1px solid rgba(255,255,255,.16);
            border-radius: 8px;
            box-sizing: border-box;
            background: rgba(10, 20, 40, .55);
            color: rgba(255,255,255,.95);
        }
        form select option{
            background: #0b1220;
            color: #ffffff;
        }
        form select{
            background: rgba(10, 20, 40, .55);
            color: #ffffff;
        }

        .message { padding: 14px 16px; border-radius: 8px; margin-bottom: 14px; }
        .message.success { background: #ecfdf5; color: #166534; }
        .message.error { background: #f8d7da; color: #842029; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 16px; align-items: center; }
        .hint { font-size: 13px; color: #6b7280; margin-top: 6px; line-height: 1.35; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        th, td { border: 1px solid #e5e7eb; padding: 12px 14px; text-align: left; vertical-align: top; }
        th { background: #0f172a; color: #fff; font-weight: 600; }
        tbody tr:nth-child(even) { background-color: #f8fafc; }
        tbody tr:hover { background-color: #f1f5f9; }
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
(function(){
    const toggleBtn = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');
    if(!toggleBtn || !passwordInput) return;

    toggleBtn.addEventListener('click', function(){
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        toggleBtn.textContent = isPassword ? '🙈' : '👁️';
    });
})();
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
                <input id="username" name="username" type="text" required placeholder="e.g., john_doe">
                <div class="hint">Must be unique. No spaces allowed.</div>

                <label for="role" style="margin-top: 14px;">Role</label>
                <select id="role" name="role" required>
                    <option value="user">Regular User</option>
                    <option value="admin">Admin</option>
                </select>
                <div class="hint">Regular users can only view/edit their own tickets. Admins can manage everything.</div>

                <label for="password" style="margin-top: 14px;">Password</label>
                <div class="password-container" style="position:relative;display:flex;align-items:center;">
                    <input id="password" name="password" type="password" required placeholder="Minimum 6 characters" style="width:100%;padding-right:48px;">
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
</body>
</html>
