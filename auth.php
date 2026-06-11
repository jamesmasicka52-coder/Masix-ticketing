<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool {
    return !empty($_SESSION['user']['id']);
}

function auth_get_current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool {
    $u = auth_get_current_user();
    return !empty($u['role']) && $u['role'] === 'admin';
}

function is_default_admin(array $user = null): bool {
    if ($user === null) {
        $user = auth_get_current_user() ?? [];
    }
    return ($user['username'] ?? '') === 'admin' && (($user['role'] ?? 'user') === 'admin');
}

function get_added_user_ids_for_admin(int $adminId): array {
    global $pdo;
    if (!isset($pdo) || !$pdo) {
        return [];
    }
    try {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE added_by_admin_id = :aid');
        $stmt->execute([':aid' => $adminId]);
        return array_map(static fn($r) => (int)$r['id'], $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (Exception $e) {
        return [];
    }
}

function get_visible_tickets_sql_where_clause_for_admin(array $currentUser): array {
    // returns [sql_fragment, params]
    $uid = (int)($currentUser['id'] ?? 0);

    if (is_default_admin($currentUser)) {
        return ['', []];
    }

    $addedIds = get_added_user_ids_for_admin($uid);
    $visibleIds = array_unique(array_merge([$uid], $addedIds));

    $placeholders = implode(',', array_fill(0, count($visibleIds), '?'));
    return [' AND created_by IN (' . $placeholders . ') ', $visibleIds];
}


function login_user_session(array $user): void {

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'username' => $user['username'] ?? '',
        'email' => $user['email'] ?? '',
        'phone' => $user['phone'] ?? '',
        'role' => $user['role'] ?? 'user',
        // admin single-device enforcement token (raw token stored in session)
        'admin_token' => $user['admin_token'] ?? null,
    ];
}


function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

function ensure_logged_in(): void {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function redirect_if_logged_in(): void {
    if (is_logged_in()) {
        // validate admin single-device as well
        enforce_admin_single_device_if_needed();
        header('Location: index.php');
        exit;
    }
}


function ensure_json_logged_in(): void {
    if (!is_logged_in()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Authentication required']);
        exit;
    }
    enforce_admin_single_device_if_needed();
}

function enforce_admin_single_device_if_needed(): void {
    $u = auth_get_current_user();
    if (!$u) return;
    if (($u['role'] ?? 'user') !== 'admin') return;

    // If admin has a token hash stored in DB, require session token to match.
    $provided = $_SESSION['admin_login_token'] ?? null;
    if (!$provided) {
        logout_user();
        header('Location: login.php?message=' . urlencode('Admin already logged in on another device. Please login again.'));
        exit;
    }

    $tokenHash = hash('sha256', (string)$provided);
    global $pdo;
    $dbHash = null;
    if (isset($pdo) && $pdo) {
        try {
            $stmt = $pdo->prepare('SELECT admin_session_token_hash FROM users WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => (int)$u['id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $dbHash = $row['admin_session_token_hash'] ?? null;
        } catch (Exception $e) {
            $dbHash = null;
        }
    }

    if ($dbHash && !hash_equals((string)$dbHash, $tokenHash)) {
        logout_user();
        header('Location: login.php?message=' . urlencode('Admin session invalid (single-device enforced).'));
        exit;
    }
}


function find_user_by_login(string $login): ?array {
    global $pdo;
    // Try database first
    if (isset($pdo) && $pdo) {
        try {
            $sql = 'SELECT * FROM users WHERE username = :login LIMIT 1';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':login' => $login]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                return $user;
            }
        } catch (Exception $e) {
            // fall through to JSON fallback
        }
    }

    // Fallback: read from users.json
    $jsonPath = __DIR__ . '/users.json';
    if (!is_readable($jsonPath)) {
        return null;
    }

    $contents = @file_get_contents($jsonPath);
    if ($contents === false) {
        return null;
    }

    $users = json_decode($contents, true);
    if (!is_array($users)) {
        return null;
    }

    foreach ($users as $u) {
        if (!empty($u['username']) && $u['username'] === $login) {
            return $u;
        }
    }

    return null;
}

function get_user_by_id(int $id): ?array {
    global $pdo;
    // Try database first
    if (isset($pdo) && $pdo) {
        try {
            $stmt = $pdo->prepare('SELECT id, username, email, phone, role, added_by_admin_id FROM users WHERE id = :id LIMIT 1');

            $stmt->execute([':id' => $id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                return $user;
            }
        } catch (Exception $e) {
            // fall through to JSON fallback
        }
    }

    // Fallback: read from users.json
    $jsonPath = __DIR__ . '/users.json';
    if (!is_readable($jsonPath)) {
        return null;
    }

    $contents = @file_get_contents($jsonPath);
    if ($contents === false) {
        return null;
    }

    $users = json_decode($contents, true);
    if (!is_array($users)) {
        return null;
    }

    foreach ($users as $u) {
        if (!empty($u['id']) && (int)$u['id'] === $id) {
            return [
                'id' => (int)$u['id'],
                'username' => $u['username'] ?? '',
                'email' => $u['email'] ?? '',
                'phone' => $u['phone'] ?? '',
                'role' => $u['role'] ?? 'user',
            ];
        }
    }

    return null;
}

function register_user(string $username, string $email, string $phone, string $password): ?array {
    global $pdo;
    $username = trim($username);
    $email = trim($email);
    $phone = trim($phone);
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // If PDO available, try DB insert
    if (isset($pdo) && $pdo) {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (username, email, phone, password_hash) VALUES (:username, :email, :phone, :password_hash)'
            );
            $stmt->execute([
                ':username' => $username,
                ':email' => $email !== '' ? $email : null,
                ':phone' => $phone !== '' ? $phone : null,
                ':password_hash' => $passwordHash,
            ]);

            return ['id' => (int)$pdo->lastInsertId(), 'store' => 'db'];
        } catch (Exception $e) {
            // fall through to JSON fallback
        }
    }

    // Fallback: save into users.json
    $jsonPath = __DIR__ . '/users.json';
    $users = [];
    if (is_readable($jsonPath)) {
        $contents = @file_get_contents($jsonPath);
        if ($contents !== false) {
            $decoded = json_decode($contents, true);
            if (is_array($decoded)) {
                $users = $decoded;
            }
        }
    }

    // Ensure username unique
    foreach ($users as $u) {
        if (!empty($u['username']) && $u['username'] === $username) {
            return null;
        }
    }

    $maxId = 0;
    foreach ($users as $u) {
        if (!empty($u['id'])) {
            $maxId = max($maxId, (int)$u['id']);
        }
    }

    $newId = $maxId + 1;
    $users[] = [
        'id' => $newId,
        'username' => $username,
        'email' => $email !== '' ? $email : null,
        'phone' => $phone !== '' ? $phone : null,
        'password_hash' => $passwordHash,
        'created_at' => date('c'),
    ];

    $written = @file_put_contents($jsonPath, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    if ($written === false) {
        return null;
    }

    return ['id' => $newId, 'store' => 'json'];
}

function authenticate_user(string $login, string $password): ?array {
    $user = find_user_by_login($login);
    if (!$user) {
        return null;
    }

    $hash = $user['password_hash'] ?? $user['password'] ?? '';
    if (password_verify($password, $hash)) {
        // include role for authorization
        return [
            'id' => (int)$user['id'],
            'username' => $user['username'] ?? '',
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? '',
            'role' => $user['role'] ?? 'user',
            // used only for admin single-device enforcement
            'admin_token' => $user['admin_session_token_hash'] ?? null,
        ];
    }

    // If DB user exists but password didn't match, try JSON fallback store

    $jsonPath = __DIR__ . '/users.json';
    if (is_readable($jsonPath)) {
        $contents = @file_get_contents($jsonPath);
        if ($contents !== false) {
            $users = json_decode($contents, true);
            if (is_array($users)) {
                foreach ($users as $u) {
                    if (!empty($u['username']) && $u['username'] === $login) {
                        $jsonHash = $u['password_hash'] ?? $u['password'] ?? '';
                        if ($jsonHash && password_verify($password, $jsonHash)) {
                            return [
                                'id' => (int)$u['id'],
                                'username' => $u['username'] ?? '',
                                'email' => $u['email'] ?? '',
                                'phone' => $u['phone'] ?? '',
                            ];
                        }
                    }
                }
            }
        }
    }

    return null;
}
