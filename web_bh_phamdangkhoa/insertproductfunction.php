
<?php

require_once __DIR__ . '/cons.php';

/*
    Insert product into MongoDB
*/

function insertProduct($data) {

    global $hanghoa;

    try {

        $product = [
            '_id' => (int)$data['productCode'],
            'name' => $data['title'],
            'image' => $data['image'],
            'brand' => $data['brand'],
            'price' => (float)$data['price'],
            'ngaycapnhat' => new MongoDB\BSON\UTCDateTime()
        ];

        // Insert product
        $result = $hanghoa->insertOne($product);

        if ($result->getInsertedCount() === 1) {
            return true;
        }

        return 'Không thể thêm sản phẩm vào MongoDB!';

    } catch (MongoDB\Driver\Exception\BulkWriteException $e) {

        // Duplicate product ID
        if ($e->getCode() === 11000) {
            return 'Mã sản phẩm đã tồn tại! Vui lòng nhập mã khác.';
        }

        error_log($e->__toString());

        return 'Lỗi khi thêm sản phẩm vào MongoDB!';

    } catch (Throwable $e) {

        error_log($e->__toString());

        return 'Không thể kết nối hoặc thêm dữ liệu vào MongoDB!';
    }
}

?>
