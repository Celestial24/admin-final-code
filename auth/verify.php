<?php
// Load shared database connection
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

session_start();

// If coming from login, we should have temp_email
if (!isset($_SESSION['temp_email']) && empty($_POST)) {
    header('Location: login.php');
    exit;
}
$prefill_email = $_SESSION['temp_email'] ?? '';

// --- HELPER FUNCTIONS ---

function send_verification_email(string $toEmail, string $toName, string $code, &$errorMsg = null): bool
{
    // ... (reuse existing logic if desired, or simplified)
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 0;
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->Port = SMTP_PORT;
        $mail->SMTPSecure = (SMTP_PORT === 465) ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Timeout = 10;
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
            </div>";
        $mail->AltBody = "Code: {$code}";
        return $mail->send();
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        return false;
    }
}

// --- HANDLE POST REQUESTS (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo = get_pdo();
    $action = $_POST['action'] ?? 'verify';
    $email = trim($_POST['email'] ?? '');

    // Response handler
    function json_out($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // Lookup User
    // Note: removed role/status from select as per request, just id/full_name needed check
    $stmt = $pdo->prepare('SELECT id, full_name FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    if (!$user)
        json_out(['ok' => false, 'message' => 'Account not found.'], 404);

    $userId = $user['id'];
    $fullName = $user['full_name'];

    // RESEND
    if ($action === 'resend') {
        // ... (Similar logic to before, simplified for brevity)
        $code = (string) random_int(100000, 999999);
        $expires = (new DateTime('+15 minutes'))->format('Y-m-d H:i:s');
        $pdo->prepare('INSERT INTO email_verifications (user_id, code, expires_at) VALUES (?,?,?)')->execute([$userId, $code, $expires]);

        if (send_verification_email($email, $fullName, $code)) {
            json_out(['ok' => true, 'message' => 'Verification email sent.']);
        } else {
            json_out(['ok' => false, 'message' => 'Failed to send.'], 500);
        }
    }

    // VERIFY
    $code = trim($_POST['code'] ?? '');
    if (!preg_match('/^\d{6}$/', $code))
        json_out(['ok' => false, 'message' => 'Invalid code format.'], 400);

    // Check code logic
    $stmt = $pdo->prepare('SELECT id, code, expires_at FROM email_verifications WHERE user_id = :uid ORDER BY id DESC LIMIT 5');
    $stmt->execute([':uid' => $userId]);
    $rows = $stmt->fetchAll();
    $now = new DateTimeImmutable();
    $matched = false;
    foreach ($rows as $row) {
        if (hash_equals($row['code'], $code)) {
            $exp = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['expires_at']);
            if ($exp > $now) {
                $matched = true;
                break;
            }
        }
    }

    if (!$matched)
        json_out(['ok' => false, 'message' => 'Invalid or expired code.'], 400);

    // Success: Login User
    // Clean verification codes
    $pdo->prepare('DELETE FROM email_verifications WHERE user_id = ?')->execute([$userId]);

    // Set Session
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $user['full_name']; // Using full_name as legacy username or just name
    $_SESSION['email'] = $email;
    // Removed role setting as requested

    // Clear temp
    unset($_SESSION['temp_email']);
    unset($_SESSION['temp_user_id']);

    json_out(['ok' => true, 'message' => 'Verified!', 'redirect' => '../Modules/facilities-reservation.php']);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Email - ATIERA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: radial-gradient(circle at 10% 20%, rgb(239, 246, 255) 0%, rgb(219, 228, 255) 90%);
            min-height: 100vh;
            display: grid;
            place-items: center;
            font-family: sans-serif;
        }

        .card {
            background: white;
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            width: 90%;
            max-width: 400px;
        }

        .btn {
            background: #0f1c49;
            color: white;
            padding: 0.75rem;
            border-radius: 0.5rem;
            width: 100%;
            font-weight: 600;
            margin-top: 1rem;
        }

        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>

<body>

    <div class="card">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Verify it's you</h1>
            <p class="text-gray-500 text-sm mt-1">We've sent a code to
                <br><strong><?php echo htmlspecialchars($prefill_email); ?></strong></p>
        </div>

        <form id="verifyForm" class="space-y-4">
            <input type="hidden" name="action" value="verify">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($prefill_email); ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">6-Digit Code</label>
                <input type="text" name="code"
                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border text-center text-2xl tracking-widest"
                    maxlength="6" placeholder="000000" required>
            </div>

            <div id="msg" class="text-center text-sm min-h-[20px]"></div>

            <button type="submit" class="btn">Verify & Login</button>
        </form>

        <div class="mt-4 text-center">
            <button type="button" id="resendBtn" class="text-sm text-blue-600 hover:text-blue-800">Resend Code</button>
            <div class="mt-2">
                <a href="login.php" class="text-xs text-gray-400 hover:text-gray-600">Back to Login</a>
            </div>
        </div>
    </div>

    <script>
        const form = document.getElementById('verifyForm');
        const msg = document.getElementById('msg');
        const resendBtn = document.getElementById('resendBtn');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            btn.disabled = true; btn.innerText = 'Verifying...';
            msg.innerText = '';

            try {
                const formData = new FormData(form);
                const res = await fetch('verify.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.ok) {
                    msg.className = 'text-center text-sm text-green-600 font-bold';
                    msg.innerText = data.message;
                    setTimeout(() => { window.location.href = data.redirect; }, 1000);
                } else {
                    msg.className = 'text-center text-sm text-red-600';
                    msg.innerText = data.message || 'Error verifying code';
                    btn.disabled = false; btn.innerText = 'Verify & Login';
                }
            } catch (err) {
                msg.className = 'text-center text-sm text-red-600';
                msg.innerText = 'Network error';
                btn.disabled = false; btn.innerText = 'Verify & Login';
            }
        });

        resendBtn.addEventListener('click', async () => {
            resendBtn.disabled = true;
            resendBtn.innerText = 'Sending...';
            try {
                const fd = new FormData();
                fd.append('action', 'resend');
                fd.append('email', form.email.value);
                const res = await fetch('verify.php', { method: 'POST', body: fd });
                const data = await res.json();
                alert(data.message);
            } catch (err) {
                alert('Failed to resend');
            }
            resendBtn.disabled = false;
            resendBtn.innerText = 'Resend Code';
        });
    </script>

</body>

</html>