<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

ensure_logged_in();

$currentUser = auth_get_current_user();
if (empty($currentUser) || ($currentUser['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Admin only']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$targetUsername = trim($_POST['username'] ?? '');
$newPassword = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';
$currentPassword = $_POST['current_password'] ?? '';

$errors = [];

$isDefault = is_default_admin($currentUser);

if ($currentPassword === '') {
    $errors[] = 'Current password is required.';
}

if ($newPassword === '' || $confirm === '') {
    // For default admin: password change is the only allowed personalization; require it.
    // For non-default admin: require password if any password fields supplied; keep strict and require password when updating password.
    if (!$isDefault) {
        // if user tries to change username without changing password, allow only if password is provided too (stronger security)
        if ($targetUsername !== '') {
            $errors[] = 'Password change is required when updating account security details.';
        }
    } else {
        $errors[] = 'New password is required.';
    }
}

if ($newPassword !== '' || $confirm !== '') {
    if ($newPassword === '' || $confirm === '') {
        $errors[] = 'New password and confirm password are required.';
    }
    if ($newPassword !== $confirm) {
        $errors[] = 'Password and confirm password do not match.';
    }
    if (strlen($newPassword) > 0 && strlen($newPassword) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
}

if (!$isDefault) {
    // username personalization allowed for non-default admins
    if ($targetUsername !== '') {
        if (!preg_match('/^[A-Za-z0-9_\.\-]{3,100}$/', $targetUsername)) {
            $errors[] = 'Username must be 3-100 characters and contain only letters, numbers, underscore, dot, or hyphen.';
        }
    }
} else {
    // default admin must not be allowed to change username
    if ($targetUsername !== '') {
        $errors[] = 'Default admin cannot change username. Only password can be updated.';
    }
}

// Authorization: allow updating only the currently logged-in account
$targetUserId = (int)($currentUser['id'] ?? 0);
if ($targetUserId <= 0) {
    $errors[] = 'Invalid session.';
}

// Verify current password
$storedRow = null;
if (isset($pdo) && $pdo) {
    try {
        $stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $targetUserId]);
        $storedRow = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $storedRow = null;
    }
}

if (!$storedRow) {
    $errors[] = 'Could not load current user.';
}

if ($storedRow) {
    $storedHash = $storedRow['password_hash'] ?? '';
    if (!is_string($storedHash) || !$storedHash || !password_verify($currentPassword, $storedHash)) {
        $errors[] = 'Current password is incorrect.';
    }
}

// Validate username uniqueness when provided and different
$finalUsername = $storedRow['username'] ?? ($currentUser['username'] ?? '');
if (!$isDefault && $targetUsername !== '' && $targetUsername !== $finalUsername) {
    if (isset($pdo) && $pdo) {
        try {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u AND id != :id LIMIT 1');
            $stmt->execute([':u' => $targetUsername, ':id' => $targetUserId]);
            $exists = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($exists) {
                $errors[] = 'Username already taken.';
            }
        } catch (Exception $e) {
            $errors[] = 'Could not validate username.';
        }
    }
}

if (!empty($errors)) {
    // If browser form posts accept redirect style
    $isJson = (strpos(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false);
    if ($isJson) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'errors' => $errors]);
        exit;
    }

    $msg = urlencode(implode(' ', $errors));
    header('Location: admin_profile.php?error=' . $msg);
    exit;
}

// Apply updates
$finalUsername = $finalUsername;
if (!$isDefault && $targetUsername !== '' && $targetUsername !== $finalUsername) {
    $finalUsername = $targetUsername;
}

$updatesPassword = ($newPassword !== '');

try {
    if (isset($pdo) && $pdo) {
        if ($updatesPassword) {
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            if (!$isDefault) {
                $stmt = $pdo->prepare('UPDATE users SET username = :u, password_hash = :h WHERE id = :id');
                $stmt->execute([':u' => $finalUsername, ':h' => $hash, ':id' => $targetUserId]);
            } else {
                $stmt = $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
                $stmt->execute([':h' => $hash, ':id' => $targetUserId]);
            }
        } else {
            // username-only updates are not allowed; but keep safe.
            http_response_code(400);
            header('Location: admin_profile.php?error=' . urlencode('Password update required.'));
            exit;
        }

        // Update session username if needed
        if (isset($finalUsername) && $finalUsername !== '') {
            $_SESSION['user']['username'] = $finalUsername;
        }
    }
} catch (Exception $e) {
    header('Location: admin_profile.php?error=' . urlencode('Database update failed.'));
    exit;
}

header('Location: admin_profile.php?message=' . urlencode('Account updated successfully.'));
exit;

