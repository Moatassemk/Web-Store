<?php
require_once '../config/db.php';

class Product {
    public function getProducts($search = '', $page = 1) {
        global $conn;
        $limit = 12;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                JOIN categories c ON p.category_id = c.id 
                WHERE p.name LIKE :searchQuery 
                LIMIT :limit OFFSET :offset";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':searchQuery', '%' . $search . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProductById($id) {
        global $conn;
        $sql = "SELECT * FROM products WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
