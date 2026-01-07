<!DOCTYPE html>
<html>
<head>
    <title><?= $title; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h1><?= $title; ?></h1>
    <table border="0">
        <tr>
            <td><strong>Kode Material:</strong></td>
            <td><?= $detail['kode_material']; ?></td>
        </tr>
        <tr>
            <td><strong>Nama Material:</strong></td>
            <td><?= $detail['nama_material']; ?></td>
        </tr>
        <tr>
            <td><strong>Tanggal Transaksi:</strong></td>
            <td><?= $detail['tanggal_transaksi']; ?></td>
        </tr>
         <tr>
            <td><strong>Jenis Transaksi:</strong></td>
            <td><?= $detail['jenis_transaksi']; ?></td>
        </tr>
    </table>
</body>
</html>