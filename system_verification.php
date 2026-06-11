<?php
/**
 * Final Verification Script
 * Verifies: Database setup, Admin user, User creation, Login, and Session display
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

echo "╔═════════════════════════════════════════════════════════════╗\n";
echo "║        SYSTEM VERIFICATION & FINAL SETUP REPORT             ║\n";
echo "╚═════════════════════════════════════════════════════════════╝\n\n";

$allPassed = true;

// 1. Database Connection
echo "1️⃣  DATABASE CONNECTION\n";
echo "────────────────────────────────────────────────────────────\n";
if (isset($pdo) && $pdo) {
    echo "✅ MySQL PDO connection: OK\n";
    try {
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        echo "   MySQL Version: $version\n";
    } catch (Exception $e) {
        echo "   Could not fetch version\n";
    }
} else {
    echo "❌ MySQL PDO connection: FAILED\n";
    $allPassed = false;
}
echo "\n";

// 2. Tables Verification
echo "2️⃣  DATABASE TABLES\n";
echo "────────────────────────────────────────────────────────────\n";

$tables = ['users', 'tickets'];
foreach ($tables as $table) {
    try {
        $result = $pdo->query("SHOW TABLES LIKE '$table'")->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            echo "✅ Table '$table': EXISTS\n";
            
            // Show column info
            $columns = $pdo->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $col) {
                echo "   • " . str_pad($col['Field'], 25) . " | " . $col['Type'] . "\n";
            }
        } else {
            echo "❌ Table '$table': NOT FOUND\n";
            $allPassed = false;
        }
    } catch (Exception $e) {
        echo "❌ Table '$table': ERROR - " . $e->getMessage() . "\n";
        $allPassed = false;
    }
}
echo "\n";

// 3. Admin User
echo "3️⃣  ADMIN USER\n";
echo "────────────────────────────────────────────────────────────\n";
try {
    $admin = $pdo->query("SELECT * FROM users WHERE username = 'admin'")->fetch(PDO::FETCH_ASSOC);
    if ($admin) {
        echo "✅ Admin user exists\n";
        echo "   ID: " . $admin['id'] . "\n";
        echo "   Username: " . $admin['username'] . "\n";
        echo "   Role: " . $admin['role'] . "\n";
        echo "   Created: " . $admin['created_at'] . "\n";
        
        // Test admin login
        $loginTest = authenticate_user('admin', 'admin123');
        if ($loginTest && $loginTest['role'] === 'admin') {
            echo "✅ Admin login test: PASSED (admin/admin123)\n";
        } else {
            echo "❌ Admin login test: FAILED\n";
            $allPassed = false;
        }
    } else {
        echo "❌ Admin user NOT found\n";
        $allPassed = false;
    }
} catch (Exception $e) {
    echo "❌ Admin user check: ERROR - " . $e->getMessage() . "\n";
    $allPassed = false;
}
echo "\n";

// 4. Test User Creation (Simulate Admin Panel)
echo "4️⃣  USER CREATION (Admin Panel Simulation)\n";
echo "────────────────────────────────────────────────────────────\n";

$newUsername = 'demo_user_' . date('YmdHis');
$newPassword = 'DemoPass123!';
$newRole = 'user';

try {
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (username, email, phone, password_hash, role) 
         VALUES (:u, :e, :p, :h, :r)'
    );
    $stmt->execute([
        ':u' => $newUsername,
        ':e' => 'demo@example.com',
        ':p' => '+1 (555) 123-4567',
        ':h' => $hash,
        ':r' => $newRole,
    ]);
    $newUserId = $pdo->lastInsertId();
    echo "✅ New user created\n";
    echo "   ID: $newUserId\n";
    echo "   Username: $newUsername\n";
    echo "   Role: $newRole\n";
    echo "   Email: demo@example.com\n";
    echo "   Password: DemoPass123!\n";
} catch (Exception $e) {
    echo "❌ User creation FAILED: " . $e->getMessage() . "\n";
    $allPassed = false;
    $newUserId = null;
}
echo "\n";

// 5. Test User Login
if ($newUserId) {
    echo "5️⃣  NEW USER LOGIN TEST\n";
    echo "────────────────────────────────────────────────────────────\n";
    
    $loginTest = authenticate_user($newUsername, $newPassword);
    if ($loginTest) {
        echo "✅ New user login: SUCCESSFUL\n";
        echo "   ID: " . $loginTest['id'] . "\n";
        echo "   Username: " . $loginTest['username'] . "\n";
        echo "   Role: " . $loginTest['role'] . "\n";
        echo "   Email: " . $loginTest['email'] . "\n";
    } else {
        echo "❌ New user login: FAILED\n";
        $allPassed = false;
    }
    echo "\n";
    
    // 6. Session & Display Test
    echo "6️⃣  SESSION & 'LOGGED IN AS' DISPLAY\n";
    echo "────────────────────────────────────────────────────────────\n";
    
    login_user_session($loginTest);
    $sessionUser = auth_get_current_user();
    
    if ($sessionUser && $sessionUser['username'] === $newUsername) {
        echo "✅ Session created successfully\n";
        echo "   Display in index.php header:\n";
        echo "   ┌──────────────────────────────────────────────┐\n";
        echo "   │ Logged in as " . str_pad($sessionUser['username'], 29) . " │\n";
        echo "   └──────────────────────────────────────────────┘\n";
    } else {
        echo "❌ Session creation: FAILED\n";
        $allPassed = false;
    }
    echo "\n";
    
    // Cleanup
    echo "7️⃣  CLEANUP\n";
    echo "────────────────────────────────────────────────────────────\n";
    try {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $newUserId]);
        echo "✅ Demo user removed\n";
    } catch (Exception $e) {
        echo "⚠️  Could not delete demo user\n";
    }
}

echo "\n";

// Summary
echo "╔═════════════════════════════════════════════════════════════╗\n";
if ($allPassed) {
    echo "║  ✅ SYSTEM READY - ALL TESTS PASSED!                      ║\n";
} else {
    echo "║  ❌ SYSTEM HAS ISSUES - REVIEW ABOVE                      ║\n";
}
echo "╚═════════════════════════════════════════════════════════════╝\n\n";

echo "QUICK START:\n";
echo "1. Admin Login:  http://localhost/SYST/login.php\n";
echo "   Username: admin\n";
echo "   Password: admin123\n\n";
echo "2. Create Users: Go to Admin Panel after login\n";
echo "3. New users can login and will see 'Logged in as [username]'\n";
echo "\n";

?>
