<?php
require_once __DIR__ . '/auth.php';
ensure_json_logged_in();

header('Content-Type: application/json; charset=utf-8');

if (!isset($pdo) || !$pdo) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Database connection not available']);
    exit;
}

try {
    $user = auth_get_current_user();
    $isAdmin = (($user['role'] ?? 'user') === 'admin');

    if ($isAdmin) {
        [$where, $params] = get_visible_tickets_sql_where_clause_for_admin($user);
        $stmt = $pdo->prepare('SELECT * FROM tickets WHERE 1=1 ' . $where . ' ORDER BY id DESC');
        $stmt->execute($params);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM tickets WHERE created_by = :uid ORDER BY id DESC');
        $stmt->execute([':uid' => (int)$user['id']]);
    }


    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Map to keys expected by tickets.php UI + script.js
    $tickets = [];
    foreach ($rows as $r) {
        $tickets[] = [
            'ticket_id'   => $r['id'] ?? '',
            'company'     => $r['company'] ?? '',
            'department'  => $r['department'] ?? '',
            'issue'       => $r['issue_title'] ?? ($r['Issue'] ?? ''),
            'solution'    => $r['solution'] ?? ($r['Solution'] ?? ''),
            'assigned_to' => $r['assigned_to'] ?? '',
            'priority'    => $r['priority'] ?? '',
            'status'      => $r['status'] ?? '',
            'date'        => $r['date_created'] ?? $r['created_at'] ?? '',
            'created_at'  => $r['created_at'] ?? '',
        ];
    }

    $today = (new DateTime('today'))->format('Y-m-d');
    if ($isAdmin) {
        [$where, $params] = get_visible_tickets_sql_where_clause_for_admin($user);
        $sql = 'SELECT COUNT(*) AS cnt FROM tickets WHERE 1=1 ' . $where . ' AND (date_created = :today OR date(date_created) = :today)';
        $stmt2 = $pdo->prepare($sql);
        $execParams = $params;
        $execParams[':today'] = $today;
        $stmt2->execute($execParams);
    } else {
        $stmt2 = $pdo->prepare(
            'SELECT COUNT(*) AS cnt FROM tickets WHERE created_by = :uid AND (date_created = :today OR date(date_created) = :today)'
        );
        $stmt2->execute([':uid' => (int)$user['id'], ':today' => $today]);
    }


    $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
    $todayCount = isset($row2['cnt']) ? (int)$row2['cnt'] : 0;

    echo json_encode(['ok' => true, 'tickets' => $tickets, 'today_count' => $todayCount]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

