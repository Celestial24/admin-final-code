<?php
// Minimal, self-contained registration with PHPMailer email verification.
// Adjust SMTP settings below to match your mail server.

// ---------- App/DB config ----------
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

// ---------- Mailer config ----------
const SMTP_HOST = 'smtp.example.com';    // e.g. smtp.gmail.com
const SMTP_PORT = 587;                   // 587 (TLS) or 465 (SSL)
const SMTP_USER = 'no-reply@example.com';
const SMTP_PASS = 'change-me';
const SMTP_FROM_EMAIL = 'no-reply@example.com';
const SMTP_FROM_NAME  = 'ATIERA Hotel';

// Lazy autoload for PHPMailer (relative to /auth)
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function ensure_verification_table(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS email_verifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            code VARCHAR(16) NOT NULL,
            expires_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (user_id),
            INDEX (code),
            CONSTRAINT fk_ev_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
}

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

session_start();
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if ($full_name === '' || $username === '' || $email === '' || $password === '' || $confirm === '') {
        $error_message = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Invalid email address.';
    } elseif ($password !== $confirm) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters.';
    } else {
        try {
            $pdo = get_pdo();
            ensure_verification_table($pdo);

            // Uniqueness checks
            $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = :email OR username = :username LIMIT 1');
            $stmt->execute([':email' => $email, ':username' => $username]);
            if ($stmt->fetch()) {
                $error_message = 'Email or username already in use.';
            } else {
                // Create user as pending
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $role = 'user';
                $status = 'pending';
                $stmt = $pdo->prepare('INSERT INTO users (full_name, username, email, password_hash, role, status) VALUES (:full_name, :username, :email, :password_hash, :role, :status)');
                $stmt->execute([
                    ':full_name' => $full_name,
                    ':username' => $username,
                    ':email' => $email,
                    ':password_hash' => $password_hash,
                    ':role' => $role,
                    ':status' => $status,
                ]);
                $user_id = (int)$pdo->lastInsertId();

                // Create a 6-digit code
                $code = (string)random_int(100000, 999999);
                $expiresAt = (new DateTime('+15 minutes'))->format('Y-m-d H:i:s');
                $stmt = $pdo->prepare('INSERT INTO email_verifications (user_id, code, expires_at) VALUES (:user_id, :code, :expires_at)');
                $stmt->execute([':user_id' => $user_id, ':code' => $code, ':expires_at' => $expiresAt]);

                // Send email
                if (!send_verification_email($email, $full_name, $code)) {
                    $error_message = 'Failed to send verification email. Please contact support.';
                } else {
                    // Guide to login to verify
                    header('Location: login.php?verify=1&email=' . urlencode($email));
                    exit;
                }
            }
        } catch (Throwable $e) {
            $error_message = 'Unexpected server error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>ATIERA — Register</title>
<link rel="icon" href="../assets/image/logo2.png">
<script src="https://cdn.tailwindcss.com"></script>
<style>
  :root{
    --blue-600:#1b2f73; --blue-700:#15265e; --blue-800:#0f1c49; --blue-a:#2342a6;
    --gold:#d4af37; --ink:#0f172a; --muted:#64748b;
    --ring:0 0 0 3px rgba(35,66,166,.28);
    --card-bg: rgba(255,255,255,.95); --card-border: rgba(226,232,240,.9);
  }
  body{
    min-height:100svh; margin:0; color:var(--ink);
    background:
      radial-gradient(70% 60% at 8% 10%, rgba(255,255,255,.18) 0, transparent 60%),
      radial-gradient(40% 40% at 100% 0%, rgba(212,175,55,.08) 0, transparent 40%),
      linear-gradient(140deg, rgba(15,28,73,1) 50%, rgba(255,255,255,1) 50%);
  }
  .card{
    background:var(--card-bg); backdrop-filter: blur(12px);
    border:1px solid var(--card-border); border-radius:18px; box-shadow:0 16px 48px rgba(2,6,23,.18);
  }
  .input{
    width:100%; border:1px solid #e5e7eb; border-radius:12px; background:#fff;
    padding:1rem .95rem; outline:none; color:#0f172a; transition:border-color .15s, box-shadow .15s, background .15s;
  }
  .input:focus{ border-color:var(--blue-a); box-shadow:var(--ring) }
  .btn{
    width:100%; display:inline-flex; align-items:center; justify-content:center; gap:.6rem;
    background:linear-gradient(180deg, var(--blue-600), var(--blue-800));
    color:#fff; font-weight:800; border-radius:14px; padding:.95rem 1rem; border:1px solid rgba(255,255,255,.06);
    box-shadow:0 8px 18px rgba(2,6,23,.18);
  }
  .alert{ border-radius:12px; padding:.65rem .8rem; font-size:.9rem }
  .alert-error{ border:1px solid #fecaca; background:#fef2f2; color:#b91c1c }
  .alert-info{ border:1px solid #c7d2fe; background:#eef2ff; color:#3730a3 }
</style>
</head>
<body class="grid md:grid-cols-2 gap-0 place-items-center p-6 md:p-10">
  <section class="hidden md:flex w-full h-full items-center justify-center">
    <div class="max-w-lg text-white px-6">
      <img src="../assets/image/logo.png" alt="ATIERA" class="w-56 mb-6 drop-shadow-xl select-none" draggable="false">
      <h1 class="text-4xl font-extrabold leading-tight tracking-tight">
        Create your <span style="color:var(--gold)">ATIERA</span> account
      </h1>
      <p class="mt-4 text-white/90 text-lg">A verification code will be sent to your email.</p>
    </div>
  </section>

  <main class="w-full max-w-md md:ml-auto">
    <div class="card p-6 sm:p-8">
      <h3 class="text-lg sm:text-xl font-semibold mb-1">Register</h3>
      <p class="text-sm text-slate-500 mb-4">Fill in your details to get started.</p>

      <?php if (!empty($error_message)): ?>
      <div class="alert alert-error mb-3"><?php echo htmlspecialchars($error_message); ?></div>
      <?php endif; ?>
      <?php if (!empty($success_message)): ?>
      <div class="alert alert-info mb-3"><?php echo htmlspecialchars($success_message); ?></div>
      <?php endif; ?>

      <form method="POST" class="space-y-4" novalidate>
        <div>
          <label class="block text-sm font-medium mb-1" for="full_name">Full name</label>
          <input id="full_name" name="full_name" type="text" required class="input" placeholder="Juan Dela Cruz" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1" for="username">Username</label>
          <input id="username" name="username" type="text" required class="input" placeholder="admin" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1" for="email">Email</label>
          <input id="email" name="email" type="email" required class="input" placeholder="admin@atiera-hotel.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1" for="password">Password</label>
          <input id="password" name="password" type="password" required class="input" placeholder="••••••••">
        </div>
        <div>
          <label class="block text-sm font-medium mb-1" for="confirm_password">Confirm Password</label>
          <input id="confirm_password" name="confirm_password" type="password" required class="input" placeholder="••••••••">
        </div>
        <button type="submit" class="btn">Create account</button>
        <p class="text-xs text-center text-slate-500">Already have an account? <a class="underline" href="login.php">Sign in</a></p>
      </form>
    </div>
  </main>
</body>
</html>


