
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

    // Fetch users
    global $conn;
    $stmt = $conn->prepare("SELECT id, eid, username, email, role, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    $GLOBALS['um_users'] = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

function user_management_view($baseURL)
{
    $users   = $GLOBALS['um_users'] ?? [];
    $success = $GLOBALS['um_success'] ?? '';
    $error   = $GLOBALS['um_error'] ?? '';
?>
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-2xl font-bold">User Management</h2>
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
                        <th>EID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['eid']) ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($u['role'])) ?></td>
                            <td><?= htmlspecialchars($u['created_at']) ?></td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button class="btn btn-info btn-sm py-5 flex  content-center" aria-label="Edit user details"
                                        onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                                                    'id' => $u['id'],
                                                                    'username' => $u['username'],
                                                                    'email' => $u['email'],
                                                                    'role' => $u['role']
                                                                ]), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i data-lucide="user-round-pen"></i>
                                    </button>

                                    <?php if ((int)$u['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                                        <button class="btn btn-error btn-sm  py-5 flex  content-center" aria-label="Delete user"
                                            onclick="openDeleteModal(<?= htmlspecialchars(json_encode([
                                                                            'id' => $u['id'],
                                                                            'username' => $u['username']
                                                                        ]), ENT_QUOTES, 'UTF-8') ?>)">
                                            <i data-lucide="user-round-x"></i>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-error btn-sm  py-5 flex  content-center btn-disabled" disabled title="You cannot delete your own account">
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

        <!-- Add User Modal -->
        <dialog id="addUserModal" class="modal">
            <form method="POST" action="includes/user_actions.php" class="modal-box">
                <div class="flex items-center gap-2">
                    <i data-lucide="user-round-plus" class="size-6 bold"></i>
                    <h3 class="font-bold text-lg">Add New User</h3>
                </div>

                <input type="hidden" name="action" value="create" />

                <div class="mt-3">
                    <label class="label"><span class="label-text">Username</span></label>
                    <input name="username" class="input input-bordered w-full" required />
                </div>

                <div class="mt-3">
                    <label class="label">Role</label>
                    <select name="role" class="select select-bordered">
                        <option value="user">User</option>
                        <option value="manager">Manager</option>
                        <option value="supervisor">Supervisor</option>
                    </select>
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
                    <label class="label">Username</label>
                    <input id="edit_username" name="username" class="input input-bordered w-full" required />
                </div>

                <div class="mt-3">
                    <label class="label">Role</label>
                    <select name="role" id="edit_role" class="select select-bordered">
                        <option value="user">User</option>
                        <option value="manager">Manager</option>
                        <option value="supervisor">Supervisor</option>
                    </select>
                    <div class="flex items-center gap-1 text-sm text-gray-500 mt-1 hidden">
                        <i data-lucide="info"></i>
                        <p class=" ">Admin role cannot be changed.</p>
                    </div>
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
                <p class="mt-2">Are you sure you want to delete <strong id="delete_username"></strong>?</p>

                <div class="modal-action">
                    <button type="submit" class="btn btn-error">Delete</button>
                    <button type="button" class="btn" onclick="deleteUserModal.close()">Cancel</button>
                </div>
            </form>
        </dialog>

        <script>
            function openEditModal(user) {
                document.getElementById('edit_user_id').value = user.id;
                document.getElementById('edit_username').value = user.username;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_password').value = '';

                const roleSelect = document.getElementById('edit_role');
                if (roleSelect) {
                    roleSelect.value = user.role;
                    roleSelect.disabled = (user.role === 'admin');
                    roleSelect.nextElementSibling.classList.toggle('hidden', user.role !== 'admin');
                }
                editUserModal.showModal();
            }

            function openDeleteModal(user) {
                document.getElementById('delete_user_id').value = user.id;
                document.getElementById('delete_username').textContent = user.username;
                deleteUserModal.showModal();
            }
        </script>
    </div>
<?php
}
