<?php
require_once 'cons.php';

// Format price
function formatMoney($amount) {
    return number_format((float)$amount, 0, ',', '.') . ' VNĐ';
}

// Escape HTML
function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Search keyword
$keyword = trim($_GET['search'] ?? '');

// Selected category
$category = $_GET['category'] ?? 'all';

// Valid categories
$validCategories = [
    'all',
    'samsung',
    'apple',
    'xiaomi',
    'oppo',
    'other'
];

if (!in_array($category, $validCategories, true)) {
    $category = 'all';
}

// Get products from MongoDB
// Latest updated products first
$products = $hanghoa->find([], [
    'sort' => ['ngaycapnhat' => -1]
])->toArray();

// Divide products into categories
$productsByCategory = [
    'samsung' => [],
    'apple' => [],
    'xiaomi' => [],
    'oppo' => [],
    'other' => []
];

// Categorize products
foreach ($products as $product) {

    $name = (string)($product['name'] ?? '');
    $brand = (string)($product['brand'] ?? '');

    // Search by product name or brand
    if (
        $keyword !== '' &&
        stripos($name, $keyword) === false &&
        stripos($brand, $keyword) === false
    ) {
        continue;
    }

    // Get brand name in lowercase
    $brandLower = strtolower(trim($brand));

    // Categorize by brand
    if ($brandLower === 'samsung') {
        $productsByCategory['samsung'][] = $product;

    } elseif ($brandLower === 'apple') {
        $productsByCategory['apple'][] = $product;

    } elseif ($brandLower === 'xiaomi') {
        $productsByCategory['xiaomi'][] = $product;

    } elseif ($brandLower === 'oppo') {
        $productsByCategory['oppo'][] = $product;

    } else {
        $productsByCategory['other'][] = $product;
    }
}

// Category information
$categoryNames = [
    'samsung' => 'Samsung',
    'apple' => 'Apple',
    'xiaomi' => 'Xiaomi',
    'oppo' => 'OPPO',
    'other' => 'Các hãng khác'
];

// Category icons
$categoryIcons = [
    'samsung' => '📱',
    'apple' => '🍎',
    'xiaomi' => '📦',
    'oppo' => '📱',
    'other' => '🏷️'
];

// Total products matching search
$totalProducts = 0;

foreach ($productsByCategory as $list) {
    $totalProducts += count($list);
}

// Create category URL while preserving search
function categoryUrl($category, $keyword) {

    $params = [];

    if ($category !== 'all') {
        $params['category'] = $category;
    }

    if ($keyword !== '') {
        $params['search'] = $keyword;
    }

    return 'index.php' . (!empty($params)
        ? '?' . http_build_query($params)
        : '');
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= $keyword !== ''
            ? 'Tìm kiếm: ' . h($keyword) . ' | Jetpack Store'
            : 'Jetpack Store - Điện thoại chính hãng'
        ?>
    </title>

    <meta name="description"
          content="Khám phá và tìm kiếm điện thoại chính hãng tại Jetpack Store.">

    <link rel="stylesheet" href="style.css">
</head>

<body>

<!-- ================= HEADER BANNER ================= -->

<div class="header-banner">

    <img src="images/oisnoanc.png" alt="Jetpack Store">

    <div class="banner-welcome">

        <h1>WELCOME TO JETPACK STORE</h1>

        <p>Từ một trang bán hòm thành một trang bán điện thoại</p>

        <a href="index.php" class="welcome-btn">
            KHÁM PHÁ NGAY
        </a>

    </div>

</div>


<!-- ================= MAIN HEADER ================= -->

<header class="header">

    <a href="index.php" class="home-logo" aria-label="Về trang chủ">
        <img src="images/logo.png" alt="Jetpack Store">
    </a>

    <!-- Search -->

    <form class="search-bar"
          action="index.php"
          method="GET"
          role="search">

        <input
            type="search"
            name="search"
            placeholder="Tìm kiếm điện thoại..."
            value="<?= h($keyword) ?>"
            aria-label="Nhập từ khóa tìm kiếm"
            autocomplete="off">

        <?php if ($category !== 'all'): ?>

            <input
                type="hidden"
                name="category"
                value="<?= h($category) ?>">

        <?php endif; ?>

        <button type="submit">
            🔍 Tìm kiếm
        </button>

    </form>

    <!-- Header actions -->

    <nav class="header-actions" aria-label="Điều hướng chính">

        <a href="support.php" class="header-link">

            <span class="header-icon">🎧</span>

            <span>
                19006656<br>
                Hỗ trợ khách hàng
            </span>

        </a>

        <a href="register.php" class="header-link">

            <span class="header-icon">👤</span>

            <span>
                Đăng ký<br>
                Đăng nhập
            </span>

        </a>

        <a href="cart.php" class="header-link cart-link">

            <span class="header-icon">🛒</span>

            <span>Giỏ hàng</span>

        </a>

        <a href="qly.php" class="header-link-management">
            <span>Quản lý điện thoại</span>
        </a>

    </nav>

</header>


<!-- ================= MAIN CONTENT ================= -->

<main class="container">

    <!-- Page heading -->

    <section class="catalog-section">

        <div class="catalog-heading">

            <div>

                <h2 class="section-title">
                    ĐIỆN THOẠI CHÍNH HÃNG
                </h2>

                <p class="search-summary">

                    <?php if ($keyword !== ''): ?>

                        Kết quả tìm kiếm cho:
                        <strong><?= h($keyword) ?></strong>

                    <?php else: ?>

                        Khám phá các dòng điện thoại mới nhất tại Jetpack Store.

                    <?php endif; ?>

                </p>

            </div>

            <?php if ($keyword !== ''): ?>

                <a href="index.php" class="clear-search">
                    ✕ Xóa tìm kiếm
                </a>

            <?php endif; ?>

        </div>

        <!-- Product count -->

        <p class="book-count">

            Hiện có
            <strong><?= $totalProducts ?></strong>
            sản phẩm

        </p>

    </section>


    <!-- ================= CATEGORY NAVIGATION ================= -->

    <section class="category-section">

        <!-- Samsung -->

        <a href="<?= h(categoryUrl('samsung', $keyword)) ?>"
           class="category-card <?= $category === 'samsung' ? 'active' : '' ?>">

            <span class="category-icon">📱</span>
            <span class="category-title">Samsung</span>

            <span class="category-description">
                <?= count($productsByCategory['samsung']) ?> sản phẩm
            </span>

        </a>

        <!-- Apple -->

        <a href="<?= h(categoryUrl('apple', $keyword)) ?>"
           class="category-card <?= $category === 'apple' ? 'active' : '' ?>">

            <span class="category-icon">🍎</span>
            <span class="category-title">Apple</span>

            <span class="category-description">
                <?= count($productsByCategory['apple']) ?> sản phẩm
            </span>

        </a>

        <!-- Xiaomi -->

        <a href="<?= h(categoryUrl('xiaomi', $keyword)) ?>"
           class="category-card <?= $category === 'xiaomi' ? 'active' : '' ?>">

            <span class="category-icon">📦</span>
            <span class="category-title">Xiaomi</span>

            <span class="category-description">
                <?= count($productsByCategory['xiaomi']) ?> sản phẩm
            </span>

        </a>

        <!-- OPPO -->

        <a href="<?= h(categoryUrl('oppo', $keyword)) ?>"
           class="category-card <?= $category === 'oppo' ? 'active' : '' ?>">

            <span class="category-icon">📱</span>
            <span class="category-title">OPPO</span>

            <span class="category-description">
                <?= count($productsByCategory['oppo']) ?> sản phẩm
            </span>

        </a>

        <!-- Other brands -->

        <a href="<?= h(categoryUrl('other', $keyword)) ?>"
           class="category-card <?= $category === 'other' ? 'active' : '' ?>">

            <span class="category-icon">🏷️</span>
            <span class="category-title">Các hãng khác</span>

            <span class="category-description">
                <?= count($productsByCategory['other']) ?> sản phẩm
            </span>

        </a>

        <!-- All products -->

        <a href="<?= h(categoryUrl('all', $keyword)) ?>"
           class="category-card <?= $category === 'all' ? 'active' : '' ?>">

            <span class="category-icon">🛍️</span>
            <span class="category-title">Tất cả</span>

            <span class="category-description">
                <?= $totalProducts ?> sản phẩm
            </span>

        </a>

    </section>


<!-- ================= PRODUCT LIST ================= -->

<?php foreach ($productsByCategory as $key => $categoryProducts): ?>

    <?php
    // Skip categories that are not selected
    if ($category !== 'all' && $category !== $key) {
        continue;
    }

    // Get the 3 newest products for HOT badges
    $hotProducts = array_slice($categoryProducts, 0, 3);

    // When viewing all brands, display only 3 newest products
    // When viewing one brand, display all its products
    if ($category === 'all') {
        $displayProducts = $hotProducts;
    } else {
        $displayProducts = $categoryProducts;
    }

    // Get IDs of the 3 newest products
    $hotProductIds = [];

    foreach ($hotProducts as $hotProduct) {
        $hotProductIds[] = (string)$hotProduct['_id'];
    }
    ?>

    <section class="catalog-section" id="<?= h($key) ?>">

        <!-- Section heading -->

        <div class="catalog-heading">

            <h2 class="section-title">
                <?= h($categoryNames[$key]) ?>
            </h2>

            <span class="book-count">

                <?php if ($category === 'all'): ?>

                    <?= count($displayProducts) ?> sản phẩm nổi bật

                <?php else: ?>

                    <?= count($displayProducts) ?> sản phẩm

                <?php endif; ?>

            </span>

        </div>

        <?php if (count($displayProducts) > 0): ?>

            <!-- Product grid -->

            <div class="book-grid">

                <?php foreach ($displayProducts as $product): ?>

                    <?php
                    // Check whether this product is one of the 3 newest
                    $isHot = in_array(
                        (string)$product['_id'],
                        $hotProductIds,
                        true
                    );
                    ?>

                    <a href="product.php?id=<?= rawurlencode((string)$product['_id']) ?>"
                       class="book-card-link">

                        <article class="book-card">

                            <!-- Product image -->

                            <div class="book-cover">

                                <?php if ($isHot): ?>
                                    <span class="hot-badge">🔥 HOT</span>
                                <?php endif; ?>

                                <?php if (!empty($product['image'])): ?>

                                    <img
                                        src="<?= h($product['image']) ?>"
                                        alt="<?= h($product['name'] ?? 'Điện thoại') ?>"
                                        loading="lazy">

                                <?php else: ?>

                                    <div class="no-image">

                                        <span class="book-icon">📱</span>

                                        <span>Chưa có hình ảnh</span>

                                    </div>

                                <?php endif; ?>

                            </div>

                            <!-- Product information -->

                            <div class="book-info">

                                <!-- Brand -->

                                <p class="book-author">
                                    <?= h($product['brand'] ?? 'Khác') ?>
                                </p>

                                <!-- Product name -->

                                <h3 class="book-title">
                                    <?= h($product['name'] ?? 'Chưa có tên sản phẩm') ?>
                                </h3>

                                <!-- Product price -->

                                <p class="book-price">
                                    <?= formatMoney($product['price'] ?? 0) ?>
                                </p>

                                <!-- Product button -->

                                <span class="view-product">
                                    Xem sản phẩm →
                                </span>

                            </div>

                        </article>

                    </a>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <!-- Empty products -->

            <div class="empty">

                <div class="empty-icon">📱</div>

                <h2>Không tìm thấy sản phẩm</h2>

                <p>
                    Danh mục này hiện chưa có sản phẩm phù hợp.
                </p>

                <a href="index.php" class="back-home">
                    Quay lại trang chủ
                </a>

            </div>

        <?php endif; ?>

    </section>

<?php endforeach; ?>

</main>


<!-- ================= FOOTER ================= -->

<footer class="footer">

    <p>JETPACK STORE - HỆ THỐNG QUẢN LÝ BÁN HÀNG</p>

    <p>© 2026 Jetpack Store</p>

</footer>

</body>
</html>
