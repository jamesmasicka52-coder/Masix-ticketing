<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
ensure_logged_in();
$user = auth_get_current_user();


// NOTE: Tickets are loaded dynamically via list_tickets_filtered.php.
// Keep PHP only for auth + message/error rendering from create/manage actions.

$message = '';
$error = '';
if (isset($_GET['message'])) {
    $message = trim($_GET['message']);
}


function normalize_ticket(array $row): array {
    return [
        'ticket_id' => $row['id'] ?? '',
        'company' => $row['company'] ?? '',
        'department' => $row['department'] ?? '',
        'issue' => $row['issue_title'] ?? $row['Issue'] ?? '',
        'solution' => $row['solution'] ?? $row['Solution'] ?? '',
        'assigned_to' => $row['assigned_to'] ?? '',
        'priority' => $row['priority'] ?? '',
        'status' => $row['status'] ?? '',
        'date' => $row['date_created'] ?? $row['created_at'] ?? '',
        'created_at' => $row['created_at'] ?? '',
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $issue = trim($_POST['issue'] ?? '');
        $solution = trim($_POST['solution'] ?? '');
        $company = trim($_POST['company'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $priority = trim($_POST['priority'] ?? '');
        $status = trim($_POST['status'] ?? 'Open');
        $assigned_to = trim($_POST['assigned_to'] ?? '');
        $date_created = trim($_POST['date'] ?? date('Y-m-d'));

        if ($issue === '' || $solution === '' || $company === '' || $department === '' || $priority === '' || $status === '' || $assigned_to === '') {
            $error = 'Please complete all required fields before creating a ticket.';
        } else {
            try {
                $ticketId = db_insert_ticket([
                    'company' => $company,
                    'department' => $department,
                    'issue_title' => $issue,
                    'solution' => $solution,
                    'assigned_to' => $assigned_to,
                    'priority' => $priority,
                    'status' => $status,
                    'created_by' => (int)($user['id'] ?? 0),
                    'date_created' => $date_created,
                ]);

                if ($ticketId !== false) {
                    $successMessage = 'Ticket #' . $ticketId . ' created successfully.';
                    header('Location: tickets.php?message=' . urlencode($successMessage));
                    exit;
                } else {
                    $error = 'Unable to save the ticket. Please try again later.';
                }
            } catch (Exception $e) {
                $error = 'Ticket creation failed: ' . htmlspecialchars($e->getMessage());
            }
        }
    }

    if ($action === 'update' && isset($_POST['ticket_id'])) {
        $ticketId = intval($_POST['ticket_id']);
        $status = trim($_POST['status'] ?? '');
        $assigned_to = trim($_POST['assigned_to'] ?? '');

        if ($ticketId <= 0 || $status === '') {
            $error = 'Invalid ticket update request.';
        } else {
            if (db_update_ticket_status($ticketId, $status, $assigned_to !== '' ? $assigned_to : null)) {
                $message = 'Ticket #' . $ticketId . ' updated successfully.';
            } else {
                $error = 'Unable to update ticket #' . $ticketId . '.';
            }
        }
    }

    if ($action === 'delete' && isset($_POST['ticket_id'])) {
        $ticketId = intval($_POST['ticket_id']);
        if ($ticketId <= 0) {
            $error = 'Invalid delete request.';
        } else {
            if (db_delete_ticket($ticketId)) {
                $message = 'Ticket #' . $ticketId . ' deleted successfully.';
            } else {
                $error = 'Unable to delete ticket #' . $ticketId . '.';
            }
        }
    }
}

$tickets = [];
if (isset($pdo) && $pdo) {
    try {
        if (($user['role'] ?? 'user') === 'admin') {
            // default admin sees all tickets; added admins see only tickets of users they added
            [$where, $params] = get_visible_tickets_sql_where_clause_for_admin($user);
            $stmt = $pdo->prepare('SELECT * FROM tickets WHERE 1=1 ' . $where . ' ORDER BY id DESC');
            $stmt->execute($params);
        } else {
            $stmt = $pdo->prepare('SELECT * FROM tickets WHERE created_by = :uid ORDER BY id DESC');
            $stmt->execute([':uid' => (int)$user['id']]);
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $row) {
            $tickets[] = normalize_ticket($row);
        }

    } catch (Exception $e) {
        $tickets = [];
    }
}

$file = __DIR__ . '/tickets.json';
if (empty($tickets) && file_exists($file)) {
    $decoded = json_decode(file_get_contents($file), true);
    if (is_array($decoded)) {
        usort($decoded, function ($a, $b) {
            $da = $a['created_at'] ?? $a['date_created'] ?? $a['date_reported'] ?? '';
            $db = $b['created_at'] ?? $b['date_created'] ?? $b['date_reported'] ?? '';
            return strcmp($db, $da);
        });
        $tickets = $decoded;
    }
}

?>


<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets</title>
    <link rel="stylesheet" href="style.css">
    <style>
        :root{
            --bg: #0b1220;
            --primary: #2563eb;
            --primary-2: #1d4ed8;
            --text: rgba(255,255,255,.92);
            --muted: rgba(255,255,255,.70);
            --border: rgba(255,255,255,.12);
            --card: rgba(255,255,255,.06);
            --card2: rgba(255,255,255,.08);
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: radial-gradient(1200px 800px at 15% 10%, rgba(37,99,235,.35), transparent 55%), radial-gradient(900px 600px at 80% 0%, rgba(29,78,216,.25), transparent 45%), var(--bg);
            color: var(--text);
        }

        .tickets-wrap{ max-width: clamp(900px, 95vw, 1800px); margin: 22px auto; padding: 0 16px; }
        .tickets-card{ background: var(--card); padding: 18px 18px; border-radius: 14px; border: 1px solid rgba(255,255,255,.12); box-shadow: 0 2px 16px rgba(0,0,0,.08); }

        .tickets-head{ display:flex; justify-content:space-between; align-items:flex-start; gap:14px; flex-wrap:wrap; margin-bottom: 12px; }
        .tickets-title{ display:flex; flex-direction:column; gap:8px; }
        .tickets-title h2{ margin:0; font-size: 22px; }

        .message { padding: 12px 14px; border-radius: 10px; margin-top: 8px; }
        .message.success { background: rgba(16,185,129,.15); color: rgba(110,231,183,1); }
        .message.error { background: rgba(239,68,68,.12); color: rgba(254,226,226,1); }

        .btn{
            display:inline-flex; align-items:center; justify-content:center;
            padding: 10px 14px; border-radius: 10px;
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(255,255,255,.04);
            color: var(--text); text-decoration:none; font-weight: 700; cursor:pointer;
            white-space: nowrap;
        }
        .btn:hover{ background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.22); }
        .btn.primary{ background: rgba(37,99,235,.18); border-color: rgba(37,99,235,.65); }
        .btn.primary:hover{ background: rgba(37,99,235,.26); border-color: rgba(37,99,235,.8); }
        .btn.secondary{ background: rgba(37,99,235,.18); border-color: rgba(37,99,235,.65); }
        .btn.secondary:hover{ background: rgba(37,99,235,.26); border-color: rgba(37,99,235,.8); }
        .btn.danger{ background: rgba(239,68,68,.16); border-color: rgba(239,68,68,.55); }

        .header-actions{ display:flex; gap:10px; flex-wrap:wrap; align-items:center; }

        .filters-card{ background: var(--card2); border: 1px solid rgba(255,255,255,.12); border-radius: 14px; padding: 14px; margin-bottom: 14px; }
        .filters-grid{ display:grid; grid-template-columns: repeat(12, 1fr); gap: 12px; }
        .field{ grid-column: span 3; }
        @media(max-width: 980px){ .field{ grid-column: span 6; } }
        @media(max-width: 600px){ .field{ grid-column: span 12; } }

        label{ display:block; font-weight: 800; font-size: 12px; color: var(--muted); margin-bottom: 6px; }
        input[type="text"], input[type="date"], select{
            width:100%; padding: 10px 12px; border-radius: 10px;
            border: 1px solid rgba(255,255,255,.16);
            background: rgba(10, 20, 40, .55);
            color: var(--text); outline: none;
        }
        select option{
            background: rgba(10, 20, 40, .98);
            color: rgba(255,255,255,.95);
        }

        input:focus, select:focus{ border-color: rgba(37,99,235,.75); box-shadow: 0 0 0 4px rgba(37,99,235,.18); }

        .filters-actions{ display:flex; gap: 10px; flex-wrap:wrap; margin-top: 12px; align-items:center; }

        table { layout: fixed; width: 100%; border-collapse: collapse; color: border-radius: 12px; var(--text); }
        th, td { border: 1px solid rgba(255,255,255,.18); padding: 10px 12px; vertical-align: top; word-break: break-word; }
        thead th { background: rgba(15,23,42,.92); color: #fff; font-weight: 800; }
        tbody tr:nth-child(odd) { background: rgba(255,255,255,.02); }
        tbody tr:nth-child(even) { background: rgba(255,255,255,.03); }
        tbody tr:hover { background: rgba(255,255,255,.06); }

        .manage-link{ display:inline-flex; padding: 0px 10px; border-radius: 10px; background: rgba(37,99,235,.18); border: 1px solid rgba(37,99,235,.65); color: #eaf2ff; font-weight: 600; font-size: 12px; text-decoration:none; }
        .manage-link:hover{ background: rgba(37,99,235,.26); }

        .id-col         { width: 50px; }
        .issue-col      { width: 180px; }
        .solution-col   { width: 180px; }
        .company-col    { width: 120px; }
        .department-col { width: 140px; }
        .assigned-col   { width: 140px; }
        .priority-col   { width: 80px; }
        .status-col     { width: 100px; }
        .date-col       { width: 120px; }
        .created-col    { width: 150px; }
        .manage-col     { width: 110px; }

        .report-container{
        width:100%;
        max-width:none;
                        }
    @media print {
        .tickets-title h2 {
        display: none !important;
    }

    .tickets-title > div[style] {
        display: none !important;
    }

    .tickets-title {
        display: none !important;
    }


    @page {
        size: A4 portrait;
        margin: 8mm;
    }

    html,
    body {
        width: 100%;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
    }

    .tickets-wrap,
    .tickets-card,
    .print-only-container,
    .printable-ticket-table {
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        border: none !important;
        box-shadow: none !important;
        background: #fff !important;
    }

    .filters-card,
    .header-actions,
    .table-shell,
    .message {
        display: none !important;
    }

    .print-only-container {
        display: block !important;
    }

    #printableTicketsTable {
        display: table !important;
        #printableTicketsTable th:nth-child(1),
#printableTicketsTable td:nth-child(1){
    width:4%;
}

#printableTicketsTable th:nth-child(2),
#printableTicketsTable td:nth-child(2){
    width:18%;
}

#printableTicketsTable th:nth-child(3),
#printableTicketsTable td:nth-child(3){
    width:18%;
}

#printableTicketsTable th:nth-child(4),
#printableTicketsTable td:nth-child(4){
    width:10%;
}

#printableTicketsTable th:nth-child(5),
#printableTicketsTable td:nth-child(5){
    width:12%;
}

#printableTicketsTable th:nth-child(6),
#printableTicketsTable td:nth-child(6){
    width:14%;
}

#printableTicketsTable th:nth-child(7),
#printableTicketsTable td:nth-child(7){
    width:7%;
}

#printableTicketsTable th:nth-child(8),
#printableTicketsTable td:nth-child(8){
    width:7%;
}

#printableTicketsTable th:nth-child(9),
#printableTicketsTable td:nth-child(9){
    width:10%;
}

#printableTicketsTable th:nth-child(10),
#printableTicketsTable td:nth-child(10){
    width:10%;
}
        visibility: visible !important;
        width: 100% !important;
        table-layout: fixed;
        border-collapse: collapse;
        font-size: 10pt;
    }

    #printableTicketsTable th,
    #printableTicketsTable td {
        border: 1px solid #000;
        padding: 6px;
        word-break: break-word;
        text-align: left;
    }

    .print-only-container * {
        color: #000 !important;
    }
}
        /* Slight spacing for print wrapper */
        .printable-ticket-table{ margin-top: 12px; }

        .skeleton { opacity: .7; }
    </style>
</head>
<body>
<div class="tickets-wrap">
    <div class="tickets-card">
        <div class="tickets-head">
            <div class="tickets-title">
                <h2>Tickets</h2>
                <?php if ($message): ?>
                    <div class="message success"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="message error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <div style="color: var(--muted); font-size: 13px; margin-top: 2px;">
                    Filter by date, priority, status, and more. Download CSV or print a clean table form.
                </div>
            </div>
            <div class="header-actions">
                <a class="btn secondary" href="index.php">Home/New Ticket</a>
                <button type="button" class="btn" onclick="history.back()" aria-label="Go back">✕</button>
            </div>
        </div>

        <div class="filters-card screen-only" id="filtersCard">
            <form id="filtersForm" onsubmit="return false;">
                <div class="filters-grid">
                    <div class="field">
                        <label for="date_from">Date From</label>
                        <input type="date" id="date_from" name="date_from">
                    </div>
                    <div class="field">
                        <label for="date_to">Date To</label>
                        <input type="date" id="date_to" name="date_to">
                    </div>
                    <div class="field">
                        <label for="search">Search</label>
                        <input type="text" id="search" name="search" placeholder="Issue, company, assignee...">
                    </div>
                    <div class="field">
                        <label for="priority">Priority</label>
                        <select id="priority" name="priority">
                            <option value="">Any</option>
                            <option value="low">Low</option>
                            <option value="medium">Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="">Any</option>
                            <option value="open">Open</option>
                            <option value="in progress">In Progress</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="field">
                        <label for="company">Company</label>
                        <input type="text" id="company" name="company" placeholder="e.g. Opal">
                    </div>
                    <div class="field">
                        <label for="department">Department</label>
                        <input type="text" id="department" name="department" placeholder="e.g. IT">
                    </div>
                    <div class="field">
                        <label for="assigned_to">Assigned To</label>
                        <input type="text" id="assigned_to" name="assigned_to" placeholder="Assignee name">
                    </div>
                </div>

                <div class="filters-actions">
                    <button type="button" class="btn primary" onclick="applyFilters()">Apply Filters</button>
                    <button type="button" class="btn" onclick="clearFilters()">Clear</button>
                    <div style="flex:1;"></div>
                    <button type="button" class="btn" onclick="openCsvImport()">Import CSV</button>
                    <button type="button" class="btn" onclick="downloadCsv()">Download CSV</button>
                    <button type="button" class="btn" onclick="printTickets()">Print</button>
                    <input type="file" id="ticketImportFile" accept=".csv" style="display:none;" onchange="importCsvFile(event)">
                    <span id="importStatus" style="font-size:0.9rem;color: rgba(255,255,255,.70); margin-left:16px; white-space:nowrap;"></span>
                </div>
            </form>
        </div>

        <!-- Single table element (shown on screen). -->
        <div class="table-shell">
            <table id="ticketsTable">


                <thead>
                    <tr>
                        <th class="id-col">ID</th>
                        <th>Issue</th>
                        <th>Solution</th>
                        <th>Company</th>
                        <th>Department</th>
                        <th>Assigned To</th>
                        <th>Priority</th>
                        <th class="manage-col">Status</th>
                        <th>Date</th>
                        <th>Created At</th>
                        <th class="manage-col">Manage</th>
                    </tr>
                </thead>
                <tbody></tbody>

            </table>
        </div>

        <!-- Print-only wrapper (hidden; used only for print) -->
        <div class="print-only-container" style="display:none;">
            <div class="printable-ticket-table">
                <div style="font-weight: 900; color:#000; font-size: 20pt; text-align: center; margin-bottom: 15px;"><strong>TICKETS-REPORT</strong></div>
                <table id="printableTicketsTable" style="display:none;">
                    <thead>

                    <tr>
                            <th class="id-col">ID</th>
                            <th>Issue</th>
                            <th>Solution</th>
                            <th>Company</th>
                            <th>Department</th>
                            <th>Assigned To</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <style>
            /* Hide manage column everywhere print/download should be table-only */
            @media print {
                /* printable table already has no Manage column, but keep it safe */
                #ticketsTableContainer a.manage-link{ display:none !important; }
            }
        </style>
    </div>
</div>

<script>
    let currentTickets = [];

    function getFilters(){
        const f = {
            date_from: document.getElementById('date_from').value,
            date_to: document.getElementById('date_to').value,
            search: document.getElementById('search').value,
            priority: document.getElementById('priority').value,
            status: document.getElementById('status').value,
            company: document.getElementById('company').value,
            department: document.getElementById('department').value,
            assigned_to: document.getElementById('assigned_to').value,
        };
        // strip empty
        Object.keys(f).forEach(k => { if (String(f[k]).trim() === '') delete f[k]; });
        return f;
    }

    function buildQuery(filters){
        const usp = new URLSearchParams();
        Object.keys(filters).forEach(k => usp.set(k, filters[k]));
        return usp.toString();
    }

    async function fetchFilteredTickets(){
        const filters = getFilters();
        const qs = buildQuery(filters);
        const url = 'list_tickets_filtered.php' + (qs ? ('?' + qs) : '');

        const tbody = document.getElementById('ticketsTable').querySelector('tbody');
        if (tbody) tbody.innerHTML = '<tr><td colspan="11" style="opacity:.7;">Loading...</td></tr>';


        const res = await fetch(url, { cache: 'no-store' });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Failed to load tickets');

        currentTickets = Array.isArray(data.tickets) ? data.tickets : [];
        renderTickets(currentTickets);
    }

    function renderTickets(tickets){
        const tableShell = document.querySelector('.table-shell');
        const table = document.getElementById('ticketsTable');
        const tbody = table.querySelector('tbody');
        tbody.innerHTML = '';

        // Fill single table with results (hidden until print)
        tickets.forEach(t => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${escapeHtml(t.ticket_id || '')}</td>
                <td>${escapeHtml(t.issue || '')}</td>
                <td>${escapeHtml(t.solution || '')}</td>
                <td>${escapeHtml(t.company || '')}</td>
                <td>${escapeHtml(t.department || '')}</td>
                <td>${escapeHtml(t.assigned_to || '')}</td>
                <td>${escapeHtml(t.priority || '')}</td>
                <td>${escapeHtml(t.status || '')}</td>
                <td>${escapeHtml(t.date || '')}</td>
                <td>${escapeHtml(t.created_at || '')}</td>
                <td><a class="manage-link" href="manage_ticket.php?id=${encodeURIComponent(t.ticket_id || '')}">Manage</a></td>
            `;
            tbody.appendChild(tr);
        });

        // Show table on screen
        if (tableShell) tableShell.style.display = 'block';


        // Populate print-only table so print preview contains ticket rows.
        fillPrintable(tickets);

    }


function fillPrintable(tickets){
        // Print-only: populate the printable table body.
        const tbody = document.getElementById('printableTicketsTable').querySelector('tbody');
        tbody.innerHTML = '';

        // Ensure we also show the printable wrapper when using print.
        // (This does not affect on-screen table rendering.)
        const wrapper = document.querySelector('.print-only-container');
        if (wrapper) wrapper.style.display = 'none';



        // Print-only: do not output filter meta or extra UI elements.


        tickets.forEach(t => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${escapeHtml(t.ticket_id || '')}</td>
                <td>${escapeHtml(t.issue || '')}</td>
                <td>${escapeHtml(t.solution || '')}</td>
                <td>${escapeHtml(t.company || '')}</td>
                <td>${escapeHtml(t.department || '')}</td>
                <td>${escapeHtml(t.assigned_to || '')}</td>
                <td>${escapeHtml(t.priority || '')}</td>
                <td>${escapeHtml(t.status || '')}</td>
                <td>${escapeHtml(t.date || '')}</td>
                <td>${escapeHtml(t.created_at || '')}</td>
            `;
            tbody.appendChild(tr);
        });

        // ensure wrapper becomes visible for print
        // Keep print wrapper hidden unless user explicitly prints.
        // wrap.style.display = 'block';
    }

    function escapeHtml(str){
        return String(str)
            .replaceAll('&','&amp;')
            .replaceAll('<','&lt;')
            .replaceAll('>','&gt;')
            .replaceAll('"','&quot;')
            .replaceAll("'",'&#039;');
    }

    async function applyFilters(){
        await fetchFilteredTickets();
    }

    function clearFilters(){
        ['date_from','date_to','search','priority','status','company','department','assigned_to'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        fetchFilteredTickets().catch(() => {
            const tbody = document.getElementById('ticketsTable').querySelector('tbody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="11" style="color: var(--muted);">Failed to load tickets.</td></tr>';
        });

    }

    function openCsvImport(){
        const input = document.getElementById('ticketImportFile');
        if (input) {
            input.value = '';
            input.click();
        }
    }

    function setImportStatus(message, isError = false){
        const status = document.getElementById('importStatus');
        if (!status) return;
        status.textContent = message;
        status.style.color = isError ? 'rgba(239,68,68,1)' : 'rgba(110,231,183,1)';
    }

    async function importCsvFile(event){
        const file = event.target.files?.[0];
        if (!file) {
            setImportStatus('No file selected.', true);
            return;
        }

        setImportStatus('Importing tickets…');
        const formData = new FormData();
        formData.append('import_csv', file);

        try {
            const response = await fetch('import_tickets_csv.php', {
                method: 'POST',
                body: formData,
            });

            const data = await response.json();
            if (!response.ok || !data.ok) {
                setImportStatus('Import failed: ' + (data.error || 'Unexpected response'), true);
                return;
            }

            setImportStatus(data.message || `Imported ${data.inserted} ticket(s).`);
            await fetchFilteredTickets();
        } catch (error) {
            setImportStatus('Import failed: ' + (error.message || 'Network error'), true);
        }
    }

    function downloadCsv(){
        const filters = getFilters();
        const qs = buildQuery(filters);
        const url = 'download_tickets_csv.php' + (qs ? ('?' + qs) : '');
        window.location.href = url;
    }

    async function printTickets(){
        // Ensure printable table is up to date
        await fetchFilteredTickets();
        window.print();
    }

    document.addEventListener('DOMContentLoaded', () => {
        // Render initial server-side data immediately.
        const initialTickets = <?php echo json_encode($tickets); ?>;
        renderTickets(initialTickets || []);

        // Then force a DB refresh so newly created tickets appear without needing a hard refresh.
        // (Also catches cases where $tickets JSON is empty/stale.)
        fetchFilteredTickets().catch(() => {
            // If AJAX fails, keep the server-rendered rows.
        });
    });
</script>

</body>
</html>


