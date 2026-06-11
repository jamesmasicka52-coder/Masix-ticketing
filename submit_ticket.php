<?php
require_once __DIR__ . '/auth.php';
ensure_logged_in();

$message = '';
$error = '';
$ticket = [
    'ticket_id' => '',
    'issue' => '',
    'solution' => '',
    'company' => '',
    'department' => '',
    'priority' => '',
    'status' => '',
    'assigned_to' => '',
    'date' => date('Y-m-d'),
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ticket['issue'] = trim($_POST['issue'] ?? '');
    $ticket['solution'] = trim($_POST['solution'] ?? '');
    $ticket['company'] = trim($_POST['company'] ?? '');
    $ticket['department'] = trim($_POST['department'] ?? '');
    $ticket['priority'] = trim($_POST['priority'] ?? '');
    $ticket['status'] = trim($_POST['status'] ?? 'open');
    $ticket['assigned_to'] = trim($_POST['assigned_to'] ?? '');
    $ticket['date'] = trim($_POST['date'] ?? date('Y-m-d'));

    if ($ticket['issue'] === '' || $ticket['solution'] === '' || $ticket['company'] === '' || $ticket['department'] === '' || $ticket['priority'] === '' || $ticket['status'] === '' || $ticket['assigned_to'] === '' || $ticket['date'] === '') {
        $error = 'Please complete all required fields.';
    } else {
        try {
            $insertedId = db_insert_ticket([
                'issue_title' => $ticket['issue'],
                'solution' => $ticket['solution'],
                'company' => $ticket['company'],
                'department' => $ticket['department'],
                'priority' => $ticket['priority'],
                'status' => $ticket['status'],
                'assigned_to' => $ticket['assigned_to'],
                'date_created' => $ticket['date'],
            ]);

            if ($insertedId !== false && $insertedId > 0) {
                $ticket['ticket_id'] = $insertedId;
                $message = 'Ticket #' . $insertedId . ' submitted successfully.';
            } else {
                $error = 'Unable to save the ticket to the database.';
            }
        } catch (Exception $e) {
            // DB insert failed: do NOT claim success
            $error = 'Ticket creation failed: ' . htmlspecialchars($e->getMessage());
        }

    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Ticket Submitted</title>
    <link rel="stylesheet" href="style.css">

    <script src="userPanelBackground.js"></script>
</head>
<body>


<div class="success-card">

    <div class="success-icon">✓</div>

    <h1>Ticket Submitted Successfully</h1>

    <p>Your ticket has been recorded in the system.</p>

    <div class="ticket-summary">
        <p><strong>Ticket ID:</strong> #<?php echo $ticket['ticket_id']; ?></p>
        <p><strong>Issue:</strong> <?php echo $ticket['issue']; ?></p>
        <p><strong>Priority:</strong> <?php echo $ticket['priority']; ?></p>
        <p><strong>Status:</strong> <?php echo $ticket['status']; ?></p>
    </div>

<div class="action-buttons">
        <a href="history.php" class="main-btn">View Tickets</a>
        <a href="index.php" class="secondary-btn">Dashboard</a>
    </div>

</div>

</body>
</html>

