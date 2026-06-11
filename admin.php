<?php
require_once __DIR__ . '/auth.php';
ensure_logged_in();

$user = auth_get_current_user();
// Ensure only admins can access admin endpoints
if (($user['role'] ?? 'user') !== 'admin') {
    header('Location: unauthorized.php');
    exit;
}


$message = '';
$error = '';
if (isset($_GET['message'])) $message = trim($_GET['message']);
if (isset($_GET['error'])) $error = trim($_GET['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
<style>
        :root{
            --bg: #0b1220;
            --primary: #2563eb;
            --primary-2: #1d4ed8;
            --text: rgba(250, 241, 241, 0.92);
            --muted: rgba(255,255,255,.70);
            --border: rgba(255,255,255,.12);
        }
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: radial-gradient(1200px 800px at 15% 10%, rgba(37, 100, 235, 0.35), transparent 55%), radial-gradient(900px 600px at 80% 0%, rgba(29,78,216,.25), transparent 45%), var(--bg); color: var(--text); }

        .admin-card { max-width: clamp(720px, 60vw, 1100px); margin: 24px auto; background: #5a5c774b; border-radius: 12px; padding: 24px; box-shadow: 0 2px 16px rgba(0,0,0,0.08); }
        h1, h2 { margin-top: 0; }
        .button { display: inline-flex; align-items: center; justify-content: center; padding: 10px 16px; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; font-weight: 600; }
        .button.primary { background: #2563eb; color: #fff; }
        .button.secondary { background: #e5e7eb; color: #111827; }
        form label { display: block; margin: 14px 0 6px; font-weight: 600; }
        form input[type="text"], form input[type="password"], form select { width: 40%; padding: 10px 12px; border: 1px solid #41464d; border-radius: 8px; }
        .form-row{display: grid; grid-template-columns: 1fr 1fr;}
        .message { padding: 14px 16px; border-radius: 8px; margin-top: 8px; margin-bottom: 14px; }
        .message.success { background: #30644c; color: #166534; }
        .message.error { background: #fad7db; color: #842029; }
    </style>
</head>
<body>
<div class="admin-card">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:18px;">
        <div>
            <h1>Admin Panel</h1>
            <p>Manage users (create accounts) and control roles.</p>
        </div>
        <div>
            <a class="button secondary" href="adminpanel.php">Back</a>
        </div>
    </div>

    <?php if ($message): ?><div class="message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <h2>Create User</h2>
    <form method="post" action="admin_save_user.php">
        <div class="form-row">
            <div>
        <label for="username">Username</label>
        <input id="username" name="username" type="text" required>
    </div>
        <div>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>
    </div>
    <div>
        <label for="role">Role</label>
        <select id="role" name="role" required>
            <option value="user">user</option>
            <option value="admin">admin</option>
        </select>
    </div>
    <div>

        <aside>

        <button class="button primary" type="submit" style="margin-top:40px;">Create</button>

    </aside>
    </div>
    </div>
    </form>
</div>
</body>
</html>

