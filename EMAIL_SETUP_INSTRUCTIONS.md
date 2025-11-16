# Email Setup Instructions

## Problem: "Failed to send verification email"

Kung nakakakuha ka ng error na "Failed to send verification email", kailangan mong i-configure ang Gmail SMTP settings.

## Solution: Gmail App Password Setup

### Step 1: Enable 2-Step Verification sa Gmail
1. Pumunta sa [Google Account Security](https://myaccount.google.com/security)
2. I-enable ang "2-Step Verification" kung hindi pa naka-enable

### Step 2: Generate App Password
1. Pumunta sa [App Passwords](https://myaccount.google.com/apppasswords)
2. Piliin ang "Mail" at "Other (Custom name)"
3. I-type ang name: "ATIERA Hotel System"
4. Click "Generate"
5. Kopyahin ang 16-character password (walang spaces)

### Step 3: Update Configuration Files

I-update ang `SMTP_PASS` sa mga sumusunod na files:

1. **auth/register.php** (line 13)
2. **auth/verify.php** (line 34)
3. **auth/login.php** (line 99 at 117)

Palitan ang:
```php
const SMTP_PASS = 'change-me';
```

Sa:
```php
const SMTP_PASS = 'your-16-character-app-password';
```

### Step 4: Verify Settings

Tiyakin na ang mga settings ay tama:

```php
const SMTP_HOST = 'smtp.gmail.com';
const SMTP_PORT = 587;
const SMTP_USER = 'atiera41001@gmail.com';
const SMTP_PASS = 'your-app-password-here'; // ← Dito ilagay ang app password
const SMTP_FROM_EMAIL = 'atiera41001@gmail.com';
```

## Alternative: Testing Mode (Development Only)

Para sa testing lang (hindi recommended sa production), pwede mong i-enable ang debug mode:

Sa `register.php` at `verify.php`, palitan ang:
```php
$mail->SMTPDebug = 0;
```

Sa:
```php
$mail->SMTPDebug = 2; // Shows detailed error messages
```

**Note:** I-disable ulit ang debug mode pagkatapos ng testing para sa security.

## Troubleshooting

### Error: "SMTP connect() failed"
- Tiyakin na tama ang app password
- Check kung naka-enable ang 2-Step Verification
- Tiyakin na walang firewall na nagb-block ng SMTP

### Error: "Authentication failed"
- Tiyakin na tama ang email address (`atiera41001@gmail.com`)
- Tiyakin na gumagamit ka ng App Password, hindi ng regular password
- I-verify na tama ang format ng app password (16 characters, walang spaces)

### Error: "Connection timeout"
- Check internet connection
- Tiyakin na hindi naka-block ang port 587
- Try port 465 with SSL instead (change `SMTP_PORT` to 465 and `ENCRYPTION_SMTPS`)

## Important Notes

- **Never commit** ang app password sa git repository
- Gumamit ng environment variables para sa production
- I-log ang errors sa file para sa debugging
- Ang verification code ay naka-save pa rin sa database kahit may email error - pwede mong gamitin ang "Resend" button

