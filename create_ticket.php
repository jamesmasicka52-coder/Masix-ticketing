<?php
require_once __DIR__ . '/auth.php';
ensure_json_logged_in();

// This endpoint is called by AJAX (fetch FormData) from script.js.
// Return JSON with ok/error (do NOT redirect).
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

// Support FormData request from index.html
$issue = trim($_POST['issue'] ?? '');

$solution = trim($_POST['solution'] ?? '');
$company = trim($_POST['company'] ?? '');
$department = trim($_POST['department'] ?? '');
$priority = trim($_POST['priority'] ?? '');
$status = trim($_POST['status'] ?? '');
$assignedTo = trim($_POST['assigned_to'] ?? '');
$date = trim($_POST['date'] ?? '');

// Validate required fields
if (
    $issue === '' ||
    $solution === '' ||
    $company === '' ||
    $department === '' ||
    $priority === '' ||
    $status === '' ||
    $assignedTo === '' ||
    $date === ''
) {
    die('Missing required fields');
}

// Convert date
try {
    $dt = new DateTime($date);
    $date_created = $dt->format('Y-m-d');
} catch (Exception $e) {
    die('Invalid date');
}

// Verify database connection (NO silent fallback)
if (!isset($pdo) || !$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database connection not available (PDO is null). Ticket not saved.']);
    exit;
}


try {


    $stmt = $pdo->prepare("
        INSERT INTO tickets
        (
            issue_title,
            solution,
            company,
            department,
            priority,
            status,
            assigned_to,
            date_created
        )
        VALUES
        (
            :issue_title,
            :solution,
            :company,
            :department,
            :priority,
            :status,
            :assigned_to,
            :date_created
        )
    ");

    $stmt->execute([
        ':issue_title' => $issue,
        ':solution' => $solution,
        ':company' => $company,
        ':department' => $department,
        ':priority' => $priority,
        ':status' => $status,
        ':assigned_to' => $assignedTo,
        ':date_created' => $date_created,
    ]);

    // Return JSON response for AJAX callers
    echo json_encode([
        'ok' => true,
        'ticket_id' => (int)$pdo->lastInsertId(),
    ]);
    exit;


} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error creating ticket: ' . $e->getMessage()]);
    exit;
}
