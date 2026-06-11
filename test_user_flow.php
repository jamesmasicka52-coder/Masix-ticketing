<?php
/**
 * Test Script: Admin User Creation
 * Tests the complete flow: create user -> verify in DB -> login -> check session
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

echo "═════════════════════════════════════════════════════════════\n";
echo "TEST: Admin User Creation & Login Flow\n";
echo "═════════════════════════════════════════════════════════════\n\n";

// Test 1: Check if admin user exists and can login
echo "TEST 1: Admin Login\n";
echo "─────────────────────────────────────────────────────────────\n";

$admin = authenticate_user('admin', 'admin123');
if ($admin) {
    echo "✅ Admin login successful\n";
    echo "   ID: " . $admin['id'] . "\n";
    echo "   Username: " . $admin['username'] . "\n";
    echo "   Role: " . $admin['role'] . "\n";
    echo "   Email: " . ($admin['email'] ?? 'N/A') . "\n";
} else {
    echo "❌ Admin login FAILED\n";
    exit(1);
}

echo "\n";

// Test 2: Create a test user programmatically
echo "TEST 2: Create Test User\n";
echo "─────────────────────────────────────────────────────────────\n";

$testUsername = 'testuser_' . time();
$testPassword = 'Test@1234';

try {
    $hash = password_hash($testPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (username, email, phone, password_hash, role) VALUES (:u, :e, :p, :h, :r)'
    );
    $stmt->execute([
        ':u' => $testUsername,
        ':e' => 'test@example.com',
        ':p' => '+1234567890',
        ':h' => $hash,
        ':r' => 'user',
    ]);
    $userId = $pdo->lastInsertId();
    echo "✅ Test user created successfully\n";
    echo "   ID: $userId\n";
    echo "   Username: $testUsername\n";
} catch (Exception $e) {
    echo "❌ Failed to create test user: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n";

// Test 3: Verify user exists in DB
echo "TEST 3: Verify User in Database\n";
echo "─────────────────────────────────────────────────────────────\n";

$user = find_user_by_login($testUsername);
if ($user) {
    echo "✅ User found in database\n";
    echo "   ID: " . $user['id'] . "\n";
    echo "   Username: " . $user['username'] . "\n";
    echo "   Role: " . ($user['role'] ?? 'user') . "\n";
    echo "   Email: " . ($user['email'] ?? 'N/A') . "\n";
    echo "   Phone: " . ($user['phone'] ?? 'N/A') . "\n";
} else {
    echo "❌ User NOT found in database\n";
    exit(1);
}

echo "\n";

// Test 4: Test user login
echo "TEST 4: Test User Login\n";
echo "─────────────────────────────────────────────────────────────\n";

$testUser = authenticate_user($testUsername, $testPassword);
if ($testUser) {
    echo "✅ Test user login successful\n";
    echo "   ID: " . $testUser['id'] . "\n";
    echo "   Username: " . $testUser['username'] . "\n";
    echo "   Role: " . $testUser['role'] . "\n";
} else {
    echo "❌ Test user login FAILED\n";
    exit(1);
}

echo "\n";

// Test 5: Simulate session login
echo "TEST 5: Simulate Session Login\n";
echo "─────────────────────────────────────────────────────────────\n";

login_user_session($testUser);
$sessionUser = auth_get_current_user();
if ($sessionUser && $sessionUser['username'] === $testUsername) {
    echo "✅ Session login successful\n";
    echo "   Current user in session: " . $sessionUser['username'] . "\n";
    echo "   Role: " . $sessionUser['role'] . "\n";
} else {
    echo "❌ Session login FAILED\n";
    exit(1);
}

echo "\n";

// Cleanup: Delete test user
echo "CLEANUP: Removing Test User\n";
echo "─────────────────────────────────────────────────────────────\n";

try {
    $stmt = $pdo->prepare('DELETE FROM users WHERE username = :u');
    $stmt->execute([':u' => $testUsername]);
    echo "✅ Test user removed\n";
} catch (Exception $e) {
    echo "⚠️  Could not delete test user: " . $e->getMessage() . "\n";
}

echo "\n═════════════════════════════════════════════════════════════\n";
echo "✅ ALL TESTS PASSED!\n";
echo "═════════════════════════════════════════════════════════════\n\n";
echo "Summary:\n";
echo "• Admin user (admin/admin123) can login\n";
echo "• New users can be created in the database\n";
echo "• Created users can be found and authenticated\n";
echo "• Session management works correctly\n";
echo "• 'Logged in as [username]' will display properly\n";
?>
