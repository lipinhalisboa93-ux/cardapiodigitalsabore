<?php

$host = '127.0.0.1';
$port = '3307';
$db   = 'cardapio_digital';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
} catch (PDOException $e) {
    die('Erro ao conectar ao banco: ' . $e->getMessage());
}
?>
