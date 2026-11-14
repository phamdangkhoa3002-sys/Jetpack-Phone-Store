
<?php

require_once __DIR__ . '/insertproductfunction.php';

$message = '';
$success = false;

// Default form values
$productCode = '';
$title = '';
$brand = 'Samsung';
$price = '';

$validBrands = [
    'Samsung',
    'Apple',
    'Xiaomi',
    'OPPO',
    'Vivo',
    'Huawei',
    'Realme',
    'Khác'
];

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $productCode = trim($_POST['productCode'] ?? '');
    $title = trim($_POST['title'] ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $price = trim($_POST['price'] ?? '');

    // Validate product ID
    if (!preg_match('/^[1-9][0-9]{0,14}$/', $productCode)) {

        $message = 'Mã sản phẩm phải là số nguyên dương hợp lệ!';

    }

    // Validate product name
    elseif ($title === '' || mb_strlen($title) > 200) {

        $message = 'Tên sản phẩm không được để trống hoặc dài hơn 200 ký tự!';

    }

    // Validate brand
    elseif (!in_array($brand, $validBrands, true)) {

        $message = 'Thương hiệu không hợp lệ!';

    }

    // Validate price
    elseif (
        !is_numeric($price) ||
        !is_finite((float)$price) ||
        (float)$price <= 0 ||
        (float)$price > 1000000000000
    ) {

        $message = 'Giá sản phẩm phải lớn hơn 0 và không vượt quá giới hạn!';

    }

    // Validate uploaded image
    elseif (
        !isset($_FILES['image']) ||
        $_FILES['image']['error'] !== UPLOAD_ERR_OK
    ) {

        $message = 'Vui lòng chọn ảnh sản phẩm hợp lệ!';

    }

    else {

        $file = $_FILES['image'];

        // Check image
        $info = @getimagesize($file['tmp_name']);

        $allowedTypes = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif'
        ];

        if ($file['size'] > 5 * 1024 * 1024) {

            $message = 'Ảnh không được lớn hơn 5 MB!';

        } elseif (
            $info === false ||
            !isset($allowedTypes[$info[2]])
        ) {

            $message = 'Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF!';

        } else {

            // Image directory
            $directory = __DIR__ . '/images/';

            if (
                !is_dir($directory) &&
                !mkdir($directory, 0755, true)
            ) {

                $message = 'Không thể tạo thư mục images!';

            } else {

                // Generate unique image filename
                $filename = bin2hex(random_bytes(16))
                    . '.' . $allowedTypes[$info[2]];

                $path = $directory . $filename;

                $imagePath = 'images/' . $filename;

                // Save uploaded image
                if (!move_uploaded_file($file['tmp_name'], $path)) {

                    $message = 'Không thể lưu ảnh sản phẩm!';

                } else {

                    // Insert product into MongoDB
                    $result = insertProduct([
                        'productCode' => $productCode,
                        'title' => $title,
                        'brand' => $brand,
                        'price' => $price,
                        'image' => $imagePath
                    ]);

                    if ($result === true) {

                        $success = true;
                        $message = 'Thêm sản phẩm thành công!';

                        // Reset form
                        $productCode = '';
                        $title = '';
                        $brand = 'Samsung';
                        $price = '';

                    } else {

                        // Delete image if insertion fails
                        if (is_file($path)) {
                            unlink($path);
                        }

                        $message = (string)$result;
                    }
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Thêm sản phẩm mới | Jetpack Store</title>

    <style>

        :root {
            --orange: #ff8a00;
            --orange-light: #ffb347;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 40px 15px;
            font-family: Arial, sans-serif;
            color: white;
            background: #181818 url("images/gradient-background.jpg")
                        center / cover fixed no-repeat;
        }

        form {
            max-width: 520px;
            margin: auto;
            padding: 30px;
            background: rgba(8, 8, 8, .95);
            border: 1px solid #333;
            border-top: 3px solid var(--orange);
            border-radius: 12px;
            box-shadow: 0 10px 35px #0007;
        }

        h2 {
            margin: 0 0 24px;
            padding-bottom: 15px;
            color: var(--orange-light);
            text-align: center;
            border-bottom: 1px solid #333;
        }

        label {
            display: block;
            margin-top: 18px;
            font-size: 14px;
            font-weight: bold;
        }

        input,
        select {
            display: block;
            width: 100%;
            margin-top: 8px;
            padding: 12px;
            color: #222;
            background: white;
            border: 2px solid transparent;
            border-radius: 7px;
            font: inherit;
        }

        input:focus,
        select:focus {
            outline: none;
            border-color: var(--orange);
            box-shadow: 0 0 0 3px #ff8a0026;
        }

        input[type="file"] {
            color: #eee;
            background: #1d1d1d;
            border-color: #444;
        }

        button {
            width: 100%;
            margin-top: 24px;
            padding: 14px;
            color: white;
            background: linear-gradient(
                135deg,
                var(--orange),
                var(--orange-light)
            );
            border: 0;
            border-radius: 8px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            transition: .2s;
        }

        button:hover {
            filter: brightness(1.1);
        }

        .message {
            padding: 12px;
            color: #ffcc80;
            background: #241b10;
            border: 1px solid #624016;
            border-radius: 7px;
            text-align: center;
            overflow-wrap: anywhere;
        }

        .message.success {
            color: #9be7b0;
            background: #10251a;
            border-color: #28623b;
        }

        .hint {
            margin-top: 7px;
            color: #bbb;
            font-size: 12px;
        }

        #imagePreview {
            display: none;
            max-width: 100%;
            max-height: 280px;
            margin: 15px auto 0;
            object-fit: contain;
            border: 1px solid #444;
            border-radius: 8px;
        }

        @media (max-width: 600px) {

            body {
                padding: 20px 12px;
                background-attachment: scroll;
            }

            form {
                padding: 22px 18px;
            }
        }

    </style>

</head>

<body>

<form method="POST" enctype="multipart/form-data">

    <h2>THÊM SẢN PHẨM MỚI</h2>

    <?php if ($message !== ''): ?>

        <p class="message <?= $success ? 'success' : '' ?>">

            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>

        </p>

    <?php endif; ?>


    <!-- Product ID -->

    <label for="productCode">
        Mã sản phẩm
    </label>

    <input
        id="productCode"
        name="productCode"
        type="text"
        inputmode="numeric"
        pattern="[1-9][0-9]{0,14}"
        maxlength="15"
        value="<?= htmlspecialchars($productCode, ENT_QUOTES, 'UTF-8') ?>"
        required
    >


    <!-- Product name -->

    <label for="title">
        Tên sản phẩm
    </label>

    <input
        id="title"
        name="title"
        type="text"
        maxlength="200"
        placeholder="Ví dụ: Điện thoại Samsung Galaxy Z Flip8"
        value="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
        required
    >


    <!-- Brand -->

    <label for="brand">
        Thương hiệu
    </label>

    <select id="brand" name="brand" required>

        <?php foreach ($validBrands as $brandOption): ?>

            <option
                value="<?= htmlspecialchars($brandOption, ENT_QUOTES, 'UTF-8') ?>"
                <?= $brand === $brandOption ? 'selected' : '' ?>
            >
                <?= htmlspecialchars($brandOption, ENT_QUOTES, 'UTF-8') ?>
            </option>

        <?php endforeach; ?>

    </select>


    <!-- Price -->

    <label for="price">
        Giá sản phẩm (VNĐ)
    </label>

    <input
        id="price"
        name="price"
        type="number"
        min="0.01"
        max="1000000000000"
        step="any"
        placeholder="Ví dụ: 29590000"
        value="<?= htmlspecialchars($price, ENT_QUOTES, 'UTF-8') ?>"
        required
    >


    <!-- Product image -->

    <label for="image">
        Ảnh sản phẩm
    </label>

    <input
        id="image"
        name="image"
        type="file"
        accept="image/jpeg,image/png,image/webp,image/gif"
        required
    >

    <p class="hint">
        JPG, PNG, WEBP hoặc GIF — tối đa 5 MB.
    </p>

    <img
        id="imagePreview"
        alt="Xem trước ảnh sản phẩm"
    >


    <!-- Submit -->

    <button type="submit">
        Thêm sản phẩm vào MongoDB
    </button>

</form>


<script>

const form = document.querySelector('form');

const imageInput = document.getElementById('image');

const imagePreview = document.getElementById('imagePreview');

let previewUrl = null;

// Preview uploaded image
imageInput.addEventListener('change', function () {

    const file = this.files[0];

    if (previewUrl) {
        URL.revokeObjectURL(previewUrl);
        previewUrl = null;
    }

    imagePreview.style.display = 'none';
    imagePreview.removeAttribute('src');

    if (!file) return;

    const allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif'
    ];

    if (
        !allowedTypes.includes(file.type) ||
        file.size > 5 * 1024 * 1024
    ) {

        alert('Ảnh phải là JPG, PNG, WEBP hoặc GIF và tối đa 5 MB!');

        this.value = '';

        return;
    }

    previewUrl = URL.createObjectURL(file);

    imagePreview.src = previewUrl;

    imagePreview.style.display = 'block';

});

</script>

</body>
</html>
