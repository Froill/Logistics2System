# Account Lockout Feature - Quick Reference

## What Was Implemented

### 1. **Lock Status Indicator in User Table**

- **Status Column** shows whether each account is locked or active
- **Locked accounts** display:
  - Red badge with lock icon (🔒 Locked)
  - Count of failed login attempts
- **Active accounts** display:
  - Green badge with unlock icon (🔓 Active)

### 2. **Security Alert Banner**

- Appears at top of User Management when accounts are locked
- Shows count of locked accounts
- "View Details" link opens comprehensive security dashboard

### 3. **Unlock Button**

- Appears in the Actions column for locked accounts only
- Yellow button with unlock icon (🔓)
- Opens unlock confirmation modal

### 4. **Security Overview Modal**

- Accessed via "View Details" link or by unlocking from main table
- **Shows:**
  - All locked accounts with email addresses
  - Failed attempt count for each
  - Lock expiration time
  - Quick unlock button for each account
  - Security statistics:
    - Total locked accounts
    - Total failed login attempts
    - Number of blocked IPs
    - Total number of users

### 5. **Unlock Confirmation Modal**

- Requires admin confirmation before unlocking
- Displays account details:
  - Account name
  - Email address
  - Number of failed attempts
- Warning text explaining the action

## How to Use

### **To View Locked Accounts:**

1. Navigate to **Dashboard → User Management**
2. Look for red **Security Alert** banner (if accounts are locked)
3. Click **"View Details"** to open Security Overview Modal
4. See all locked accounts with their details

### **To Unlock a Single Account:**

**Method 1: From User Table**

1. Find the user in the table with 🔒 **Locked** status
2. Click the yellow **unlock button** (🔓) in Actions column
3. Review account details in confirmation modal
4. Click **"Unlock"** to confirm
5. Account status changes to 🔓 **Active**

**Method 2: From Security Overview Modal**

1. Click **"View Details"** in alert banner
2. Find the locked account in the list
3. Click **"Unlock Account"** button
4. Confirm in the popup modal
5. Account is immediately unlocked

### **To Check Failed Login Attempts:**

- In the user table Status column, locked accounts show attempt count
- In Security Overview Modal, each locked account displays full details including:
  - Number of failed attempts
  - Time when lock was applied
  - When lock will automatically expire

## Key Features

✅ **Admin-Only Access**

- Only administrators can unlock accounts
- All unlock actions are logged to audit trail

✅ **Real-Time Status**

- Lock status updates immediately in user table
- Reflects current login attempt count

✅ **Security Monitoring**

- Dashboard statistics show security overview
- Alerts for multiple locked accounts
- Track failed attempts per user

✅ **Automatic Lock Expiration**

- Accounts auto-unlock after configured duration
- Manual unlock provides immediate access
- Recommended for user support scenarios

## Configuration

The following settings control account lockout behavior:

From `includes/security_config.php`:

- `SECURITY_MAX_FAILED_ATTEMPTS` = 3 (lock after 3 failures)
- `SECURITY_LOCKOUT_DURATION_MINUTES` = 60 (1 hour auto-unlock)
- `SECURITY_IP_RATE_LIMIT_ATTEMPTS` = 20 (IP rate limit)
- `SECURITY_IP_RATE_LIMIT_WINDOW_MINUTES` = 60 (rate limit window)

## Audit Trail

All account unlocks are recorded in the audit log with:

- Timestamp of unlock
- Admin who performed unlock
- User account that was unlocked
- Action: "ACCOUNT_UNLOCKED"

View audit log to track all unlock actions for compliance.

## Troubleshooting

### Account not showing as locked?

- Verify `account_lockout.sql` has been imported
- Check that user table has these columns:
  - `account_locked`
  - `locked_until`
  - `failed_login_count`
  - `last_failed_login`

### Unlock button not appearing?

- User must have admin role to see unlock feature
- Account must have `account_locked = 1` in database

### "Unauthorized access" message?

- Verify you're logged in as admin user
- Non-admins will be redirected to dashboard

## Files Modified/Created

**Modified:**

- `modules/user_management.php` - Added lock status display and unlock UI
- `includes/security_config.php` - Configuration (cleaned of unused code)

**Created:**

- `includes/lock_unlock_handler.php` - Handles unlock requests
- `ACCOUNT_LOCKOUT_INTEGRATION.md` - Technical documentation

**Used (existing):**

- `includes/security_lockout.php` - Core security functions
- `modules/lock_management.php` - Admin management functions
- `database/account_lockout.sql` - Database schema (must be imported)
