
<?php

require_once __DIR__ . '/updatefunction.php';

$message = '';
$error = '';
$editProduct = null;
$newImagePath = null;

/* Handle update and delete */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    try {

        /* Validate product ID */
        $idText = $_POST['id'] ?? '';

        if (
            !is_string($idText) ||
            !ctype_digit($idText) ||
            (int)$idText <= 0
        ) {
            throw new RuntimeException('Mã sản phẩm không hợp lệ.');
        }

        $id = (int)$idText;

        $existingProduct = upd_getProduct($id);

        if ($existingProduct === null) {
            throw new RuntimeException('Không tìm thấy sản phẩm.');
        }

        /* Delete product */
        if ($action === 'delete') {

            $oldImage = (string)($existingProduct['image'] ?? '');

            if (upd_deleteProduct($id)) {

                upd_removeImage($oldImage);

                header('Location: update.php?message=deleted');
                exit;

            } else {
                throw new RuntimeException('Không thể xóa sản phẩm.');
            }
        }

        if ($action !== 'update') {
            throw new RuntimeException('Thao tác không hợp lệ.');
        }

        /* Validate product name */
        $name = trim($_POST['name'] ?? '');

        if ($name === '') {
            throw new RuntimeException(
                'Tên sản phẩm không được để trống.'
            );
        }

        if (mb_strlen($name, 'UTF-8') > 200) {
            throw new RuntimeException(
                'Tên sản phẩm không được vượt quá 200 ký tự.'
            );
        }

        /* Validate brand */
        $brand = $_POST['brand'] ?? '';

        $allowedBrands = [
            'Samsung',
            'Apple',
            'Xiaomi',
            'OPPO',
            'Vivo',
            'Huawei',
            'Realme',
            'Khác'
        ];

        if (!in_array($brand, $allowedBrands, true)) {
            throw new RuntimeException(
                'Thương hiệu không hợp lệ.'
            );
        }

        /* Validate price */
        $priceText = $_POST['price'] ?? '';

        if (
            !is_string($priceText) ||
            !is_numeric($priceText) ||
            !is_finite((float)$priceText) ||
            (float)$priceText <= 0 ||
            (float)$priceText > 1000000000000
        ) {
            throw new RuntimeException(
                'Giá sản phẩm phải là số lớn hơn 0.'
            );
        }

        $price = (float)$priceText;

        /* Keep old image unless a new one is uploaded */
        $image = (string)($existingProduct['image'] ?? '');

        if (
            isset($_FILES['image']) &&
            $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE
        ) {
            $newImagePath = upd_uploadImage($_FILES['image']);
            $image = $newImagePath;
        }

        if ($image === '') {
            throw new RuntimeException(
                'Sản phẩm chưa có hình ảnh. Vui lòng tải ảnh lên.'
            );
        }

        /* Update MongoDB */
        $updated = upd_updateProduct($id, [
            'name' => $name,
            'image' => $image,
            'brand' => $brand,
            'price' => $price
        ]);

        if (!$updated) {
            throw new RuntimeException(
                'Không thể cập nhật sản phẩm.'
            );
        }

        /* Remove old image after successful update */
        if (
            $newImagePath !== null &&
            $existingProduct['image'] !== $newImagePath
        ) {
            upd_removeImage($existingProduct['image'] ?? '');
        }

        header('Location: update.php?message=updated');
        exit;

    } catch (Throwable $e) {

        /* Remove new image if update failed */
        if ($newImagePath !== null) {
            upd_removeImage($newImagePath);
        }

        $error = $e instanceof RuntimeException
            ? $e->getMessage()
            : 'Có lỗi xảy ra. Vui lòng kiểm tra MongoDB và cấu hình PHP.';

        if (!($e instanceof RuntimeException)) {
            error_log($e->__toString());
        }
    }
}

/* Success messages */
if (isset($_GET['message'])) {

    if ($_GET['message'] === 'updated') {
        $message = 'Cập nhật sản phẩm thành công!';
    } elseif ($_GET['message'] === 'deleted') {
        $message = 'Xóa sản phẩm thành công!';
    }
}

/* Load selected product */
if (
    isset($_GET['edit']) &&
    ctype_digit((string)$_GET['edit']) &&
    (int)$_GET['edit'] > 0
) {
    $editProduct = upd_getProduct((int)$_GET['edit']);

    if ($editProduct === null) {
        $error = 'Không tìm thấy sản phẩm cần sửa.';
    }
}

/* Load all products */
try {
    $products = upd_getProducts();

} catch (Throwable $e) {

    error_log($e->__toString());

    $products = [];

    $error = 'Không thể tải danh sách sản phẩm. Vui lòng kiểm tra MongoDB.';
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Quản lý sản phẩm | Jetpack Store</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 25px;
            background: #111;
            color: #f5f5f5;
            font-family: Arial, Helvetica, sans-serif;
        }

        .container {
            max-width: 1400px;
            margin: auto;
        }

        h1, h2 {
            color: #ff6b00;
        }

        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
        }

        .btn {
            display: inline-block;
            padding: 9px 14px;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-edit {
            color: #111;
            background: #ffb347;
        }

        .btn-delete {
            color: #fff;
            background: #dc3545;
        }

        .btn-save {
            color: #fff;
            background: #ff6b00;
        }

        .btn-cancel {
            color: #fff;
            background: #555;
        }

        .message, .error {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .message {
            color: #9ff0b5;
            background: #173222;
            border: 1px solid #367c4b;
        }

        .error {
            color: #ffb8b8;
            background: #351919;
            border: 1px solid #9b3838;
        }

        .form-box {
            margin-bottom: 30px;
            padding: 22px;
            background: #1b1b1b;
            border: 1px solid #393939;
            border-radius: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
            min-width: 0;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            color: #ddd;
            font-weight: bold;
        }

        input, select {
            width: 100%;
            padding: 11px;
            color: #fff;
            background: #292929;
            border: 1px solid #555;
            border-radius: 5px;
        }

        input:focus, select:focus {
            outline: 1px solid #ff6b00;
            border-color: #ff6b00;
        }

        input[readonly] {
            color: #aaa;
            background: #202020;
        }

        .help-text {
            color: #aaa;
            font-size: 12px;
            line-height: 1.5;
        }

        .current-image {
            width: 130px;
            height: 130px;
            object-fit: contain;
            background: #fff;
            border-radius: 5px;
            padding: 5px;
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
        }

        .table-wrapper {
            overflow-x: auto;
            background: #1b1b1b;
            border: 1px solid #393939;
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th, td {
            padding: 13px 12px;
            text-align: left;
            border-bottom: 1px solid #393939;
        }

        th {
            color: #fff;
            background: #ff6b00;
        }

        tr:hover td {
            background: #252525;
        }

        .product-image {
            width: 75px;
            height: 75px;
            object-fit: contain;
            background: #fff;
            border-radius: 5px;
            padding: 3px;
        }

        .product-name {
            min-width: 200px;
            max-width: 300px;
            white-space: normal;
            line-height: 1.5;
        }

        .brand {
            color: #ff8a36;
            font-weight: bold;
        }

        .price {
            color: #ffb347;
            font-weight: bold;
            white-space: nowrap;
        }

        .actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .actions form {
            margin: 0;
        }

        .empty {
            padding: 25px;
            text-align: center;
            color: #aaa;
        }

        @media (max-width: 650px) {
            body {
                padding: 12px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }
        }
    </style>

</head>

<body>

<div class="container">

    <div class="top-bar">
        <h1>Quản lý sản phẩm</h1>

<a href="qly.php" target="_top" class="btn btn-edit">
    ← Quay lại quản lý
</a>
    </div>

    <?php if ($message !== ''): ?>
        <div class="message">
            <?= upd_h($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="error">
            <?= upd_h($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($editProduct !== null): ?>

        <section class="form-box">

            <h2>Sửa thông tin sản phẩm</h2>

            <form
                method="post"
                action="update.php?edit=<?= urlencode((string)$editProduct['_id']) ?>"
                enctype="multipart/form-data"
            >

                <input type="hidden" name="action" value="update">

                <div class="form-grid">

                    <div class="form-group">
                        <label for="id">Mã sản phẩm (_id)</label>

                        <input
                            id="id"
                            name="id"
                            type="text"
                            value="<?= upd_h($editProduct['_id']) ?>"
                            readonly
                        >
                    </div>

                    <div class="form-group">
                        <label for="name">Tên sản phẩm</label>

                        <input
                            id="name"
                            name="name"
                            type="text"
                            maxlength="200"
                            required
                            value="<?= upd_h($editProduct['name'] ?? '') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="brand">Thương hiệu</label>

                        <select id="brand" name="brand" required>

                            <?php
                            $allowedBrands = [
                                'Samsung',
                                'Apple',
                                'Xiaomi',
                                'OPPO',
                                'Vivo',
                                'Huawei',
                                'Realme',
                                'Khác'
                            ];
                            ?>

                            <?php foreach ($allowedBrands as $brand): ?>

                                <option
                                    value="<?= upd_h($brand) ?>"
                                    <?= ($editProduct['brand'] ?? '') === $brand
                                        ? 'selected'
                                        : '' ?>
                                >
                                    <?= upd_h($brand) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>
                    </div>

                    <div class="form-group">
                        <label for="price">Giá sản phẩm (VNĐ)</label>

                        <input
                            id="price"
                            name="price"
                            type="number"
                            min="0.01"
                            step="any"
                            required
                            value="<?= upd_h($editProduct['price'] ?? '') ?>"
                        >
                    </div>

                    <div class="form-group full">

                        <label>Hình ảnh hiện tại</label>

                        <?php if (!empty($editProduct['image'])): ?>

                            <img
                                class="current-image"
                                src="<?= upd_h($editProduct['image']) ?>"
                                alt="Hình ảnh sản phẩm"
                            >

                        <?php else: ?>

                            <p>Chưa có hình ảnh.</p>

                        <?php endif; ?>

                    </div>

                    <div class="form-group full">

                        <label for="image">
                            Chọn hình ảnh mới (không bắt buộc)
                        </label>

                        <input
                            id="image"
                            name="image"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp,.gif,image/jpeg,image/png,image/webp,image/gif"
                        >

                        <small class="help-text">
                            Chấp nhận JPG, PNG, WEBP hoặc GIF.
                            Dung lượng tối đa 5 MB.
                            Không chọn ảnh mới nếu muốn giữ ảnh hiện tại.
                        </small>

                    </div>

                    <div class="form-group full">

                        <label>Ngày cập nhật</label>

                        <input
                            type="text"
                            readonly
                            value="<?= upd_h(
                                upd_date($editProduct['ngaycapnhat'] ?? null)
                            ) ?>"
                        >

                        <small class="help-text">
                            Ngày cập nhật sẽ tự động thay đổi sau khi lưu.
                        </small>

                    </div>

                </div>

                <div class="form-actions">

                    <button type="submit" class="btn btn-save">
                        Lưu thay đổi
                    </button>

                    <a href="update.php" class="btn btn-cancel">
                        Hủy
                    </a>

                </div>

            </form>

        </section>

    <?php endif; ?>

    <h2>Danh sách sản phẩm</h2>

    <div class="table-wrapper">

        <table>

            <thead>
                <tr>
                    <th>Mã SP</th>
                    <th>Tên sản phẩm</th>
                    <th>Hình ảnh</th>
                    <th>Thương hiệu</th>
                    <th>Giá sản phẩm</th>
                    <th>Ngày cập nhật</th>
                    <th>Thao tác</th>
                </tr>
            </thead>

            <tbody>

                <?php if (count($products) === 0): ?>

                    <tr>
                        <td colspan="7" class="empty">
                            Chưa có dữ liệu sản phẩm.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($products as $item): ?>

                        <?php
                        $productId = (string)$item['_id'];
                        $productImage = (string)($item['image'] ?? '');
                        ?>

                        <tr>

                            <td>
                                <?= upd_h($productId) ?>
                            </td>

                            <td class="product-name">
                                <?= upd_h($item['name'] ?? '') ?>
                            </td>

                            <td>

                                <?php if ($productImage !== ''): ?>

                                    <img
                                        class="product-image"
                                        src="<?= upd_h($productImage) ?>"
                                        alt="Hình ảnh sản phẩm"
                                        loading="lazy"
                                    >

                                <?php else: ?>

                                    Chưa có ảnh

                                <?php endif; ?>

                            </td>

                            <td class="brand">
                                <?= upd_h($item['brand'] ?? '') ?>
                            </td>

                            <td class="price">
                                <?= upd_h(upd_money($item['price'] ?? 0)) ?>
                            </td>

                            <td>
                                <?= upd_h(
                                    upd_date($item['ngaycapnhat'] ?? null)
                                ) ?>
                            </td>

                            <td>

                                <div class="actions">

                                    <a
                                        class="btn btn-edit"
                                        href="update.php?edit=<?= urlencode($productId) ?>"
                                    >
                                        Sửa
                                    </a>

                                    <form
                                        method="post"
                                        action="update.php"
                                        onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');"
                                    >

                                        <input
                                            type="hidden"
                                            name="action"
                                            value="delete"
                                        >

                                        <input
                                            type="hidden"
                                            name="id"
                                            value="<?= upd_h($productId) ?>"
                                        >

                                        <button
                                            type="submit"
                                            class="btn btn-delete"
                                        >
                                            Xóa
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <p style="color:#aaa; margin-top:15px;">
        Tổng số sản phẩm:
        <strong style="color:#ffb347;">
            <?= count($products) ?>
        </strong>
    </p>

</div>

</body>
</html>
