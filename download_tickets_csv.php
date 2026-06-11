<?php
require_once __DIR__ . '/auth.php';
ensure_json_logged_in();

if (!isset($pdo) || !$pdo) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Database connection not available';
    exit;
}

function get_param_date(string $key): ?string {
    $v = trim((string)($_GET[$key] ?? ''));
    if ($v === '') return null;
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return $v;
    return null;
}

function normalize_ticket(array $row): array {
    return [
        'ticket_id' => $row['id'] ?? '',
        'company' => $row['company'] ?? '',
        'department' => $row['department'] ?? '',
        'issue' => $row['issue_title'] ?? '',
        'solution' => $row['solution'] ?? '',
        'assigned_to' => $row['assigned_to'] ?? '',
        'priority' => $row['priority'] ?? '',
        'status' => $row['status'] ?? '',
        'date' => $row['date_created'] ?? '',
        'created_at' => $row['created_at'] ?? '',
    ];
}

try {
    $user = auth_get_current_user();
    $isAdmin = (($user['role'] ?? 'user') === 'admin');
    $uid = (int)($user['id'] ?? 0);

    $dateFrom = get_param_date('date_from');
    $dateTo = get_param_date('date_to');
    $search = trim((string)($_GET['search'] ?? ''));
    $priority = trim((string)($_GET['priority'] ?? ''));
    $status = trim((string)($_GET['status'] ?? ''));
    $company = trim((string)($_GET['company'] ?? ''));
    $department = trim((string)($_GET['department'] ?? ''));
    $assignedTo = trim((string)($_GET['assigned_to'] ?? ''));
    $ticketId = trim((string)($_GET['ticket_id'] ?? ''));

    $where = ' WHERE 1=1 ';
    $params = [];

    if ($isAdmin) {
        [$adminWhere, $adminParams] = get_visible_tickets_sql_where_clause_for_admin($user);
        $where .= $adminWhere;
        $params = array_merge($params, $adminParams);
    } else {
        $where .= ' AND created_by = :uid ';
        $params[':uid'] = $uid;
    }

    if ($ticketId !== '' && ctype_digit($ticketId)) {
        $where .= ' AND id = :ticket_id ';
        $params[':ticket_id'] = (int)$ticketId;
    }

    if ($dateFrom !== null) {
        $where .= ' AND date_created >= :date_from ';
        $params[':date_from'] = $dateFrom;
    }

    if ($dateTo !== null) {
        $where .= ' AND date_created <= :date_to ';
        $params[':date_to'] = $dateTo;
    }

    if ($priority !== '') {
        $allowedPriority = ['low', 'medium', 'high'];
        if (in_array($priority, $allowedPriority, true)) {
            $where .= ' AND priority = :priority ';
            $params[':priority'] = $priority;
        }
    }

    if ($status !== '') {
        $allowedStatus = ['open', 'in progress', 'closed'];
        if (in_array($status, $allowedStatus, true)) {
            $where .= ' AND status = :status ';
            $params[':status'] = $status;
        }
    }

    if ($company !== '') {
        $where .= ' AND company = :company ';
        $params[':company'] = $company;
    }

    if ($department !== '') {
        $where .= ' AND department = :department ';
        $params[':department'] = $department;
    }

    if ($assignedTo !== '') {
        $where .= ' AND assigned_to = :assigned_to ';
        $params[':assigned_to'] = $assignedTo;
    }

    if ($search !== '') {
        $like = '%' . $search . '%';
        $where .= ' AND (issue_title LIKE :s OR solution LIKE :s OR company LIKE :s OR department LIKE :s OR assigned_to LIKE :s) ';
        $params[':s'] = $like;
    }

    $sql = 'SELECT * FROM tickets ' . $where . ' ORDER BY id DESC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tickets = [];
    foreach ($rows as $r) {
        $tickets[] = normalize_ticket($r);
    }

    $filename = 'tickets_' . date('Y-m-d_H-i-s') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');

    // CSV headers
    fputcsv($out, [
        'ID',
        'Issue',
        'Solution',
        'Company',
        'Department',
        'Assigned To',
        'Priority',
        'Status',
        'Date Created',
        'Created At'
    ]);

    foreach ($tickets as $t) {
        fputcsv($out, [
            $t['ticket_id'],
            $t['issue'],
            $t['solution'],
            $t['company'],
            $t['department'],
            $t['assigned_to'],
            $t['priority'],
            $t['status'],
            $t['date'],
            $t['created_at'],
        ]);
    }

    fclose($out);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'CSV export failed: ' . $e->getMessage();
}

