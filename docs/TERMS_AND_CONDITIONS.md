# Terms and Conditions Implementation

## Overview

This feature implements a Terms and Conditions (T&C) page that users must accept after logging in or entering the correct OTP for the first time. The implementation includes role-specific content, a mandatory checkbox, and a disabled I Agree button until the checkbox is checked.

## Files Modified/Created

### 1. Database Migration

**File:** `database/add_tnc_column.sql`

- Adds two new columns to the `users` table:
  - `t_and_c_accepted` (TINYINT, default 0): Tracks whether user has accepted T&C
  - `t_and_c_accepted_at` (DATETIME, nullable): Records timestamp of acceptance

**To Apply:**

```bash
# Run in phpMyAdmin or MySQL CLI
mysql -u root -p logistics2_db < database/add_tnc_column.sql
```

### 2. New Terms and Conditions Page

**File:** `terms-and-conditions.php`

- Displays role-specific T&C content
- Shows user's full name at the top
- Scrollable content area for long terms
- Mandatory checkbox: "I have read and fully understand..."
- Two buttons:
  - "Decline & Logout" (always enabled)
  - "I Agree & Continue" (disabled until checkbox is checked)
- Redirects unauthorized users back to login
- Redirects users who already accepted T&C to dashboard

**Features:**

- Beautiful gradient design with DaisyUI styling
- Responsive layout (mobile-friendly)
- Role-specific content for different user types

### 3. T&C Acceptance Handler

**File:** `includes/handle_tnc_acceptance.php`

- Processes form submission from T&C page
- Validates that user is authenticated
- Verifies checkbox was checked
- Updates database with acceptance timestamp
- Sets session variable `t_and_c_accepted = 1`
- Logs the acceptance in audit trail
- Redirects to dashboard on success

### 4. Modified Authentication Files

#### `includes/validate_login.php`

**Changes:**

- Added `t_and_c_accepted` field to user SELECT query
- Set `$_SESSION['t_and_c_accepted']` from database
- For trusted device login, redirects to T&C page if not yet accepted before going to dashboard

#### `includes/validate_otp.php`

**Changes:**

- Fetches `t_and_c_accepted` status from database after successful OTP validation
- Sets `$_SESSION['t_and_c_accepted']` for the session
- Redirects to T&C page if not yet accepted instead of directly to dashboard

### 5. Modified Dashboard

**File:** `dashboard.php`
**Changes:**

- Added security check after session start
- Redirects users to T&C page if they haven't accepted yet
- Prevents access to dashboard and all modules without T&C acceptance

## Authentication Flow

### Standard Login Flow

```
1. User enters credentials in login.php
2. validate_login.php validates credentials
3. OTP is generated and sent via email
4. User enters OTP in verify-otp.php
5. validate_otp.php verifies OTP
6. If T&C not accepted:
   → Redirect to terms-and-conditions.php
7. If T&C accepted:
   → Redirect to dashboard.php
```

### Trusted Device Flow

```
1. User has valid device token cookie
2. validate_login.php verifies device fingerprint
3. If T&C not accepted:
   → Redirect to terms-and-conditions.php
4. If T&C accepted:
   → Redirect to dashboard.php
```

## Role-Specific Content

The T&C page displays different content based on user role:

- **Admin**: Administrative responsibilities, data protection, system maintenance
- **Superadmin**: Complete system control, security, architecture, compliance
- **Manager**: Managerial responsibilities, resource management, accountability
- **Supervisor**: Supervisory duties, safety, record keeping
- **Driver**: Driver responsibilities, vehicle usage, safety compliance
- **Staff**: Employment terms, company resources, code of conduct
- **Requester**: Service usage, request management, data confidentiality
- **Default**: General terms for any other roles

## Database Schema

```sql
ALTER TABLE users ADD COLUMN t_and_c_accepted TINYINT(1) DEFAULT 0;
ALTER TABLE users ADD COLUMN t_and_c_accepted_at DATETIME DEFAULT NULL;
```

### User Table Changes

- Existing users have `t_and_c_accepted = 0` by default
- When user accepts T&C:
  - `t_and_c_accepted` set to 1
  - `t_and_c_accepted_at` set to current timestamp

## User Interface

### Terms and Conditions Page

- **Header**: Shows role-specific title and greeting
- **Content Area**: Scrollable section with T&C text
- **Checkbox**: Required to proceed
- **Buttons**:
  - Decline button (redirects to logout)
  - I Agree button (disabled until checkbox is checked)

### Visual Feedback

- Checkbox is styled with DaisyUI checkbox component
- "I Agree" button is disabled with visual indication
- Button becomes enabled with full color once checkbox is checked
- Form prevents submission if checkbox is unchecked

## Frontend Logic

The T&C page includes client-side validation:

```javascript
// Enable/disable button based on checkbox
checkbox.addEventListener('change', function () {
  agreeBtn.disabled = !this.checked;
  if (this.checked) {
    agreeBtn.classList.remove('disabled');
  } else {
    agreeBtn.classList.add('disabled');
  }
});

// Prevent form submission if not checked
form.addEventListener('submit', function (e) {
  if (!checkbox.checked) {
    e.preventDefault();
    alert('Please check the agreement checkbox to continue.');
  }
});
```

## Backend Validation

The handler validates:

1. User is authenticated (SESSION['user_id'] exists)
2. Request is POST method
3. Checkbox was checked and submitted
4. Database update succeeds
5. All errors are logged appropriately

## Audit Logging

All T&C acceptances are logged in the audit trail:

- **Event Type**: Authentication
- **Action**: T&C Acceptance
- **User**: User's full name
- **Details**: "User accepted Terms and Conditions"
- **Timestamp**: Recorded when accepted

## Session Management

After T&C acceptance:

```php
$_SESSION['t_and_c_accepted'] = 1;
```

This variable persists for the entire session and is checked:

- When accessing dashboard
- When accessing any module
- Can be used for future access control

## Security Considerations

1. **Server-side Validation**: Always validates checkbox on server (client-side can be bypassed)
2. **Session Required**: Must have valid user session to accept T&C
3. **Database Record**: Persistent record of acceptance with timestamp
4. **Audit Trail**: All acceptances logged for compliance
5. **Logout Option**: Users can decline and logout at any time

## Testing Checklist

- [ ] Database migration applied successfully
- [ ] New user can see T&C page after login/OTP
- [ ] Checkbox is required to proceed
- [ ] "I Agree" button disabled when checkbox unchecked
- [ ] "I Agree" button enabled when checkbox checked
- [ ] Clicking "I Agree" redirects to dashboard
- [ ] Clicking "Decline" redirects to logout
- [ ] Audit log records acceptance
- [ ] Returning user doesn't see T&C page again
- [ ] Role-specific content displays correctly
- [ ] User's name displays on T&C page
- [ ] Page is responsive on mobile devices
- [ ] Trusted device login also checks T&C
- [ ] OTP verification flow checks T&C

## Important Notes

1. Existing users will have `t_and_c_accepted = 0` after database migration
2. They will see the T&C page on their next login
3. The T&C page checks if already accepted and redirects to dashboard if true
4. The dashboard redirects to T&C if not yet accepted
5. All redirects prevent direct dashboard access without T&C acceptance

## Future Enhancements

Potential improvements:

- Version tracking for T&C updates
- Different T&C versions per role
- Re-acceptance on T&C updates
- Admin dashboard to track T&C acceptance statistics
- Email confirmation of T&C acceptance
- Scheduled re-acceptance requirements
