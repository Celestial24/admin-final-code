<?php
// API Endpoint for Email Verification and Resending codes
// IT DOES NOT RENDER HTML. It returns JSON.

require_once __DIR__ . '/../db/db.php';
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Constants
const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 587;
const SMTP_USER = 'atiera41001@gmail.com';
const SMTP_PASS = 'pjln rqjf revf ryic';
const SMTP_FROM_EMAIL = 'atiera41001@gmail.com';
const SMTP_FROM_NAME = 'ATIERA Hotel';

header('Content-Type: application/json');
session_start();

function json_out($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// 1. Validate Session Context
// We expect 'temp_email' and 'temp_user_id' to be set by login.php
if (empty($_SESSION['temp_user_id']) || empty($_SESSION['temp_email'])) {
    json_out(['ok' => false, 'message' => 'Session expired. Please login again.'], 401);
}

$userId = $_SESSION['temp_user_id'];
$email = $_SESSION['temp_email'];
$name = $_SESSION['temp_name'] ?? 'Admin';

$action = $_POST['action'] ?? 'verify';

// --- HELPER: Send Email ---
function send_email($to, $name, $code)
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->Port = SMTP_PORT;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Your ATIERA Verification Code';
        $mail->Body = "
            <div style=\"font-family: sans-serif; padding: 20px; color: #1e293b;\">
                <h2 style=\"color: #0f172a;\">Verify Login</h2>
                <p>Hello {$name},</p>
                <p>Please use the following code to complete your login:</p>
                <div style=\"font-size: 24px; font-weight: bold; letter-spacing: 5px; color: #1e40af; margin: 20px 0;\">
                    {$code}
                </div>
                <p>This code expires in 15 minutes.</p>
            </div>
        ";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

try {
    $pdo = get_pdo();

    // --- ACTION: RESEND ---
    if ($action === 'resend') {
        // Generate new code
        $code = (string) random_int(100000, 999999);
        $expires = (new DateTime('+15 minutes'))->format('Y-m-d H:i:s');

        $stmt = $pdo->prepare('INSERT INTO email_verifications (user_id, code, expires_at) VALUES (?,?,?)');
        $stmt->execute([$userId, $code, $expires]);

        if (send_email($email, $name, $code)) {
            json_out(['ok' => true, 'message' => 'New code sent to ' . $email]);
        } else {
            json_out(['ok' => false, 'message' => 'Failed to send email.'], 500);
        }
    }

    // --- ACTION: VERIFY ---
    if ($action === 'verify') {
        $code = trim($_POST['code'] ?? '');
        if (!preg_match('/^\d{6}$/', $code)) {
            json_out(['ok' => false, 'message' => 'Invalid code format.'], 400);
        }

        // Check DB
        $stmt = $pdo->prepare('SELECT code, expires_at FROM email_verifications WHERE user_id = ? ORDER BY id DESC LIMIT 5');
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $valid = false;
        $now = new DateTimeImmutable();

        foreach ($rows as $row) {
            if (hash_equals($row['code'], $code)) {
                $exp = new DateTimeImmutable($row['expires_at']);
                if ($exp > $now) {
                    $valid = true;
                    break;
                }
            }
        }

        if ($valid) {
            // Success! Promote temp session to real session
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $_SESSION['temp_username'];
            $_SESSION['email'] = $email;
            $_SESSION['name'] = $name;
            // Note: Role removed as per previous instructions

            // Cleanup
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_username']);
            unset($_SESSION['temp_email']);
            unset($_SESSION['temp_name']);
            // unset($_SESSION['temp_role']); // Was removed previously

            $pdo->prepare('DELETE FROM email_verifications WHERE user_id = ?')->execute([$userId]);

            json_out(['ok' => true, 'redirect' => '../Modules/facilities-reservation.php']);
        } else {
            json_out(['ok' => false, 'message' => 'Invalid or expired code.'], 400);
        }
    }

} catch (Exception $e) {
    json_out(['ok' => false, 'message' => 'Server error.'], 500);
}
?>