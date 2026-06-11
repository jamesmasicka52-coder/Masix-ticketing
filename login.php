<?php
require_once __DIR__ . '/auth.php';
redirect_if_logged_in();

$message = isset($_GET['message']) ? trim($_GET['message']) : '';
$error = isset($_GET['error']) ? trim($_GET['error']) : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($identifier === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $user = authenticate_user($identifier, $password);
        if ($user) {
            // Single-device enforcement token only for admin.
            if (($user['role'] ?? 'user') === 'admin') {
                $token = bin2hex(random_bytes(32));
                $_SESSION['admin_login_token'] = $token;

                // Persist hash in DB so previous admin session becomes invalid.
                if (isset($pdo) && $pdo) {
                    try {
                        $tokenHash = hash('sha256', $token);
                        $stmt = $pdo->prepare('UPDATE users SET admin_session_token_hash = :h WHERE id = :id');
                        $stmt->execute([':h' => $tokenHash, ':id' => (int)$user['id']]);
                        $user['admin_token'] = $tokenHash;
                    } catch (Exception $e) {
                        // If DB update fails, still allow login.
                    }
                }
            }

            login_user_session($user);
            header('Location: index.php');
            exit;
        }

        $error = 'Invalid login credentials. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Ticketing System</title>
    <style>
        :root{
            --bg:#0b1220;
            --card: rgba(255,255,255,.06);
            --card-2: rgba(255,255,255,.09);
            --text: rgba(255,255,255,.92);
            --muted: rgba(255,255,255,.72);
            --border: rgba(255,255,255,.12);
            --primary:#2563eb;
            --primary-2:#1d4ed8;
            --danger:#ef4444;
        }
        *{box-sizing:border-box;}
        body{
            margin:0;
            min-height:100vh;
            display:grid;
            place-items:center;
            padding:24px;
            font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial;
            background: radial-gradient(1200px 800px at 15% 10%, rgba(37,99,235,.35), transparent 55%),
                        radial-gradient(900px 600px at 80% 0%, rgba(29,78,216,.25), transparent 45%),
                        var(--bg);
            color:var(--text);
        }
        .auth-card{
            width:100%;
            max-width: clamp(320px, 90vw, 600px);
            background: rgba(17,24,39,.78);
            border:1px solid var(--border);
            border-radius:18px;
            box-shadow:0 20px 60px rgba(0,0,0,.35);
            padding:26px;
            backdrop-filter: blur(10px);
        }
        .top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;}
        .brand h1{margin:0;font-size:26px;letter-spacing:.2px;}
        .brand p{margin:6px 0 0;color:var(--muted);font-size:13px;line-height:1.4;}

        .message{padding:12px 14px;border-radius:12px;margin-top:16px;font-weight:700;font-size:13px;}
        .message.success{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.25);color:#d1fae5;}
        .message.error{background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.25);color:#fee2e2;}

        form{margin-top:18px;display:grid;gap:14px;}
        label{display:block;font-weight:800;font-size:13px;color:var(--muted);margin-bottom:6px;}
        .field{display:grid;gap:6px;}
        input{
            width:100%;
            padding:12px 14px;
            border-radius:12px;
            border:1px solid rgba(255,255,255,.14);
            background: rgba(255,255,255,.04);
            color:var(--text);
            outline:none;
        }
        input:focus{border-color: rgba(37,99,235,.75); box-shadow:0 0 0 4px rgba(37,99,235,.18);}        

        .password-container{position:relative;display:flex;align-items:center;}
        .password-container input{padding-right:48px;}
        .toggle-password{
            position:absolute;
            right:10px;
            background:transparent;
            border:none;
            color: rgba(255,255,255,.65);
            cursor:pointer;
            font-size:18px;
            padding:8px;
            line-height:1;
        }
        .toggle-password:hover{color:rgba(255,255,255,.92);}        

        .submit{
            width:100%;
            padding:12px 14px;
            border:none;
            border-radius:12px;
            background: var(--primary);
            color:#fff;
            font-weight:900;
            cursor:pointer;
            margin-top:4px;
        }
        .submit:hover{background:var(--primary-2);}        

        .footer-note{margin-top:14px;color:var(--muted);font-size:12px;line-height:1.5;}
        .footer-note a{color: rgba(147,197,253,.95); text-decoration:none;}
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="top">
            <div class="brand">
                <h1>Login</h1>
                <p>Sign in to manage tickets securely.</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="field">
                <label for="identifier">Username</label>
                <input type="text" id="identifier" name="identifier" required placeholder="Enter your username" />
            </div>

            <div class="field">
                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required placeholder="Enter your password" />
                    <button type="button" class="toggle-password" id="togglePassword" aria-label="Toggle password visibility">👁️</button>
                </div>
            </div>

            <button class="submit" type="submit">Log In</button>
        </form>

        <div class="footer-note">
            <a href="landing.php">Back to Landing</a>
        </div>
    </div>

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
</body>
</html>

