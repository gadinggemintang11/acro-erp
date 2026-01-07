<?php
$host = 'localhost';
$user = 'canmaxid_admin';
$pass = 'Forever2023'; // ganti bila sudah reset
$db   = 'canmaxid_mingbao';

$mysqli = @new mysqli($host, $user, $pass, $db);

if ($mysqli->connect_errno) {
    echo "CONNECT ERROR: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    exit;
}
echo "Connected OK — MySQL server version: " . $mysqli->server_info;
$mysqli->close();
