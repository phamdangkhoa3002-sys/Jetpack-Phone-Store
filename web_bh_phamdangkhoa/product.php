
<?php
require_once __DIR__ . '/cons.php';

// Escape HTML
function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Format price
function money($value): string {
    return number_format((float)$value, 0, ',', '.') . ' VNĐ';
}

$product = null;
$error = null;

try {

    // Get product ID from URL
    $id = $_GET['id'] ?? '';

    if (!is_string($id) || $id === '' || !ctype_digit($id)) {
        http_response_code(400);
        throw new RuntimeException('Mã sản phẩm không hợp lệ.');
    }

    // Find product in MongoDB
    $product = $hanghoa->findOne([
        '_id' => (int)$id
    ]);

    // Check whether product exists
    if ($product === null) {
        http_response_code(404);
        throw new RuntimeException('Không tìm thấy sản phẩm có mã: ' . $id);
    }

} catch (Throwable $e) {

    error_log($e->__toString());

    $error = $e->getMessage();

    if (!($e instanceof RuntimeException)) {
        http_response_code(500);
        $error = 'Không thể tải thông tin sản phẩm. Vui lòng kiểm tra kết nối MongoDB.';
    }
}

// Product information
$title = $product['name'] ?? 'Chi tiết sản phẩm';
$code = isset($product['_id']) ? (string)$product['_id'] : '';
$brand = $product['brand'] ?? 'Chưa phân loại';
$image = trim((string)($product['image'] ?? ''));
$price = (float)($product['price'] ?? 0);

// Check product image
$showImage = $image !== '' && (
    filter_var($image, FILTER_VALIDATE_URL) ||
    file_exists(__DIR__ . '/' . ltrim($image, '/\\'))
);

// Image source
$imageSrc = $image;

if ($showImage && !filter_var($image, FILTER_VALIDATE_URL)) {
    $imageSrc = ltrim($image, '/');
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= h($title) ?> | Jetpack Store</title>

    <link rel="stylesheet" href="style.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #0c0c0c;
            color: #f5f5f5;
            font-family: Arial, Helvetica, sans-serif;
        }

        a {
            color: #ff8a36;
        }

        /* Header */
        .product-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 16px 5%;
            background: #141414;
            border-bottom: 1px solid #ff6b00;
        }

        .product-logo {
            color: #ff8a36;
            font-size: 27px;
            font-weight: 900;
            text-decoration: none;
            letter-spacing: 1px;
        }

        /* Main container */
        .product-container {
            max-width: 1150px;
            margin: 35px auto;
            padding: 0 22px;
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
            color: #999;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .breadcrumb a {
            color: #ff9a50;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            color: #ffc18c;
            text-decoration: underline;
        }

        /* Product card */
        .product-detail {
            display: grid;
            grid-template-columns: minmax(0, 0.9fr) minmax(0, 1.1fr);
            gap: 45px;
            align-items: start;
            padding: 35px;
            background: linear-gradient(145deg, #1b1b1b, #141414);
            border: 1px solid #303030;
            border-radius: 16px;
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.3);
        }

        /* Product image */
        .product-image {
            min-width: 0;
            text-align: center;
        }

        .product-image .image-wrapper {
            position: relative;
            display: inline-block;
            width: 100%;
            max-width: 400px;
            padding: 15px;
            background: #202020;
            border: 1px solid #333;
            border-radius: 12px;
        }

        .product-image img {
            display: block;
            width: 100%;
            max-width: 400px;
            max-height: 560px;
            margin: 0 auto;
            object-fit: contain;
            border-radius: 6px;
        }

        /* No image */
        .no-image {
            display: flex;
            min-height: 280px;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #aaa;
            background: #222;
            border: 1px dashed #555;
            border-radius: 8px;
        }

        /* Product information */
        .product-information {
            min-width: 0;
        }

        .product-eyebrow {
            margin: 0;
            color: #ff8a36;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .product-information h1 {
            margin: 12px 0 22px;
            color: #fff;
            font-size: clamp(25px, 3vw, 34px);
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .product-information > p {
            color: #ccc;
            line-height: 1.7;
        }

        .product-information strong {
            color: #fff;
        }

        /* Product price */
        .product-price {
            margin: 25px 0;
            color: #ff8a36;
            font-size: clamp(27px, 3vw, 34px);
            font-weight: 900;
            line-height: 1.4;
        }

        /* Brand */
        .product-brand {
            display: inline-block;
            margin: 10px 0;
            padding: 7px 12px;
            color: #ffb347;
            background: #292016;
            border: 1px solid #60401f;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 800;
        }

        /* Description */
        .product-description {
            margin: 30px 0;
            line-height: 1.8;
            overflow-wrap: anywhere;
        }

        .product-description h2 {
            padding-bottom: 12px;
            color: #fff;
            font-size: 21px;
            border-bottom: 1px solid #383838;
        }

        .product-description p {
            color: #bbb;
        }

        /* Back button */
        .back-home {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-top: 12px;
            padding: 12px 20px;
            color: #111;
            background: #ff8a36;
            border: 1px solid #ff8a36;
            border-radius: 7px;
            text-decoration: none;
            font-weight: 800;
            transition: background 0.2s ease,
                        transform 0.2s ease;
        }

        .back-home:hover {
            color: #111;
            background: #ffb347;
            border-color: #ffb347;
            transform: translateY(-2px);
        }

        /* Error message */
        .error {
            padding: 25px;
            color: #fff;
            background: #211714;
            border: 1px solid #ff6b00;
            border-radius: 10px;
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            margin-top: 45px;
            padding: 25px 20px;
            text-align: center;
            color: #999;
            background: #141414;
            border-top: 1px solid #ff6b00;
            line-height: 1.6;
        }

        /* Tablet and mobile */
        @media (max-width: 700px) {

            .product-header {
                flex-direction: column;
                padding: 15px 20px;
            }

            .product-container {
                margin: 22px auto;
                padding: 0 14px;
            }

            .product-detail {
                grid-template-columns: minmax(0, 1fr);
                gap: 28px;
                padding: 18px;
            }

            .product-image .image-wrapper {
                max-width: 350px;
            }

            .product-information h1 {
                font-size: 26px;
            }

            .product-price {
                font-size: 28px;
            }
        }

        @media (max-width: 380px) {

            .product-detail {
                padding: 13px;
            }
        }

    </style>

</head>

<body>

<!-- ================= HEADER ================= -->

<header class="product-header">

    <a href="index.php" class="product-logo">
        JETPACK STORE
    </a>

    <a href="index.php" class="back-home">
        ← Tiếp tục mua sắm
    </a>

</header>


<!-- ================= PRODUCT DETAIL ================= -->

<main class="product-container">

    <?php if ($error !== null): ?>

        <div class="error">

            <h1>Không thể mở sản phẩm</h1>

            <p><?= h($error) ?></p>

            <a href="index.php" class="back-home">
                Quay về trang chủ
            </a>

        </div>

    <?php else: ?>

        <!-- Breadcrumb -->

        <nav class="breadcrumb" aria-label="Đường dẫn">

            <a href="index.php">Trang chủ</a>

            <span>›</span>

            <a href="index.php?category=<?= h(strtolower($brand)) ?>">
                <?= h($brand) ?>
            </a>

            <span>›</span>

            <span><?= h($title) ?></span>

        </nav>


        <!-- Product detail -->

        <section class="product-detail">

            <!-- Product image -->

            <div class="product-image">

                <?php if ($showImage): ?>

                    <div class="image-wrapper">

                        <img
                            src="<?= h($imageSrc) ?>"
                            alt="<?= h($title) ?>"
                        >

                    </div>

                <?php else: ?>

                    <div class="no-image">
                        📱 Chưa có ảnh sản phẩm
                    </div>

                <?php endif; ?>

            </div>


            <!-- Product information -->

            <div class="product-information">

                <p class="product-eyebrow">
                    JETPACK STORE
                </p>

                <h1><?= h($title) ?></h1>

                <p>
                    <strong>Thương hiệu:</strong>
                </p>

                <span class="product-brand">
                    <?= h($brand) ?>
                </span>

                <?php if ($code !== ''): ?>

                    <p>
                        <strong>Mã sản phẩm:</strong>
                        <?= h($code) ?>
                    </p>

                <?php endif; ?>


                <!-- Price -->

                <div class="product-price">
                    <?= h(money($price)) ?>
                </div>


                <!-- Description -->

                <div class="product-description">

                    <h2>Thông tin sản phẩm</h2>

                    <p>
                        <?= h($title) ?> là sản phẩm thuộc thương hiệu
                        <?= h($brand) ?>.
                    </p>

                    <p>
                        Để biết thêm thông tin về sản phẩm,
                        vui lòng liên hệ Jetpack Store.
                    </p>

                </div>


                <a href="index.php" class="back-home">
                    ← Quay lại danh sách sản phẩm
                </a>

            </div>

        </section>

    <?php endif; ?>

</main>


<!-- ================= FOOTER ================= -->

<footer class="footer">

    <p>© <?= date('Y') ?> Jetpack Store</p>

</footer>

</body>
</html>
