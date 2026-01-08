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
            $role = $_POST['role'];
            $status = $_POST['status'];
            $password = $_POST['password'];

            try {
                if (!empty($password)) {
                    // Update with password
                    $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, full_name=?, role=?, status=?, password_hash=?, updated_at=NOW() WHERE id=?");
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Adjust if using plain or other hash
                    $stmt->execute([$username, $email, $full_name, $role, $status, $hashed_password, $id]);
                } else {
                    // Update without password
                    $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, full_name=?, role=?, status=?, updated_at=NOW() WHERE id=?");
                    $stmt->execute([$username, $email, $full_name, $role, $status, $id]);
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
            $role = $_POST['role'];
            $password = $_POST['password'];

            if (!empty($username) && !empty($password)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, role, status, password_hash, created_at) VALUES (?, ?, ?, ?, 'active', ?, NOW())");
                    $stmt->execute([$username, $email, $full_name, $role, password_hash($password, PASSWORD_DEFAULT)]);
                    $message = "User created successfully!";
                } catch (PDOException $e) {
                    $error = "Error creating user: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch Users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
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
    <style>
        /* Reusing basic dashboard styles */
        :root {
            --primary-color: #1a365d;
            --secondary-color: #2c5282;
            --bg-color: #f8fafc;
            --text-color: #2d3748;
            --border-color: #e2e8f0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: var(--bg-color);
            color: var(--text-color);
            display: flex;
        }

        .main-content {
            flex: 1;
            margin-left: 260px; /* Sidebar width from common css */
            padding: 20px;
        }

        .header-title { margin-bottom: 2rem; }
        .header-title h1 { color: var(--primary-color); margin: 0; }
        .header-title p { color: #718096; margin: 5px 0 0; }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }

        .table-container { overflow-x: auto; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.95rem;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th {
            background-color: #f8fafc;
            color: var(--primary-color);
            font-weight: 600;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary { background: var(--primary-color); color: white; }
        .btn-primary:hover { background: var(--secondary-color); }
        
        .btn-danger { background: #e53e3e; color: white; }
        .btn-danger:hover { background: #c53030; }
        
        .btn-outline { background: transparent; border: 1px solid var(--border-color); color: var(--text-color); }
        .btn-outline:hover { background: #f1f5f9; }

        .btn-sm { padding: 4px 10px; font-size: 0.85rem; }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-active { background: #def7ec; color: #03543f; }
        .badge-inactive { background: #fde8e8; color: #9b1c1c; }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            padding: 24px;
            position: relative;
        }
        .close-modal {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #a0aec0;
        }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-family: inherit;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success { background: #def7ec; color: #03543f; }
        .alert-error { background: #fde8e8; color: #9b1c1c; }
        
        /* Sidebar inclusion fix */
        nav.sidebar { position: fixed; height: 100vh; width: 260px; left: 0; top: 0; background: white; border-right: 1px solid var(--border-color); overflow-y: auto; }

        /* Icon fixes */
        .icon-img-placeholder { display: inline-block; }
    </style>
    <!-- Include existing sidebar CSS if needed, but styling inline for simplicity check -->
    <link rel="stylesheet" href="../assets/css/facilities-reservation.css"> 
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main-content">
        <header class="header-title">
            <h1>Account Settings</h1>
            <p>Manage Admin Accounts and System Users</p>
        </header>

        <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3>Users List</h3>
                <button class="btn btn-primary" onclick="openCreateModal()">
                    <i class="fa fa-plus"></i> Add User
                </button>
            </div>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>#<?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['full_name']) ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><span style="text-transform: capitalize;"><?= htmlspecialchars($user['role']) ?></span></td>
                                <td>
                                    <span class="badge badge-<?= $user['status'] ?>">
                                        <?= ucfirst($user['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-outline btn-sm" onclick='openEditModal(<?= json_encode($user) ?>)'>
                                        <i class="fa fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="openDeleteModal(<?= $user['id'] ?>)">
                                        <i class="fa fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Edit/Create User Modal -->
    <div class="modal" id="userModal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal('userModal')">&times;</span>
            <h3 id="modalTitle">Edit User</h3>
            <form method="POST" id="userForm">
                <input type="hidden" name="action" id="formAction" value="update_user">
                <input type="hidden" name="user_id" id="userId">
                
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" id="fullName" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" id="userName" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="userEmail" class="form-control" required>
                </div>

                <div class="form-group">
                    <label>Role</label>
                    <select name="role" id="userRole" class="form-control">
                        <option value="staff">Staff</option>
                        <option value="manager">Manager</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" id="userStatus" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Password <small>(Leave blank to keep unchanged)</small></label>
                    <input type="password" name="password" class="form-control" placeholder="New Password">
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content" style="max-width:400px; text-align:center;">
            <i class="fa fa-exclamation-triangle" style="font-size:48px; color:#e53e3e; margin-bottom:15px;"></i>
            <h3>Delete User?</h3>
            <p>Are you sure you want to delete this user? This action cannot be undone.</p>
            <form method="POST">
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" id="deleteUserId">
                <div style="display:flex; gap:10px; justify-content:center; margin-top:20px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete User</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(user) {
            document.getElementById('modalTitle').innerText = 'Edit User';
            document.getElementById('formAction').value = 'update_user';
            document.getElementById('userId').value = user.id;
            document.getElementById('fullName').value = user.full_name;
            document.getElementById('userName').value = user.username;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userRole').value = user.role;
            document.getElementById('userStatus').value = user.status;
            // Disable status for self if needed, assuming admin is smart
            
            document.getElementById('userModal').classList.add('active');
        }

        function openCreateModal() {
            document.getElementById('modalTitle').innerText = 'Add New User';
            document.getElementById('formAction').value = 'create_user';
            document.getElementById('userId').value = '';
            document.getElementById('userForm').reset();
            document.getElementById('userStatus').value = 'active'; // Default
            document.getElementById('userRole').value = 'staff'; // Default
            
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
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>
