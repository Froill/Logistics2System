<?php
// USER PROFILE MODULE
// Purpose: Show the signed-in user's profile, and allow safe updates to email and password.
// Security: Requires active session; uses CSRF tokens; all updates require the current password.
// Performance: Fetch only needed columns; use a LEFT JOIN for optional driver license.

require_once __DIR__ . '/../includes/db.php';

/**
 * Generate (or reuse) a CSRF token for this session.
 */
function profile_get_csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        // 32 bytes -> 64 hex chars; cryptographically secure
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from POST.
 */
function profile_verify_csrf_token(): bool
{
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])) return false;
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Safely get the first letter to display for the avatar.
 */
function profile_initial(string $fullName = null, string $email = null): string
{
    $candidate = trim($fullName ?: '');
    if ($candidate !== '') {
        // Multibyte safe fallback; for strict MB use mb_substr if extension is enabled.
        return strtoupper(substr($candidate, 0, 1));
    }
    if (!empty($email)) {
        return strtoupper(substr($email, 0, 1));
    }
    return '?';
}

/**
 * Logic handler:
 * - Access control
 * - Handle POST actions for updating email/password
 * - Fetch current user data to render the view
 * - Set flash messages on $_SESSION and shadow them into $GLOBALS for the view
 */
function profile_logic($baseURL)
{
    // Access control: must be logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }

    // Prepare flash messages (success/error)
    $GLOBALS['profile_success'] = $_SESSION['profile_success'] ?? '';
    $GLOBALS['profile_error']   = $_SESSION['profile_error'] ?? '';
    unset($_SESSION['profile_success'], $_SESSION['profile_error']);

    // Shortcuts
    $userId = (int)$_SESSION['user_id'];

    // Handle POST submissions (email/password updates)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            if (!profile_verify_csrf_token()) {
                throw new Exception('Security check failed. Please refresh the page and try again.');
            }

            // We require the current password for both operations
            $currentPassword = $_POST['current_password'] ?? '';

            if ($currentPassword === '') {
                throw new Exception('Current password is required.');
            }

            // Fetch the current password hash once (performance optimization)
            global $conn;
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();

            if (!$row || empty($row['password'])) {
                throw new Exception('Account not found.');
            }

            if (!password_verify($currentPassword, $row['password'])) {
                // Timing-safe password check via password_verify
                throw new Exception('Current password is incorrect.');
            }

            // Decide which action to process
            $action = $_POST['action'] ?? '';

            if ($action === 'update_email') {
                // Update Email Flow
                $newEmail = trim($_POST['new_email'] ?? '');
                if ($newEmail === '') {
                    throw new Exception('New email is required.');
                }
                if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Please provide a valid email address.');
                }

                // Ensure email is unique (edge case: unchanged same email should be allowed)
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1");
                $stmt->bind_param("si", $newEmail, $userId);
                $stmt->execute();
                $exists = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($exists) {
                    throw new Exception('This email is already in use by another account.');
                }

                // Proceed with update
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->bind_param("si", $newEmail, $userId);
                $stmt->execute();
                $stmt->close();

                // Optional: Refresh session email if you store it there
                $_SESSION['email'] = $newEmail;

                $_SESSION['profile_success'] = 'Email updated successfully.';
                header("Location: {$baseURL}");
                exit;
            } elseif ($action === 'update_password') {
                // Update Password Flow
                $newPassword     = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if ($newPassword === '' || $confirmPassword === '') {
                    throw new Exception('Both new password fields are required.');
                }
                if ($newPassword !== $confirmPassword) {
                    throw new Exception('New password and confirmation do not match.');
                }
                // Basic password policy (adjust as needed)
                if (strlen($newPassword) < 8) {
                    throw new Exception('New password must be at least 8 characters.');
                }

                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);

                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $newHash, $userId);
                $stmt->execute();
                $stmt->close();

                $_SESSION['profile_success'] = 'Password updated successfully.';
                header("Location: {$baseURL}");
                exit;
            } else {
                throw new Exception('Invalid action.');
            }
        } catch (Exception $e) {
            // Capture error and redirect to avoid resubmission on refresh
            $_SESSION['profile_error'] = $e->getMessage();
            header("Location: {$baseURL}");
            exit;
        }
    }

    // Fetch the current user record for display (fetch only the columns we need)
    global $conn;
    $stmt = $conn->prepare("
        SELECT 
            u.id,
            u.eid,
            u.full_name,
            u.email,
            u.role,
            u.created_at,
            d.license_number
        FROM users u
        LEFT JOIN drivers d ON d.eid = u.eid
        WHERE u.id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $GLOBALS['profile_user'] = $result->fetch_assoc() ?: [];
    $stmt->close();

    // CSRF token for forms
    $GLOBALS['profile_csrf'] = profile_get_csrf_token();
}

/**
 * View renderer: shows the profile and provides forms to update email/password.
 */
function profile_view($baseURL)
{
    $user    = $GLOBALS['profile_user'] ?? [];
    $success = $GLOBALS['profile_success'] ?? '';
    $error   = $GLOBALS['profile_error'] ?? '';
    $csrf    = $GLOBALS['profile_csrf'] ?? '';

    // Compute initial for avatar
    $initial = profile_initial($user['full_name'] ?? '', $user['email'] ?? '');
?>
    <div class="w-full mx-auto">
        <h2 class="text-xl md:text-2xl font-bold mb-4">My Profile</h2>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success mb-4"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Profile Card -->
        <div class="card bg-base-100 shadow mb-6">
            <div class="card-body">
                <!-- Avatar and Name -->
                <div class="flex items-center gap-4">
                    <!-- First letter avatar -->
                    <div class="w-14 h-14 rounded-full bg-gradient-to-tr from-blue-600 via-blue-500 to-blue-400 text-white flex items-center justify-center text-2xl font-semibold"
                        aria-label="User initial">
                        <?= htmlspecialchars($initial) ?>
                    </div>
                    <div>
                        <p class="text-lg font-semibold">
                            <?= htmlspecialchars($user['full_name'] ?? '') ?>
                        </p>
                        <p class="text-sm text-gray-500">
                            <?= htmlspecialchars(ucfirst($user['role'] ?? '')) ?>
                        </p>
                    </div>
                </div>

                <!-- Info Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div class="p-3 rounded border border-base-300">
                        <p class="text-xs text-gray-500">Employee ID</p>
                        <p class="font-medium"><?= htmlspecialchars($user['eid'] ?? '') ?></p>
                    </div>
                    <div class="p-3 rounded border border-base-300">
                        <p class="text-xs text-gray-500">Email</p>
                        <p class="font-medium break-all"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                    </div>
                    <div class="p-3 rounded border border-base-300">
                        <p class="text-xs text-gray-500">Created</p>
                        <p class="font-medium">
                            <?= htmlspecialchars($user['created_at'] ?? '') ?>
                        </p>
                    </div>
                    <?php if (($user['role'] ?? '') === 'driver'): ?>
                        <div class="p-3 rounded border border-base-300">
                            <p class="text-xs text-gray-500">License Number</p>
                            <p class="font-medium">
                                <?= htmlspecialchars($user['license_number'] ?? 'N/A') ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="divider">
                    <button onclick="updateEmailModal.showModal()" class="btn btn-primary">Change Email</button>
                    <button onclick="updatePasswordModal.showModal()" class="btn btn-info">Change Password</button>
                </div>


            </div>
        </div>

        <!-- Change Email Modal -->
        <dialog id="updateEmailModal" class="modal">
            <form method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="modal-box space-y-3">
                <!-- CSRF token -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="update_email" />

                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="mail" class="size-5"></i>
                    <h3 class="font-semibold text-lg">Change Email</h3>
                </div>

                <div class="mt-3">
                    <label class="label"><span class="label-text">New Email</span></label>
                    <input type="email" name="new_email" class="input input-bordered w-full" required />
                </div>

                <div class="mt-3">
                    <label class="label"><span class="label-text">Current Password</span></label>
                    <input type="password" name="current_password" class="input input-bordered w-full" required />
                </div>

                <div class="modal-action">
                    <button type="submit" class="btn btn-primary">Update Email</button>
                    <button type="button" class="btn" onclick="updateEmailModal.close()">Cancel</button>
                </div>
            </form>
        </dialog>


        <!-- Change Password Modal -->
        <dialog id="updatePasswordModal" class="modal">
            <form id="updatePasswordForm" method="POST" action="<?= htmlspecialchars($baseURL) ?>" class="modal-box space-y-3" autocomplete="off">
                <!-- CSRF token -->
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="action" value="update_password" />

                <div class="flex items-center gap-2 mb-2">
                    <i data-lucide="key-round" class="size-5"></i>
                    <h3 class="font-semibold text-lg">Change Password</h3>
                </div>

                <!-- Error message (hidden by default) -->
                <div id="passwordError" class="alert alert-error hidden text-sm"></div>

                <div class="mt-3">
                    <label class="label"><span class="label-text">Current Password</span></label>
                    <input type="password" name="current_password" id="current_password" class="input input-bordered w-full" required />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="mt-3">
                        <label class="label"><span class="label-text">New Password</span></label>
                        <input type="password" name="new_password" id="new_password" class="input input-bordered w-full" required />
                    </div>
                    <div class="mt-3">
                        <label class="label"><span class="label-text">Confirm New Password</span></label>
                        <input type="password" name="confirm_password" id="confirm_password" class="input input-bordered w-full" required />
                    </div>
                </div>

                <p class="text-xs text-gray-500 mt-2">
                    Tip: Use at least 8 characters and avoid common words or reused passwords.
                </p>

                <div class="modal-action">
                    <button type="submit" class="btn btn-primary">Update Password</button>
                    <button type="button" class="btn" onclick="updatePasswordModal.close()">Cancel</button>
                </div>
            </form>
        </dialog>

    </div>

    <script>
        // Initialize icons on this page. If you already call lucide.createIcons() globally, this is safe to call again.
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            window.lucide.createIcons();
        }

        // Client-side validation for password form
        document.getElementById('updatePasswordForm').addEventListener('submit', function(e) {
            const current = document.getElementById('current_password').value.trim();
            const newPass = document.getElementById('new_password').value.trim();
            const confirmPass = document.getElementById('confirm_password').value.trim();
            const errorBox = document.getElementById('passwordError');

            let errorMsg = "";

            if (!current) {
                errorMsg = "Current password is required.";
            } else if (newPass.length < 8) {
                errorMsg = "New password must be at least 8 characters long.";
            } else if (newPass !== confirmPass) {
                errorMsg = "New password and confirmation do not match.";
            }

            if (errorMsg) {
                e.preventDefault(); // Stop form submission
                errorBox.textContent = errorMsg;
                errorBox.classList.remove("hidden");
            } else {
                errorBox.classList.add("hidden");
            }
        });
    </script>
<?php
}
