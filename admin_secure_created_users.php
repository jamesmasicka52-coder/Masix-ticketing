<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

ensure_logged_in();

$currentUser = auth_get_current_user();
if (empty($currentUser) || ($currentUser['role'] ?? 'user') !== 'admin') {
    header('Location: unauthorized.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: adminpanel.php');
    exit;
}

$targetUsername = trim($_POST['username'] ?? '');
$newPassword = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

$errors = [];
if ($targetUsername === '') $errors[] = 'Username is required.';
if ($newPassword === '') $errors[] = 'New password is required.';
if ($newPassword !== $confirm) $errors[] = 'Password and confirm password do not match.';
if (strlen($newPassword) < 6) $errors[] = 'Password must be at least 6 characters.';

// Authorization: non-default admins can only update security for users they created.
$isDefault = is_default_admin($currentUser);

if (empty($errors)) {
    $targetUser = find_user_by_login($targetUsername);
    if (!$targetUser) {
        $errors[] = 'User not found.';
    } else {
        // Default admin can update anyone (admin restrictions handled below)
        if (!$isDefault) {
            // Non-default admins can only update users they added
            $addedBy = (int)($targetUser['added_by_admin_id'] ?? 0);
            if ($addedBy !== (int)$currentUser['id']) {
                $errors[] = 'You can only update security for users you created.';
            }
        }

        // Security hardening: never allow changing password for admins (optional rule; matches your existing admin management constraints)
        if (($targetUser['role'] ?? 'user') === 'admin') {
            $errors[] = 'Admin accounts cannot be updated here.';
        }
    }
}

if (!empty($errors)) {
    header('Location: adminpanel.php?error=' . urlencode(implode(' ', $errors)));
    exit;
}

try {
    if (isset($pdo) && $pdo) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :h WHERE id = :id LIMIT 1');
        $stmt->execute([':h' => $hash, ':id' => (int)$targetUser['id']]);
    }

    header('Location: adminpanel.php?message=' . urlencode('Password updated for: ' . $targetUsername));
    exit;
} catch (Exception $e) {
    header('Location: adminpanel.php?error=' . urlencode('Database update failed.'));
    exit;
}

