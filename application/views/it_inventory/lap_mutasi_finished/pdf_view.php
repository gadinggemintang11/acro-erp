<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $title ?></title>
  <style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10pt; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #000; padding: 4px; }
    th { background: #ddd; }
    h3 { text-align: center; margin-bottom: 10px; }
  </style>
</head>
<body>
<h3><?= $title ?></h3>
<table>
  <thead>
    <tr>
      <th>Kode FG</th>
      <th>Nama FG</th>
      <th>Production No</th>
      <th>WO No</th>
      <th>Sales Order Number</th>
      <th>Material Request</th>
      <th>Material</th>
      <th>Base On</th>
      <th>Source Doc</th>
      <th>Supplier</th>
      <th>BC Type</th>
      <th>BC No</th>
      <th>Qty Issued</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($data as $r): ?>
    <tr>
      <td><?= $r->kode_fg ?></td>
      <td><?= $r->nama_fg ?></td>
      <td><?= $r->production_number ?></td>
      <td><?= $r->wo_number ?></td>
      <td><?= $r->sales_contract_number ?></td>
      <td><?= $r->material_request_number ?></td>
      <td><?= $r->nama_material ?></td>
      <td><?= $r->base_on_type ?></td>
      <td><?= $r->source_doc_number ?></td>
      <td><?= $r->supplier_name ?></td>
      <td><?= $r->bc_type ?></td>
      <td><?= $r->bc_number ?></td>
      <td style="text-align:right;"><?= $r->qty_issued ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</body>
</html>
