# Authentication & Security

Backender includes comprehensive security features for user authentication and account management.

## 🎯 Quick Reference

| Feature | Status | Details |
|---------|--------|---------|
| Email Authentication | ✅ | Required, validated format |
| Email Verification | ✅ | 24-hour token expiry |
| Password Complexity | ✅ | 8+ chars, upper, lower, number, special |
| Password Reset | ✅ | 1-hour token expiry |
| Session Management | ✅ | Secure cookie-based |
| 2FA | 🔮 | Planned for future |
| Rate Limiting | 🔮 | Planned for future |

## 🔐 Security Features

### Email-Based Authentication
- Users register with **email addresses** (not usernames)
- Email validation on registration
- Secure password storage using bcrypt

### Email Verification
- Users must verify their email before logging in
- Verification tokens expire after 24 hours
- Prevents fake account creation

### Password Complexity Requirements
Passwords must meet these requirements:
- **Minimum 8 characters**
- At least one **uppercase letter** (A-Z)
- At least one **lowercase letter** (a-z)
- At least one **number** (0-9)
- At least one **special character** (!@#$%^&* etc.)

### Password Reset
- Secure "forgot password" flow
- Reset tokens expire after 1 hour
- Tokens are single-use only
- Safe error messages that don't reveal if email exists

### Session Management
- Secure session-based authentication
- Sessions persist across requests
- Clean logout functionality

## 📧 Email Configuration

By default, Backender logs emails to `/app/storage/logs/emails.log` for development. This means you can test the system locally without configuring SMTP.

### Development Mode (Default)
```bash
# Emails are logged to file instead of sent
MAIL_DRIVER=log
```

Check verification/reset links in the log file:
```bash
docker exec backender cat /app/storage/logs/emails.log
```

### Production Mode with SMTP
Set these environment variables:

```bash
docker run -d -p 8080:80 \
  -e MAIL_DRIVER=smtp \
  -e MAIL_FROM_ADDRESS=noreply@yourdomain.com \
  -e MAIL_FROM_NAME="Your App Name" \
  -e APP_URL=https://yourdomain.com \
  -v $(pwd)/storage:/app/storage \
  --name backender backender
```

**Environment Variables:**
- `MAIL_DRIVER`: `log` (default) or `smtp`
- `MAIL_FROM_ADDRESS`: Sender email address
- `MAIL_FROM_NAME`: Sender name
- `APP_URL`: Your application URL (for email links)

## 🚀 User Flows

### 1. Registration (Setup)
```
1. User visits /setup (first run only)
2. Enters email and password (with complexity validation)
3. System creates account with unverified status
4. Verification email sent (logged or sent via SMTP)
5. User redirected to confirmation page
```

### 2. Email Verification
```
1. User receives verification email
2. Clicks verification link: /verify?token=xxxxx
3. System validates token (not expired, not already used)
4. Email marked as verified
5. User can now log in
```

### 3. Login
```
1. User visits /login
2. Enters email and password
3. System checks:
   - Email exists
   - Password correct
   - Email verified ✓
4. If all checks pass, user logged in
5. Redirected to dashboard
```

### 4. Forgot Password
```
1. User visits /forgot-password
2. Enters email address
3. System generates reset token (1 hour expiry)
4. Reset email sent (or logged)
5. Generic success message shown
```

### 5. Reset Password
```
1. User clicks reset link: /reset-password?token=xxxxx
2. System validates token (not expired)
3. User enters new password (with complexity validation)
4. Password updated, token invalidated
5. User can log in with new password
```

## 🔒 Database Schema

```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    email_verified_at DATETIME,
    verification_token TEXT,
    verification_expires DATETIME,
    reset_token TEXT,
    reset_expires DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

**Security Features:**
- Passwords are **bcrypt hashed** (never stored plain text)
- Tokens are **cryptographically random** (32 bytes)
- Expiration timestamps prevent token reuse
- Email uniqueness prevents duplicate accounts

## 🧪 Testing Locally

### Test Registration
```bash
# 1. Visit setup page
curl http://localhost:8080/setup

# 2. Create account
curl -X POST http://localhost:8080/setup \
  -d "email=test@example.com" \
  -d "password=SecurePass123!" \
  -d "password_confirm=SecurePass123!"

# 3. Check verification email
docker exec backender cat /app/storage/logs/emails.log

# 4. Extract token and verify
# Look for: /verify?token=xxxxxx
curl http://localhost:8080/verify?token=PASTE_TOKEN_HERE

# 5. Now you can log in
curl -X POST http://localhost:8080/login \
  -c cookies.txt \
  -d "email=test@example.com" \
  -d "password=SecurePass123!"
```

### Test Password Reset
```bash
# 1. Request reset
curl -X POST http://localhost:8080/forgot-password \
  -d "email=test@example.com"

# 2. Check reset email
docker exec backender cat /app/storage/logs/emails.log

# 3. Extract token and reset
# Look for: /reset-password?token=xxxxxx
curl -X POST "http://localhost:8080/reset-password?token=PASTE_TOKEN_HERE" \
  -d "password=NewPass456!" \
  -d "password_confirm=NewPass456!"
```

## 🎯 Best Practices

### For Development
1. Use `MAIL_DRIVER=log` (default)
2. Check `storage/logs/emails.log` for verification/reset links
3. Manually copy tokens for testing
4. Delete `storage/database/backender.sqlite` to reset

### For Production
1. Set `MAIL_DRIVER=smtp` with proper SMTP credentials
2. Use HTTPS (required for secure cookies)
3. Set `APP_URL` to your actual domain
4. Configure proper email service (SendGrid, Mailgun, etc.)
5. Consider adding rate limiting to prevent abuse
6. Backup `storage/database/backender.sqlite` regularly

## 🔮 Future Enhancements

### Two-Factor Authentication (2FA)
Coming soon! Will include:
- TOTP-based 2FA (Google Authenticator, Authy)
- QR code generation for setup
- Backup codes
- Optional enforcement

### Additional Security Features (Planned)
- Rate limiting on login attempts
- Account lockout after failed attempts
- Password expiration policies
- Session timeout configuration
- IP whitelisting
- Audit logs for authentication events

## 🆘 Troubleshooting

### "Please verify your email before logging in"
- Check `storage/logs/emails.log` for verification link
- Or manually update database:
```bash
docker exec -it backender sqlite3 /app/storage/database/backender.sqlite \
  "UPDATE users SET email_verified_at = datetime('now') WHERE email = 'your@email.com'"
```

### Expired Verification Token
- Request a new verification email (future feature)
- Or manually extend expiration:
```bash
docker exec -it backender sqlite3 /app/storage/database/backender.sqlite \
  "UPDATE users SET verification_expires = datetime('now', '+24 hours') WHERE email = 'your@email.com'"
```

### Lost Password Reset Token
- Request a new reset link at `/forgot-password`
- Tokens expire after 1 hour for security

### Can't Access Setup Page
- Setup is only available on first run
- If you need to recreate the admin account:
```bash
docker exec -it backender rm /app/storage/database/backender.sqlite
docker restart backender
```
