<?php
// Configurações do banco de dados - ajuste conforme seu ambiente
$db_host = 'localhost';
$db_name = 'inscricaoavadb';
$db_user = 'inscricaoavauser';
$db_pass = '05hsqwjG8vLsIVBvQ7Iu';

try {
    // Conectar ao banco de dados
    $pdo = new PDO("mysql:host=$db_host;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Criar banco de dados se não existir
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$db_name`");
    
    // Criar tabela de pré-matrículas
    $pdo->exec("CREATE TABLE IF NOT EXISTS `prematriculas` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `polo_id` varchar(50) NOT NULL,
        `polo_name` varchar(100) NOT NULL,
        `category_id` int(11) NOT NULL,
        `category_name` varchar(255) NOT NULL,
        `first_name` varchar(100) NOT NULL,
        `last_name` varchar(100) NOT NULL,
        `email` varchar(255) NOT NULL,
        `phone` varchar(50) NOT NULL,
        `cpf` varchar(20) NOT NULL,
        `address` varchar(255) DEFAULT NULL,
        `city` varchar(100) DEFAULT NULL,
        `state` varchar(2) DEFAULT NULL,
        `zipcode` varchar(10) DEFAULT NULL,
        `education_level` varchar(50) DEFAULT NULL,
        `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
        `payment_method` varchar(50) DEFAULT NULL,
        `payment_details` text DEFAULT NULL,
        `admin_notes` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`id`),
        UNIQUE KEY `email_category` (`email`, `category_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    echo "Banco de dados e tabela criados com sucesso!";
    
} catch (PDOException $e) {
    die("Erro ao configurar banco de dados: " . $e->getMessage());
}
?>