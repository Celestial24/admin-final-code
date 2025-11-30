<?php
// Email verification + resend endpoint.
// POST verify: email, code
// POST resend: action=resend, email

// Load shared database connection
require_once __DIR__ . '/../db/db.php';

require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Reuse SMTP config from register.php (keep in sync)
const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 587;
const SMTP_USER = 'atiera41001@gmail.com';
const SMTP_PASS = 'pjln rqjf revf ryic'; // Update with app-specific password
const SMTP_FROM_EMAIL = 'atiera41001@gmail.com';
const SMTP_FROM_NAME  = 'ATIERA Hotel';

function send_verification_email(string $toEmail, string $toName, string $code, &$errorMsg = null): bool {
    $mail = new PHPMailer(true);
    try {
        // Enable verbose debug output (set to 0 for production, 2 for debugging)
        $mail->SMTPDebug = 0; // 0 = off, 2 = client and server messages
        $mail->Debugoutput = function($str, $level) {
            // Log debug messages if needed
        };
        
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->Port = SMTP_PORT;
        $mail->SMTPSecure = (SMTP_PORT === 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Timeout = 10; // 10 seconds timeout

        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName ?: $toEmail);
        $mail->isHTML(true);
        $mail->Subject = 'Your ATIERA verification code';
        $mail->Body = "
            <div style=\"font-family:Arial,sans-serif;font-size:14px;line-height:1.6;color:#0f172a\">
              <h2 style=\"margin:0 0 10px\">Verify your email</h2>
              <p>Hello " . htmlspecialchars($toName ?: $toEmail) . ",</p>
              <p>Use the verification code below to activate your account. It expires in 15 minutes.</p>
              <p style=\"font-size:18px;font-weight:700;letter-spacing:2px;background:#0f1c49;color:#fff;display:inline-block;padding:8px 12px;border-radius:8px\">{$code}</p>
              <p>If you didn't request this, you can ignore this email.</p>
              <p>— ATIERA</p>
            </div>
        ";
        $mail->AltBody = "Your ATIERA verification code is: {$code}\nThis code expires in 15 minutes.";
        
        $result = $mail->send();
        if (!$result) {
            $errorMsg = $mail->ErrorInfo;
        }
        return $result;
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        return false;
    }
}

function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        // For direct visits, redirect to login
        header('Location: login.php');
        exit;
    }

    $pdo = get_pdo();
    $action = $_POST['action'] ?? 'verify';
    $email  = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        json_response(['ok' => false, 'message' => 'Invalid email.'], 400);
    }

    // Find user
    $stmt = $pdo->prepare('SELECT id, full_name, status FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    if (!$user) {
        json_response(['ok' => false, 'message' => 'Account not found.'], 404);
    }
    $userId = (int)$user['id'];
    $fullName = (string)($user['full_name'] ?? '');

    if ($action === 'resend') {
        // Check if there's an existing valid code (not expired)
        $now = new DateTimeImmutable('now');
        $stmt = $pdo->prepare('SELECT code, expires_at FROM email_verifications WHERE user_id = :uid ORDER BY id DESC LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $existing = $stmt->fetch();
        
        $code = null;
        if ($existing) {
            $expires = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $existing['expires_at']);
            // Reuse code if it's still valid (not expired)
            if ($expires && $expires > $now) {
                $code = $existing['code'];
            }
        }
        
        // Generate new code only if no valid code exists
        if (!$code) {
            $code = (string)random_int(100000, 999999);
            $expiresAt = (new DateTime('+15 minutes'))->format('Y-m-d H:i:s');
            $stmt = $pdo->prepare('INSERT INTO email_verifications (user_id, code, expires_at) VALUES (:user_id, :code, :expires_at)');
            $stmt->execute([':user_id' => $userId, ':code' => $code, ':expires_at' => $expiresAt]);
        }
        
        $emailError = '';
        $ok = send_verification_email($email, $fullName, $code, $emailError);
        if (!$ok) {
            // Log error for debugging
            error_log("Email resend failed for {$email}: {$emailError}");
            json_response(['ok' => false, 'message' => 'Failed to send verification email. Please check your email settings or contact support.'], 500);
        }
        json_response(['ok' => true, 'message' => 'Verification email sent.']);
    }

    // Default: verify
    $code = trim($_POST['code'] ?? '');
    if ($code === '' || !preg_match('/^\d{6}$/', $code)) {
        json_response(['ok' => false, 'message' => 'Invalid verification code.'], 400);
    }

    $stmt = $pdo->prepare('SELECT id, code, expires_at FROM email_verifications WHERE user_id = :uid ORDER BY id DESC LIMIT 5');
    $stmt->execute([':uid' => $userId]);
    $rows = $stmt->fetchAll();
    $now = new DateTimeImmutable('now');
    $matched = null;
    foreach ($rows as $row) {
        if (hash_equals($row['code'], $code)) {
            $expires = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['expires_at']);
            if ($expires && $expires > $now) {
                $matched = $row;
                break;
            }
        }
    }
    if (!$matched) {
        json_response(['ok' => false, 'message' => 'Code is incorrect or expired.'], 400);
    }

    // Activate user
    $pdo->prepare('UPDATE users SET status = :s WHERE id = :id')->execute([':s' => 'active', ':id' => $userId]);
    // Clean used codes (optional)
    $pdo->prepare('DELETE FROM email_verifications WHERE user_id = :uid')->execute([':uid' => $userId]);

    // Get user info for session
    $stmt = $pdo->prepare('SELECT id, full_name, username, email, role FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Start session and set user data
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        // Clear temp session data
        unset($_SESSION['temp_user_id']);
        unset($_SESSION['temp_username']);
        unset($_SESSION['temp_name']);
        unset($_SESSION['temp_email']);
        unset($_SESSION['temp_role']);
    }

    // Return success JSON for AJAX handling
    json_response(['ok' => true, 'message' => 'Email verified successfully!', 'redirect' => '../Modules/facilities-reservation.php']);
} catch (Throwable $e) {
    json_response(['ok' => false, 'message' => 'Server error. Please try again.'], 500);
}


