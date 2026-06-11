<?php
/**
 * ADMIN PANEL DIAGNOSTIC & FIX SCRIPT
 * This script checks and fixes common issues
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel Diagnostic</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .section { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #2563eb; }
        .pass { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
        .warn { color: orange; font-weight: bold; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; }
        h2 { margin-top: 0; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #0f172a; color: white; }
        .action-btn { background: #2563eb; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; }
        .action-btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>

<h1>Admin Panel - System Diagnostic</h1>

<?php

$checks = [
    'session' => false,
    'logged_in' => false,
    'is_admin' => false,
    'db_connection' => false,
    'admin_user_exists' => false,
    'admin_has_role' => false,
    'adminpanel_accessible' => false,
];

// Check 1: Session
echo '<div class="section">';
echo '<h2>Session Status</h2>';
if (session_status() === PHP_SESSION_ACTIVE) {
    echo '<p class="pass">✅ Session is active</p>';
    $checks['session'] = true;
} else {
    echo '<p class="fail">❌ Session not active</p>';
}
echo '</div>';

// Check 2: Logged In
echo '<div class="section">';
echo '<h2>Authentication Status</h2>';
if (is_logged_in()) {
    echo '<p class="pass">✅ User is logged in</p>';
    $checks['logged_in'] = true;
    $user = auth_get_current_user();
    echo '<table>';
    echo '<tr><th>Field</th><th>Value</th></tr>';
    echo '<tr><td>ID</td><td>' . htmlspecialchars($user['id'] ?? 'N/A') . '</td></tr>';
    echo '<tr><td>Username</td><td>' . htmlspecialchars($user['username'] ?? 'N/A') . '</td></tr>';
    echo '<tr><td>Email</td><td>' . htmlspecialchars($user['email'] ?? 'N/A') . '</td></tr>';
    echo '<tr><td>Role</td><td><strong>' . htmlspecialchars($user['role'] ?? 'N/A') . '</strong></td></tr>';
    echo '</table>';
} else {
    echo '<p class="fail">❌ User is NOT logged in - Cannot proceed with other checks</p>';
    echo '<a href="login.php">Go to Login</a>';
    echo '</div></body></html>';
    exit;
}
echo '</div>';

// Check 3: Is Admin
echo '<div class="section">';
echo '<h2>Admin Status</h2>';
if (is_admin()) {
    echo '<p class="pass">✅ User IS an admin</p>';
    $checks['is_admin'] = true;
} else {
    echo '<p class="fail">❌ User is NOT an admin - Admin panel not accessible</p>';
    echo '<p>Current role: <code>' . htmlspecialchars($user['role'] ?? 'user') . '</code></p>';
}
echo '</div>';

// Check 4: Database Connection
echo '<div class="section">';
echo '<h2>Database Connection</h2>';
if (isset($pdo) && $pdo) {
    echo '<p class="pass">✅ Database connection is active</p>';
    $checks['db_connection'] = true;
} else {
    echo '<p class="fail">❌ Database connection failed</p>';
}
echo '</div>';

// Check 5: Admin User in Database
echo '<div class="section">';
echo '<h2>Admin User Database Check</h2>';
if (isset($pdo) && $pdo) {
    try {
        $stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => 'admin']);
        $admin_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin_user) {
            echo '<p class="pass">✅ Admin user found in database</p>';
            $checks['admin_user_exists'] = true;
            echo '<table>';
            echo '<tr><th>Field</th><th>Value</th></tr>';
            echo '<tr><td>ID</td><td>' . htmlspecialchars($admin_user['id']) . '</td></tr>';
            echo '<tr><td>Username</td><td>' . htmlspecialchars($admin_user['username']) . '</td></tr>';
            echo '<tr><td>Role</td><td><strong>' . htmlspecialchars($admin_user['role']) . '</strong></td></tr>';
            echo '</table>';
            
            // Check if role is set correctly
            if ($admin_user['role'] === 'admin') {
                echo '<p class="pass">✅ Admin role is correctly set</p>';
                $checks['admin_has_role'] = true;
            } else {
                echo '<p class="fail">❌ Admin user role is NOT set to "admin" - it is "' . htmlspecialchars($admin_user['role']) . '"</p>';
                echo '<p class="warn">⚠️  ISSUE FOUND: The admin user\'s role must be "admin"</p>';
                
                // Offer fix
                if ($_POST['fix_admin_role'] ?? false) {
                    try {
                        $update_stmt = $pdo->prepare('UPDATE users SET role = :r WHERE id = :id');
                        $update_stmt->execute([':r' => 'admin', ':id' => (int)$admin_user['id']]);
                        echo '<p class="pass">✅ Fixed! Admin role updated to "admin"</p>';
                        $checks['admin_has_role'] = true;
                    } catch (Exception $e) {
                        echo '<p class="fail">❌ Failed to update: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    }
                } else {
                    echo '<form method="post" style="margin-top: 10px;">';
                    echo '<button type="submit" name="fix_admin_role" value="1" class="action-btn">🔧 Fix Admin Role</button>';
                    echo '</form>';
                }
            }
        } else {
            echo '<p class="fail">❌ Admin user NOT found in database</p>';
            echo '<p class="warn">⚠️  CRITICAL: The "admin" user does not exist in the users table</p>';
            
            // Offer to create
            if ($_POST['create_admin_user'] ?? false) {
                try {
                    $hash = password_hash('admin123', PASSWORD_DEFAULT);
                    $insert_stmt = $pdo->prepare(
                        'INSERT INTO users (username, password_hash, role) VALUES (:u, :h, :r)'
                    );
                    $insert_stmt->execute([
                        ':u' => 'admin',
                        ':h' => $hash,
                        ':r' => 'admin'
                    ]);
                    echo '<p class="pass">✅ Created! Admin user with password "admin123" inserted</p>';
                    $checks['admin_user_exists'] = true;
                    $checks['admin_has_role'] = true;
                } catch (Exception $e) {
                    echo '<p class="fail">❌ Failed to create: ' . htmlspecialchars($e->getMessage()) . '</p>';
                }
            } else {
                echo '<form method="post" style="margin-top: 10px;">';
                echo '<button type="submit" name="create_admin_user" value="1" class="action-btn">🔨 Create Admin User</button>';
                echo '</form>';
            }
        }
    } catch (Exception $e) {
        echo '<p class="fail">❌ Database query error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
} else {
    echo '<p class="fail">❌ Cannot check - Database not connected</p>';
}
echo '</div>';

// Check 6: adminpanel.php accessibility
echo '<div class="section">';
echo '<h2>Admin Panel Accessibility</h2>';
if ($checks['is_admin']) {
    echo '<p class="pass">✅ You should be able to access adminpanel.php</p>';
    $checks['adminpanel_accessible'] = true;
    echo '<p><a href="adminpanel.php" class="action-btn">→ Go to Admin Panel</a></p>';
} else {
    echo '<p class="fail">❌ Admin panel not accessible - User is not admin</p>';
}
echo '</div>';

// Summary
echo '<div class="section">';
echo '<h2>Diagnostic Summary</h2>';
$pass_count = array_sum($checks);
$total = count($checks);
echo '<p>';
if ($pass_count === $total) {
    echo '<span class="pass">✅ ALL CHECKS PASSED</span>';
    echo '<br>Admin panel should be fully functional!';
    echo '<br><a href="adminpanel.php" class="action-btn" style="margin-top:10px;">Access Admin Panel</a>';
} else {
    echo '<span class="warn">⚠️  ' . $pass_count . '/' . $total . ' checks passed</span>';
    echo '<br>Issues found above - scroll up to see recommended fixes';
}
echo '</p>';
echo '</div>';

?>

</body>
</html>
