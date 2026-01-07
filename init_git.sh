#!/bin/bash

# Script Inisialisasi Git untuk ERP Mingbao
# Jalankan dengan: bash init_git.sh

echo "================================================"
echo "  Inisialisasi Git Repository - ERP Mingbao"
echo "================================================"
echo ""

# Cek apakah sudah ada .git
if [ -d ".git" ]; then
    echo "⚠️  Git repository sudah ada!"
    read -p "Ingin reset ulang? (y/n): " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        rm -rf .git
        echo "✅ Repository lama dihapus"
    else
        echo "❌ Dibatalkan"
        exit 1
    fi
fi

# Inisialisasi git
echo "🔧 Inisialisasi git repository..."
git init

if [ $? -ne 0 ]; then
    echo "❌ Gagal inisialisasi git!"
    exit 1
fi

echo "✅ Git repository berhasil dibuat"
echo ""

# Cek .gitignore
if [ ! -f ".gitignore" ]; then
    echo "⚠️  File .gitignore tidak ditemukan!"
    echo "   Silakan buat manual atau gunakan template"
else
    echo "✅ File .gitignore sudah ada"
fi
echo ""

# Add all files
echo "📦 Menambahkan file ke staging area..."
git add .

if [ $? -ne 0 ]; then
    echo "❌ Gagal add files!"
    exit 1
fi

# Show status
echo ""
echo "📊 Status git:"
git status --short
echo ""

# Commit
read -p "💬 Masukkan commit message [Initial commit]: " COMMIT_MSG
COMMIT_MSG=${COMMIT_MSG:-"Initial commit - ERP Mingbao"}

echo "💾 Melakukan commit..."
git commit -m "$COMMIT_MSG"

if [ $? -ne 0 ]; then
    echo "❌ Gagal commit!"
    exit 1
fi

echo "✅ Commit berhasil!"
echo ""

# Tanya apakah ingin setup remote
read -p "🌐 Ingin setup remote repository? (y/n): " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo ""
    echo "Pilih provider:"
    echo "  1) GitHub"
    echo "  2) GitLab"
    echo "  3) Bitbucket"
    echo "  4) Custom URL"
    read -p "Pilihan (1-4): " -n 1 -r PROVIDER
    echo ""
    echo ""
    
    read -p "📝 Masukkan repository URL: " REPO_URL
    
    if [ -n "$REPO_URL" ]; then
        echo "🔗 Menambahkan remote origin..."
        git remote add origin "$REPO_URL"
        
        if [ $? -ne 0 ]; then
            echo "❌ Gagal menambahkan remote!"
        else
            echo "✅ Remote berhasil ditambahkan"
            echo ""
            
            # Rename branch ke main
            echo "🔄 Rename branch ke 'main'..."
            git branch -M main
            
            read -p "🚀 Push ke remote sekarang? (y/n): " -n 1 -r
            echo ""
            if [[ $REPLY =~ ^[Yy]$ ]]; then
                echo "📤 Pushing ke remote..."
                git push -u origin main
                
                if [ $? -ne 0 ]; then
                    echo "❌ Gagal push!"
                    echo "   Coba manual: git push -u origin main"
                else
                    echo "✅ Push berhasil!"
                fi
            fi
        fi
    fi
fi

echo ""
echo "================================================"
echo "  ✅ Git Setup Selesai!"
echo "================================================"
echo ""
echo "Perintah git yang berguna:"
echo "  git status              - Cek status perubahan"
echo "  git add .               - Tambahkan semua perubahan"
echo "  git commit -m 'msg'     - Commit perubahan"
echo "  git push origin main    - Push ke remote"
echo "  git pull origin main    - Pull dari remote"
echo ""
echo "Remote repository:"
git remote -v
echo ""
