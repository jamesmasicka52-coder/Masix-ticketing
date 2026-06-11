<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Unauthorized</title>
  <style>
    :root{ --bg:#0b1220; }
    body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: radial-gradient(1200px 800px at 15% 10%, rgba(37,99,235,.35), transparent 55%), radial-gradient(900px 600px at 80% 0%, rgba(29,78,216,.25), transparent 45%), var(--bg); color: rgba(255,255,255,.92); }

    .card { max-width: clamp(680px, 60vw, 1100px); margin: 60px auto; background: #534949; border-radius: 12px; padding: 24px; box-shadow: 0 2px 16px rgba(0,0,0,0.08); }
    h1 { margin: 0 0 10px; font-size: 26px; }
    p { margin: 0 0 18px; color: #374151; line-height: 1.5; }
    .btn { display: inline-block; padding: 10px 16px; border-radius: 8px; text-decoration: none; font-weight: 700; background: #2563eb; color: #fff; }
    .btn.secondary { background: #6a6f7a; color: #111827; }
  </style>
</head>
<body>
  <div class="card">
    <h1>Admin Privileges Only</h1>
    <p>This privileges are only for the admin.</p>
    <div style="display:flex; gap:12px; flex-wrap:wrap;">
      <a class="btn secondary" href="javascript:history.back()">Back</a>
      <a class="btn" href="index.php">Go to Home</a>
    </div>
  </div>
</body>
</html>

