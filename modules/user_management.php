<?php
// USER MANAGEMENT MODULE

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security_lockout.php';
require_once __DIR__ . '/lock_management.php';

function user_management_logic($baseURL)
{
    // Access control
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        header("Location: dashboard.php");
        exit;
    }

    // Handle flash messages
    $GLOBALS['um_success'] = $_SESSION['um_success'] ?? '';
    $GLOBALS['um_error']   = $_SESSION['um_error'] ?? '';
    unset($_SESSION['um_success'], $_SESSION['um_error']);

    global $conn;

    // Pagination setup
    $limit = 10; // users per page
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $limit;

    // Count total users
    $countResult = $conn->query("SELECT COUNT(*) as total FROM users");
    $total_users = $countResult->fetch_assoc()['total'];
    $total_pages = ceil($total_users / $limit);

    // Fetch users with pagination
    $stmt = $conn->prepare("
        SELECT 
            u.id, 
            u.eid, 
            u.full_name, 
            u.email, 
            u.role, 
            u.created_at,
            u.account_locked,
            u.locked_until,
            u.failed_login_count,
            d.license_number
        FROM users u
        LEFT JOIN drivers d ON u.eid = d.eid
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $GLOBALS['um_users'] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch locked accounts for security overview
    $GLOBALS['locked_accounts'] = getAllLockedAccounts($conn);

    // Get security statistics
    $GLOBALS['security_stats'] = getSecurityStats($conn);

    // Store pagination info
    $GLOBALS['um_page'] = $page;
    $GLOBALS['um_total_pages'] = $total_pages;
}



function user_management_view($baseURL)
{
    // Add log to the current module that is being accessed by the user
    $moduleName = 'user_management';

    if ($_SESSION['current_module'] !== $moduleName) {
        log_audit_event(
            'User Mgmt',
            'ACCESS',
            null,
            $_SESSION['full_name'],
            'User accessed User Management module'
        );
        $_SESSION['current_module'] = $moduleName;
    }

    $users   = $GLOBALS['um_users'] ?? [];
    $success = $GLOBALS['um_success'] ?? '';
    $error   = $GLOBALS['um_error'] ?? '';
    $page    = $GLOBALS['um_page'] ?? 1;
    $total_pages = $GLOBALS['um_total_pages'] ?? 1;
?>
    <div class="overflow-x-auto w-auto">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between mb-4 gap-2">
            <h2 class="text-xl md:text-2xl font-bold">User Management</h2>
            <button onclick="addUserModal.showModal()" class="btn btn-primary">Add User</button>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success mb-4"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Security Overview Section -->
        <?php
        $locked_accounts = $GLOBALS['locked_accounts'] ?? [];
        $security_stats = $GLOBALS['security_stats'] ?? [];
        if (!empty($locked_accounts)):
        ?>
            <div class="alert alert-warning mb-4 flex items-start gap-3">
                <i data-lucide="alert-circle" class="size-5 flex-shrink-0"></i>
                <div class="flex-1">
                    <h3 class="font-bold">Security Alert</h3>
                    <p class="text-sm mt-1">
                        <strong><?= count($locked_accounts) ?></strong> account(s) currently locked due to failed login attempts.
                        <button onclick="securityModal.showModal()" class="link link-primary ml-2">View Details</button>
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <div class="overflow-x-auto rounded-box border border-base-content/5 bg-base-100">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th class="sticky left-0 bg-base-100 z-10">EID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['id']) ?></td>
                            <td class="sticky left-0 bg-base-100 z-10"><?= htmlspecialchars($u['eid']) ?></td>
                            <td><?= htmlspecialchars($u['full_name']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($u['role'])) ?></td>
                            <td>
                                <?php if ($u['account_locked'] == 1): ?>
                                    <div class="badge badge-error py-4 gap-2">
                                        <i data-lucide="lock"></i>
                                        Locked
                                    </div>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Attempts: <?= $u['failed_login_count'] ?? 0 ?>
                                    </div>
                                <?php else: ?>
                                    <div class="badge badge-success py-4 gap-2">
                                        <i data-lucide="unlock"></i>
                                        Active
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($u['created_at']) ?></td>
                            <td class="text-right">
                                <div class="flex flex-wrap md:flex-nowrap items-center justify-end gap-2">
                                    <button class="btn btn-info btn-sm py-5 flex content-center"
                                        onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                                                    'id' => $u['id'],
                                                                    'full_name' => $u['full_name'],
                                                                    'license_number' => $u['license_number'] ?? '',
                                                                    'email' => $u['email'],
                                                                    'role' => $u['role']
                                                                ]), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i data-lucide="user-round-pen"></i>
                                    </button>

                                    <?php if ($u['account_locked'] == 1): ?>
                                        <button class="btn btn-warning btn-sm py-5 flex content-center"
                                            onclick="openUnlockModal(<?= htmlspecialchars(json_encode([
                                                                            'id' => $u['id'],
                                                                            'full_name' => $u['full_name'],
                                                                            'email' => $u['email'],
                                                                            'attempts' => $u['failed_login_count'] ?? 0
                                                                        ]), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i data-lucide="lock-open"></i>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ((int)$u['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                                        <button class="btn btn-error btn-sm py-5 flex content-center"
                                            onclick="openDeleteModal(<?= htmlspecialchars(json_encode([
                                                                            'id' => $u['id'],
                                                                            'full_name' => $u['full_name']
                                                                        ]), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i data-lucide="user-round-x"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-error btn-sm py-5 flex content-center btn-disabled" disabled>
                                            <i data-lucide="user-round-x"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex justify-center mt-4 gap-2 join">
            <?php if ($page > 1): ?>
                <a href="<?= $baseURL ?>&page=<?= $page - 1 ?>" class="join-item btn btn-sm">Prev</a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="<?= $baseURL ?>&page=<?= $i ?>"
                    class="join-item btn btn-sm <?= $i == $page ? 'btn-primary' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if ($page < $total_pages): ?>
                <a href="<?= $baseURL ?>&page=<?= $page + 1 ?>" class="join-item btn btn-sm">Next</a>
            <?php endif; ?>
        </div>



    </div>
    </div>

    <!-- Security Overview Modal -->
    <dialog id="securityModal" class="modal">
        <div class="modal-box max-w-2xl">
            <div class="flex items-center gap-2">
                <i data-lucide="shield-alert" class="size-6 bold"></i>
                <h3 class="font-bold text-lg">Security Overview</h3>
            </div>

            <div class="mt-4">
                <?php
                $locked = $GLOBALS['locked_accounts'] ?? [];
                $stats = $GLOBALS['security_stats'] ?? [];
                ?>

                <!-- Locked Accounts -->
                <h4 class="font-bold text-base mb-2">Locked Accounts (<?= count($locked) ?>)</h4>
                <?php if (!empty($locked)): ?>
                    <div class="space-y-2 mb-4">
                        <?php foreach ($locked as $account): ?>
                            <div class="border border-error/30 rounded-lg p-3 bg-error/5">
                                <div class="flex justify-between items-center mb-1">
                                    <div class="font-semibold"><?= htmlspecialchars($account['full_name']) ?></div>
                                    <div class="text-xs font-mono text-base-600"><?= htmlspecialchars($account['email']) ?></div>
                                </div>
                                <div class="text-xs text-base-400 mb-2">
                                    Failed Attempts: <span class="font-bold"><?= $account['failed_login_count'] ?? 0 ?></span>
                                    | Locked Until: <span class="font-bold"><?= $account['locked_until'] ?? 'Unknown' ?></span>
                                </div>
                                <button class="btn btn-warning btn-xs"
                                    onclick="openUnlockModal(<?= htmlspecialchars(json_encode([
                                                                    'id' => $account['user_id'],
                                                                    'full_name' => $account['full_name'],
                                                                    'email' => $account['email'],
                                                                    'attempts' => $account['failed_login_count'] ?? 0
                                                                ]), ENT_QUOTES, 'UTF-8') ?>); securityModal.close()">
                                    Unlock Account
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600 text-sm mb-4">No locked accounts at this time.</p>
                <?php endif; ?>

                <!-- Statistics -->
                <?php if (!empty($stats)): ?>
                    <h4 class="font-bold text-base mb-2">Statistics</h4>
                    <div class="grid grid-cols-2 gap-2 text-sm">
                        <div class="bg-info/10 p-2 rounded">
                            <div class="text-info font-bold"><?= $stats['total_locked_accounts'] ?? 0 ?></div>
                            <div class="text-xs">Total Locked</div>
                        </div>
                        <div class="bg-warning/20 p-2 rounded">
                            <div class="text-error font-bold"><?= $stats['total_failed_attempts'] ?? 0 ?></div>
                            <div class="text-xs">Failed Attempts</div>
                        </div>
                        <div class="bg-error/10 p-2 rounded">
                            <div class="text-error font-bold"><?= $stats['locked_ips'] ?? 0 ?></div>
                            <div class="text-xs">Blocked IPs</div>
                        </div>
                        <div class="bg-success/10 p-2 rounded">
                            <div class="text-success font-bold"><?= $stats['total_users'] ?? 0 ?></div>
                            <div class="text-xs">Total Users</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="modal-action">
                <button type="button" class="btn" onclick="securityModal.close()">Close</button>
            </div>
        </div>
    </dialog>

    <!-- Unlock Account Modal -->
    <dialog id="unlockUserModal" class="modal">
        <form method="POST" action="includes/lock_unlock_handler.php" class="modal-box">
            <input type="hidden" name="action" value="unlock" />
            <input type="hidden" name="user_id" id="unlock_user_id" />

            <div class="flex items-center gap-2">
                <i data-lucide="lock-open" class="size-6 bold"></i>
                <h3 class="font-bold text-lg">Unlock Account</h3>
            </div>

            <div class="mt-4">
                <p class="mb-3">
                    Are you sure you want to unlock <strong id="unlock_full_name"></strong>?
                </p>
                <div class="bg-info/10 p-3 rounded text-sm">
                    <div class="font-semibold mb-1">Account Details:</div>
                    <div>Email: <span id="unlock_email" class="font-mono text-xs"></span></div>
                    <div>Failed Attempts: <span id="unlock_attempts" class="font-bold"></span></div>
                </div>
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-warning">Unlock</button>
                <button type="button" class="btn" onclick="unlockUserModal.close()">Cancel</button>
            </div>
        </form>
    </dialog>

    <!-- Add User Modal -->
    <dialog id="addUserModal" class="modal">
        <form method="POST" action="includes/user_actions.php" class="modal-box">
            <div class="flex items-center gap-2">
                <i data-lucide="user-round-plus" class="size-6 bold"></i>
                <h3 class="font-bold text-lg">Add New User</h3>
            </div>

            <input type="hidden" name="action" value="create" />

            <div class="mt-3">
                <label class="label"><span class="label-text">Full Name</span></label>
                <input name="full_name" class="input input-bordered w-full" required />
            </div>

            <div class="mt-3">
                <label class="label">Role</label>
                <select name="role" id="add_role" class="select select-bordered" required>
                    <option value="requester">Requester</option>
                    <option value="driver">Driver</option>
                    <option value="staff">Staff</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="manager">Manager</option>
                </select>
            </div>

            <div class="mt-3 hidden" id="add_license_field">
                <label class="label"><span class="label-text">License Number</span></label>
                <input type="text" name="license_number" class="input input-bordered w-full" />
            </div>

            <div class="mt-3">
                <label class="label"><span class="label-text">Email</span></label>
                <input type="email" name="email" class="input input-bordered w-full" required />
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-primary">Create</button>
                <button type="button" class="btn" onclick="addUserModal.close()">Cancel</button>
            </div>
        </form>
    </dialog>

    <!-- Edit User Modal -->
    <dialog id="editUserModal" class="modal">
        <form method="POST" action="includes/user_actions.php" class="modal-box">
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="user_id" id="edit_user_id" />

            <div class="flex items-center gap-2">
                <i data-lucide="user-round-pen" class="size-6 bold"></i>
                <h3 class="font-bold text-lg">Edit User</h3>
            </div>

            <div class="mt-3">
                <label class="label">Full Name</label>
                <input id="edit_full_name" name="full_name" class="input input-bordered w-full" required />
            </div>

            <div class="mt-3">
                <label class="label">Role</label>
                <select name="role" id="edit_role" class="select select-bordered" required>
                    <option value="requester">Requester</option>
                    <option value="driver">Driver</option>
                    <option value="supervisor">Supervisor</option>
                    <option value="manager">Manager</option>
                </select>
                <div class="flex items-center gap-1 text-sm text-gray-500 mt-1 hidden">
                    <i data-lucide="info"></i>
                    <p>Admin role cannot be changed.</p>
                </div>
            </div>

            <div class="mt-3 hidden" id="edit_license_field">
                <label class="label"><span class="label-text">License Number</span></label>
                <input type="text" id="edit_license_number" name="license_number" class="input input-bordered w-full" />
            </div>

            <div class="mt-3">
                <label class="label">Email</label>
                <input id="edit_email" type="email" name="email" class="input input-bordered w-full" required />
            </div>

            <div class="mt-3">
                <label class="label">New Password (leave blank to keep)</label>
                <input id="edit_password" type="password" name="password" class="input input-bordered w-full" />
            </div>

            <div class="modal-action">
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="button" class="btn" onclick="editUserModal.close()">Cancel</button>
            </div>
        </form>
    </dialog>


    <!-- Delete User Modal -->
    <dialog id="deleteUserModal" class="modal">
        <form method="POST" action="includes/user_actions.php" class="modal-box">
            <input type="hidden" name="action" value="delete" />
            <input type="hidden" id="delete_user_id" name="user_id" />

            <div class="flex items-center gap-2">
                <i data-lucide="user-round-x" class="size-6 bold"></i>
                <h3 class="font-bold text-lg">Delete User</h3>
            </div>
            <p class="mt-2">Are you sure you want to delete <strong id="delete_full_name"></strong>?</p>

            <div class="modal-action">
                <button type="submit" class="btn btn-error">Delete</button>
                <button type="button" class="btn" onclick="deleteUserModal.close()">Cancel</button>
            </div>
        </form>
    </dialog>

    <script>
        function openEditModal(user) {
            // Fill form fields
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_license_number').value = user.license_number || '';
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_password').value = '';

            // Role select handling
            const roleSelect = document.getElementById('edit_role');
            if (roleSelect) {
                roleSelect.value = user.role;
                roleSelect.disabled = (user.role === 'admin');

                // Show "Admin role cannot be changed." notice
                const adminNotice = roleSelect.nextElementSibling;
                if (adminNotice) {
                    adminNotice.classList.toggle('hidden', user.role !== 'admin');
                }
            }

            // License field only visible for drivers
            const licenseField = document.getElementById('edit_license_field');
            if (licenseField) {
                if (user.role === 'driver') {
                    licenseField.classList.remove("hidden");
                } else {
                    licenseField.classList.add("hidden");
                }
            }

            editUserModal.showModal();
        }

        function openUnlockModal(account) {
            document.getElementById('unlock_user_id').value = account.id;
            document.getElementById('unlock_full_name').textContent = account.full_name;
            document.getElementById('unlock_email').textContent = account.email;
            document.getElementById('unlock_attempts').textContent = account.attempts;
            unlockUserModal.showModal();
        }

        function openDeleteModal(user) {
            document.getElementById('delete_user_id').value = user.id;
            document.getElementById('delete_full_name').textContent = user.full_name;
            deleteUserModal.showModal();
        }

        // --- Role change handlers ---
        function handleRoleChange(selectId, fieldId) {
            const select = document.getElementById(selectId);
            const field = document.getElementById(fieldId);
            if (!select || !field) return;

            select.addEventListener("change", function() {
                if (this.value === "driver") {
                    field.classList.remove("hidden");
                } else {
                    field.classList.add("hidden");
                }
            });
        }

        // Apply to Add User modal and Edit User modal
        handleRoleChange("add_role", "add_license_field");
        handleRoleChange("edit_role", "edit_license_field");
    </script>

    </div>
<?php
}
