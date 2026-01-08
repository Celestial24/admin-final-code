<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../db/db.php';
$pdo = get_pdo();

$message = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        /* Update User */
        if ($_POST['action'] === 'update_user') {
            $id = intval($_POST['user_id']);
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $full_name = trim($_POST['full_name']);
            $password = $_POST['password'];

            try {
                if (!empty($password)) {
                    // Update with password
                    $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, full_name=?, password_hash=? WHERE id=?");
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt->execute([$username, $email, $full_name, $hashed_password, $id]);
                } else {
                    // Update without password
                    $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, full_name=? WHERE id=?");
                    $stmt->execute([$username, $email, $full_name, $id]);
                }
                $message = "User updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating user: " . $e->getMessage();
            }
        }
        /* Delete User */ elseif ($_POST['action'] === 'delete_user') {
            $id = intval($_POST['user_id']);
            // Prevent deleting self?
            if ($id == $_SESSION['user_id']) {
                $error = "You cannot delete your own account.";
            } else {
                try {
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
                    $stmt->execute([$id]);
                    $message = "User deleted successfully!";
                } catch (PDOException $e) {
                    $error = "Error deleting user: " . $e->getMessage();
                }
            }
        }
        /* Create User (Optional but good to have) */ elseif ($_POST['action'] === 'create_user') {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $full_name = trim($_POST['full_name']);
            $password = $_POST['password'];

            if (!empty($username) && !empty($password)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password_hash) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $full_name, password_hash($password, PASSWORD_DEFAULT)]);
                    $message = "User created successfully!";
                } catch (PDOException $e) {
                    $error = "Error creating user: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch Users
$stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - Admin</title>
    <link rel="icon" type="image/x-icon" href="../assets/image/logo2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/facilities-reservation.css">
    <style>
        .icon-img-placeholder {
            display: inline-block;
        }

        .dashboard-layout .main-content {
            margin-left: 280px;
        }

        @media screen and (max-width: 991px) {
            .dashboard-layout .main-content {
                margin-left: 0;
            }
        }

        /* Modal logic fix for this page */
        .modal {
            display: none;
            align-items: center;
            justify-content: center;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            width: 100%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .close-modal {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-size: 1.5rem;
            cursor: pointer;
            color: #718096;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
    </style>
</head>

<body class="dashboard-layout">
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <header class="top-header">
                <div class="header-title">
                    <button class="mobile-menu-btn" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>Account Settings</h1>
                    <span style="color: #718096; margin-left: 10px; font-size: 0.9rem; font-weight: 400;">Manage Admin Accounts and System Users</span>
                </div>
                <div class="header-actions">
                    <div class="user-info" style="display: flex; align-items: center; gap: 10px; font-weight: 600;">
                        <span class="icon-img-placeholder">👤</span> Admin
                    </div>
                </div>
            </header>

            <div class="dashboard-content">
                <?php if ($message): ?>
                    <div class="alert alert-success" style="padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; background: #c6f6d5; color: #22543d; border: 1px solid #9ae6b4; display: flex; align-items: center; gap: 10px;">
                        <span class="icon-img-placeholder">✅</span> <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error" style="padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; background: #fed7d7; color: #c53030; border: 1px solid #feb2b2; display: flex; align-items: center; gap: 10px;">
                        <span class="icon-img-placeholder">⚠️</span> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <div class="card" style="background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 1.5rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom: 1px solid #edf2f7; padding-bottom: 1rem;">
                        <h3 style="color: #2d3748; font-size: 1.5rem; font-weight: 600;">Users List</h3>
                        <button class="btn btn-primary" onclick="openCreateModal()">
                            <span class="icon-img-placeholder">➕</span> Add User
                        </button>
                    </div>

                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th style="text-align: center;">ID</th>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th style="text-align: center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td style="text-align: center; font-weight: 600; color: #718096;">#<?= $user['id'] ?></td>
                                        <td style="font-weight: 500;"><?= htmlspecialchars($user['full_name']) ?></td>
                                        <td><?= htmlspecialchars($user['username']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td style="text-align: center;">
                                            <div style="display: flex; gap: 8px; justify-content: center;">
                                                <button class="btn btn-outline btn-sm"
                                                    onclick='openEditModal(<?= json_encode($user) ?>)'>
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button class="btn btn-danger btn-sm" onclick="openDeleteModal(<?= $user['id'] ?>)">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>

        <!-- Edit/Create User Modal -->
        <div class="modal" id="userModal">
            <div class="modal-content">
                <span class="close-modal" onclick="closeModal('userModal')">&times;</span>
                <h3 id="modalTitle" style="margin-top: 0; margin-bottom: 1.5rem; color: var(--primary);">Edit User</h3>
                <form method="POST" id="userForm">
                    <input type="hidden" name="action" id="formAction" value="update_user">
                    <input type="hidden" name="user_id" id="userId">

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" id="fullName" class="form-control" required placeholder="Enter full name">
                    </div>

                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" id="userName" class="form-control" required placeholder="Choose a username">
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" id="userEmail" class="form-control" required placeholder="Enter email address">
                    </div>

                    <div class="form-group">
                        <label>Password <small style="font-weight: 400; color: #718096;">(Leave blank to keep unchanged)</small></label>
                        <input type="password" name="password" class="form-control" placeholder="Enter new password">
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1rem;">
                        <span class="icon-img-placeholder">💾</span> Save Changes
                    </button>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div class="modal" id="deleteModal">
            <div class="modal-content" style="max-width:400px; text-align:center;">
                <div style="color: #e53e3e; font-size: 3rem; margin-bottom: 1rem;">
                    <span class="icon-img-placeholder">⚠️</span>
                </div>
                <h3 style="margin-top: 0; color: #2d3748;">Delete User?</h3>
                <p style="color: #718096; margin-bottom: 1.5rem;">Are you sure you want to delete this user? This action cannot be undone.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <div style="display:flex; gap:10px; justify-content:center;">
                        <button type="button" class="btn btn-outline" style="flex: 1;"
                            onclick="closeModal('deleteModal')">Cancel</button>
                        <button type="submit" class="btn btn-danger" style="flex: 1;">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/Javascript/facilities-reservation.js"></script>
    <script>
        function openEditModal(user) {
            document.getElementById('modalTitle').innerText = 'Edit User';
            document.getElementById('formAction').value = 'update_user';
            document.getElementById('userId').value = user.id;
            document.getElementById('fullName').value = user.full_name;
            document.getElementById('userName').value = user.username;
            document.getElementById('userEmail').value = user.email;

            document.getElementById('userModal').classList.add('active');
        }

        function openCreateModal() {
            document.getElementById('modalTitle').innerText = 'Add New User';
            document.getElementById('formAction').value = 'create_user';
            document.getElementById('userId').value = '';
            document.getElementById('userForm').reset();

            document.getElementById('userModal').classList.add('active');
        }

        function openDeleteModal(id) {
            document.getElementById('deleteUserId').value = id;
            document.getElementById('deleteModal').classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        // Close modal on outside click
        window.onclick = function (event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>

</html>