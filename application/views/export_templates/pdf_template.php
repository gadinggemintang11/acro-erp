
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $title ?></title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }
        .container {
            width: 100%;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
        }
        .header p {
            margin: 5px 0;
        }
        .content {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 10px;
        }
        .footer .page-number:before {
            content: "Page " counter(page);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= $title ?></h1>
            <?php if (isset($subtitle)): ?>
                <p><?= $subtitle ?></p>
            <?php endif; ?>
            <p>Periode: <?= isset($start_date) ? date("d M Y", strtotime($start_date)) . ' - ' . date("d M Y", strtotime($end_date)) : date("d M Y", strtotime($as_of_date)) ?></p>
        </div>

        <div class="content">
            <?= $content ?>
        </div>

        <div class="footer">
            <p>Dicetak pada: <?= date("d M Y H:i:s") ?> - <span class="page-number"></span></p>
        </div>
    </div>
</body>
</html>
