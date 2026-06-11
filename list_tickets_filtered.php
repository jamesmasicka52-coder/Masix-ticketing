<?php
require_once __DIR__ . '/auth.php';
ensure_json_logged_in();

header('Content-Type: application/json; charset=utf-8');

if (!isset($pdo) || !$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database connection not available']);
    exit;
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

function get_param_date(string $key): ?string {
    $v = trim((string)($_GET[$key] ?? ''));
    if ($v === '') return null;
    // accept YYYY-MM-DD
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return $v;
    return null;
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
    $sort = trim((string)($_GET['sort'] ?? ''));

    $allowedSorts = ['id_desc', 'id_asc', 'date_desc', 'date_asc', 'created_at_desc', 'created_at_asc'];
    if (!in_array($sort, $allowedSorts, true)) {
        $sort = 'id_desc';
    }

    $orderBy = match ($sort) {
        'id_asc' => 'id ASC',
        'date_desc' => 'date_created DESC, id DESC',
        'date_asc' => 'date_created ASC, id ASC',
        'created_at_asc' => 'created_at ASC, id ASC',
        'created_at_desc' => 'created_at DESC, id DESC',
        default => 'id DESC',
    };

    $where = ' WHERE 1=1 ';
    $params = [];

    // Authorization scope
    if ($isAdmin) {
        [$adminWhere, $adminParams] = get_visible_tickets_sql_where_clause_for_admin($user);
        $where .= $adminWhere;
        $params = array_merge($params, $adminParams);
    } else {
        $where .= ' AND created_by = :uid ';
        $params[':uid'] = $uid;
    }

    // Filters
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

    $sql = 'SELECT * FROM tickets ' . $where . ' ORDER BY ' . $orderBy;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $tickets = [];
    foreach ($rows as $r) {
        $tickets[] = normalize_ticket($r);
    }

    $totalCount = count($tickets);

    echo json_encode(['ok' => true, 'tickets' => $tickets, 'total_count' => $totalCount]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

