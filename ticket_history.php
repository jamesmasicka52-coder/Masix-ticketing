<?php
// Ticket History (MySQL via PDO)
require_once __DIR__ . '/auth.php';
ensure_logged_in();

$tickets = [];

if (isset($pdo) && $pdo) {
    try {
        $currentUser = auth_get_current_user();
        $role = $currentUser['role'] ?? 'user';
        if ($role === 'admin') {
            // default admin sees everything; added admins see only tickets for users they added
            [$where, $params] = get_visible_tickets_sql_where_clause_for_admin($currentUser);
            $stmt = $pdo->prepare('SELECT * FROM tickets WHERE 1=1 ' . $where . ' ORDER BY id DESC');
            $stmt->execute($params);
        } else {
            $uid = (int)($currentUser['id'] ?? 0);
            $stmt = $pdo->prepare('SELECT * FROM tickets WHERE created_by = :uid ORDER BY id DESC');
            $stmt->execute([':uid' => $uid]);
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


        foreach ($rows as $r) {
            $tickets[] = [
                'issue' => $r['issue_title'] ?? ($r['Issue'] ?? ''),
                'solution' => $r['solution'] ?? ($r['Solution'] ?? ''),
                'company' => $r['company'] ?? '',
                'department' => $r['department'] ?? '',
                'priority' => $r['priority'] ?? '',
                'status' => $r['status'] ?? '',
                'assigned_to' => $r['assigned_to'] ?? '',
                'date' => $r['date_created'] ?? $r['created_at'] ?? '',
            ];
        }
    } catch (Exception $e) {
        $tickets = [];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Ticket History</title>
    <style>
      :root{ --bg:#0b1220; }
      body{ background: radial-gradient(1200px 800px at 15% 10%, rgba(37,99,235,.35), transparent 55%), radial-gradient(900px 600px at 80% 0%, rgba(29,78,216,.25), transparent 45%), var(--bg); color: rgba(255,255,255,.92); margin:0; font-family: Arial, sans-serif; }
      table{ background: rgba(255,255,255,.04); color: rgba(255,255,255,.92); border-color: rgba(255,255,255,.2); }
    </style>
</head>
<body>


<h2>Ticket History</h2>

<table border="1" cellpadding="10">
    <tr>
        <th>Issue</th>
        <th>Solution</th>
        <th>Company</th>
        <th>Department</th>
        <th>Priority</th>
        <th>Status</th>
        <th>Assigned To</th>
        <th>Date</th>
    </tr>

    <?php if (!empty($tickets)): ?>
        <?php foreach ($tickets as $row): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['issue'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['solution'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['company'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['department'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['priority'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['status'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['assigned_to'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($row['date'] ?? $row['created_at'] ?? ''); ?></td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="8">No tickets found.</td>
        </tr>
    <?php endif; ?>
</table>

</body>
</html>

