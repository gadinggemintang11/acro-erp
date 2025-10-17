<?php
// tools/reset_admin_password.php
// Jalankan file ini sekali saja lewat browser atau CLI
define('BASEPATH', true);
require_once __DIR__ . '/../index.php';

$CI =& get_instance();
$CI->load->database();

$username = 'admin';
$new_password = 'admin123';
$hash = password_hash($new_password, PASSWORD_DEFAULT);

$exists = $CI->db->get_where('users', ['username' => $username])->row();

if ($exists) {
    $CI->db->where('username', $username)->update('users', ['password' => $hash, 'status' => 'active']);
    echo "✅ Password untuk user '{$username}' berhasil di-reset ke '{$new_password}' (bcrypt)\n";
} else {
    $CI->db->insert('users', [
        'username' => $username,
        'password' => $hash,
        'full_name' => 'Administrator',
        'role_id' => 1,
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s')
    ]);
    echo "✅ User '{$username}' berhasil dibuat dengan password '{$new_password}' (bcrypt)\n";
}

echo "\nSelesai. Sekarang login di http://localhost/neokaber/auth pakai:\n";
echo "Username: admin\n";
echo "Password: admin123\n";
