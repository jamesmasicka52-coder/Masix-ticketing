<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
ensure_logged_in();

$currentUser = auth_get_current_user();

// Privileges only for admin action
if (($currentUser['role'] ?? 'user') !== 'admin') {
    header('Location: unauthorized.php');
    exit;
}

$deletingDefaultAdmin = is_default_admin($currentUser);


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: adminpanel.php?error=' . urlencode('Invalid request'));
    exit;
}

$username = trim($_POST['username'] ?? '');

if ($username === '') {
    header('Location: adminpanel.php?error=' . urlencode('Username is required'));
    exit;
}

// Prevent admin from deleting themselves
if ($username === $currentUser['username']) {
    header('Location: adminpanel.php?error=' . urlencode('Cannot delete your own admin account. This protects your access.'));
    exit;
}


// Find user to delete
$userToDelete = find_user_by_login($username);
if (!$userToDelete) {
    header('Location: adminpanel.php?error=' . urlencode('User not found: ' . htmlspecialchars($username)));
    exit;
}

// Authorization: default admin can delete anyone. Added admins can only delete users they added, and never delete admins.
if (!$deletingDefaultAdmin) {
    // Never delete an admin account
    if (($userToDelete['role'] ?? 'user') === 'admin') {
        header('Location: adminpanel.php?error=' . urlencode('Only the default admin can remove admin accounts.'));
        exit;
    }

    // Only delete users that this admin added
    $addedBy = (int)($userToDelete['added_by_admin_id'] ?? 0);
    if ($addedBy !== (int)$currentUser['id']) {
        header('Location: adminpanel.php?error=' . urlencode('You can only delete users you added.'));
        exit;
    }
}

// Delete user from database
if (isset($pdo) && $pdo) {
    try {
        // Delete user's tickets first (if any)
        $stmt = $pdo->prepare('DELETE FROM tickets WHERE created_by = :user_id');
        $stmt->execute([':user_id' => (int)$userToDelete['id']]);
        
        // Delete user
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => (int)$userToDelete['id']]);
        
        header('Location: adminpanel.php?message=' . urlencode('User deleted successfully: ' . htmlspecialchars($username)));
        exit;
    } catch (Exception $e) {
        header('Location: adminpanel.php?error=' . urlencode('Database error: ' . htmlspecialchars($e->getMessage())));
        exit;
    }
}


// Fallback: try to delete from JSON
$jsonPath = __DIR__ . '/users.json';
if (is_readable($jsonPath)) {
    $contents = @file_get_contents($jsonPath);
    if ($contents !== false) {
        $users = json_decode($contents, true);
        if (is_array($users)) {
            $filtered = [];
            $found = false;
            foreach ($users as $u) {
                if (($u['username'] ?? '') === $username) {
                    $found = true;
                    continue;
                }
                $filtered[] = $u;
            }
            
            if ($found) {
                $written = @file_put_contents($jsonPath, json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
                if ($written !== false) {
                    header('Location: adminpanel.php?message=' . urlencode('User deleted successfully: ' . htmlspecialchars($username)));
                    exit;
                }
            }
        }
    }
}

header('Location: adminpanel.php?error=' . urlencode('Could not delete user. User may not exist or database is unavailable.'));
exit;
