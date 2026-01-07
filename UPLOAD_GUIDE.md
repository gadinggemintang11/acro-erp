# Upload Guide untuk Work Order Update

## File yang Perlu Diupload ke Server Production:

1. **Model:**
   - `application/models/Wo_model.php`

2. **Controller:**
   - `application/controllers/Work_order.php`

3. **View:**
   - `application/views/work_order/index.php`

## Cara Upload via FTP/SFTP:

### Opsi 1: FileZilla
1. Buka FileZilla
2. Connect ke server: mingbaolianchuangint.com
3. Navigate ke folder: `/home/canmaxid/mingbaolianchuangint.com/`
4. Upload file-file di atas sesuai strukturnya

### Opsi 2: Via Terminal (jika punya akses SSH)
```bash
# Upload Wo_model.php
scp application/models/Wo_model.php canmaxid@mingbaolianchuangint.com:/home/canmaxid/mingbaolianchuangint.com/application/models/

# Upload Work_order.php
scp application/controllers/Work_order.php canmaxid@mingbaolianchuangint.com:/home/canmaxid/mingbaolianchuangint.com/application/controllers/

# Upload index.php view
scp application/views/work_order/index.php canmaxid@mingbaolianchuangint.com:/home/canmaxid/mingbaolianchuangint.com/application/views/work_order/
```

### Opsi 3: Via cPanel File Manager
1. Login ke cPanel
2. Buka File Manager
3. Navigate ke folder yang sesuai
4. Upload file satu per satu

## Verifikasi Setelah Upload:
1. Refresh halaman Work Order
2. Cek apakah error "Call to undefined method Wo_model::count_all()" hilang
3. Test fitur search dan pagination

## Catatan Penting:
- Pastikan permission file tetap 644
- Backup file lama sebelum replace
- Clear cache browser setelah upload
