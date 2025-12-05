# Panduan Implementasi Multi-Language

## Struktur File

Sistem multi-language telah diimplementasikan dengan struktur:

```
application/
├── language/
│   ├── indonesian/
│   │   └── app_lang.php
│   ├── english/
│   │   └── app_lang.php
│   └── chinese/
│       └── app_lang.php
├── helpers/
│   └── language_helper.php
└── core/
    └── MY_Controller.php (updated)
```

## Cara Menggunakan di View

### 1. Menggunakan Helper Function
```php
<?= lang_line('key_bahasa', 'Default Text'); ?>
```

Contoh:
```php
<h1><?= lang_line('menu_dashboard'); ?></h1>
<button><?= lang_line('btn_save'); ?></button>
```

### 2. Menggunakan CI Language Library
```php
<?= $this->lang->line('key_bahasa'); ?>
```

## Cara Menambah Terjemahan Baru

### 1. Edit file bahasa di `application/language/{bahasa}/app_lang.php`

**Indonesian:**
```php
$lang['my_new_key'] = 'Teks dalam Bahasa Indonesia';
```

**English:**
```php
$lang['my_new_key'] = 'Text in English';
```

**Chinese:**
```php
$lang['my_new_key'] = '中文文本';
```

### 2. Gunakan di View:
```php
<?= lang_line('my_new_key'); ?>
```

## Fitur yang Tersedia

1. **Dropdown Bahasa di Topbar**
   - Indonesia 🇮🇩
   - English 🇺🇸
   - 中文 🇨🇳

2. **Auto-save Preferensi**
   - Session
   - Cookie (30 hari)

3. **Helper Functions**
   - `lang_line($key, $default)` - Get translated text
   - `get_current_language()` - Get active language
   - `get_language_name($code)` - Get language display name
   - `get_available_languages()` - Get all available languages

## Contoh Implementasi di Controller

```php
class My_controller extends MY_Controller {
    
    public function index() {
        $data['title'] = $this->lang('menu_dashboard', 'Dashboard');
        
        // atau langsung di view
        $this->load->view('my_view', $data);
    }
}
```

## Contoh Implementasi di View Baru

```php
<div class="page-header">
    <h1><?= lang_line('menu_production'); ?></h1>
</div>

<button class="btn btn-primary">
    <i class="fas fa-save"></i>
    <?= lang_line('btn_save'); ?>
</button>

<table>
    <thead>
        <tr>
            <th><?= lang_line('label_no'); ?></th>
            <th><?= lang_line('label_code'); ?></th>
            <th><?= lang_line('label_name'); ?></th>
            <th><?= lang_line('label_action'); ?></th>
        </tr>
    </thead>
</table>
```

## Keys yang Sudah Tersedia

### Menu
- menu_dashboard, menu_master, menu_inventory, menu_production, menu_sales, menu_purchasing, menu_accounting, menu_reports, menu_settings, menu_users, menu_log_activity

### Buttons
- btn_add, btn_edit, btn_delete, btn_save, btn_cancel, btn_back, btn_search, btn_reset, btn_export, btn_import, btn_print, btn_refresh, btn_detail, btn_view, btn_approve, btn_reject, btn_submit, btn_close

### Labels
- label_no, label_code, label_name, label_description, label_status, label_date, label_time, label_action, label_remarks, label_created_by, label_created_at, label_updated_by, label_updated_at, label_total, label_qty, label_unit, label_price, label_amount

### Status
- status_draft, status_pending, status_approved, status_rejected, status_completed, status_cancelled, status_active, status_inactive

### Messages
- msg_success_add, msg_success_update, msg_success_delete, msg_error, msg_no_data, msg_confirm_delete, msg_loading

## Tips

1. **Selalu gunakan key yang konsisten** untuk mudah maintenance
2. **Tambahkan default text** sebagai fallback
3. **Group keys by module** untuk organisasi yang baik
4. **Test di semua bahasa** sebelum deploy

## Troubleshooting

**Bahasa tidak berubah?**
- Clear browser cache dan cookies
- Check session aktif
- Pastikan file language ada di folder yang benar

**Text masih hardcode?**
- Ganti text hardcode dengan `lang_line('key')`
- Pastikan key ada di semua file bahasa
