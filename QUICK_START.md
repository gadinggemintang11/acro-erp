# 🚀 Quick Start - ERP Mingbao

## Setup Local dalam 5 Menit

### 1. Database
```bash
# Buka phpMyAdmin: http://localhost/phpmyadmin
# - Buat database baru: erp_mingbao_local
# - Import file: db/neokabersis_db.sql (atau db_neokaber.sql)
```

### 2. Environment Config (.env)
```bash
# File .env sudah dibuat otomatis dengan config local
# Cek isinya:
cat .env

# Sudah berisi:
# DB_USERNAME=root
# DB_PASSWORD=
# DB_DATABASE=erp_mingbao_local
# APP_BASE_URL=http://localhost/erp_mingbao/

# Jika perlu edit:
nano .env
```

### 3. Jalankan
```bash
# Start XAMPP Apache & MySQL
# Akses: http://localhost/erp_mingbao/
```

---

## Push ke cPanel dalam 3 Langkah

### 1. Setup Git (Pertama kali)
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/erp_mingbao
./init_git.sh
# Script akan guide Anda step-by-step
```

### 2. Connect ke GitHub/GitLab
```bash
# Buat repository di GitHub/GitLab
# Lalu jalankan:
git remote add origin https://github.com/USERNAME/REPO.git
git branch -M main
git push -u origin main
```

### 3. Deploy di cPanel
```
1. Login cPanel → MySQL Database Wizard
   - Buat database & user (CATAT credentials!)
   - phpMyAdmin → Import database

2. Git Version Control → Create
   - Clone URL: https://github.com/USERNAME/REPO.git
   - Path: /home/username/public_html
   
3. File Manager → Buat file .env
   - Copy dari .env.production
   - Edit dengan kredensial cPanel
   
4. Set Permissions
   - application/cache → 777
   - application/logs → 777
   
5. Test: https://yourdomain.com
```

**📖 Panduan lengkap push:** `PUSH_TO_CPANEL.md`

**⚡ Quick reference:** `PUSH_QUICK_REF.md`

**🗺️ Visual diagram:** `WORKFLOW_DIAGRAM.md`

---

## Update Code (Workflow Harian)

### Local → GitHub:
```bash
git add .
git commit -m "Pesan perubahan"
git push origin main
```

### GitHub → cPanel:
---

**Panduan lengkap:**
- `ENV_GUIDE.md` - 🔐 Panduan Environment Variables (.env)
- `LOCAL_SETUP_GUIDE.md` - Setup local detail
- `DEPLOYMENT_CHECKLIST.md` - Checklist deployment
- `README_DEPLOYMENT.md` - Overview lengkap

Atau via SSH:
```bash
ssh user@domain.com
cd ~/public_html
git pull origin main
```

---

**Lihat panduan lengkap di:** `LOCAL_SETUP_GUIDE.md`
