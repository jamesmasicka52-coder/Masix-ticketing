<?php
require_once __DIR__ . '/auth.php';
ensure_json_logged_in();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed. Use POST with a CSV file.']);
    exit;
}

if (!isset($_FILES['import_csv'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No CSV file uploaded.']);
    exit;
}

$file = $_FILES['import_csv'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Failed to upload CSV file.']);
    exit;
}

$tmpPath = $file['tmp_name'] ?? '';
if (!is_uploaded_file($tmpPath)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Uploaded file is not available.']);
    exit;
}

if (!isset($pdo) || !$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database connection not available.']);
    exit;
}

$handle = fopen($tmpPath, 'r');
if ($handle === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Unable to read uploaded CSV file.']);
    exit;
}

$header = fgetcsv($handle);
if (!is_array($header) || count($header) === 0) {
    fclose($handle);
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'CSV file appears to be empty or invalid.']);
    exit;
}

$normalize = static fn($value): string => mb_strtolower(trim((string)$value));
$headerMap = array_map($normalize, $header);

function findColumn(array $headerMap, array $keys): ?int {
    foreach ($keys as $key) {
        $lower = mb_strtolower($key);
        $index = array_search($lower, $headerMap, true);
        if ($index !== false) {
            return $index;
        }
    }
    return null;
}

$columns = [
    'issue' => findColumn($headerMap, ['issue', 'issue title', 'issue_title']),
    'solution' => findColumn($headerMap, ['solution', 'resolution']),
    'company' => findColumn($headerMap, ['company']),
    'department' => findColumn($headerMap, ['department']),
    'assigned_to' => findColumn($headerMap, ['assigned to', 'assigned_to', 'assignee']),
    'priority' => findColumn($headerMap, ['priority']),
    'status' => findColumn($headerMap, ['status']),
    'date_created' => findColumn($headerMap, ['date created', 'date_created', 'date']),
];

if ($columns['issue'] === null) {
    fclose($handle);
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'CSV file must include an Issue column.']);
    exit;
}

$allowedPriority = ['low', 'medium', 'high'];
$allowedStatus = ['open', 'in progress', 'closed'];
$user = auth_get_current_user();
$createdBy = (int)($user['id'] ?? 0);
$inserted = 0;
$skipped = 0;
$insertStmt = $pdo->prepare(
    'INSERT INTO tickets (issue_title, solution, company, department, priority, status, assigned_to, created_by, date_created, created_at)
     VALUES (:issue_title, :solution, :company, :department, :priority, :status, :assigned_to, :created_by, :date_created, :created_at)'
);

while (($row = fgetcsv($handle)) !== false) {
    if (count(array_filter($row, static fn($cell) => trim((string)$cell) !== '')) === 0) {
        continue;
    }

    $issue = trim($row[$columns['issue']] ?? '');
    if ($issue === '') {
        $skipped++;
        continue;
    }

    $solution = trim($row[$columns['solution']] ?? '');
    $company = trim($row[$columns['company']] ?? '');
    $department = trim($row[$columns['department']] ?? '');
    $assignedTo = trim($row[$columns['assigned_to']] ?? '');
    $priority = mb_strtolower(trim($row[$columns['priority']] ?? ''));
    $status = mb_strtolower(trim($row[$columns['status']] ?? ''));
    $dateValue = trim($row[$columns['date_created']] ?? '');

    if (!in_array($priority, $allowedPriority, true)) {
        $priority = 'medium';
    }
    if (!in_array($status, $allowedStatus, true)) {
        $status = 'open';
    }

    $dateCreated = date('Y-m-d');
    if ($dateValue !== '') {
        try {
            $dt = new DateTime($dateValue);
            $dateCreated = $dt->format('Y-m-d');
        } catch (Exception $e) {
            $dateCreated = date('Y-m-d');
        }
    }

    try {
        $insertStmt->execute([
            ':issue_title' => $issue,
            ':solution' => $solution,
            ':company' => $company,
            ':department' => $department,
            ':priority' => $priority,
            ':status' => $status,
            ':assigned_to' => $assignedTo,
            ':created_by' => $createdBy,
            ':date_created' => $dateCreated,
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
        $inserted++;
    } catch (Exception $e) {
        $skipped++;
        continue;
    }
}

fclose($handle);

if ($inserted === 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No valid tickets were found in the CSV file.']);
    exit;
}

echo json_encode([
    'ok' => true,
    'inserted' => $inserted,
    'skipped' => $skipped,
    'message' => 'Imported ' . $inserted . ' ticket(s).' . ($skipped ? ' Skipped ' . $skipped . ' invalid row(s).' : ''),
]);
