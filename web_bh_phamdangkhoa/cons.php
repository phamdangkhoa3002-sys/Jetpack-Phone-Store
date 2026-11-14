<?php
require_once __DIR__ . '/vendor/autoload.php';

try {
    // Connect to MongoDB
    $client = new MongoDB\Client("mongodb://127.0.0.1:27017");

    // Select database
    $database = $client->selectDatabase("bh_phamdangkhoa");

    // Select 3 collections
    $hanghoa = $database->selectCollection("hanghoa_phamdangkhoa");
    $hoadon = $database->selectCollection("hoadon_phamdangkhoa");
    $cthd = $database->selectCollection("cthd_phamdangkhoa");

} catch (Exception $e) {
    die("MongoDB connection error: " . $e->getMessage());
}
?>
