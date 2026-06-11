<?php
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Ticketing System</title>
  <style>
    .four-cards{
      display:grid;
      grid-template-columns:1fr 1fr;
      grid-auto-rows:1fr;
      gap:14px;
      padding:16px;
      border-radius:16px;
      background: rgba(255,255,255,.04);
      border:1px solid rgba(255,255,255,.10);
      backdrop-filter: blur(10px);
      box-shadow: 0 10px 30px rgba(0,0,0,.20);
      margin:0;
    }
    .stat-card{
      background: rgba(255,255,255,.03);
      border:1px solid rgba(255,255,255,.10);
      border-radius:14px;
      padding:16px;
      display:flex;
      flex-direction:column;
      justify-content:center;
      align-items:center;
      text-align:center;
    }
    .stat-title{
      margin-top:8px;
      font-weight:800;
      color: rgba(255,255,255,.78);
      font-size:13px;
    }

    :root{
      --bg: #0b1220;
      --card: rgba(255,255,255,.06);
      --card-2: rgba(255,255,255,.09);
      --text: rgba(255,255,255,.92);
      --muted: rgba(255,255,255,.70);
      --primary: #2563eb;
      --primary-2:#1d4ed8;
      --border: rgba(255,255,255,.12);
      --shadow: 0 20px 60px rgba(0,0,0,.35);
    }
    *{box-sizing:border-box;}
    body{
      margin:0;
      font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial;
      background: radial-gradient(1200px 800px at 15% 10%, rgba(37,99,235,.35), transparent 55%),
                  radial-gradient(900px 600px at 80% 0%, rgba(29,78,216,.25), transparent 45%),
                  var(--bg);
      color: var(--text);
      min-height: 100vh;
    }
    .container{max-width: clamp(1000px, 92vw, 1800px);margin:0 auto;padding:28px 18px;}
    header{
      position:sticky;top:0;z-index:10;
      backdrop-filter: blur(10px);
      background: rgba(11,18,32,.65);
      border-bottom: 1px solid var(--border);
    }
    .topbar{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;padding:14px 18px;max-width: clamp(1000px, 92vw, 1800px);margin:0 auto;}
    .brand h1{margin:0;font-size:18px;letter-spacing:.2px;}
    .brand p{margin:4px 0 0;color:var(--muted);font-size:13px;}
    .btn{
      display:inline-flex;align-items:center;justify-content:center;
      padding:10px 14px;border-radius:12px;text-decoration:none;
      font-weight:800;border:1px solid var(--border);
      background: rgba(255,255,255,.04); color: var(--text);
      transition: transform .08s ease, background .15s ease, border-color .15s ease;
    }
    .btn:hover{transform:translateY(-1px);background: rgba(255,255,255,.06);border-color: rgba(255,255,255,.18);}    
    .btn.primary{background: rgba(37,99,235,.18);border-color: rgba(37,99,235,.55);}    
    .btn.primary:hover{background: rgba(37,99,235,.28);border-color: rgba(29,78,216,.9);}    
    .hero{
      display:grid;grid-template-columns: 1.1fr .9fr;gap:18px;align-items:start;
      margin-top:18px;
    }
    @media(max-width:980px){.hero{grid-template-columns:1fr;}}

    .stat-num{
    font-size: 3rem;
    font-weight: 700;
    color: #38bdf8;
    display: block;
}

.stat-label{
    font-size: 1rem;
    opacity: 0.9;
}

    .card{
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      box-shadow: var(--shadow);
      overflow:hidden;
    }
    .card .card-body{padding:18px;}
    .card h2{margin:0 0 8px;}
    .card p{margin:0;color:var(--muted);line-height:1.55;font-size:14px;}

    .login-area{padding:18px;}
    .manual{padding:18px;}
    .section{padding:18px;}
    .section-title{margin:0 0 10px;font-size:16px;}

    ul{margin:10px 0 0; padding-left:20px; color: var(--muted);}    
    li{margin:8px 0; line-height:1.55;}

    .grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
    @media(max-width:760px){.grid-2{grid-template-columns:1fr;}}

    .pill{
      display:inline-flex;align-items:center;gap:8px;
      border:1px solid var(--border);
      background: rgba(255,255,255,.04);
      padding:8px 12px;border-radius:999px;color:var(--muted);
      font-size:12px;font-weight:700;
    }

    footer{
      margin-top:22px;
      padding:22px 0 30px;
      border-top:1px solid var(--border);
      color: var(--muted);
    }
    .footer-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;align-items:start;}
    @media(max-width:900px){.footer-grid{grid-template-columns:1fr;}}
    .orgs{display:flex;flex-wrap:wrap;gap:10px;margin-top:10px;}
    .org-tag{border:1px solid var(--border);background: rgba(255,255,255,.04);padding:8px 12px;border-radius:12px;font-weight:800;color:var(--muted);font-size:13px;}
  </style>
</head>
<body>
  <header>
    <div class="topbar">
      <div class="brand">
        <h1>Ticketing System</h1>
        <p>Secure IT support workflow</p>
      </div>
      <a class="btn" href="index.php">Home</a>
    </div>
  </header>

  <main class="container">
    <div class="hero">
      <section class="card">
        <div class="card-body manual">
          <div class="pill">✅ User Manual</div>
          <h2 style="margin-top:12px;">How to use the system</h2>
          <p style="margin-top:6px;">Follow these steps to submit and manage your tickets safely.</p>

          <div class="grid-2" style="margin-top:14px;">
            <div>
              <h3 style="margin:0 0 8px; font-size:14px;">1) Sign in</h3>
              <ul>
                <li>Click <strong>Login</strong> to access your account.</li>
                <li>Admins can manage users and tickets; regular users manage their own tickets.</li>
              </ul>
            </div>
            <div>
              <h3 style="margin:0 0 8px; font-size:14px;">2) Create a ticket</h3>
              <ul>
                <li>Open the tickets page and submit your issue.</li>
                <li>Provide clear issue details and solution updates when available.</li>
              </ul>
            </div>
            <div>
              <h3 style="margin:0 0 8px; font-size:14px;">3) Manage your ticket</h3>
              <ul>
                <li>You can edit your ticket details (admin or ticket owner scope).</li>
                <li>Only the correct users can update/delete based on authorization rules.</li>
              </ul>
            </div>
            <div>
              <h3 style="margin:0 0 8px; font-size:14px;">4) Single-device security (Admins)</h3>
              <ul>
                <li>Admins are protected with a single-device login enforcement token.</li>
                <li>This reduces unauthorized admin session reuse.</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="card-body section">
          <h3 class="section-title">Quick checks</h3>
          <ul>
            <li><strong>Regular users:</strong> cannot personalize other users or modify admin security settings.</li>
            <li><strong>Admins:</strong> can manage ticket/user scope based on default admin vs added admins.</li>
            <li><strong>Default admin:</strong> has the highest permissions for user role management.</li>
          </ul>
        </div>
        <div class="card-body">
          <div class="four-cards feature-card">
            <div class="stat-card">
              <div class="stat-num" data-target="99.9%" data-final="99%">0.0%</div>
              <div class="stat-title">Accuracy</div>
            </div>

            <div class="stat-card">
              <div class="stat-num" data-target="24" data-final="24/7">0hr</div>
              <div class="stat-title">Availability</div>
            </div>

            <div class="stat-card">
              <div class="stat-num" data-target="4" data-final="4+">0+</div>
              <div class="stat-title">Organizations</div>
            </div>

            <div class="stat-card">
              <div class="stat-num" data-target="5" data-final="5">0</div>
              <div class="stat-title">Versions</div>
            </div>
          </div>

<script>

function animateStatCounter(stat) {
    const targetRaw = String(stat.getAttribute('data-target') || '0');
    const numericText = targetRaw.replace(/[^0-9\.]/g, '');
    const target = Math.max(0, parseFloat(numericText) || 0);
    const finalText = String(stat.getAttribute('data-final') || targetRaw);
    let count = 0;
    const increment = Math.max(1, target / 50);

    function formatValue(value) {
        if (targetRaw.includes('%')) {
            return Math.ceil(value) + '%';
        }
        if (finalText.toLowerCase().includes('hr')) {
            return Math.ceil(value) + 'hr';
        }
        if (finalText.includes('+')) {
            return Math.ceil(value) + '+';
        }
        return Math.ceil(value).toString();
    }

    function finishValue() {
        return finalText;
    }

    function updateCount() {
        count += increment;
        if (count < target) {
            stat.textContent = formatValue(count);
            requestAnimationFrame(updateCount);
        } else {
            stat.textContent = finishValue();
        }
    }

    updateCount();
}

document.addEventListener('DOMContentLoaded', () => {
    const stats = document.querySelectorAll('.stat-num');
    const statsSection = document.querySelector('.four-cards');

    if (!statsSection || stats.length === 0) {
        stats.forEach(animateStatCounter);
        return;
    }

    const observer = new IntersectionObserver((entries, observerRef) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) {
                return;
            }

            stats.forEach(stat => {
                if (stat.dataset.animated === 'true') {
                    return;
                }
                stat.dataset.animated = 'true';
                animateStatCounter(stat);
            });

            observerRef.unobserve(entry.target);
        });
    }, {
        threshold: 0.35,
    });

    observer.observe(statsSection);
});
</script>
</div>

      </section>

      <aside class="card">
        <div class="card-body login-area">
          <h2 style="margin:0 0 8px;">Get started</h2>
          <p style="margin-bottom:16px;">Login to create and manage tickets.</p>
          <a class="btn primary" style="width:100%; padding:12px 14px;" href="login.php">Login</a>

          <div style="height:16px;"></div>
          <div class="pill" style="display:flex;justify-content:space-between;align-items:center;width:100%;gap:12px;padding:12px 14px;">
            <span style="display:flex;align-items:center;gap:10px;">
              <span aria-hidden="true">🔐</span>
              <span><strong>Secure Access</strong></span>
            </span>
            <span style="color:var(--muted);font-size:13px;white-space:nowrap;">Server-side authorization enforced</span>
          </div>

        </div>

        <div class="card-body section">
          <h3 class="section-title">About this system</h3>
          <p>
            This ticketing platform helps teams capture and resolve IT issues with structured fields,
            role-based access control, and scoped ticket visibility.
          </p>
          <div class="grid-2" style="margin-top:14px;">
            <div>
              <p><strong>Core features</strong></p>
              <ul>
                <li>Create tickets with priority & status</li>
                <li>Manage tickets (edit/delete with authorization)</li>
                <li>Admin user management</li>
              </ul>
            </div>
            <div>
              <p><strong>Security model</strong></p>
              <ul>
                <li>Default admin vs added admins</li>
                <li>Ticket scope by creator relationship</li>
                <li>Server-side enforcement for all sensitive actions</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="card-body section">
          <h3 class="section-title">Developer</h3>
          <p>
            Built by <strong>Masix-Tech</strong>. The system includes user management, ticket workflows,
            and role-scoped security checks.
          </p>
          <div class="grid-2" style="margin-top:14px;">
            <div>
              <h3 style="margin:0 0 8px; font-size:14px;">Contacts</h3>
              <ul>
                <li>Email: <em>masikajames289@gmail.com</em></li>
                <li>Phone: <em>+254793587867</em></li>
              </ul>
            </div>
            <div>
              <h3 style="margin:0 0 8px; font-size:14px;">Access Links</h3>
              <ul>
                <li>Portfolio: <a href="#" style="color:var(--text);">https://My-Academic-Portfolio.onrender.com</a></li>
                <li>GitHub: <a href="#" style="color:var(--text);"><github class="com">Github.com/in/jamesmasicka52-coder
                <li>LinkedIn: <a href="#" style="color:var(--text);">linkedin.com/in/masix-tech</a></li>
              </ul>
            </div>
          </div>

          <h3 style="margin:14px 0 8px; font-size:14px;">Additional features accredited to the developer</h3>
          <ul>
            <li>Admin single-device enforcement token logic</li>
            <li>Admin scoped ticket management by creator/admin relationship</li>
            <li>Admin self security management pages</li>
          </ul>
        </div>
      </aside>
    </div>

    <footer>
      <div class="footer-grid">
        <div>
          <strong style="color: rgba(255,255,255,.86);">Organizations using this system</strong>
          <div class="orgs">
            <span class="org-tag">Opal</span>
            <span class="org-tag">Insight</span>
            <span class="org-tag">Agro Orbit</span>
            <span class="org-tag">Clorox</span>
          </div>
          <strong>
          <p style="margin-top:12px;">This list is Updated by the developer</p>
        </strong>
        </div>
        <div>
          <strong style="color: rgba(255,255,255,.86);">Disclaimer</strong>
          <p style="margin-top:10px;">All permissions are enforced server-side. Keep the production admin credentials secure.</p>
        </div>
      </div>
    </footer>
  </main>
</body>
</html>

