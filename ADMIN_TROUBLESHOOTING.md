# 🔧 ADMIN PANEL - TROUBLESHOOTING & FIX GUIDE

## Step 1: Run the Diagnostic
Visit this URL in your browser:
```
http://localhost/SYST/admin_diagnostic.php
```

This will check:
- ✅ Is your session active?
- ✅ Are you logged in?
- ✅ Is your account an admin?
- ✅ Does admin user exist in database?
- ✅ Is admin role set correctly?
- ✅ Can you access the admin panel?

## Step 2: Common Issues & Solutions

### Issue 1: "Role is not 'admin'"
**Problem:** Admin user exists but role is set to 'user' instead of 'admin'

**Solution:**
1. Go to admin_diagnostic.php
2. Click "🔧 Fix Admin Role" button
3. Done! Role will be updated to 'admin'

### Issue 2: "Admin user not found in database"
**Problem:** Admin user doesn't exist in the database

**Solution:**
1. Go to admin_diagnostic.php
2. Click "🔨 Create Admin User" button
3. Done! Admin user created with password: admin123

### Issue 3: "Cannot access admin_diagnostic.php"
**Problem:** You're not logged in

**Solution:**
1. Go to: http://localhost/SYST/login.php
2. Login with:
   - Username: admin
   - Password: admin123
3. Then visit admin_diagnostic.php

### Issue 4: Still can't see admin panel button
**Problem:** You're logged in but not as admin, OR the button isn't loading

**Solution:**
1. Make sure you're admin (check with diagnostic tool)
2. Clear browser cache (Ctrl+Shift+Delete)
3. Hard refresh page (Ctrl+F5)
4. Try directly: http://localhost/SYST/adminpanel.php

## Step 3: Direct Access Links

If diagnostic tool shows all green, use these links:

**Main Dashboard:**
http://localhost/SYST/index.php

**Admin Panel:**
http://localhost/SYST/adminpanel.php

**Admin Diagnostic Tool:**
http://localhost/SYST/admin_diagnostic.php

## Quick Fix Checklist

- [ ] Visit admin_diagnostic.php
- [ ] Read all check results
- [ ] Click any needed "Fix" buttons
- [ ] Refresh the page
- [ ] Check if all green now
- [ ] Visit adminpanel.php or click the button on index.php

## If Still Not Working

1. **Take a screenshot** of the error or what you see
2. **Run the diagnostic** and screenshot the results
3. **Check MySQL is running** - XAMPP control panel
4. **Check Apache is running** - XAMPP control panel
5. **Check database exists** - Open phpMyAdmin, look for "syst_ticketing"

## Contact Support

If you still can't get it working:
1. Run admin_diagnostic.php
2. Screenshot the complete output
3. Note what error messages you see
4. Share the diagnostic results
