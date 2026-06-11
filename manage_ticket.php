<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
ensure_logged_in();

$message = '';
$error = '';
$ticket_id = null;
$ticket = null;

if (isset($_GET['id'])) {
    $ticket_id = intval($_GET['id']);
}

$currentUser = auth_get_current_user();
$currentUserId = $currentUser['id'] ?? 0;
$currentIsAdmin = ($currentUser['role'] ?? 'user') === 'admin';
$isDefaultAdmin = is_default_admin($currentUser);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = intval($_POST['ticket_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($ticket_id <= 0) {
        $error = 'Invalid ticket selected.';
    } else {
        if ($action === 'update') {
            $issue = trim($_POST['issue'] ?? '');
            $solution = trim($_POST['solution'] ?? '');
            $company = trim($_POST['company'] ?? '');
            $department = trim($_POST['department'] ?? '');
            $priority = trim($_POST['priority'] ?? '');
            $status = trim($_POST['status'] ?? '');
            $assigned_to = trim($_POST['assigned_to'] ?? '');
            $date_created = trim($_POST['date_created'] ?? '');

            if ($issue === '' || $solution === '' || $company === '' || $department === '' || $priority === '' || $status === '' || $assigned_to === '' || $date_created === '') {
                $error = 'Please fill in all ticket fields before saving.';
            } else {
                try {
                    // Authorization: default admin can manage any; added admins can manage only tickets for users they added.
                    $authSql = 'UPDATE tickets SET issue_title = :issue_title, solution = :solution, company = :company, department = :department, priority = :priority, status = :status, assigned_to = :assigned_to, date_created = :date_created WHERE id = :id';

                    // base params for the update
                    $params = [
                        ':issue_title' => $issue,
                        ':solution' => $solution,
                        ':company' => $company,
                        ':department' => $department,
                        ':priority' => $priority,
                        ':status' => $status,
                        ':assigned_to' => $assigned_to,
                        ':date_created' => $date_created,
                        ':id' => $ticket_id,
                    ];

                    if ($currentIsAdmin) {
                        if (!$isDefaultAdmin) {
                            $visibleUserIds = array_merge([$currentUserId], get_added_user_ids_for_admin((int)$currentUserId));
                            $visibleUserIds = array_unique($visibleUserIds);
                            $placeholders = [];
                            foreach ($visibleUserIds as $i => $uid) {
                                $key = ':uid_' . $i;
                                $placeholders[] = $key;
                                $params[$key] = (int)$uid;
                            }
                            $authSql .= ' AND created_by IN (' . implode(',', $placeholders) . ')';
                        }
                    } else {
                        // regular users can only modify their own tickets
                        $authSql .= ' AND created_by = :created_by';
                        $params[':created_by'] = (int)$currentUserId;
                    }

                    $stmt = $pdo->prepare($authSql);
                    $stmt->execute($params);
                    if ($stmt->rowCount() > 0) {
                        $message = 'Ticket #' . $ticket_id . ' updated successfully.';
                    } else {
                        $error = 'Unauthorized or ticket not found.';
                    }
                } catch (Exception $e) {
                    $error = 'Unable to update ticket: ' . htmlspecialchars($e->getMessage());
                }
            }
        }

        if ($action === 'delete') {
            try {
                $deleteSql = 'DELETE FROM tickets WHERE id = :id';
                $params = [':id' => $ticket_id];

                if ($currentIsAdmin) {
                    if (!$isDefaultAdmin) {
                        $visibleUserIds = array_merge([$currentUserId], get_added_user_ids_for_admin((int)$currentUserId));
                        $visibleUserIds = array_unique($visibleUserIds);
                        $placeholders = [];
                        foreach ($visibleUserIds as $i => $uid) {
                            $key = ':uid_del_' . $i;
                            $placeholders[] = $key;
                            $params[$key] = (int)$uid;
                        }
                        $deleteSql .= ' AND created_by IN (' . implode(',', $placeholders) . ')';
                    }
                } else {
                    $deleteSql .= ' AND created_by = :created_by';
                    $params[':created_by'] = (int)$currentUserId;
                }

                $stmt = $pdo->prepare($deleteSql);
                $stmt->execute($params);
                if ($stmt->rowCount() > 0) {
                    header('Location: tickets.php?message=' . urlencode('Ticket #' . $ticket_id . ' deleted successfully.'));
                    exit;
                }
                $error = 'Unauthorized or ticket not found.';
            } catch (Exception $e) {
                $error = 'Unable to delete ticket: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

if ($ticket_id > 0 && isset($pdo) && $pdo) {
    try {
        $sql = 'SELECT * FROM tickets WHERE id = :id';
        $params = [':id' => $ticket_id];

        if ($currentIsAdmin) {
            if (!$isDefaultAdmin) {
                $visibleUserIds = array_merge([$currentUserId], get_added_user_ids_for_admin((int)$currentUserId));
                $visibleUserIds = array_unique($visibleUserIds);
                $placeholders = [];
                foreach ($visibleUserIds as $i => $uid) {
                    $key = ':uid_load_' . $i;
                    $placeholders[] = $key;
                    $params[$key] = (int)$uid;
                }
                $sql .= ' AND created_by IN (' . implode(',', $placeholders) . ')';
            }
        } else {
            $sql .= ' AND created_by = :created_by';
            $params[':created_by'] = (int)$currentUserId;
        }

        $sql .= ' LIMIT 1';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$ticket) {
            $error = 'Ticket not found or not in your management scope.';
        }
    } catch (Exception $e) {
        $error = 'Unable to load ticket: ' . htmlspecialchars($e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Ticket</title>
    <link rel="stylesheet" href="style.css">
    <style>
        :root{
            --bg: #0b1220;
            --primary: #2563eb;
            --primary-2: #1d4ed8;
            --text: rgba(226, 215, 215, 0.92);
            --muted: rgba(248, 245, 245, 0.7);
            --border: rgba(255,255,255,.12);
        }

        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: radial-gradient(1200px 800px at 15% 10%, rgba(37,99,235,.35), transparent 55%), radial-gradient(900px 600px at 80% 0%, rgba(29,78,216,.25), transparent 45%), var(--bg); color: var(--text); }

.manage-card { max-width: clamp(720px, 60vw, 1100px); margin: 24px auto; background: rgba(255,255,255,.06); border-radius: 12px; padding: 24px; box-shadow: 0 2px 16px rgba(0,0,0,0.08); border: 1px solid rgba(255,255,255,.12); }
        .message.success { background: rgba(16,185,129,.12); color: rgba(209,250,229,1); }
        .message.error { background: rgba(239,68,68,.12); color: rgba(254,226,226,1); }
        h1, h2 { margin-top: 0; }
        .button { display: inline-flex; align-items: center; justify-content: center; padding: 10px 16px; border-radius: 8px; border: none; cursor: pointer; text-decoration: none; font-weight: 600; }
        .button.primary { background: #2563eb; color: #fff; }
        .button.danger { background: #ef4444; color: #fff; }
        .button.secondary { background: #424346; color: #ccd0da; }
        form label { display: block; margin: 14px 0 6px; font-weight: 600; }
        form input[type="text"], form textarea, form select, form input[type="date"]{
            width: 100%;
            padding: 10px 12px;
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 8px;
            background: rgba(10, 20, 40, .55);
            color: var(--text);
        }
        form select option{
            background: rgba(10, 20, 40, .98);
            color: rgba(255,255,255,.95);
        }
        form input[type="text"]::placeholder, form textarea::placeholder{ color: rgba(255,255,255,.55); }
        form input[type="text"]:focus, form textarea:focus, form select:focus, form input[type="date"]:focus{
            outline:none;
            box-shadow: 0 0 0 4px rgba(37,99,235,.18);
            border-color: rgba(37,99,235,.70);
        }

        /* Ensure field text color matches tickets.php table text */
        form input[type="text"], form textarea, form select, form input[type="date"]{ color: rgba(255,255,255,.92); }

        /* Match tickets.php row/column button colors */
        .actions .button.primary { background: #2563eb; }
        .actions .button.primary:hover { background: #1d4ed8; }
        form textarea { min-height: 110px; resize: vertical; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
        .message { padding: 14px 16px; border-radius: 8px; margin-bottom: 18px; }
        .message.success { background: #637c70; color: #166534; }
        .message.error { background: #f8d7da; color: #842029; }
    </style>
</head>
<body>
<div class="manage-card">
    <div style="display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:18px;">
        <div>
            <h1>Manage Ticket</h1>
            <p>Use this page to edit or delete the ticket.</p>
        </div>
        <div>
            <a class="button secondary" href="tickets.php">Back to Tickets</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

<?php if ($ticket): ?>
        <form method="post">
            <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket['id']); ?>">
            <input type="hidden" name="action" value="update">

            <label for="issue">Issue</label>

            <input type="text" id="issue" name="issue" value="<?php echo htmlspecialchars($ticket['issue_title'] ?? ''); ?>" required>

            <label for="solution">Solution</label>
            <textarea id="solution" name="solution" required><?php echo htmlspecialchars($ticket['solution'] ?? ''); ?></textarea>

            <label for="company">Company</label>
            <input type="text" id="company" name="company" value="<?php echo htmlspecialchars($ticket['company'] ?? ''); ?>" required>

            <label for="department">Department</label>
            <input type="text" id="department" name="department" value="<?php echo htmlspecialchars($ticket['department'] ?? ''); ?>" required>

            <label for="assigned_to">Assigned To</label>
            <input type="text" id="assigned_to" name="assigned_to" value="<?php echo htmlspecialchars($ticket['assigned_to'] ?? ''); ?>" required>

            <label for="priority">Priority</label>
            <select id="priority" name="priority" required>
                <option value="low"<?php echo ($ticket['priority'] ?? '') === 'low' ? ' selected' : ''; ?>>Low</option>
                <option value="medium"<?php echo ($ticket['priority'] ?? '') === 'medium' ? ' selected' : ''; ?>>Medium</option>
                <option value="high"<?php echo ($ticket['priority'] ?? '') === 'high' ? ' selected' : ''; ?>>High</option>
            </select>

            <label for="status">Status</label>
            <select id="status" name="status" required>
                <option value="open"<?php echo ($ticket['status'] ?? '') === 'open' ? ' selected' : ''; ?>>Open</option>
                <option value="in progress"<?php echo ($ticket['status'] ?? '') === 'in progress' ? ' selected' : ''; ?>>In Progress</option>
                <option value="closed"<?php echo ($ticket['status'] ?? '') === 'closed' ? ' selected' : ''; ?>>Closed</option>
            </select>

            <label for="date_created">Date</label>
            <input type="date" id="date_created" name="date_created" value="<?php echo htmlspecialchars($ticket['date_created'] ?? date('Y-m-d')); ?>" required>

            <div class="actions">
                <button type="submit" class="button primary" style="background: var(--primary); border-color: rgba(37,99,235,.70); color:#fff;">Save Changes</button>
            </div>

        </form>

        <form method="post" onsubmit="return confirm('Delete ticket #<?php echo htmlspecialchars($ticket['id']); ?>?');" style="margin-top:16px;">
            <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($ticket['id']); ?>">
            <input type="hidden" name="action" value="delete">
            <button type="submit" class="button danger">Delete Ticket</button>
        </form>
    <?php else: ?>
        <p>No ticket selected.</p>
    <?php endif; ?>
</div>
</body>
</html>
