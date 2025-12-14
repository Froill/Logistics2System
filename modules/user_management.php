<?php
// USER MANAGEMENT MODULE

require_once __DIR__ . '/../includes/db.php';

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

    // Store pagination info
    $GLOBALS['um_page'] = $page;
    $GLOBALS['um_total_pages'] = $total_pages;
}



function user_management_view($baseURL)
{
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

        <div class="overflow-x-auto rounded-box border border-base-content/5 bg-base-100">
            <table class="table w-full">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th class="sticky left-0 bg-base-100 z-10">EID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
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
