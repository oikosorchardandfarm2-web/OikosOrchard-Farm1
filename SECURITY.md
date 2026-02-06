# Security Implementation Guide - Oikos Orchard & Farm

## Security Measures Implemented

### 1. **File & Directory Protection (.htaccess)**
- ✅ Disabled directory listing (prevents browsing folders)
- ✅ Blocked direct access to sensitive files (.php, .json, .env, .log, .txt)
- ✅ Protected `/config/` directory from web access
- ✅ Prevented access to common exploit paths (etc/passwd, boot.ini, etc.)
- ✅ Blocked SQL injection attempts via URL parameters
- ✅ Enabled HTTP compression and caching

### 2. **PHP Security Headers**
- ✅ `X-Content-Type-Options: nosniff` - Prevents MIME type sniffing
- ✅ `X-Frame-Options: SAMEORIGIN` - Prevents clickjacking attacks
- ✅ `X-XSS-Protection: 1` - Enables XSS protection in older browsers
- ✅ `Content-Security-Policy` - Restricts script/style/resource loading
- ✅ `Referrer-Policy` - Controls how referrer info is shared
- ✅ `Permissions-Policy` - Disables geolocation, microphone, camera access

### 3. **Hidden Server Information**
- ✅ Server version hidden from HTTP headers
- ✅ PHP version hidden
- ✅ Custom `X-Powered-By` header instead of revealing PHP

### 4. **Session Security** (security.php)
- ✅ HttpOnly cookies - Prevents JavaScript access to session cookies
- ✅ Secure flag - Cookies only sent over HTTPS
- ✅ SameSite=Strict - Prevents CSRF attacks
- ✅ Session ID regeneration - Prevents session fixation attacks

### 5. **Input Validation**
- ✅ Email validation using `filter_var()`
- ✅ Phone number validation with regex
- ✅ HTML escaping with `htmlspecialchars()`
- ✅ UTF-8 encoding enforcement
- ✅ Message length limits (160 chars for SMS)

### 6. **Rate Limiting**
- ✅ Prevents brute force attacks
- ✅ Limits API requests per time window
- ✅ Tracks requests by IP address

### 7. **Logging & Monitoring**
- ✅ Security events logged to `/logs/security.log`
- ✅ Contact submissions logged with timestamp
- ✅ Failed attempts tracked by IP address
- ✅ Error logs separated from access logs

### 8. **Data Protection**
- ✅ `.gitignore` prevents committing sensitive files
- ✅ Config files never exposed in version control
- ✅ API keys and tokens protected
- ✅ Log files excluded from git

### 9. **Anti-Hotlinking**
- ✅ Prevents image/video theft from other websites
- ✅ Only serves media to your domain

---

## Critical Files Added

### `/config/security.php`
Central security configuration loaded by all PHP files. Includes:
- Header security
- Session security
- Helper functions for validation
- Rate limiting
- CSRF token generation

### `/.htaccess`
Apache security rules for:
- File protection
- SQL injection prevention
- Exploit blocking
- Performance optimization

### `/.gitignore`
Protects sensitive files from being committed to git

---

## Important Security Reminders

### ⚠️ Production Deployment (CRITICAL)
When moving to production:

1. **Enable HTTPS/SSL Certificate**
   - Use Let's Encrypt (free) or Cloudflare SSL
   - Change all URLs from `http://` to `https://`
   - Update Twilio credentials for HTTPS

2. **Update Database Credentials**
   - Change all default passwords
   - Use strong, unique credentials
   - Rotate credentials periodically

3. **Disable Debug Mode**
   - In `security.php`, ensure `display_errors = 0` for non-localhost
   - Enable proper logging instead

4. **Set Proper File Permissions**
   ```bash
   chmod 755 /config/
   chmod 644 /config/*.php
   chmod 755 /logs/
   chmod 600 /config/twilio-config.php
   ```

5. **Regular Backups**
   - Back up config files separately
   - Store credentials in password manager
   - Never commit .env files to git

6. **Monitoring**
   - Check `/logs/security.log` regularly
   - Monitor rate limiting hits
   - Set up alerts for suspicious activity

### 🔒 Credentials Management
**NEVER commit these files:**
- `config/twilio-config.php`
- `config/gmail-config.php`
- `firebase-config.json`
- Any `.env` files

**Instead:**
1. Use environment variables
2. Store in a `.env` file (excluded from git)
3. Load credentials at runtime from secure location

### 📝 Regular Security Checks
- [ ] Update PHP to latest version
- [ ] Review Apache logs for suspicious activity
- [ ] Check `/logs/security.log` for attacks
- [ ] Audit database access logs
- [ ] Review uploaded files/submissions
- [ ] Test form inputs with injection attempts
- [ ] Validate all external API calls (Twilio, Gmail, Google Sheets)

---

## Files Now Protected

| File/Folder | Protection | Method |
|---|---|---|
| `/config/` | Blocked from web access | .htaccess |
| `twilio-config.php` | Not in git, blocked access | .gitignore + .htaccess |
| `gmail-config.php` | Not in git, blocked access | .gitignore + .htaccess |
| `firebase-config.json` | Not in git, blocked access | .gitignore + .htaccess |
| `*.log` | Not in git, blocked from web | .gitignore + .htaccess |
| `.env` files | Not in git | .gitignore |
| Source code | Directory listing disabled | .htaccess |
| Sessions | Secured with HttpOnly + Secure flags | security.php |

---

## Testing Security

### Test 1: Verify Protected Files Return 403
Try accessing these in browser (should get 403 Forbidden):
```
http://localhost/OikosOrchardandFarm/config/twilio-config.php
http://localhost/OikosOrchardandFarm/api/contact-log.txt
```

### Test 2: Verify Directory Listing Disabled
Try accessing this (should get 403 or redirect):
```
http://localhost/OikosOrchardandFarm/api/
```

### Test 3: Check Security Headers
Use online tool or curl:
```bash
curl -I http://localhost/OikosOrchardandFarm/
```
Should show security headers like:
- X-Content-Type-Options: nosniff
- X-Frame-Options: SAMEORIGIN
- X-XSS-Protection: 1

### Test 4: Verify Input Validation
Try submitting contact form with:
- Invalid email: "notanemail"
- Message > 160 chars (should be rejected)
- SQL injection in message: `'; DROP TABLE--`

---

## Next Steps

1. **Monitor Logs**: Check `/logs/security.log` for any suspicious activity
2. **Test Forms**: Ensure forms still work with new security measures
3. **Before Production**: 
   - Install SSL certificate
   - Migrate to production server with HTTPS
   - Update all configuration files
   - Run security audit
4. **Regular Maintenance**:
   - Keep PHP updated
   - Review logs weekly
   - Update security rules as needed

---

## Questions?

For security questions or concerns, check:
- `/config/security.php` - Main security functions
- `/.htaccess` - Server-level rules
- Apache error logs: `C:\xampp\apache\logs\error.log`
- PHP error logs: `C:\xampp\logs\php-errors.log` (if enabled)
