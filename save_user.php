<?php
require_once __DIR__ . '/auth.php';

$currentUser = auth_get_current_user();
if (empty($currentUser) || ($currentUser['role'] ?? 'user') !== 'admin') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Admin only']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    header('Content-Type: text/plain; charset=utf-8');
    echo 'Method not allowed';
    exit;
}

$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

$errors = [];
if ($username === '') {
    $errors[] = 'Username is required.';
}
if ($password === '') {
    $errors[] = 'Password is required.';
}
if ($password !== $confirm) {
    $errors[] = 'Password and confirm password do not match.';
}
if (strlen($password) > 0 && strlen($password) < 6) {
    $errors[] = 'Password must be at least 6 characters.';
}

// check existing username
if (!$errors) {
    $existing = find_user_by_login($username);
    if ($existing) {
        $errors[] = 'Username already taken.';
    }
}

$isJson = (strpos(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false) || isset($_POST['json']);

if ($errors) {
    if ($isJson) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'errors' => $errors]);
        exit;
    }

    $msg = urlencode(implode(' ', $errors));
    header('Location: login.php?message=' . $msg);
    exit;
}

$result = register_user($username, $email, $phone, $password);
if ($result && isset($result['id'])) {
    $newId = $result['id'];
    $store = $result['store'] ?? 'unknown';
    if ($isJson) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'id' => $newId, 'store' => $store]);
        exit;
    }

    $msg = urlencode('Account created. Stored in: ' . strtoupper($store) . '. Please log in.');
    header('Location: login.php?message=' . $msg);
    exit;
}

// fallback failure
if ($isJson) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Could not create account']);
    exit;
}

header('Location: login.php?message=' . urlencode('Could not create account'));
exit;
