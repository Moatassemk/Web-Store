<?php
require_once '../models/Product.php';

class ProductController {
    public function listProducts($search = '', $page = 1) {
        $productModel = new Product();
        return $productModel->getProducts($search, $page);
    }

    public function viewProduct($id) {
        $productModel = new Product();
        return $productModel->getProductById($id);
    }
}
