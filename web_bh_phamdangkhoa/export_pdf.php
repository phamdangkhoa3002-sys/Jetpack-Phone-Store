<?php
require_once __DIR__ . '/cons.php';

try {
    // Get all phone products from MongoDB
    $products = $hanghoa->find(
        [],
        ['sort' => ['_id' => 1]]
    )->toArray();

} catch (Throwable $e) {
    die("Không thể lấy dữ liệu sản phẩm từ MongoDB.");
}

/* Escape HTML */
function pdf_h($value): string {
    return htmlspecialchars(
        (string)$value,
        ENT_QUOTES,
        'UTF-8'
    );
}

/* Format price */
function pdf_money($value): string {
    return number_format((float)$value, 0, ',', '.') . ' VNĐ';
}

/* Format MongoDB date */
function pdf_date($value): string {
    if ($value instanceof MongoDB\BSON\UTCDateTime) {
        return $value->toDateTime()
            ->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))
            ->format('d/m/Y H:i');
    }

    return '';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Xuất dữ liệu sản phẩm PDF | Jetpack Store</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #0f0f0f;
            color: #f5f5f5;
        }

        /* Header */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            padding: 18px 5%;
            background: #151515;
            border-bottom: 2px solid #ff6b00;
        }

        .header h1 {
            margin: 0;
            color: #ff8a36;
            font-size: 25px;
        }

        .header a {
            color: #fff;
            text-decoration: none;
            font-weight: bold;
        }

        .header a:hover {
            color: #ff8a36;
        }

        /* Main container */
        .container {
            width: 95%;
            max-width: 1600px;
            margin: 35px auto;
            padding: 0 10px;
        }

        .page-title {
            margin: 0 0 10px;
            color: #fff;
            font-size: 30px;
        }

        .page-description {
            margin-bottom: 25px;
            color: #aaa;
            line-height: 1.6;
        }

        /* Report section */
        .content-section {
            width: 100%;
            padding: 25px;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
        }

        .content-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }

        .content-heading h2 {
            margin: 0;
            color: #ff8a36;
            font-size: 23px;
        }

        /* Toolbar */
        .toolbar {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 25px;
        }

        .back-button,
        .print-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 18px;
            border: none;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }

        .back-button {
            color: #fff;
            background: #333;
        }

        .back-button:hover {
            color: #fff;
            background: #444;
        }

        .print-button {
            color: #111;
            background: #ff8a36;
        }

        .print-button:hover {
            background: #ffb347;
            transform: translateY(-1px);
        }

        /* Report heading */
        .report-heading {
            margin-bottom: 25px;
            padding: 22px;
            text-align: center;
            background: #202020;
            border: 1px solid #383838;
            border-radius: 10px;
        }

        .report-heading h1 {
            margin: 0 0 10px;
            color: #ff8a36;
            font-size: 27px;
            line-height: 1.4;
        }

        .report-heading p {
            margin: 0;
            color: #aaa;
            line-height: 1.6;
        }

        /* Total products */
        .summary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0 0 20px;
            padding: 12px 16px;
            color: #fff;
            background: #242424;
            border-left: 4px solid #ff6b00;
            border-radius: 5px;
            font-weight: bold;
        }

        .summary strong {
            color: #ff8a36;
            font-size: 18px;
        }

        /* Table */
        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #383838;
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        th,
        td {
            padding: 13px 10px;
            border-bottom: 1px solid #383838;
            text-align: left;
            vertical-align: middle;
            overflow-wrap: anywhere;
        }

        th {
            color: #ffb347;
            background: #252525;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        td {
            color: #ddd;
            background: #1a1a1a;
            line-height: 1.5;
        }

        tbody tr:nth-child(even) td {
            background: #202020;
        }

        tbody tr:hover td {
            background: #29231f;
        }

        tbody tr:last-child td {
            border-bottom: none;
        }

        .number {
            text-align: right;
            white-space: nowrap;
        }

        .product-image {
            width: 65px;
            height: 65px;
            object-fit: contain;
            background: #fff;
            border-radius: 6px;
            padding: 4px;
        }

        /* Footer */
        .footer {
            margin-top: 25px;
            padding: 20px;
            text-align: center;
            color: #999;
            background: #151515;
            border-top: 1px solid #333;
            line-height: 1.6;
        }

        /* Mobile */
        @media (max-width: 700px) {
            .header {
                flex-direction: column;
                text-align: center;
            }

            .container {
                width: 100%;
                margin: 22px auto;
                padding: 0 12px;
            }

            .content-section {
                padding: 15px;
            }

            .page-title {
                font-size: 25px;
            }

            .content-heading h2 {
                font-size: 20px;
            }

            .report-heading {
                padding: 16px;
            }

            .report-heading h1 {
                font-size: 22px;
            }

            .toolbar {
                align-items: stretch;
            }

            .back-button,
            .print-button {
                flex: 1;
            }
        }

        /* PDF / Print */
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        @media print {
            body {
                padding: 0;
                color: #222;
                background: #fff;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .header,
            .toolbar,
            .page-title,
            .page-description,
            .footer {
                display: none !important;
            }

            .container {
                width: 100%;
                max-width: none;
                margin: 0;
                padding: 0;
            }

            .content-section {
                padding: 0;
                background: #fff;
                border: none;
                border-radius: 0;
                box-shadow: none;
            }

            .content-heading {
                margin-bottom: 12px;
            }

            .content-heading h2 {
                color: #222;
                font-size: 18px;
            }

            .report-heading {
                margin-bottom: 15px;
                padding: 12px;
                background: #fff;
                border: 1px solid #ccc;
                border-radius: 0;
            }

            .report-heading h1 {
                color: #b84b00;
                font-size: 20px;
            }

            .report-heading p {
                color: #555;
            }

            .summary {
                margin-bottom: 12px;
                padding: 8px 12px;
                color: #222;
                background: #f5f5f5;
                border-left: 3px solid #ff6b00;
            }

            .summary strong {
                color: #b84b00;
            }

            .table-wrapper {
                overflow: visible;
                border: none;
                border-radius: 0;
            }

            table {
                width: 100%;
                table-layout: fixed;
                font-size: 9px;
            }

            th,
            td {
                padding: 6px 5px;
                color: #222;
                border: 1px solid #bbb;
                overflow-wrap: anywhere;
            }

            th {
                color: #222;
                background: #ffe1c7 !important;
                font-size: 8px;
            }

            td {
                background: #fff !important;
            }

            .product-image {
                width: 48px;
                height: 48px;
                background: #fff;
            }

            thead {
                display: table-header-group;
            }

            tr {
                break-inside: avoid;
            }

            .number {
                white-space: normal;
            }
        }
    </style>
</head>

<body>

<header class="header">
    <h1>JETPACK STORE</h1>
    <a href="index.php">Trang chủ</a>
</header>

<main class="container">

    <h1 class="page-title">Xuất dữ liệu sản phẩm</h1>

    <p class="page-description">
        Xem danh sách điện thoại và in hoặc lưu báo cáo dưới dạng PDF.
    </p>

    <section class="content-section">

        <div class="toolbar">

            <a href="qly.php" target="_top" class="back-button">
                ← Quay lại quản lý
            </a>

            <button class="print-button" onclick="window.print()">
                📄 In / Lưu PDF
            </button>

        </div>

        <div class="report-heading">
            <h1>DANH SÁCH SẢN PHẨM ĐIỆN THOẠI</h1>

            <p>
                HỆ THỐNG QUẢN LÝ SẢN PHẨM - JETPACK STORE
            </p>
        </div>

        <p class="summary">
            Tổng số sản phẩm:
            <strong><?= count($products) ?></strong>
        </p>

        <div class="table-wrapper">

            <table>
                <thead>
                    <tr>
                        <th>Mã sản phẩm</th>
                        <th>Ảnh sản phẩm</th>
                        <th>Tên sản phẩm</th>
                        <th>Thương hiệu</th>
                        <th>Giá sản phẩm</th>
                        <th>Ngày cập nhật</th>
                    </tr>
                </thead>

                <tbody>

                    <?php if (count($products) > 0): ?>

                        <?php foreach ($products as $product): ?>

                            <tr>

                                <td>
                                    <?= pdf_h($product['_id'] ?? '') ?>
                                </td>

                                <td>
                                    <?php
                                    $image = (string)($product['image'] ?? '');
                                    ?>

                                    <?php if ($image !== ''): ?>
                                        <img
                                            src="<?= pdf_h($image) ?>"
                                            alt="Ảnh sản phẩm"
                                            class="product-image"
                                        >
                                    <?php else: ?>
                                        Không có ảnh
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= pdf_h($product['name'] ?? '') ?>
                                </td>

                                <td>
                                    <?= pdf_h($product['brand'] ?? '') ?>
                                </td>

                                <td class="number">
                                    <?= pdf_money($product['price'] ?? 0) ?>
                                </td>

                                <td>
                                    <?= pdf_h(
                                        pdf_date($product['ngaycapnhat'] ?? '')
                                    ) ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" style="text-align:center;">
                                Chưa có dữ liệu sản phẩm.
                            </td>
                        </tr>

                    <?php endif; ?>

                </tbody>
            </table>

        </div>

        <div class="footer">
            Ngày xuất báo cáo:
            <?= date('d/m/Y H:i') ?>
            <br>
            Jetpack Store - Hệ thống quản lý điện thoại
        </div>

    </section>

</main>

</body>
</html>
