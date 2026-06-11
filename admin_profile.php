<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

ensure_logged_in();

$currentUser = auth_get_current_user();
if (empty($currentUser) || ($currentUser['role'] ?? 'user') !== 'admin') {
    header('Location: unauthorized.php');
    exit;
}

$isDefault = is_default_admin($currentUser);
$message = isset($_GET['message']) ? trim($_GET['message']) : '';
$error = isset($_GET['error']) ? trim($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Security - Admin</title>
  <style>
    :root{
      --bg: #0b1220;
      --primary: #2563eb;
      --text: rgba(255,255,255,.92);
      --muted: rgba(255,255,255,.70);
      --border: rgba(255,255,255,.12);
    }
    body{font-family:Arial,sans-serif;margin:0;padding:0;background:radial-gradient(1200px 800px at 15% 10%, rgba(37,99,235,.35), transparent 55%),radial-gradient(900px 600px at 80% 0%, rgba(29,78,216,.25), transparent 45%),var(--bg);color:var(--text);}
    .wrap{max-width: clamp(700px, 60vw, 1200px);margin:24px auto;padding:0 18px;}
    .card{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:12px;padding:22px;box-shadow:0 2px 16px rgba(0,0,0,.2);}
    h1{margin:0 0 6px;}
    .sub{margin:0 0 16px;color:var(--muted);font-size:13px;line-height:1.4;}
    .row{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px;}
    .btn{display:inline-flex;align-items:center;justify-content:center;padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:700;border:1px solid var(--border);background:rgba(255,255,255,.04);color:var(--text);} 
    .btn:hover{background:rgba(255,255,255,.06);} 
    .btn.primary{background:rgba(37,99,235,.18);border-color:rgba(37,99,235,.45);} 
    .message{padding:14px 16px;border-radius:8px;margin-bottom:14px;}
    .message.success{background:rgba(16,185,129,.12);color:rgba(209,250,229,1);border:1px solid rgba(16,185,129,.25);} 
    .message.error{background:rgba(239,68,68,.12);color:rgba(254,226,226,1);border:1px solid rgba(239,68,68,.25);} 
    form label{display:block;margin:14px 0 6px;font-weight:700;font-size:13px;color:var(--muted);} 
    input{width:100%;padding:10px 12px;border-radius:10px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.04);color:var(--text);box-sizing:border-box;}
    input:focus{border-color:rgba(37,99,235,.70);outline:none;box-shadow:0 0 0 4px rgba(37,99,235,.18);} 
    .actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="row">
        <div>
          <h1>My Security</h1>
          <p class="sub">
            Logged in as <strong><?php echo htmlspecialchars($currentUser['username'] ?? ''); ?></strong>
            (<?php echo $isDefault ? 'Default Admin' : 'Admin'; ?>)
          </p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <a class="btn" href="adminpanel.php">Back</a>
          <a class="btn" href="logout.php">Logout</a>
        </div>
      </div>

      <?php if ($message): ?><div class="message success"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
      <?php if ($error): ?><div class="message error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>

      <form method="post" action="admin_update_self.php">
        <?php if (!$isDefault): ?>
          <label for="username">Username</label>
          <input id="username" name="username" type="text" value="<?php echo htmlspecialchars($currentUser['username'] ?? ''); ?>" required>
          <div style="color:var(--muted);font-size:13px;line-height:1.4;">Admins (non-default) can change their own username.</div>
        <?php endif; ?>

        <label for="current_password">Current password</label>
        <div style="position:relative;">
          <input id="current_password" name="current_password" type="password" required style="padding-right:48px; color:var(--text);">
          <button type="button" id="toggle_current_password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,.25);border:1px solid rgba(255,255,255,.18);color:rgba(255,255,255,.92);cursor:pointer;font-size:16px; width:36px; height:36px; border-radius:10px;">👁️</button>
        </div>


        <label for="password">New password</label>
        <div style="position:relative;">
          <input id="password" name="password" type="password" required style="padding-right:48px; color:var(--text);">
          <button type="button" id="toggle_new_password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,.25);border:1px solid rgba(255,255,255,.18);color:rgba(255,255,255,.92);cursor:pointer;font-size:16px; width:36px; height:36px; border-radius:10px;">👁️</button>
        </div>

        <label for="confirm_password">Confirm new password</label>
        <div style="position:relative;">
          <input id="confirm_password" name="confirm_password" type="password" required style="padding-right:48px; color:var(--text);">
          <button type="button" id="toggle_confirm_password" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:rgba(0,0,0,.25);border:1px solid rgba(255,255,255,.18);color:rgba(255,255,255,.92);cursor:pointer;font-size:16px; width:36px; height:36px; border-radius:10px;">👁️</button>
        </div>


        <?php if ($isDefault): ?>
          <div style="color:var(--muted);font-size:13px;line-height:1.4;margin-top:10px;">
            Default admin can personalize only password (username remains unchanged).
          </div>
        <?php endif; ?>

        <script>
          (function(){
            function attachToggle(inputId, btnId){
              const input = document.getElementById(inputId);
              const btn = document.getElementById(btnId);
              if(!input || !btn) return;
              btn.addEventListener('click', function(){
                const isPw = input.type === 'password';
                input.type = isPw ? 'text' : 'password';
                btn.textContent = isPw ? '🙈' : '👁️';
              });
            }

            attachToggle('current_password','toggle_current_password');
            attachToggle('password','toggle_new_password');
            attachToggle('confirm_password','toggle_confirm_password');

            // Ensure default button icon matches initial input state
            ['current_password','password','confirm_password'].forEach(function(id){
              const input = document.getElementById(id);
              const btnId = id === 'current_password' ? 'toggle_current_password' : (id === 'password' ? 'toggle_new_password' : 'toggle_confirm_password');
              const btn = document.getElementById(btnId);
              if(!input || !btn) return;
              const isPw = input.type === 'password';
              btn.textContent = isPw ? '👁️' : '🙈';
            });
          })();
        </script>


        <div class="actions">
          <button class="btn primary" type="submit">Update Security</button>
        </div>
      </form>
    </div>
  </div>
</body>
</html>

