<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/Config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$pdo = get_pdo();
$userId = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        if (!empty($password)) {
            // Update with password
            $stmt = $pdo->prepare("UPDATE users SET full_name=?, username=?, email=?, password_hash=? WHERE id=?");
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt->execute([$full_name, $username, $email, $hashed, $userId]);

            // Update session
            $_SESSION['username'] = $username;
            $_SESSION['name'] = $full_name;
            $_SESSION['email'] = $email;

            // Send Security Notification
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
                $mail->Port = SMTP_PORT;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->SMTPOptions = array(
                    'ssl' => array('verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true)
                );
                $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                $mail->addAddress($email, $full_name);
                $mail->isHTML(true);
                $mail->Subject = 'Security Notice: Your ATIERA Profile was Updated';
                $mail->Body = "
                    <div style=\"font-family: sans-serif; padding: 20px; color: #1e293b; max-width: 500px; margin: auto; border: 1px solid #e2e8f0; border-radius: 12px;\">
                        <h2 style=\"color: #0f172a;\">Account Security Notice</h2>
                        <p>Hello {$full_name},</p>
                        <p>Your account information and/or password for the ATIERA Admin Panel has been updated successfully.</p>
                        <p>If you did not make this change, please reset your password immediately or contact support.</p>
                        <div style=\"margin: 20px 0; text-align: center;\">
                            <a href=\"" . getBaseUrl() . "/auth/login.php\" style=\"background: #1e40af; color: white; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block;\">Go to Dashboard</a>
                        </div>
                    </div>
                ";
                $mail->send();
            } catch (Exception $e) {
                $error = "Profile updated but notification email failed. Mailer Error: " . $mail->ErrorInfo;
            }
        } else {
            // Update without password
            $stmt = $pdo->prepare("UPDATE users SET full_name=?, username=?, email=? WHERE id=?");
            $stmt->execute([$full_name, $username, $email, $userId]);
            $_SESSION['username'] = $username;
            $_SESSION['name'] = $full_name;
            $_SESSION['email'] = $email;
        }
        $message = "Profile updated successfully!";
    } catch (PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - ATIERA Admin</title>
    <link rel="icon" type="image/x-icon" href="../assets/image/logo2.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/facilities-reservation.css">
    <style>
        .account-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #4a5568;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #1e40af;
            box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.1);
        }

        .btn-update {
            background: #1e40af;
            color: white;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
        }

        .btn-update:hover {
            background: #1e3a8a;
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: #f1f5f9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1rem;
            color: #1e40af;
        }
    </style>
</head>

<body>
    <div class="main-layout">
        <?php include 'sidebar.php'; ?>

        <main class="main-content">
            <header class="main-header"
                style="justify-content: space-between; padding: 1rem 2rem; background: white; border-bottom: 1px solid #edf2f7; display: flex; align-items: center;">
                <div class="header-title" style="display: flex; align-items: center; gap: 15px;">
                    <h1>My Account</h1>
                </div>
                <div class="user-info" style="display: flex; align-items: center; gap: 10px;">
                    <span class="icon-img-placeholder">👤</span> <?= htmlspecialchars($user['full_name']) ?>
                </div>
            </header>

            <div class="dashboard-content">
                <?php if ($message): ?>
                    <div class="alert alert-success"
                        style="padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; background: #c6f6d5; color: #22543d; border: 1px solid #9ae6b4;">
                        <?= htmlspecialchars($message) ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-error"
                        style="padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; background: #fed7d7; color: #c53030; border: 1px solid #feb2b2;">
                        <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="account-container">
                    <div class="profile-header">
                        <div class="profile-avatar">👤</div>
                        <h2>Profile Settings</h2>
                        <p style="color: #718096;">Manage your personal information and password</p>
                    </div>

                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control"
                                value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control"
                                value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Email Address</label>
                            <input type="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>New Password (leave blank to keep current)</label>
                            <input type="password" name="password" class="form-control"
                                placeholder="Enter new password">
                        </div>

                        <button type="submit" class="btn-update">
                            <i class="fas fa-save" style="margin-right: 8px;"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>

</html>