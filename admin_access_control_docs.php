<?php
/**
 * Admin Access Control Documentation & Test Interface
 * Shows the complete access control implementation
 */

require_once __DIR__ . '/auth.php';

$currentUser = is_logged_in() ? auth_get_current_user() : null;
$isAdmin = $currentUser && ($currentUser['role'] ?? 'user') === 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Control Documentation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: clamp(1000px, 92vw, 1800px); margin: 0 auto; }
        .header { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); margin-bottom: 30px; text-align: center; }
        .header h1 { color: #333; margin-bottom: 10px; }
        .header p { color: #666; font-size: 16px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        @media (max-width: 768px) { .grid { grid-template-columns: 1fr; } }
        .card { background: white; border-radius: 10px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .card h2 { color: #333; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
        .card h3 { color: #555; margin: 20px 0 10px 0; font-size: 16px; border-bottom: 2px solid #f0f0f0; padding-bottom: 8px; }
        .feature { padding: 12px; margin: 8px 0; border-radius: 6px; display: flex; align-items: center; gap: 10px; }
        .feature.allowed { background: #e8f5e9; color: #2e7d32; border-left: 4px solid #4caf50; }
        .feature.denied { background: #ffebee; color: #c62828; border-left: 4px solid #f44336; }
        .badge { display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .badge.admin { background: #e3f2fd; color: #1565c0; }
        .badge.user { background: #f3e5f5; color: #6a1b9a; }
        .icon { font-size: 18px; }
        .allowed .icon::before { content: "✅"; }
        .denied .icon::before { content: "❌"; }
        .current-user { background: #f5f5f5; padding: 15px; border-radius: 8px; margin-top: 15px; }
        .current-user strong { color: #333; }
        .comparison-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .comparison-table th, .comparison-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .comparison-table th { background: #f5f5f5; font-weight: 600; color: #333; }
        .comparison-table tr:hover { background: #f9f9f9; }
        .comparison-table .yes { color: #4caf50; font-weight: bold; }
        .comparison-table .no { color: #f44336; font-weight: bold; }
        .code-section { background: #f5f5f5; padding: 15px; border-radius: 6px; margin-top: 15px; font-family: monospace; font-size: 12px; overflow-x: auto; line-height: 1.5; }
        .info-box { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; border-radius: 4px; margin-top: 15px; }
        .info-box strong { color: #1565c0; }
        .warning-box { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 4px; margin-top: 15px; }
        .warning-box strong { color: #856404; }
        .footer { text-align: center; color: white; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Access Control System</h1>
            <p>Admin-Only User Management Implementation</p>
        </div>

        <div class="grid">
            <!-- Admin Capabilities -->
            <div class="card">
                <h2><span class="badge admin">ADMIN</span> Capabilities</h2>
                <div class="feature allowed">
                    <span class="icon"></span>
                    <span><strong>View User List:</strong> See all users in the system</span>
                </div>
                <div class="feature allowed">
                    <span class="icon"></span>
                    <span><strong>Create Users:</strong> Add new user accounts</span>
                </div>
                <div class="feature allowed">
                    <span class="icon"></span>
                    <span><strong>Assign Roles:</strong> Set user roles (admin/user)</span>
                </div>
                <div class="feature allowed">
                    <span class="icon"></span>
                    <span><strong>Delete Users:</strong> Remove user accounts</span>
                </div>
                <div class="feature allowed">
                    <span class="icon"></span>
                    <span><strong>Access Admin Panel:</strong> Full access to management interface</span>
                </div>
                <div class="feature allowed">
                    <span class="icon"></span>
                    <span><strong>View User Details:</strong> Email, phone, creation date</span>
                </div>

                <?php if ($currentUser && $isAdmin): ?>
                <div class="current-user">
                    <strong>ℹ️ You are logged in as:</strong><br>
                    <strong style="color: #4caf50;"><?php echo htmlspecialchars($currentUser['username']); ?></strong> <span class="badge admin">ADMIN</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Regular User Capabilities -->
            <div class="card">
                <h2><span class="badge user">REGULAR USER</span> Capabilities</h2>
                <div class="feature denied">
                    <span class="icon"></span>
                    <span><strong>View User List:</strong> Cannot access user management</span>
                </div>
                <div class="feature denied">
                    <span class="icon"></span>
                    <span><strong>Create Users:</strong> Cannot create accounts</span>
                </div>
                <div class="feature denied">
                    <span class="icon"></span>
                    <span><strong>Assign Roles:</strong> Cannot modify user roles</span>
                </div>
                <div class="feature denied">
                    <span class="icon"></span>
                    <span><strong>Delete Users:</strong> Cannot delete accounts</span>
                </div>
                <div class="feature denied">
                    <span class="icon"></span>
                    <span><strong>Access Admin Panel:</strong> Redirected to home page</span>
                </div>
                <div class="feature denied">
                    <span class="icon"></span>
                    <span><strong>View Other Users:</strong> Cannot see user information</span>
                </div>

                <?php if ($currentUser && !$isAdmin): ?>
                <div class="current-user">
                    <strong>ℹ️ You are logged in as:</strong><br>
                    <strong style="color: #6a1b9a;"><?php echo htmlspecialchars($currentUser['username']); ?></strong> <span class="badge user">USER</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Comparison Table -->
        <div class="card">
            <h2>📊 Feature Comparison</h2>
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>Feature</th>
                        <th>Admin User</th>
                        <th>Regular User</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Access Admin Panel</td>
                        <td class="yes">✓ Yes</td>
                        <td class="no">✗ No</td>
                    </tr>
                    <tr>
                        <td>View User List</td>
                        <td class="yes">✓ Yes</td>
                        <td class="no">✗ No</td>
                    </tr>
                    <tr>
                        <td>Create New Users</td>
                        <td class="yes">✓ Yes</td>
                        <td class="no">✗ No</td>
                    </tr>
                    <tr>
                        <td>Delete Users</td>
                        <td class="yes">✓ Yes</td>
                        <td class="no">✗ No</td>
                    </tr>
                    <tr>
                        <td>Assign User Roles</td>
                        <td class="yes">✓ Yes</td>
                        <td class="no">✗ No</td>
                    </tr>
                    <tr>
                        <td>View All Tickets</td>
                        <td class="yes">✓ Yes</td>
                        <td class="no">✗ Own only</td>
                    </tr>
                    <tr>
                        <td>Create Tickets</td>
                        <td class="yes">✓ Yes</td>
                        <td class="yes">✓ Yes</td>
                    </tr>
                    <tr>
                        <td>Login to System</td>
                        <td class="yes">✓ Yes</td>
                        <td class="yes">✓ Yes</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Implementation Details -->
        <div class="card">
            <h2>🔧 Implementation Details</h2>
            
            <h3>Protected Endpoints</h3>
            <div class="code-section">
// admin_panel.php - Line 7-10<br>
if (($currentUser['role'] ?? 'user') !== 'admin') {<br>
&nbsp;&nbsp;&nbsp;&nbsp;header('Location: index.php');<br>
&nbsp;&nbsp;&nbsp;&nbsp;exit;<br>
}<br>
            </div>

            <h3>Access Control Flow</h3>
            <div class="code-section">
admin_panel.php → Admin check → Access granted/denied<br>
admin_save_user.php → Admin check → User creation allowed/blocked<br>
admin_delete_user.php → Admin check → User deletion allowed/blocked
            </div>

            <h3>Role Validation</h3>
            <div class="code-section">
$currentUser = auth_get_current_user();<br>
$isAdmin = ($currentUser['role'] ?? 'user') === 'admin';
            </div>

            <div class="info-box">
                <strong>ℹ️ How it works:</strong>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>Every admin endpoint checks the user's role before processing</li>
                    <li>Non-admin users are redirected to the home page</li>
                    <li>Database stores role for each user (admin/user)</li>
                    <li>Role is retrieved from the session on each request</li>
                </ul>
            </div>

            <div class="warning-box">
                <strong>⚠️ Security Note:</strong>
                <ul style="margin-top: 10px; margin-left: 20px;">
                    <li>All admin endpoints must perform role checks</li>
                    <li>Role checks must happen BEFORE any database operations</li>
                    <li>Never trust client-side role information</li>
                    <li>Always validate on the server-side</li>
                </ul>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card">
            <h2>🔗 Quick Links</h2>
            <?php if ($isAdmin): ?>
                <p>You are logged in as an admin. Access the Admin Panel:</p>
                <p><a href="admin_panel.php" style="display: inline-block; background: #667eea; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 10px;">→ Go to Admin Panel</a></p>
            <?php else: ?>
                <p>You must be logged in as an admin to access the user management panel.</p>
                <p><a href="login.php" style="display: inline-block; background: #667eea; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 10px;">→ Login with Admin Account</a></p>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p>© 2026 Ticketing System - Admin Access Control</p>
        </div>
    </div>
</body>
</html>
