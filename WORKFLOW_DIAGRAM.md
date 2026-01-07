# 🗺️ Workflow Diagram - Push ke cPanel

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         SETUP PERTAMA KALI                               │
└─────────────────────────────────────────────────────────────────────────┘

┌──────────────────┐
│   LOCAL (Mac)    │
│   XAMPP          │
└────────┬─────────┘
         │
         │ 1. git init
         │ 2. git add .
         │ 3. git commit
         │
         ▼
┌────────────────────┐
│  ./init_git.sh     │  ← Script otomatis
└────────┬───────────┘
         │
         │ 4. git remote add origin URL
         │ 5. git push -u origin main
         │
         ▼
┌──────────────────────────┐
│   GitHub / GitLab        │
│   Repository Online      │  ← Code disimpan di cloud
└──────────┬───────────────┘
           │
           │ Clone repository
           │
           ▼
    ┌─────────────────────┐
    │   cPanel Server     │
    │   Production        │
    └─────────────────────┘
           │
           ├─→ Git Version Control (Clone)
           ├─→ MySQL Database (Setup)
           ├─→ File Manager (Buat .env)
           ├─→ Permissions (Set 777)
           └─→ Test (https://domain.com)
```

---

```
┌─────────────────────────────────────────────────────────────────────────┐
│                      WORKFLOW UPDATE CODE                                │
└─────────────────────────────────────────────────────────────────────────┘

LOCAL (Development)                    GITHUB                    CPANEL (Production)
──────────────────                     ──────                    ───────────────────

1. Edit code                                                     
   ├─ index.php                                                  
   ├─ controllers/                                               
   └─ models/                                                    
                                                                 
2. Test di local                                                 
   http://localhost/erp_mingbao/                                 
                                                                 
3. Git commands:                                                 
   $ git add .                                                   
   $ git commit -m "msg"                                         
   $ git push origin main  ──────────▶  Repository Updated       
                                        ├─ main branch           
                                        ├─ commit history        
                                        └─ all files                          
                                                 │               
                                                 │ Pull/Deploy   
                                                 │               
                                                 ▼               
                                                          cPanel Git Control
                                                          ├─ Manage
                                                          ├─ Pull or Deploy
                                                          └─ Update from Remote
                                                                 │
                                                                 ▼
                                                          Files Updated! ✅
                                                          Test: https://domain.com
```

---

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    FILE STRUCTURE & .env                                 │
└─────────────────────────────────────────────────────────────────────────┘

LOCAL                                    CPANEL
─────                                    ──────

.env ────────────┐                       .env ────────────┐
                 │                                        │
┌────────────────┴───────────────┐      ┌────────────────┴────────────────┐
│ CI_ENVIRONMENT=development     │      │ CI_ENVIRONMENT=production       │
│ DB_USERNAME=root               │      │ DB_USERNAME=cpanel_user_erp     │
│ DB_PASSWORD=                   │      │ DB_PASSWORD=Strong_Pass_123     │
│ DB_DATABASE=erp_mingbao_local  │      │ DB_DATABASE=cpanel_user_mingbao │
│ APP_BASE_URL=http://localhost/ │      │ APP_BASE_URL=https://domain.com/│
│ DB_DEBUG=true                  │      │ DB_DEBUG=false                  │
└────────────────────────────────┘      └─────────────────────────────────┘
         │                                       │
         │ TIDAK di-commit ke Git!               │ Upload manual!
         │                                       │
         ▼                                       ▼
     .gitignore ────────────▶             .env (diabaikan Git)
     (exclude .env)


SAMA CODEBASE, BEDA KONFIGURASI! 🎉
```

---

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    GIT COLLABORATION FLOW                                │
└─────────────────────────────────────────────────────────────────────────┘

Developer 1 (Local)          GitHub/GitLab           Developer 2 / Server
───────────────────          ─────────────           ────────────────────

git clone URL                                        git clone URL
      │                                                    │
      ▼                                                    ▼
Edit code                                            Edit code
      │                                                    │
      ▼                                                    ▼
git add .                                            git add .
git commit -m "msg"                                  git commit -m "msg"
      │                                                    │
      ├──────────▶ git push ──────────▶ Repository ◀──────┤
      │                                    │               │
      │                                    │               │
      │           ◀────── git pull ────────┤               │
      │                                                    │
      └──────────────────── Sync ─────────────────────────┘

Semua developer & server bisa sync code dengan mudah!
```

---

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         SECURITY LAYERS                                  │
└─────────────────────────────────────────────────────────────────────────┘

PUBLIC (Di Git)                          PRIVATE (Tidak di Git)
───────────────                          ──────────────────────

✅ Source code                           ❌ .env file
✅ Controllers                           ❌ Passwords
✅ Models                                ❌ API Keys
✅ Views                                 ❌ Database credentials
✅ Libraries                             ❌ Secret tokens
✅ .env.example (template)               
✅ .gitignore                            
                                         ▲
                                         │
                                    Protected by
                                    .gitignore
                                         │
┌────────────────────────────────────────┴──────────────────────────┐
│ # .gitignore                                                      │
│ .env                    ← File ini diabaikan Git                  │
│ .env.local                                                        │
│ .env.production                                                   │
└───────────────────────────────────────────────────────────────────┘

CREDENTIALS AMAN! ✅
```

---

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    DEPLOYMENT COMPARISON                                 │
└─────────────────────────────────────────────────────────────────────────┘

┌──────────────────────┐              ┌──────────────────────┐
│   Git Method         │              │   Manual FTP         │
├──────────────────────┤              ├──────────────────────┤
│                      │              │                      │
│ Setup: 30 min        │              │ Setup: 15 min        │
│ Update: 2 min ⚡     │              │ Update: 15 min 🐌   │
│ Rollback: Easy ✅    │              │ Rollback: Hard ❌    │
│ Team: Easy ✅        │              │ Team: Hard ❌        │
│ History: Yes ✅      │              │ History: No ❌       │
│ Professional: ⭐⭐⭐⭐⭐│              │ Professional: ⭐⭐    │
│                      │              │                      │
│ RECOMMENDED! 🎯      │              │ Backup only          │
└──────────────────────┘              └──────────────────────┘
```

---

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         TIMELINE EXAMPLE                                 │
└─────────────────────────────────────────────────────────────────────────┘

Hari 1: Setup Awal
────────────────────────────────────────────────────────────────
09:00  │ Init Git local
09:10  │ Push ke GitHub
09:20  │ Setup database cPanel
09:30  │ Clone di cPanel
09:40  │ Buat .env production
09:50  │ Set permissions & test
10:00  │ ✅ Deploy selesai!
       │
────────────────────────────────────────────────────────────────

Hari 2-∞: Update Rutin
────────────────────────────────────────────────────────────────
       │ Edit code, test local
       │ git add, commit, push (1 min)
       │ cPanel pull (1 min)
       │ ✅ Update production!
       │
       │ ... cycle repeat ...
       │
────────────────────────────────────────────────────────────────

EFISIEN & MUDAH! 🚀
```

---

**📖 Lihat panduan lengkap di:**
- `PUSH_TO_CPANEL.md` - Panduan detail step-by-step
- `PUSH_QUICK_REF.md` - Quick reference card

