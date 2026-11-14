
<?php
require_once __DIR__ . '/cons.php';

// Escape HTML
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Format price
function formatMoney($amount) {
    return number_format((float)$amount, 0, ',', '.') . ' VNĐ';
}

// Current management page
$page = $_GET['page'] ?? 'home';

if (!in_array($page, ['home', 'insert', 'update'])) {
    $page = 'home';
}

// Brand filter
$brandFilter = $_GET['brand'] ?? 'all';

$allowedBrands = [
    'Samsung', 'Apple', 'Xiaomi', 'OPPO',
    'Vivo', 'Huawei', 'Realme', 'Khác'
];

if ($brandFilter !== 'all' && !in_array($brandFilter, $allowedBrands)) {
    $brandFilter = 'all';
}

// Brand sorting
$sortBrand = $_GET['sort'] ?? 'asc';

if (!in_array($sortBrand, ['asc', 'desc'])) {
    $sortBrand = 'asc';
}

// MongoDB filter
$filter = [];

if ($brandFilter !== 'all') {
    $filter['brand'] = $brandFilter;
}

// Get products and sort by brand
$products = $hanghoa->find($filter, [
    'sort' => [
        'brand' => $sortBrand === 'asc' ? 1 : -1,
        'name' => 1
    ]
]);

// Select page to display in iframe
$targetFile = '';

if ($page === 'insert') {
    $targetFile = 'insertproduct.php';
} elseif ($page === 'update') {
    $targetFile = 'update.php';
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Quản lý Jetpack Store</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            font-family: Arial, Helvetica, sans-serif;
            background: #0f0f0f;
            color: #f5f5f5;
        }

        .container {
            max-width: 1400px;
            margin: auto;
        }

        h1 {
            color: #ff6b00;
            margin-bottom: 25px;
        }

        h2 {
            color: #ff8a36;
            margin-top: 30px;
        }

        .menu {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 25px;
        }

        .menu a {
            padding: 12px 20px;
            background: #252525;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
        }

        .menu a:hover,
        .menu a.active {
            background: #ff6b00;
        }

        .card-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-bottom: 30px;
        }

        .card {
            background: #1c1c1c;
            padding: 22px;
            border-radius: 10px;
            border: 1px solid #333;
        }

        .card h3 {
            color: #ff8a36;
            margin-top: 0;
        }

        .card p {
            color: #bbb;
        }

        .card a {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 15px;
            background: #ff6b00;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
        }

        .card a:hover {
            background: #ff8a36;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            margin: 20px 0;
            padding: 18px;
            background: #1c1c1c;
            border-radius: 8px;
        }

        select,
        button {
            padding: 10px 14px;
            border: 1px solid #444;
            border-radius: 5px;
            font-size: 14px;
        }

        select {
            background: #292929;
            color: white;
        }

        button {
            background: #ff6b00;
            color: white;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #ff8a36;
        }

        .table-container {
            width: 100%;
            overflow-x: auto;
            background: #1c1c1c;
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th {
            background: #ff6b00;
            color: white;
            padding: 14px;
            text-align: left;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #333;
        }

        tr:hover {
            background: #292929;
        }

        .product-image {
            width: 65px;
            height: 65px;
            object-fit: contain;
            background: white;
            border-radius: 5px;
            padding: 3px;
        }

        .price {
            color: #ffb347;
            font-weight: bold;
            white-space: nowrap;
        }

        .brand {
            color: #ff8a36;
            font-weight: bold;
        }

        .empty {
            text-align: center;
            padding: 25px;
            color: #aaa;
        }

        iframe {
            width: 100%;
            min-height: 750px;
            border: none;
            background: #0f0f0f;
            border-radius: 8px;
        }

        .footer {
            text-align: center;
            color: #777;
            margin-top: 35px;
        }

        @media (max-width: 600px) {
            body {
                padding: 12px;
            }

            h1 {
                font-size: 23px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <h1>📱 JETPACK STORE - QUẢN LÝ SẢN PHẨM</h1>

    <div class="menu">

            <a href="index.php" target="_top" class="btn-home">
    ← Quay lại trang chủ
</a>

        <a href="qly.php"
           class="<?= $page === 'home' ? 'active' : '' ?>">
            Trang Quản lý
        </a>

        <a href="qly.php?page=insert"
           class="<?= $page === 'insert' ? 'active' : '' ?>">
            Thêm sản phẩm
        </a>

        <a href="qly.php?page=update"
           class="<?= $page === 'update' ? 'active' : '' ?>">
            Quản lý sản phẩm
        </a>

        <a href="export_pdf.php" target="_blank">
            Xuất PDF
        </a>


    </div>



    <?php if ($page === 'home'): ?>

        <div class="card-container">

            <div class="card">
                <h3>➕ Thêm sản phẩm</h3>
                <p>Thêm điện thoại mới vào cơ sở dữ liệu MongoDB.</p>
                <a href="qly.php?page=insert">Thêm ngay</a>
            </div>

            <div class="card">
                <h3>📋 Quản lý sản phẩm</h3>
                <p>Cập nhật hoặc xóa thông tin sản phẩm.</p>
                <a href="qly.php?page=update">Quản lý ngay</a>
            </div>

            <div class="card">
                <h3>📄 Xuất dữ liệu PDF</h3>
                <p>Xuất danh sách sản phẩm thành file PDF.</p>
                <a href="export_pdf.php" target="_blank">Xuất PDF</a>
            </div>

        </div>

        <h2>📦 Danh sách sản phẩm</h2>

        <!-- Filter and sort -->
        <form method="GET" action="qly.php" class="filter-form">

            <input type="hidden" name="page" value="home">

            <label for="brand">Lọc theo thương hiệu:</label>

            <select name="brand" id="brand">
                <option value="all"
                    <?= $brandFilter === 'all' ? 'selected' : '' ?>>
                    Tất cả thương hiệu
                </option>

                <?php foreach ($allowedBrands as $brand): ?>
                    <option value="<?= h($brand) ?>"
                        <?= $brandFilter === $brand ? 'selected' : '' ?>>
                        <?= h($brand) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="sort">Sắp xếp thương hiệu:</label>

            <select name="sort" id="sort">
                <option value="asc"
                    <?= $sortBrand === 'asc' ? 'selected' : '' ?>>
                    A - Z
                </option>

                <option value="desc"
                    <?= $sortBrand === 'desc' ? 'selected' : '' ?>>
                    Z - A
                </option>
            </select>

            <button type="submit">Áp dụng</button>

            <a href="qly.php"
               style="color:#ffb347; text-decoration:none;">
                Xóa bộ lọc
            </a>

        </form>

        <!-- Product table -->
        <div class="table-container">

            <table>
                <thead>
                    <tr>
                        <th>Mã SP</th>
                        <th>Hình ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Thương hiệu</th>
                        <th>Giá</th>
                        <th>Ngày cập nhật</th>
                    </tr>
                </thead>

                <tbody>

                <?php
                $count = 0;

                foreach ($products as $product):
                    $count++;

                    $image = $product['image'] ?? '';

                    $date = '';

                    if (isset($product['ngaycapnhat'])) {
                        $date = $product['ngaycapnhat']
                            ->toDateTime()
                            ->format('d/m/Y H:i');
                    }
                ?>

                    <tr>
                        <td><?= h($product['_id'] ?? '') ?></td>

                        <td>
                            <?php if ($image !== ''): ?>
                                <img
                                    class="product-image"
                                    src="<?= h($image) ?>"
                                    alt="<?= h($product['name'] ?? '') ?>"
                                >
                            <?php else: ?>
                                Không có ảnh
                            <?php endif; ?>
                        </td>

                        <td>
                            <?= h($product['name'] ?? '') ?>
                        </td>

                        <td class="brand">
                            <?= h($product['brand'] ?? '') ?>
                        </td>

                        <td class="price">
                            <?= formatMoney($product['price'] ?? 0) ?>
                        </td>

                        <td>
                            <?= h($date) ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

                <?php if ($count === 0): ?>
                    <tr>
                        <td colspan="6" class="empty">
                            Không tìm thấy sản phẩm nào!
                        </td>
                    </tr>
                <?php endif; ?>

                </tbody>
            </table>

        </div>

        <p style="color:#aaa; margin-top:15px;">
            Tổng số sản phẩm: <strong style="color:#ffb347;">
                <?= $count ?>
            </strong>
        </p>

    <?php else: ?>

        <h2>
            <?= $page === 'insert'
                ? '➕ THÊM SẢN PHẨM MỚI'
                : '📋 QUẢN LÝ SẢN PHẨM' ?>
        </h2>

        <iframe src="<?= h($targetFile) ?>"></iframe>

    <?php endif; ?>

    <div class="footer">
        Jetpack Store - Hệ thống quản lý sản phẩm
    </div>

</div>

</body>
</html>
