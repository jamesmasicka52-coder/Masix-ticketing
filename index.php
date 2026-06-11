<?php
require_once __DIR__ . '/auth.php';
$user = auth_get_current_user();
$isLoggedIn = is_logged_in();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ticketing System</title>
  <style>
    :root{
      --bg: #0b1220;
      --card: rgba(255,255,255,.06);
      --card-2: rgba(255,255,255,.08);
      --text: rgba(255,255,255,.92);
      --muted: rgba(255,255,255,.70);
      --primary: #2563eb;
      --primary-2: #1d4ed8;
      --danger: #ef4444;
      --border: rgba(255,255,255,.12);
      --shadow: 0 20px 60px rgba(0,0,0,.35);
    }

    *{ box-sizing: border-box; }
    body{
      margin: 0;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, "Apple Color Emoji","Segoe UI Emoji";
      background: radial-gradient(1200px 800px at 15% 10%, rgba(37,99,235,.35), transparent 55%),
                  radial-gradient(900px 600px at 80% 0%, rgba(29,78,216,.25), transparent 45%),
                  var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    header{
      position: sticky;
      top: 0;
      z-index: 5;
      backdrop-filter: blur(10px);
      background: rgba(11,18,32,.65);
      border-bottom: 1px solid var(--border);
    }

    .container{
      max-width: clamp(1000px, 92vw, 1800px);
      margin: 0 auto;
      padding: 0 18px;
    }

    .topbar{
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 14px 0;
    }

    .brand h1{
      font-size: 18px;
      margin: 0;
      letter-spacing: .2px;
    }
    .brand p{
      margin: 4px 0 0 0;
      color: var(--muted);
      font-size: 13px;
    }

    nav{
      display: flex;
      gap: 10px;
      align-items: center;
      flex-wrap: wrap;
      justify-content: flex-end;
    }

    .btn{
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 10px 14px;
      border-radius: 10px;
      text-decoration: none;
      font-weight: 700;
      border: 1px solid var(--border);
      background: rgba(255,255,255,.04);
      color: var(--text);
      transition: transform .08s ease, background .15s ease, border-color .15s ease;
    }
    .btn:hover{ transform: translateY(-1px); background: rgba(255,255,255,.06); border-color: rgba(255,255,255,.18); }

    .btn.primary{ background: rgba(37,99,235,.18); border-color: rgba(37,99,235,.45); }
    .btn.primary:hover{ background: rgba(37,99,235,.28); border-color: rgba(37,99,235,.65); }
    .btn.danger{ background: rgba(239,68,68,.14); border-color: rgba(239,68,68,.40); }

    .hero{
      padding: 26px 0 10px;
    }

    .grid{
      display: grid;
      grid-template-columns: 1.05fr .95fr;
      gap: 18px;
      align-items: start;
    }

    @media (max-width: 980px){
      .grid{ grid-template-columns: 1fr; }
    }

    .card{
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      box-shadow: var(--shadow);
      overflow: hidden;
    }

    .card .card-body{ padding: 18px; }

    .card-title{
      margin: 0;
      font-size: 16px;
      letter-spacing: .2px;
    }
    .card-subtitle{
      margin: 6px 0 0;
      color: var(--muted);
      font-size: 13px;
      line-height: 1.4;
    }

    .stat-row{
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      margin-top: 14px;
    }

    @media (max-width: 520px){
      .stat-row{ grid-template-columns: 1fr; }
    }

    .stat{
      background: rgba(255,255,255,.05);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 14px;
    }
    .stat h2{ margin: 0; font-size: 18px; }
    .stat p{ margin: 6px 0 0; color: var(--muted); font-size: 13px; }

    form{
      margin-top: 10px;
      display: grid;
      gap: 12px;
    }

    label{
      font-weight: 700;
      font-size: 13px;
      color: var(--muted);
      display: block;
      margin-bottom: 6px;
    }

    input, select, textarea{
      width: 100%;
      padding: 10px 12px;
      border-radius: 12px;
      border: 1px solid rgba(255,255,255,.14);
      background: rgba(10, 20, 40, .55);
      color: var(--text);
      outline: none;
    }
    select option{
      background: rgba(10, 20, 40, .98);
      color: rgba(255,255,255,.95);
    }
    textarea{ min-height: 96px; resize: vertical; }

    input:focus, select:focus, textarea:focus{
      border-color: rgba(37,99,235,.70);
      box-shadow: 0 0 0 4px rgba(37,99,235,.18);
    }

    .actions{
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      align-items: center;
      margin-top: 4px;
    }

    .btn-submit{
      background: var(--primary);
      border-color: rgba(37,99,235,.70);
      color: #fff;
    }
    .btn-submit:hover{ background: var(--primary-2); border-color: rgba(29,78,216,.9); }

    .list{
      margin-top: 14px;
      padding-left: 18px;
      color: var(--muted);
      line-height: 1.6;
      font-size: 14px;
    }

    footer{
      margin-top: 22px;
      padding: 20px 0 28px;
      border-top: 1px solid var(--border);
      color: var(--muted);
    }

    .footer-inner{
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }
    .pill{
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: 1px solid var(--border);
      background: rgba(255,255,255,.04);
      padding: 8px 12px;
      border-radius: 999px;
      font-size: 12px;
      color: var(--muted);
    }
  </style>
</head>
<body>
  <header>
    <div class="container topbar">
      <div class="brand">
        <h1>Ticketing System</h1>
        <p>Logged in as <strong><?php echo htmlspecialchars($user['username'] ?? ''); ?></strong></p>
      </div>

      <?php if (!$isLoggedIn): ?>
        <a class="btn btn" href="landing.php" style="border-radius:999px; width:40px; height:40px; font-weight:900; display:inline-flex; align-items:center; justify-content:center; padding:0; position:fixed; right:18px; top:14px;">✕</a>
      <?php endif; ?>

      <nav>
        <?php if (($user['role'] ?? 'user') === 'admin'): ?>
          <a class="btn primary" href="adminpanel.php">Admin</a>
        <?php endif; ?>
        <a class="btn" href="logout.php">Logout</a>
      </nav>
    </div>
  </header>

  <main class="container hero">
    <div class="grid">
      <!-- Left: Create ticket -->
      <section class="card">
        <div class="card-body">
          <h2 class="card-title">Create a New Ticket</h2>
          <p class="card-subtitle">Submit an IT issue and track progress in your dashboard.</p>
          <div class="actions" style="margin-top:14px; margin-bottom:10px;">
            <?php if (!$isLoggedIn): ?>
              <a class="btn btn-submit" href="login.php" style="padding:10px 16px;">Login to Continue</a>
              <p class="card-subtitle" style="margin:10px 0 0;">You must log in to create tickets.</p>
            <?php else: ?>
              <a class="btn btn-submit" href="tickets.php" style="padding:10px 16px;">Open Tickets Page</a>
              <div class="pill">Today's tickets: <strong><span id="todayTicketsCount">-</span></strong></div>
            <?php endif; ?>
          </div>

          <form id="ticketForm" action="tickets.php" method="POST" <?php echo $isLoggedIn ? '' : 'style="display:flex;"'; ?> >
            <div>
              <label for="issue">Issue</label>
              <input type="text" id="issue" name="issue" required />
            </div>


            <div>
              <label for="solution">Solution</label>
              <textarea id="solution" name="solution" required></textarea>
            </div>

            <div>
              <label for="company">Company</label>
              <select id="company" name="company" required>
                <option value="">Select Company</option>
                <option value="opal">Opal</option>
                <option value="Insight">Insight</option>
                <option value="Agro orbit">Agro orbit</option>
                <option value="clorox">Clorox</option>
              </select>
            </div>

            <div>
              <label for="department">Department</label>
              <select id="department" name="department" required>
                <option value="">Select Department</option>
                <option value="IT">IT</option>
                <option value="HR">HR</option>
                <option value="Finance">Finance</option>
                <option value="Insight">Insight</option>
                <option value="supply chain">supply chain</option>
                <option value="sulphonation">sulphonation</option>
                <option value="sales">sales</option>
                <option value="Engineering">Engineering</option>
                <option value="detergents">Detergents</option>
                <option value="All">All</option>
              </select>
            </div>

            <div>
              <label for="priority">Priority</label>
              <select id="priority" name="priority" required>
                <option value="">Select Priority</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
              </select>
            </div>

            <div>
              <label for="status">Status</label>
              <select id="status" name="status" required>
                <option value="">Select Status</option>
                <option value="open">Open</option>
                <option value="in progress">In Progress</option>
                <option value="closed">Closed</option>
              </select>
            </div>

            <div>
              <label for="assigned_to">Assigned To</label>
              <input type="text" id="assigned_to" name="assigned_to" required />
            </div>

            <div>
              <label for="date">Date</label>
              <input type="date" id="date" name="date" required />
            </div>

            <div class="actions">
              <button class="btn btn-submit" type="submit" name="submit">Submit Ticket</button>
            </div>
          </form>
        </div>
      </section>

      <!-- Right: Value props -->
      <aside class="card">
        <div class="card-body">
          <h2 class="card-title">Operational Overview</h2>
          <p class="card-subtitle">A modern ticket workflow built for reliability and fast resolution.</p>

          <div class="stat-row">
            <div class="stat">
              <h2>24/7</h2>
              <p>Support Availability</p>
            </div>
            <div class="stat">
              <h2>99%</h2>
              <p>Resolution Rate</p>
            </div>
            <div class="stat">
              <h2>Secure</h2>
              <p>Enterprise Protection</p>
            </div>
          </div>

          <ul class="list">
            <li><strong>Ticket Creation:</strong> Submit new tickets with priority and status.</li>
            <li><strong>Ticket Management:</strong> View and manage tickets from the dashboard.</li>
            <li><strong>Database Persistence:</strong> Tickets remain available after refresh.</li>
            <li><strong>Export/Import:</strong> Download tickets as JSON and import later.</li>
            <li><strong>Authentication:</strong> Only authenticated users can create and manage tickets.</li>
          </ul>
        </div>
      </aside>
    </div>

    <div class="card" style="margin-top:18px;">
      <div class="card-body">
        <h2 class="card-title">Key Features</h2>
        <p class="card-subtitle">Everything you need to run an organized and trackable IT support workflow.</p>
      </div>
    </div>
  </main>

  <footer>
    <div class="container footer-inner">
      <div>&copy; 2026 Ticketing System. All rights reserved.</div>
      <div>Developed by <strong>Masix-Tech</strong>.</div>
    </div>
  </footer>
</body>
</html>

