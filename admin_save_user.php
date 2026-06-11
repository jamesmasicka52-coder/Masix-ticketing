<?php
require_once __DIR__ . '/auth.php';
ensure_logged_in();

$currentUser = auth_get_current_user();

// Privileges only for admin action
if (($currentUser['role'] ?? 'user') !== 'admin') {
    header('Location: unauthorized.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    http_response_code(405);
    header('Location: admin_panel.php?error=' . urlencode('Method not allowed'));
    exit;
}

$username = trim($_POST['username'] ?? '');
$role = trim($_POST['role'] ?? 'user');
$password = $_POST['password'] ?? '';

// Non-default admins cannot create other admins (only the default admin can manage admin roles)
if (!is_default_admin($currentUser) && $role === 'admin') {
    header('Location: admin.php?error=' . urlencode('Only the default admin can create admin accounts.'));
    exit;
}


if ($username === '' || !in_array($role, ['admin', 'user'], true) || $password === '') {
    header('Location: admin.php?error=' . urlencode('Invalid input')); 
    exit;
}
if (strlen($password) < 6) {
    header('Location: admin.php?error=' . urlencode('Password must be at least 6 characters')); 
    exit;
}

// If username exists, reject
$existing = find_user_by_login($username);
if ($existing) {
    header('Location: admin.php?error=' . urlencode('Username already exists'));
    exit;
}

// Create user: reuse existing register_user flow, then update role.
// register_user expects email/phone; keep null.
$email = '';
$phone = '';

$result = null;
try {
    // Prefer DB insert directly to set role.
    global $pdo;
    if (isset($pdo) && $pdo) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, phone, password_hash, role, added_by_admin_id) VALUES (:u, :e, :p, :h, :r, :added_by)'
        );
        $stmt->execute([
            ':u' => $username,
            ':e' => $email !== '' ? $email : null,
            ':p' => $phone !== '' ? $phone : null,
            ':h' => $hash,
            ':r' => $role,
            ':added_by' => is_default_admin($currentUser) ? null : (int)$currentUser['id'],
        ]);
        $result = ['ok' => true, 'id' => (int)$pdo->lastInsertId()];



    } else {
        // Fallback JSON so the created user can still log in
        // (users.json is used by auth.php find_user_by_login/authenticate_user)
        $jsonPath = __DIR__ . '/users.json';
        $users = [];
        if (is_readable($jsonPath)) {
            $contents = @file_get_contents($jsonPath);
            if ($contents !== false) {
                $decoded = json_decode($contents, true);
                if (is_array($decoded)) $users = $decoded;
            }
        }

        $maxId = 0;
        foreach ($users as $u) {
            if (!empty($u['id'])) $maxId = max($maxId, (int)$u['id']);
        }

        $newId = $maxId + 1;
        $users[] = [
            'id' => $newId,
            'username' => $username,
            'email' => $email !== '' ? $email : null,
            'phone' => $phone !== '' ? $phone : null,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role' => $role,
            'added_by_admin_id' => is_default_admin($currentUser) ? null : (int)$currentUser['id'],
            'created_at' => date('c'),
        ];


        $written = @file_put_contents($jsonPath, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        if ($written === false) {
            $result = null;
        } else {
            $result = ['ok' => true, 'id' => $newId];
        }
    }
} catch (Exception $e) {
    $result = null;
}

if (!$result || !isset($result['ok'])) {
    header('Location: admin.php?error=' . urlencode('Could not create user. Ensure DB schema is applied.'));
    exit;
}

header('Location: admin.php?message=' . urlencode('User created successfully: ' . $username));
exit;

