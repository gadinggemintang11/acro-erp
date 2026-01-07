# ⚡ Quick Reference - Push ke cPanel

## 🎯 Setup Pertama Kali (30 menit)

### 1️⃣ Git Local
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/erp_mingbao
./init_git.sh
# Atau manual: git init && git add . && git commit -m "Initial"
```

### 2️⃣ GitHub/GitLab
```
1. Buka github.com → Login
2. New Repository → erp-mingbao
3. JANGAN init with README
4. Create → Copy URL
```

### 3️⃣ Push ke GitHub
```bash
git remote add origin https://github.com/USERNAME/erp-mingbao.git
git branch -M main
git push -u origin main
```

### 4️⃣ Setup cPanel Database
```
cPanel → MySQL Database Wizard
→ Create DB: mingbao
→ Create User: admin_erp + password (CATAT!)
→ Add to DB: ALL PRIVILEGES
→ phpMyAdmin → Import → db/neokabersis_db.sql
```

### 5️⃣ Clone di cPanel
```
cPanel → Git Version Control → Create
→ Clone URL: https://github.com/USERNAME/erp-mingbao.git
→ Path: /home/username/public_html
→ Create
```

### 6️⃣ Buat .env Production
```
File Manager → public_html → + File → .env
Edit dengan:

CI_ENVIRONMENT=production
APP_BASE_URL=https://yourdomain.com/
DB_USERNAME=username_admin_erp
DB_PASSWORD=your_password_here
DB_DATABASE=username_mingbao
DB_DEBUG=false
DISPLAY_ERRORS=false
```

### 7️⃣ Set Permissions
```
File Manager:
→ application/cache → 777
→ application/logs → 777
→ uploads → 777 (jika ada)
```

### 8️⃣ Test
```
https://yourdomain.com → Login → ✅
```

---

## 🔄 Update Code (2 menit)

### Local → GitHub
```bash
# Edit code → Test
git add .
git commit -m "Update feature X"
git push origin main
```

### GitHub → cPanel
```
cPanel → Git Version Control
→ Manage
→ Pull or Deploy
→ Update from Remote
→ ✅ Done!
```

---

## 🆘 Troubleshooting

### Push ditolak
```bash
git pull origin main --rebase
git push origin main
```

### .env tidak terbaca
```bash
# Cek file ada
ls -la .env

# Set permission
chmod 644 .env
```

### Database error
```bash
# Debug .env values
echo getenv('DB_DATABASE');
echo getenv('DB_USERNAME');
```

---

## 📱 Contact Info cPanel

Simpan info ini:
```
cPanel URL: https://yourdomain.com:2083
Username: 
Password: 
FTP Host: ftp.yourdomain.com
SSH: ssh username@yourdomain.com
```

---

**📖 Panduan Lengkap:** `PUSH_TO_CPANEL.md`

