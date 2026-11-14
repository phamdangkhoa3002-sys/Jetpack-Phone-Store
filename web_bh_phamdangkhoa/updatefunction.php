
<?php

require_once __DIR__ . '/cons.php';

/* Escape HTML */
function upd_h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/* Format price */
function upd_money($price) {
    return number_format((float)$price, 0, ',', '.') . ' VNĐ';
}

/* Format MongoDB date */
function upd_date($date) {
    if ($date instanceof MongoDB\BSON\UTCDateTime) {
        return $date->toDateTime()
            ->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'))
            ->format('d/m/Y H:i');
    }

    return '';
}

/* Get one product by ID */
function upd_getProduct($id) {
    global $hanghoa;

    $product = $hanghoa->findOne([
        '_id' => (int)$id
    ]);

    return $product;
}

/* Get all products: newest to oldest */
function upd_getProducts() {
    global $hanghoa;

    $cursor = $hanghoa->find([], [
        'sort' => [
            'ngaycapnhat' => -1
        ]
    ]);

    return iterator_to_array($cursor, false);
}

/* Upload product image */
function upd_uploadImage($file) {

    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Không thể tải ảnh lên.');
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Ảnh không được vượt quá 5 MB.');
    }

    $imageInfo = getimagesize($file['tmp_name']);

    if ($imageInfo === false) {
        throw new RuntimeException('File tải lên không phải hình ảnh hợp lệ.');
    }

    $allowedTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
        IMAGETYPE_GIF  => 'gif'
    ];

    $imageType = $imageInfo[2];

    if (!isset($allowedTypes[$imageType])) {
        throw new RuntimeException(
            'Chỉ chấp nhận ảnh JPG, PNG, WEBP hoặc GIF.'
        );
    }

    $imageFolder = __DIR__ . '/images/';

    if (!is_dir($imageFolder)) {
        if (!mkdir($imageFolder, 0755, true)) {
            throw new RuntimeException('Không thể tạo thư mục images.');
        }
    }

    $fileName = 'product_' . bin2hex(random_bytes(12))
        . '.' . $allowedTypes[$imageType];

    $destination = $imageFolder . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Không thể lưu hình ảnh.');
    }

    return 'images/' . $fileName;
}

/* Validate product price */
function upd_validatePrice($price) {

    // Check if price is numeric
    if (!is_numeric($price)) {
        throw new RuntimeException(
            'Giá sản phẩm phải là số hợp lệ!'
        );
    }

    // Check if price is greater than 0
    if ((float)$price <= 0) {
        throw new RuntimeException(
            'Giá sản phẩm phải lớn hơn 0!'
        );
    }

    return (float)$price;
}

/* Update product */
function upd_updateProduct($id, $data) {
    global $hanghoa;

    // Validate price before updating
    $price = upd_validatePrice($data['price']);

    $result = $hanghoa->updateOne(
        ['_id' => (int)$id],
        [
            '$set' => [
                'name' => $data['name'],
                'image' => $data['image'],
                'brand' => $data['brand'],
                'price' => $price,
                'ngaycapnhat' => new MongoDB\BSON\UTCDateTime()
            ]
        ]
    );

    return $result->getMatchedCount() === 1;
}


/* Delete product */
function upd_deleteProduct($id) {
    global $hanghoa;

    $result = $hanghoa->deleteOne([
        '_id' => (int)$id
    ]);

    return $result->getDeletedCount() === 1;
}

/* Delete an image stored in the images folder */
function upd_removeImage($imagePath) {

    if (
        !is_string($imagePath) ||
        !str_starts_with($imagePath, 'images/')
    ) {
        return;
    }

    $imageFolder = realpath(__DIR__ . '/images');
    $imageFile = realpath(__DIR__ . '/' . $imagePath);

    if (
        $imageFolder !== false &&
        $imageFile !== false &&
        str_starts_with(
            $imageFile,
            $imageFolder . DIRECTORY_SEPARATOR
        ) &&
        is_file($imageFile)
    ) {
        @unlink($imageFile);
    }
}

?>
