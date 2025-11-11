<?php
// Email verification + resend endpoint.
// POST verify: email, code
// POST resend: action=resend, email

function get_pdo() {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    $host = '127.0.0.1';
    $db   = 'admin';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';
    $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);
    return $pdo;
}

require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Reuse SMTP config from register.php (keep in sync)
const SMTP_HOST = 'smtp.example.com';
const SMTP_PORT = 587;
const SMTP_USER = 'no-reply@example.com';
const SMTP_PASS = 'change-me';
const SMTP_FROM_EMAIL = 'no-reply@example.com';
const SMTP_FROM_NAME  = 'ATIERA Hotel';

function send_verification_email(string $toEmail, string $toName, string $code): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->Port = SMTP_PORT;
        $mail->SMTPSecure = (SMTP_PORT === 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
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
              <p>If you didn’t request this, you can ignore this email.</p>
              <p>— ATIERA</p>
            </div>
        ";
        $mail->AltBody = "Your ATIERA verification code is: {$code}\nThis code expires in 15 minutes.";
        return $mail->send();
    } catch (Exception $e) {
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
        // Create new code and email it
        $code = (string)random_int(100000, 999999);
        $expiresAt = (new DateTime('+15 minutes'))->format('Y-m-d H:i:s');
        $stmt = $pdo->prepare('INSERT INTO email_verifications (user_id, code, expires_at) VALUES (:user_id, :code, :expires_at)');
        $stmt->execute([':user_id' => $userId, ':code' => $code, ':expires_at' => $expiresAt]);
        $ok = send_verification_email($email, $fullName, $code);
        if (!$ok) {
            json_response(['ok' => false, 'message' => 'Failed to send verification email.'], 500);
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

    // Redirect back to login with a success flag
    header('Location: login.php?verified=1');
    exit;
} catch (Throwable $e) {
    json_response(['ok' => false, 'message' => 'Server error. Please try again.'], 500);
}


