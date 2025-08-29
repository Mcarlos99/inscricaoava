-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 29/08/2025 às 03:48
-- Versão do servidor: 8.0.36-28
-- Versão do PHP: 8.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `inscricaoavadb`
--
DROP DATABASE IF EXISTS `inscricaoavadb`;
CREATE DATABASE IF NOT EXISTS `inscricaoavadb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `inscricaoavadb`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `prematriculas`
--

DROP TABLE IF EXISTS `prematriculas`;
CREATE TABLE `prematriculas` (
  `id` int NOT NULL,
  `polo_id` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `polo_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` int NOT NULL,
  `category_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zipcode` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `education_level` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_details` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `admin_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `prematriculas`
--

INSERT INTO `prematriculas` (`id`, `polo_id`, `polo_name`, `category_id`, `category_name`, `first_name`, `last_name`, `email`, `phone`, `cpf`, `address`, `city`, `state`, `zipcode`, `education_level`, `status`, `payment_method`, `payment_details`, `admin_notes`, `created_at`, `updated_at`) VALUES
(139, 'ava', 'AVA', 55, 'EJA- Educação de Jovens e Adultos ', 'MAURO CARLOS', 'DE SA', 'maurocarlos.ti@gmail.com', '(94) 98170-9809', '031.839.245-36', 'Rua Santo Antônio', 'Tucuruí', 'PA', '68458-471', '', 'approved', 'PIX', '', '', '2025-08-27 00:15:04', '2025-08-27 00:18:35'),
(140, 'ava', 'AVA', 27, 'Técnico em Eletromecânica ', 'Carlos', 'Santos', 'diego2008tuc@gmail.com', '(94) 98160-6474', '032.643.651-03', 'Rua Bragança n63', 'Tucuruí', 'PA', '68455-705', '', 'pending', NULL, NULL, NULL, '2025-08-27 17:42:45', '2025-08-27 17:42:45'),
(141, 'ava', 'AVA', 29, 'Técnico em Segurança do Trabalho', 'José Augusto ', ' caxias leão', 'caxiasaugusto798@gmail.com', '(94) 98425-1957', '037.262.512-60', 'Travessa Chico Mendes', '107', 'PA', '68455-705', 'medio', 'approved', 'Boleto', '10 X 150 VENCIMENTO DIA 10/09', '', '2025-08-28 15:01:10', '2025-08-28 15:02:30');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `prematriculas`
--
ALTER TABLE `prematriculas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_category` (`email`,`category_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `prematriculas`
--
ALTER TABLE `prematriculas`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=142;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
