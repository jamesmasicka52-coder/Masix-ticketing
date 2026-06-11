<?php

// Azure App Service configuration (preferred)
// Set these in Azure Portal -> App Service -> Configuration (Application settings)
$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'syst_ticketing';

// mysqli connection (legacy)
$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// PDO connection (used by tickets endpoints)
try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (Exception $e) {
    // Keep $conn alive; endpoints will fail gracefully
    $pdo = null;
}


function db_insert_ticket(array $ticket) {
    global $pdo;
    if (!isset($pdo) || !$pdo) {
        return false;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO tickets
        (issue_title, solution, company, department, priority, status, assigned_to, created_by, date_created)
        VALUES
        (:issue_title, :solution, :company, :department, :priority, :status, :assigned_to, :created_by, :date_created)"
    );

    $stmt->execute([
        ':issue_title' => $ticket['issue_title'] ?? $ticket['issue'] ?? $ticket['Issue'] ?? '',
        ':solution' => $ticket['solution'] ?? '',
        ':company' => $ticket['company'] ?? '',
        ':department' => $ticket['department'] ?? '',
        ':priority' => $ticket['priority'] ?? '',
        ':status' => $ticket['status'] ?? '',
        ':assigned_to' => $ticket['assigned_to'] ?? '',
        ':created_by' => $ticket['created_by'] ?? null,
        ':date_created' => $ticket['date_created'] ?? $ticket['date'] ?? $ticket['date_reported'] ?? date('Y-m-d'),
    ]);

    return (int)$pdo->lastInsertId();
}

function db_update_ticket_status(int $ticketId, string $status, string $assignedTo = null) {
    global $pdo;
    if (!isset($pdo) || !$pdo) {
        return false;
    }

    $sql = "UPDATE tickets SET status = :status";
    $params = [':status' => $status, ':ticket_id' => $ticketId];

    if ($assignedTo !== null) {
        $sql .= ", assigned_to = :assigned_to";
        $params[':assigned_to'] = $assignedTo;
    }

    $sql .= " WHERE id = :ticket_id";
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function db_delete_ticket(int $ticketId) {
    global $pdo;
    if (!isset($pdo) || !$pdo) {
        return false;
    }

    $stmt = $pdo->prepare("DELETE FROM tickets WHERE id = :ticket_id");
    return $stmt->execute([':ticket_id' => $ticketId]);
}
?>
