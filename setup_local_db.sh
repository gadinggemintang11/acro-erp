#!/bin/bash

# Script Setup Database Local untuk ERP NSI
# Jalankan dengan: bash setup_local_db.sh

echo "================================================"
echo "  Setup Database Local - ERP NSI"
echo "================================================"
echo ""

# Konfigurasi
DB_NAME="erp_mingbao_local"
DB_USER="root"
DB_PASS=""
DB_HOST="localhost"

# Tentukan file SQL yang akan diimport
SQL_FILE="db/neokabersis_db.sql"

if [ ! -f "$SQL_FILE" ]; then
    SQL_FILE="db_neokaber.sql"
    if [ ! -f "$SQL_FILE" ]; then
        echo "❌ Error: File SQL tidak ditemukan!"
        echo "   Pastikan file db/neokabersis_db.sql atau db_neokaber.sql ada"
        exit 1
    fi
fi

echo "📁 File SQL yang akan digunakan: $SQL_FILE"
echo ""

# Cek apakah MySQL running
if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL command tidak ditemukan!"
    echo "   Pastikan XAMPP MySQL sudah running"
    exit 1
fi

# Cek koneksi MySQL
echo "🔍 Memeriksa koneksi MySQL..."
if ! mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1" &> /dev/null; then
    echo "❌ Tidak bisa connect ke MySQL!"
    echo "   Pastikan XAMPP MySQL sudah running"
    exit 1
fi

echo "✅ Koneksi MySQL OK"
echo ""

# Drop database jika sudah ada (opsional)
read -p "⚠️  Database '$DB_NAME' akan dibuat ulang. Lanjutkan? (y/n): " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Dibatalkan"
    exit 1
fi

# Drop dan create database
echo "🗑️  Menghapus database lama (jika ada)..."
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "DROP DATABASE IF EXISTS $DB_NAME" 2>/dev/null

echo "📦 Membuat database baru: $DB_NAME"
mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8 COLLATE utf8_general_ci"

if [ $? -ne 0 ]; then
    echo "❌ Gagal membuat database!"
    exit 1
fi

echo "✅ Database berhasil dibuat"
echo ""

# Import SQL file
echo "📥 Mengimport data dari $SQL_FILE..."
echo "   (Mohon tunggu, proses ini bisa memakan waktu...)"

mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$SQL_FILE"

if [ $? -ne 0 ]; then
    echo "❌ Gagal mengimport database!"
    exit 1
fi

echo "✅ Import database berhasil!"
echo ""

# Tampilkan informasi
echo "================================================"
echo "  ✅ Setup Database Selesai!"
echo "================================================"
echo ""
echo "Konfigurasi:"
echo "  Database: $DB_NAME"
echo "  Host:     $DB_HOST"
echo "  User:     $DB_USER"
echo "  Password: (kosong)"
echo ""
echo "Langkah selanjutnya:"
echo "  1. Pastikan Apache & MySQL XAMPP running"
echo "  2. Akses: http://localhost/erp_mingbao/"
echo ""
echo "Jika butuh reset password admin:"
echo "  - Akses: http://localhost/erp_mingbao/tools/reset_admin_password.php"
echo ""
