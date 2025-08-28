<?php
session_start();
require_once 'includes/db.php'; // provides $conn

// // Access control: only admins
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     header('Location: dashboard.php');
//     exit();
// }

// Flash messages
$success = $_SESSION['um_success'] ?? '';
$error   = $_SESSION['um_error'] ?? '';
unset($_SESSION['um_success'], $_SESSION['um_error']);

// Fetch users
$stmt = $conn->prepare("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html data-theme="light" lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>User Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.4.19/dist/full.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="./css/style.css">
</head>

<body class="min-h-screen flex flex-row">
    <?php include 'includes/sidebar.php'; ?>

    <div class="flex flex-col flex-grow">
        <?php include 'includes/navbar.php'; ?>


        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h1 class="text-2xl font-bold">User Management</h1>
                <button onclick="addUserModal.showModal()" class="btn btn-primary">
                    Add User
                </button>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success mb-4"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-error mb-4"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="overflow-x-auto bg-white rounded-lg shadow">
                <table class="table w-full">
                    <thead>
                        <tr>
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
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo htmlspecialchars($u['role']); ?></td>
                                <td><?php echo htmlspecialchars($u['created_at']); ?></td>
                                <td class="text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button class="btn btn-sm btn-outline"
                                            onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                                                        'id' => $u['id'],
                                                                        'username' => $u['username'],
                                                                        'email' => $u['email'],
                                                                        'role' => $u['role'],
                                                                    ]), ENT_QUOTES, 'UTF-8') ?>)">
                                            Edit
                                        </button>



                                        <?php if ((int)$u['id'] !== (int)($_SESSION['user_id'] ?? 0)): ?>
                                            <button
                                                class="btn btn-sm btn-error"
                                                onclick="openDeleteModal(<?= htmlspecialchars(json_encode([
                                                                                'id' => $u['id'],
                                                                                'username' => $u['username']
                                                                            ]), ENT_QUOTES, 'UTF-8') ?>)">
                                                Delete
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-disabled" title="You cannot delete your own account" disabled>Delete</button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <dialog id="addUserModal" class="modal">
        <form method="POST" action="includes/user_actions.php" class="modal-box">
            <div class="flex items-center gap-2">
                <i data-lucide="user-round-plus" class="size-6 bold"></i>
                <h3 class="font-bold text-lg ">Add New User</h3>
            </div>

            <input type="hidden" name="action" value="create" />

            <div class="mt-3">
                <label class="label"><span class="label-text">Username</span></label>
                <input name="username" class="input input-bordered w-full" required />
            </div>

            <div class="mt-3">
                <label class="label">Role</label>
                <select name="role" id="edit_role" class="select select-bordered">
                    <option value="user">User</option>
                    <option value="manager">Manager</option>
                    <option value="staff">Staff</option>
                </select>
                <p class="text-sm text-gray-500 mt-1 hidden">Admin role cannot be changed.</p>
            </div>

            <div class="mt-3">
                <label class="label"><span class="label-text">Email</span></label>
                <input type="email" name="email" class="input input-bordered w-full" required />
            </div>
            <div class="mt-3">
                <label class="label"><span class="label-text">Password</span></label>
                <input type="password" name="password" class="input input-bordered w-full" required />
            </div>

            <!-- role forced to 'user' -->
            <input type="hidden" name="role" value="user" />

            <div class="modal-action">
                <button type="submit" class="btn btn-primary">Create</button>
                <button type="button" class="btn" onclick="addUserModal.close()">Cancel</button>
            </div>
        </form>
    </dialog>

    <!-- Edit User Modal -->
    <dialog id="editUserModal" class="modal">
        <form method="POST" action="includes/user_actions.php" class="modal-box">

            <div class="flex items-center gap-2">
                <i data-lucide="user-round-pen" class="size-6 bold"></i>
                <h3 class="font-bold text-lg">Edit User</h3>
            </div>

            <!-- Hidden fields -->
            <input type="hidden" name="action" value="update" />
            <input type="hidden" name="user_id" id="edit_user_id" />

            <div class="mt-3">
                <label class="label"><span class="label-text">Username</span></label>
                <input id="edit_username" name="username" class="input input-bordered w-full" required />
            </div>

            <div class="mt-3">
                <label class="label">Role</label>
                <select name="role" id="edit_role" class="select select-bordered">
                    <option value="user">User</option>
                    <option value="manager">Manager</option>
                    <option value="staff">Staff</option>
                </select>
                <p class="text-sm text-gray-500 mt-1 hidden">Admin role cannot be changed.</p>
            </div>


            <div class="mt-3">
                <label class="label"><span class="label-text">Email</span></label>
                <input id="edit_email" type="email" name="email" class="input input-bordered w-full" required />
            </div>

            <div class="mt-3">
                <label class="label"><span class="label-text">New Password (leave blank to keep)</span></label>
                <input id="edit_password" type="password" name="password" class="input input-bordered w-full" />
            </div>

            <!-- role cannot be changed by admin -->
            <div class="modal-action">
                <button type="submit" class="btn btn-primary">Save</button>
                <button type="button" class="btn" onclick="editUserModal.close()">Cancel</button>
            </div>
        </form>
    </dialog>

    <!-- Delete User Modal -->
    <dialog id="deleteUserModal" class="modal">
        <form method="POST" action="includes/user_actions.php" class="modal-box">

            <div class="flex items-center gap-2">
                <i data-lucide="user-round-x" class="size-6 bold"></i>
                <h3 class="font-bold text-lg">Delete User</h3>
            </div>
            <p class="mt-2">Are you sure you want to delete <strong id="delete_username"></strong>?</p>
            <input type="hidden" name="action" value="delete" />
            <input type="hidden" id="delete_user_id" name="user_id" />

            <div class="modal-action">
                <button type="submit" class="btn btn-error">Delete</button>
                <button type="button" class="btn" onclick="deleteUserModal.close()">Cancel</button>
            </div>
        </form>
    </dialog>

    <script src="https://cdn.tailwindcss.com"></script>
    <!-- DaisyUI (works with Tailwind CDN) -->
    <script>
        tailwind.config = {
            plugins: [daisyui],
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/daisyui@4.12.10/dist/full.min.js"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="./js/soliera.js"></script>

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
</body>

</html>