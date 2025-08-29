-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 26/08/2025 às 23:58
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
CREATE DATABASE IF NOT EXISTS `inscricaoavadb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `inscricaoavadb`;

-- --------------------------------------------------------

--
-- Estrutura para tabela `prematriculas`
--

DROP TABLE IF EXISTS `prematriculas`;
CREATE TABLE `prematriculas` (
  `id` int NOT NULL,
  `polo_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `polo_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` int NOT NULL,
  `category_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zipcode` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `education_level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_details` text COLLATE utf8mb4_unicode_ci,
  `admin_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `prematriculas`
--

INSERT INTO `prematriculas` (`id`, `polo_id`, `polo_name`, `category_id`, `category_name`, `first_name`, `last_name`, `email`, `phone`, `cpf`, `address`, `city`, `state`, `zipcode`, `education_level`, `status`, `payment_method`, `payment_details`, `admin_notes`, `created_at`, `updated_at`) VALUES
(18, 'breu-branco', 'Breu Branco', 1, 'Técnico em Enfermagem', 'Carlos', 'Santos', 'diego2008tuc@gmail.com', '(94) 98160-6474', '032.643.651-03', 'Rua Bragança n63', 'Tucuruí', 'PA', '68455-705', 'medio', 'approved', 'Boleto', '18x de 250', '', '2025-05-15 15:43:56', '2025-05-16 16:55:37'),
(19, 'breu-branco', 'Breu Branco', 1, 'Técnico em Enfermagem', 'queila ', 'reis da silva ', 'keilareis67890@gmail.com', '(94) 99249-6844', '052.257.332-04', 'valter junior ', 'breu branco', 'PA', '68488-000', 'superior', 'approved', 'Cartão de Crédito', '', '', '2025-05-15 17:26:41', '2025-05-15 17:29:01'),
(20, 'breu-branco', 'Breu Branco', 1, 'Técnico em Enfermagem', 'MICHELY ', ' SILVA DE OLIVEIRA VIANA', 'silvadeoliveiramichely@gmail.com', '(94) 99149-3406', '763.713.602-72', 'AV BRASILIA ', 'breu branco', 'PA', '68488-000', 'medio', 'approved', 'Cartão de Crédito', '', '', '2025-05-15 18:34:11', '2025-05-15 18:49:22'),
(21, 'breu-branco', 'Breu Branco', 1, 'Técnico em Enfermagem', 'RUCIENE ', 'DE SOUZA', 'lucienesouaz@gmail.com', '(94) 98448-0690', '702.834.582-32', 'WALTER JUNIOR ', 'breu branco', 'PA', '68488-000', 'medio', 'approved', 'Cartão de Crédito', '', '', '2025-05-15 18:38:40', '2025-05-15 18:46:18'),
(22, 'breu-branco', 'Breu Branco', 1, 'Técnico em Enfermagem', 'MILENA ', 'DA SILVA SANTOS', 'milenadasilvasantos434@gmail.com', '(94) 99140-5119', '047.552.382-26', 'RUA FLORIANO PEIXOTO', 'breu branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-15 18:51:43', '2025-05-15 19:19:43'),
(23, 'breu-branco', 'Breu Branco', 1, 'Técnico em enfermagem 09', 'TANIA ', 'NOMINATO LIMA', 'tanianominato@gmail.com', '(94) 98424-1506', '057.405.752-81', 'RUA: ARCO IRIS', 'breu branco', 'PA', '68488-000', '', 'approved', 'Cartão de Crédito', '', '', '2025-05-15 19:43:30', '2025-05-15 20:21:16'),
(24, 'breu-branco', 'Breu Branco', 1, 'Técnico em enfermagem 09', 'ELLY ', 'ESTUMANO FARIAS', 'ellyestumano123@gmail.com', '(94) 99124-7476', '004.660.972-55', 'RUA SAO TIAGO', 'breu branco', 'PA', '68488-000', '', 'approved', 'Cartão de Crédito', '', '', '2025-05-15 19:45:46', '2025-05-15 19:50:02'),
(25, 'breu-branco', 'Breu Branco', 1, 'Técnico em enfermagem 09', 'YASMIN ', 'FERREIRA GALVÃO', 'ferreiragalvaoyasmim@gmail.com', '(94) 99135-7615', '057.701.232-06', 'MALHEIRO MOTA ', '', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-15 19:52:18', '2025-05-15 20:21:31'),
(26, 'breu-branco', 'Breu Branco', 1, 'Técnico em enfermagem 09', 'MARIA EDUARDA ', ' FERREIRA DOS SANTOS', 'dudinhaferreira124810@gmail.com', '(94) 98400-5642', '081.882.062-45', 'SÃO LUCAS', 'breu branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-15 19:54:00', '2025-05-15 20:21:40'),
(27, 'breu-branco', 'Breu Branco', 1, 'Técnico em enfermagem 09', 'BEATRIZ ', 'DA SILVA MACEDO', 'beatrizsilvamacedo2022@gmail.com', '(94) 99128-0849', '051.431.542-35', 'RUA SUDOESTE', 'breu branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-15 19:55:48', '2025-05-15 20:21:49'),
(28, 'breu-branco', 'Breu Branco', 1, 'Técnico em enfermagem 09', 'INAJARA ', ' DIAS DE OLIVEIRA', 'klebersantos1077@gmail.com', '(94) 99153-1594', '014.043.832-71', 'RUA SÃO PEDRO', 'breu branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-15 19:59:34', '2025-05-15 20:21:54'),
(29, 'breu-branco', 'Breu Branco', 1, 'Técnico em enfermagem 09', 'RAILA ', 'NASCIMENTO DE SENA', 'rayllanascimento14@gmail.com', '(94) 99273-5068', '063.704.472-00', 'QUADRA 10 LOTE 11', 'breu branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-15 20:04:23', '2025-05-15 20:22:08'),
(30, 'breu-branco', 'Breu Branco', 1, 'Técnico em enfermagem 09', 'CRISTIANE ', ' BATISTA NASCIMENTO', 'cristianebatistan@gmail.com', '(94) 99155-8600', '780.005.302-44', 'RUA PIAUÍ', 'breu branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-15 20:06:58', '2025-05-15 20:22:15'),
(31, 'breu-branco', 'Breu Branco', 1, 'Técnico em enfermagem 09', 'ADRIELLY ', 'DA CRUZ BORGES DOS SANTOS', 'adriellyborges34@gmail.com', '(91) 98559-0825', '054.711.862-73', 'SÃO LUCAS ', 'breu branco', 'PA', '68488-000', 'medio', 'approved', 'Cartão de Crédito', '', '', '2025-05-15 20:10:24', '2025-05-15 20:22:22'),
(32, 'breu-branco', 'Breu Branco', 1, 'Técnico em enfermagem 09', 'VANESSA', ' DO CARMO MOTA', 'vermota1068@gmail.com', '(94) 99198-8300', '086.986.252-90', 'AREAL PITINGA ', 'breu branco', 'PA', '68488-000', 'medio', 'approved', 'Cartão de Crédito', '', '', '2025-05-15 20:13:12', '2025-05-15 20:34:42'),
(33, 'breu-branco', 'Breu Branco', 1, 'Técnico em enfermagem 09', 'CYNTHIA CAROLINE CUNHA SANTOS', 'CUNHA SANTOS', 'cynthiacarolinnecunhaa@gmail.com', '(94) 99184-3342', '073.045.822-94', 'DEPUTADO BELÉM ', 'breu branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-15 20:28:24', '2025-05-15 20:34:52'),
(34, 'breu-branco', 'Breu Branco', 1, 'Técnico em enfermagem 09', 'VITORIA ', 'SOUSA PEREIRA', 'vp390282@gmail.com', '(94) 99102-7906', '017.901.552-40', 'SÃO MATEUS ', 'breu branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-15 20:31:37', '2025-05-15 20:39:04'),
(35, 'breu-branco', 'Breu Branco', 1, 'Técnico em enfermagem 09', 'KATIANA', 'GOMES CRUZ', 'katianegomescruz7@gmail.com', '(91) 99169-2555', '053.291.842-82', 'SÃO MARCOS ', 'breu branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-15 20:37:12', '2025-05-16 14:46:18'),
(36, 'breu-branco', 'Breu Branco', 21, 'Técnico em Enfermagem 08', 'Geovana ', 'Lima alves', 'alvesvana07@gmail.com', '(94) 99289-2705', '103.788.452-33', 'Olinda cavalgante ', 'Breu Branco ', 'PA', '68488-000', 'tecnico', 'approved', 'Boleto', '', '', '2025-05-16 19:57:49', '2025-05-16 20:08:14'),
(37, 'breu-branco', 'Breu Branco', 1, 'Técnico em Enfermagem 09', 'Vitória Sousa', 'Pereira ', 'vp390272@gmail.com', '(94) 99102-7906', '017.901.552-40', 'Rua: São Mateus, N°16 , Bairro: Santa Catarina ', 'Breu Branco ', 'PA', '68488-000', 'tecnico', 'approved', 'Boleto', '', '', '2025-05-16 19:58:51', '2025-05-16 20:08:01'),
(38, 'breu-branco', 'Breu Branco', 1, 'Técnico em Enfermagem 09', 'Diely ', 'Maranhão ', 'dielymaranhao1@gmail.com', '(94) 99235-3276', '047.368.092-05', 'Zona rural ', 'Breu branco ', 'PA', '68488-000', 'tecnico', 'approved', 'Boleto', '', '', '2025-05-16 20:02:45', '2025-05-16 20:07:51'),
(39, 'breu-branco', 'Breu Branco', 16, 'Técnico em Enfermagem 10', 'Jhennfyr', 'Valadares ', 'jhenyvaladares83@gmail.com', '(94) 99142-6146', '058.564.522-16', 'Santa André ', 'Breu Branco ', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-16 20:07:53', '2025-05-16 20:08:09'),
(40, 'breu-branco', 'Breu Branco', 21, 'Técnico em Enfermagem 08', 'Kauany vitória ', 'Vitória ', 'kauany.vitoria789@icloud.com', '(94) 99290-9066', '062.540.772-56', 'Rua b número 12', 'Breu Branco ', 'PA', '68488-000', 'tecnico', 'approved', 'Boleto', '', '', '2025-05-16 20:10:53', '2025-05-16 20:52:52'),
(41, 'breu-branco', 'Breu Branco', 16, 'Técnico em Enfermagem 10', 'Maria Eduarda', 'Silva martins ', 'duddamartins0223@gmail.com', '(94) 98413-1758', '054.783.332-17', 'Rua Marcelina Alves ', 'Breu Branco ', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-16 20:14:13', '2025-05-16 20:53:45'),
(42, 'breu-branco', 'Breu Branco', 1, 'Técnico em Enfermagem 09', 'Vanessa do Carmo ', 'Mota', 'Vmota1068@gmail.com', '(94) 99198-8300', '086.986.252-90', 'Rua Piauí', 'Breu Branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-16 20:15:54', '2025-05-16 20:53:39'),
(43, 'breu-branco', 'Breu Branco', 16, 'Técnico em Enfermagem 10', 'Vanessa do Carmo', 'Mota', 'Vmota1068@gmail.com', '(94) 99198-8300', '086.986.252-90', 'Rua Piauí', 'Breu Branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-16 20:18:10', '2025-05-16 20:53:35'),
(44, 'breu-branco', 'Breu Branco', 16, 'Técnico em Enfermagem 10', 'Yandra ', 'Carine ', 'yandracarine244@gmail.com', '(94) 98419-1403', '086.602.922-27', 'Costa Rica Bairro continental ', 'Breu Branco ', 'PA', '68488-000', 'tecnico', 'approved', 'Boleto', '', '', '2025-05-16 20:27:20', '2025-05-16 20:53:28'),
(45, 'breu-branco', 'Breu Branco', 1, 'Técnico em Enfermagem 09', 'Wectory ', 'Cley Lima da Costa', 'wcostalimaw@gmail.com', '(94) 98427-2782', '064.968.362-56', 'Rua Santos Dumont', 'Tucuruí ', 'PA', '68456-460', 'superior', 'approved', 'Boleto', '', '', '2025-05-16 20:30:37', '2025-05-16 20:53:22'),
(46, 'breu-branco', 'Breu Branco', 16, 'Técnico em Enfermagem 10', 'Yandra ', 'Carine ', 'yandracarine224@gmail.com', '(94) 98419-1403', '086.602.922-27', 'Costa Rica Bairro continental ', 'Breu branco ', 'PA', '68488-000', 'tecnico', 'approved', 'Boleto', '', '', '2025-05-16 20:31:12', '2025-05-16 20:53:14'),
(47, 'breu-branco', 'Breu Branco', 21, 'Técnico em Enfermagem 08', 'Cassiane ', 'Bispo Carmo ', 'bispocarmocassiane@gmail.com', '(94) 99203-0363', '059.746.812-58', 'Novo Horizonte. R:Bahia N:127 ', 'Breu branco ', 'PA', '68488-000', 'tecnico', 'approved', 'Boleto', '', '', '2025-05-16 20:33:39', '2025-05-16 20:53:07'),
(48, 'breu-branco', 'Breu Branco', 16, 'Técnico em Enfermagem 10', 'Rebeca ', 'Almeida Sousa Brasil ', 'bebecaalmeida951@gmail.com', '(94) 99207-1775', '062.796.462-11', 'Av Sebastião Camargo Correa ', 'Breu Branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-16 20:34:00', '2025-05-16 20:53:01'),
(49, 'breu-branco', 'Breu Branco', 21, 'Técnico em Enfermagem 08', 'Natália ', 'Bezerra Ferreira ', 'nathaliaferreira30005@gmail.com', '(94) 99296-9710', '079.255.792-18', 'Rua: são Pedro N°18', 'Breu Branco ', 'PA', '68488-000', 'tecnico', 'approved', 'Boleto', '', '', '2025-05-16 20:56:57', '2025-05-16 21:07:25'),
(50, 'breu-branco', 'Breu Branco', 21, 'Técnico em Enfermagem 08', 'Francineide Brandão', 'Cunha', 'francineidebrandao024@gmail.com', '(94) 99202-1866', '015.892.922-51', 'João Pereira ', 'Breu Branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-16 21:19:47', '2025-05-19 12:21:24'),
(51, 'breu-branco', 'Breu Branco', 16, 'Técnico em Enfermagem 10', 'Sileia ', 'Da Costa  Rodrigues de Sousa ', 'Sileiadacostarodriguesdesousa@gmail.com', '(94) 99286-5597', '008.558.772-25', 'Leonina babosa ', 'Breu Branco ', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-16 22:42:33', '2025-05-19 12:21:19'),
(52, 'breu-branco', 'Breu Branco', 21, 'Técnico em Enfermagem 08', 'ARLETIANE ', 'MARANGUAPE DA SILVA SANTOS', 'arlet.maranguape@gmail.com', '(94) 99128-5379', '947.579.682-72', 'RUA BAHIA Nº97', 'BREU BRANCO', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-16 23:59:46', '2025-05-19 12:21:13'),
(53, 'breu-branco', 'Breu Branco', 16, 'Técnico em Enfermagem 10', 'Ariana ', 'Cipriano ', 'arianacipriano010@gmail.com', '(94) 99155-039', '028.142.482-94', 'Rua da jaqueira número 100 conquista ', 'Breu branco ', 'PA', '68488-00', 'medio', 'approved', 'Boleto', '', '', '2025-05-17 00:57:22', '2025-05-19 12:21:07'),
(54, 'breu-branco', 'Breu Branco', 16, 'Técnico em Enfermagem 10', 'Gesianne', 'Alves Mourão ', 'gesiannealves896@gmail.com', '(94) 98411-0193', '618.411.923-09', 'Travessa Amazonas ', 'Breu branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-17 01:45:13', '2025-05-19 12:21:01'),
(55, 'breu-branco', 'Breu Branco', 16, 'Técnico em Enfermagem 10', 'Rafael  ', 'Araújo Dos Santos ', 'todinwar@gmail.com', '(94) 99242-1451', '052.320.912-63', 'Atravessar esperança ', 'Breu branco ', 'PA', '68488-000', '', 'approved', 'Boleto', '', '', '2025-05-17 02:11:48', '2025-05-19 12:20:54'),
(56, 'breu-branco', 'Breu Branco', 21, 'Técnico em Enfermagem 08', 'JAQUELINA SOUSA ', 'SILVA ', 'silvajaquelina540@gmail.com', '(94) 99134-7158', '883.555.192-72', 'Parauapebas quadra 12 Lote 12 Ismar Vilela 01 ', 'Breu Branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-17 03:16:16', '2025-05-19 12:20:47'),
(57, 'breu-branco', 'Breu Branco', 16, 'Técnico em Enfermagem 10', 'Luana ', 'Gaia Barbosa ', 'jonataluana1@gmail.com', '(91) 99397-4850', '055.021.971-40', 'NSRA DA CONCEIÇÃO, 31', 'Baião', 'PA', '68465-00', 'medio', 'approved', 'Boleto', '', '', '2025-05-17 11:38:50', '2025-05-19 12:20:39'),
(58, 'breu-branco', 'Breu Branco', 1, 'Técnico em Enfermagem 09', 'Leidiane maia trindade ', 'Maia ', 'leiditrindade195@gmail.com', '(94) 99145-3800', '082.775.992-42', 'Vila muru', 'Breu branco ', 'PA', '68480-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-17 11:47:49', '2025-05-19 12:20:25'),
(59, 'breu-branco', 'Breu Branco', 21, 'Técnico em Enfermagem 08', 'Maria de Fátima ', 'Pinto Gonçalves ', 'fatimagalsao2004@gmail.com', '(94) 99153-6719', '049.758.622-39', 'Rua: Malheiro moto, 108   B: Conquista ', 'Breu branco ', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-17 19:55:39', '2025-05-19 12:20:15'),
(60, 'breu-branco', 'Breu Branco', 16, 'Técnico em Enfermagem 10', 'Suliane  ', 'Sacramento ferreira ', 'sulianeferreira39@gmail.com', '(55) 94992-0191', '024.513.652-50', 'Estrada das criolas', 'Breu branco ', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-18 12:02:56', '2025-05-19 12:20:06'),
(61, 'breu-branco', 'Breu Branco', 21, 'Técnico em Enfermagem 08', 'Andrielly da cruz Borges ', 'Dos santos ', 'andriellyborges14@gmail.com', '(94) 98446-7543', '054.711.862-73', 'Rua Ceará bairro Novo Horizonte', 'Breu Branco ', 'PA', '', 'medio', 'approved', 'Boleto', '', '', '2025-05-18 12:10:01', '2025-05-19 12:19:53'),
(62, 'breu-branco', 'Breu Branco', 16, 'Técnico em Enfermagem 10', 'Valéria da Silva dos Santos ', 'Silva ', 'vs8819868@gmail.com', '(94) 99208-7013', '021.708.232-71', 'Joaquim Fonseca ', 'Breu branco ', 'PA', '68488-000', '', 'approved', 'Boleto', '', '', '2025-05-18 13:45:08', '2025-05-19 12:19:47'),
(63, 'breu-branco', 'Breu Branco', 16, 'Técnico em Enfermagem 10', 'Rute ', 'Silva Vieira de Oliveira ', 'rutesilvavieira26@gmail.com', '(94) 99228-3115', '052.801.912-05', 'Filadélfia, 35', 'Breu Branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-19 16:12:26', '2025-05-20 12:33:54'),
(64, 'breu-branco', 'Breu Branco', 21, 'Técnico em Enfermagem 08', 'ANA PAULA', 'APPOLINARIO', 'paulaappolinario17@gmail.com', '(94) 99944-6465', '990.026.392-87', 'av minas gerais, 332, centro', 'breu branco', 'PA', '68488-000', 'superior', 'approved', 'Boleto', '', '', '2025-05-19 18:05:36', '2025-05-20 12:34:12'),
(65, 'breu-branco', 'Breu Branco', 1, 'Técnico em Enfermagem 09', 'Emilly', 'Lopes Matos ', 'matosemilly485@gmail.com', '(94) 99272-3092', '057.701.382-15', 'Rua marabá Qd 15 Lt 27, Bairro vilela 01 ', 'Breu Branco ', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-19 19:53:11', '2025-05-20 12:34:02'),
(66, 'breu-branco', 'Breu Branco', 21, 'Técnico em Enfermagem 08', 'Taissa ', 'Silva Souza ', 'staissa700@gmail.com', '(94) 99270-0798', '062.818.752-14', 'Travessa Apóstolo Paulo ', 'BREU BRANCO', 'PA', '68488-000', 'tecnico', 'approved', 'Boleto', '', '', '2025-05-20 22:26:06', '2025-05-21 19:10:22'),
(67, 'breu-branco', 'Breu Branco', 21, 'Técnico em Enfermagem 08', 'Juliana ', 'Medeiros Gonçalves ', 'julianamedeiros380@gmail.com', '(94) 99974-6329', '039.285.962-92', 'Travessa apostolo Paulo ', 'Breu Branco ', 'PA', '68488-000', 'tecnico', 'approved', 'Boleto', '', '', '2025-05-20 22:28:35', '2025-05-21 19:10:10'),
(68, 'breu-branco', 'Breu Branco', 21, 'Técnico em Enfermagem 08', 'Maria luziane de Sousa ', 'Sousa', 'marialuzianedesousa9@gmail.com', '(94) 99165-4488', '058.070.612-50', 'Reginaldo bonfim 67', 'Breu branco ', 'PA', '68488-000', 'tecnico', 'approved', 'Boleto', '', '', '2025-05-20 22:37:12', '2025-05-21 19:10:03'),
(69, 'breu-branco', 'Breu Branco', 21, 'Técnico em Enfermagem 08', 'Gabrielly ', 'Silva Lima ', 'gabriellysandes2526@gmail.com', '(94) 99252-2974', '082.615.662-26', 'Rua Maranhão, N° 53 , Bairro Novo Horizonte ', 'Breu Branco ', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-05-21 15:06:52', '2025-05-21 19:09:47'),
(71, 'breu-branco', 'Breu Branco', 16, 'Técnico em Enfermagem 10', 'Raquel ', ' pereira morais', 'raquelpereira0804@gmail.com', '(94) 99157-0978', '781.525.182-04', 'rua: pastor Araújo', 'Breu Branco', 'PA', '68488-000', 'pos', 'approved', 'Boleto', '', '', '2025-06-10 19:23:48', '2025-07-01 16:29:29'),
(72, 'breu-branco', 'Breu Branco', 21, 'Técnico em Enfermagem 08', 'Erlaiane ', 'Cantão de Souza ', 'erlaianecantaodesouza@gamil.com', '(94) 99157-7603', '051.180.542-06', 'Conquista', 'Breu Branco ', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-06-13 22:02:21', '2025-07-01 16:29:35'),
(73, 'breu-branco', 'Breu Branco', 1, 'Técnico em Enfermagem ', 'Carlos', 'Santos', 'diretor@magalhaes-edu.com.br', '(94) 98160-6474', '034.576.932-50', 'Rua Bragança n63', 'Tucuruí', 'PA', '68455-705', 'medio', 'approved', 'Boleto', '', '', '2025-06-19 18:22:02', '2025-06-19 18:22:25'),
(74, 'breu-branco', 'Breu Branco', 1, 'CURSOS TÉCNICOS ', 'Joelma ', 'Costa Leandro', 'joelmacostaleandro@hotmail.com', '(94) 99953-5869', '009.311.772-86', 'Rua Maranhão ', 'Breu Branco', 'PA', '68488-000', 'superior', 'approved', 'Boleto', '', '', '2025-06-21 22:04:57', '2025-06-24 13:44:33'),
(75, 'breu-branco', 'Breu Branco', 1, 'CURSOS TÉCNICOS ', 'Paula ', 'Corrêa', 'paulaccgcorrea@gmail.com', '(91) 99370-5303', '026.837.622-08', 'Travessa Coronel Vitório', 'Igarapé-Miri', 'PA', '68430-000', 'pos', 'approved', 'Boleto', '', '', '2025-06-24 13:37:30', '2025-06-24 13:44:26'),
(77, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Joice ', 'Oliveira Dias ', 'joiceolivdias123@gmail.com', '(94) 98414-803', '036.360.082-50', 'Rua vitória Araújo número 15', 'Breu Branco ', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-06-26 23:05:04', '2025-07-01 16:29:41'),
(78, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Erlaiane ', 'Cantão de Souza ', 'erlaianecantaodesouza@gmail.com', '(94) 99157-7603', '051.180.542-06', 'Conquista, rua Rafael paz, número 48', 'Breu Branco ', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-06-27 00:57:57', '2025-07-01 16:29:45'),
(79, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Francineide Brandão cunha ', 'Brandão ', 'francineidebrandao024@gmail.com', '(94) 99202-1866', '015.892.922-51', 'João Pereira 50', 'Breu Branco ', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-06-27 17:56:05', '2025-07-01 16:29:49'),
(80, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Maria luziane de Sousa ', 'Sousa', 'marialuzianedesousa9@gmail.com', '(94) 99165-4488', '058.070.612-50', 'Reginaldo bonfim 67', 'Breu branco ', 'PA', '68488-000', '', 'approved', 'Boleto', '', '', '2025-06-27 18:25:32', '2025-07-01 16:29:53'),
(81, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Poliane ', 'Cardoso Leite ', 'polianaleite9100@gmail.com', '(94) 99290-8362', '031.558.852-70', 'Vila sapucaia ', 'Breu Branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-06-27 20:12:12', '2025-07-01 16:29:59'),
(82, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Jose Antonio ', 'Da Costa Nascimento ', 'jdacostanascimento5@gmail.com', '(91) 98584-2277', '030.718.652-01', 'Rua Piauí  n,97', 'Breu Branco ', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-06-30 20:49:44', '2025-07-01 16:30:04'),
(83, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Daniela', 'Nascimento rosa', 'danielanascimentorosa107@gmail.com', '(94) 99661-4541', '028.160.292-12', 'Vilela dois', 'Breu branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-06-30 22:17:34', '2025-07-01 16:30:12'),
(84, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Jossenira  ', ' Miranda Rodrigues', 'josseniramiranda909@gmail.com', '(94) 99295-1705', '720.847.232-72', 'Rua São Tomé ', 'Breu Branco ', 'PA', '68480-00', 'medio', 'approved', 'Boleto', '', '', '2025-07-01 22:23:57', '2025-07-03 13:07:19'),
(85, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Roseane ', 'Maia ', 'maiaroseane90@gmail.com', '(94) 99128-5496', '013.437.952-73', 'Rua; Bahia 42', 'Breu Branco ', 'PA', '68488-000', 'fundamental', 'approved', 'Boleto', '', '', '2025-07-01 22:30:47', '2025-07-03 13:07:11'),
(86, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Letícia de Abreu pinto ', 'Abreu ', 'leticiaabreu0905@gmail.com', '(94) 99263-5362', '058.334.612-00', 'Muniz Lopes ', 'Breu Branco ', 'PA', '68480-00', 'medio', 'approved', 'Boleto', '', '', '2025-07-01 22:32:15', '2025-07-03 13:07:04'),
(87, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Daniela', 'Nascimento rosa', 'danielanascimentorosa197@gmail.com', '(94) 99661-4541', '028.160.292-12', 'Vilela dois', 'Breu branco', 'PA', '68488-000', 'medio', 'approved', 'Boleto', '', '', '2025-07-03 23:36:47', '2025-07-08 13:32:26'),
(88, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Vanessa do Carmo', 'Mota', 'Vmota1068@gmail.com', '(94) 99198-8300', '086.986.252-90', 'Rua Piauí', 'Breu Branco', 'PA', '68488-000', 'medio', 'approved', 'PIX', '', '', '2025-07-12 14:48:29', '2025-07-16 17:23:54'),
(89, 'breu-branco', 'Breu Branco', 29, 'Técnico em Segurança do Trabalho', 'JOÃO', 'PAULO MIRANDA DA COSTA', 'paulomiranda3378@gmail.com', '(94) 99239-0270', '074.707.942-04', 'TANCREDO NEVES', 'BREU BRANCO', 'PA', '68488-000', 'tecnico', 'approved', 'PIX', '', '', '2025-07-15 12:34:27', '2025-07-16 17:23:44'),
(91, 'breu-branco', 'Breu Branco', 52, 'Módulo I | Técnico em eletrotécnica ', 'ARTUR', 'GUILHERME CARVALHO MONTEIRO', 'rodriguescarvalho107@gmail.com', '(94) 98132-1651', '068.625.982-36', 'RUA FRANÇA, 23, BAIRRO CONTINENTAL', 'BREU BRANCO', 'PA', '68488-000', 'medio', 'approved', 'Cartão de Crédito', '', '', '2025-07-18 17:22:43', '2025-07-22 14:44:24'),
(92, 'breu-branco', 'Breu Branco', 44, 'Módulo III | Técnico em enfermagem ', 'MARIA', 'LUZIANE DE SOUSA SOUSA', 'marialuzianedesousa9@gmail.com', '(94) 99165-4488', '058.070.612-50', 'Reginaldo bonfim 67', 'BREU BRANCO', 'PA', '68488-000', 'tecnico', 'approved', 'PIX', '', '', '2025-07-23 12:30:45', '2025-07-23 12:31:23'),
(93, 'breu-branco', 'Breu Branco', 45, 'Módulo IV | Técnico em enfermagem ', 'Reginaldo ', 'Araújo', 'reggisantos11@gmail.com', '(94) 98174-9547', '015.515.572-58', 'Rua Floriano Peixoto. Bairro Bela vista.  73b', 'Breu branco ', 'PA', '68488-000', 'medio', 'approved', 'PIX', '', '', '2025-07-23 23:27:23', '2025-07-24 11:31:33'),
(94, 'breu-branco', 'Breu Branco', 45, 'Módulo IV | Técnico em enfermagem ', 'Hudson ', 'Daniel', 'hodsondaniel2007@gmail.com', '(94) 98434-8541', '052.867.872-82', 'B: Novo Horizonte R: São Luís N°: 9B', 'Breu Branco ', 'PA', '68488-000', 'tecnico', 'approved', 'PIX', '', '', '2025-07-24 14:22:00', '2025-07-24 14:43:25'),
(108, 'ava', 'AVA', 52, 'Módulo I | Técnico em eletrotécnica ', 'Joana', ' Farias Caldas', 'joana-stefany@hotmail.com', '(94) 98171-0356', '794.174.692-15', '', '', 'PA', '', '', 'approved', 'Boleto', '', '', '2025-07-31 19:19:05', '2025-07-31 19:19:43'),
(109, 'ava', 'AVA', 52, 'Módulo I | Técnico em eletrotécnica ', 'GUILHERME', 'SOUSA LOPES', 'guilherme.slopes20@gmail.com', '(94) 99288-7580', '063.856.112-48', 'avenida getulio vargas, 43, Bela vista', 'breu branco', 'PA', '68488-000', '', 'approved', 'PIX', '', '', '2025-07-31 19:31:17', '2025-07-31 19:31:59'),
(110, 'ava', 'AVA', 52, 'Módulo I | Técnico em eletrotécnica ', 'MAURO CARLOS', 'DE SA', 'diego2008tuc@gmail.com', '(94) 98170-9809', '031.839.245-36', 'Rua Santo Antônio', 'Tucuruí', 'PA', '68458-471', '', 'approved', 'PIX', '', '', '2025-07-31 19:35:09', '2025-07-31 19:35:56'),
(111, 'breu-branco', 'Breu Branco', 48, 'Módulo III | Técnico segurança do trabalho ', 'ANA LÚCIA', 'RUBINO', 'rubinoanalucia@icloud.com', '(11) 98995-9254', '128.234.488-96', 'RUA ALBERTO FLORES', 'SÃO PAULO', 'SP', '03558-000', 'tecnico', 'approved', 'Boleto', '', '', '2025-07-31 22:00:00', '2025-08-01 19:36:40'),
(112, 'ava', 'AVA', 52, 'Módulo I | Técnico em eletrotécnica ', 'Carlos tu', 'Santos', 'diego20086tuc@gmail.com', '(94) 98160-6474', '025.789.472-10', 'Rua Bragança n63', 'Tucuruí', 'PA', '68455-705', 'fundamental', 'approved', 'Boleto', '', '', '2025-08-01 19:36:27', '2025-08-01 19:36:49'),
(113, 'breu-branco', 'Breu Branco', 42, 'Módulo I | Técnico em enfermagem ', 'LENIMAR', 'FERNANDES SILVA', 'LENAFERNANDES5978@GMAIL.COM', '(94) 99159-7883', '860.419.672-20', 'RUA JERICÓ, 24 BAIRRO LIBERDADE', 'BREU BRANCO', 'PA', '68488-000', 'tecnico', 'approved', 'PIX', '', '', '2025-08-02 11:27:27', '2025-08-02 11:28:01'),
(114, 'ava', 'AVA', 28, 'Técnico em Eletrotécnica ', 'Erik', 'Lisboa Caldas', 'eriklcaldas0@gmail.com', '(94) 98125-4733', '029.070.762-56', '', '', 'PA', '', '', 'approved', 'Cartão de Crédito', '', '', '2025-08-05 16:58:41', '2025-08-05 16:59:08'),
(115, 'ava', 'AVA', 29, 'Técnico em Segurança do Trabalho', 'Lucas', 'Ribeiro', 'lucascarvalhoribeiro7@gmail.com', '(94) 99220-9459', '047.095.332-29', '', '', 'PA', '', '', 'approved', 'Boleto', '', '', '2025-08-05 17:01:39', '2025-08-05 17:03:19'),
(116, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Maria vivian', 'De Sousa da silva', 'silvavivian236@gmail.com', '(94) 99908-9023', '071.267.982-02', 'Alfonso pena ', 'Breu branco ', 'PA', '68488-000', 'tecnico', 'approved', 'PIX', '', '', '2025-08-05 22:40:42', '2025-08-05 22:47:05'),
(117, 'ava', 'AVA', 26, 'Técnico em Enfermagem ', 'Antônia Alana ', 'Gomes ', 'gomes.rios@icloud.com', '(66) 99237-8303', '067.076.202-40', '', '', 'PA', '', '', 'approved', 'Boleto', '', '', '2025-08-06 17:53:01', '2025-08-06 17:53:18'),
(118, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Diego ', 'Cruz Viana ', 'diegocruzviana7@gmail.com', '(94) 99192-4730', '060.854.272-55', 'Rua Gabriel Barbosa 89 Conquista ', 'Breu Branco ', 'PA', '68488-000', '', 'approved', 'PIX', '', '', '2025-08-07 01:28:11', '2025-08-07 13:12:50'),
(119, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Raquel ', 'Castro Silva', 'raquelcastroel26@gmail.com', '(94) 99157-2510', '031.936.312-05', 'VILA MURU', 'breu branco', 'PA', '68488-000', 'tecnico', 'approved', 'PIX', '', '', '2025-08-07 13:11:06', '2025-08-07 13:13:01'),
(120, 'ava', 'AVA', 27, 'Técnico em Eletromecânica ', 'Karla', 'MACEDO', 'diego2008tuc@gmail.com', '(94) 98160-6474', '516.552.60', 'Rua Bragança n63', 'Tucuruí', 'PA', '68455-705', '', 'approved', 'Boleto', '', '', '2025-08-07 17:16:34', '2025-08-07 17:17:11'),
(121, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Raimundo ', 'Aragão ', 'raimundoaragao69@gmail.com', '(94) 99279-0313', '909.439.962-87', 'Vila murú ', 'Breu branco ', 'PA', '68488-000', 'tecnico', 'approved', 'PIX', '', '', '2025-08-08 00:16:30', '2025-08-08 00:19:52'),
(122, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Benezaide', 'Gonçalves pantoja', 'pantojabenezaide@gmail.com', '(94) 99260-9938', '958.538.042-00', 'Vila muru', 'Breu Branco', 'PA', '68488-000', 'tecnico', 'approved', 'Cartão de Crédito', '', '', '2025-08-08 00:20:06', '2025-08-11 16:01:17'),
(123, 'ava', 'AVA', 55, 'EJA- Educação de Jovens e Adultos ', 'Welida Ariadne ', 'Silva', 'welidacontabil@gmail.com', '(94) 99196-1751', '019.329.802-37', '', '', 'PA', '', '', 'approved', 'Cartão de Crédito', '', '', '2025-08-11 16:00:54', '2025-08-11 16:01:32'),
(124, 'breu-branco', 'Breu Branco', 29, 'Técnico em Segurança do Trabalho', 'BRUNO', 'COELHO MEDEIROS', 'brunocoelho789hotmail.com@gmail.com', '(94) 98155-330', '048.925.622-89', 'TV. GOIANIA, N 134, BAIRRO JD. COLORADO', 'TUCURUI', 'PA', '68456-902', 'medio', 'approved', 'PIX', '', '', '2025-08-11 22:40:06', '2025-08-11 22:40:57'),
(125, 'ava', 'AVA', 29, 'Técnico em Segurança do Trabalho', 'JOÃO', 'PAULO MIRANDA DA COSTA', 'vinancioscj@gmail.com', '(94) 99239-0270', '074.707.942-04', 'TANCREDO NEVES', 'BREU BRANCO', 'PA', '68488-000', 'tecnico', 'approved', 'PIX', '', '', '2025-08-12 13:32:48', '2025-08-12 13:33:34'),
(126, 'ava', 'AVA', 28, 'Técnico em Eletrotécnica ', 'Hyan Feitosa ', 'Silva ', 'hyansilva1155@gmail.com', '(91) 99846-8268', '064.821.162-21', '', '', 'PA', '', '', 'approved', 'Boleto', '', '', '2025-08-12 20:11:14', '2025-08-12 20:11:30'),
(127, 'ava', 'AVA', 29, 'Técnico em Segurança do Trabalho', 'BRUNO COELHO', 'MEDEIROS', 'vinicius.jesus.estudos@gmail.com', '(94) 98155-3307', '048.925.622-89', 'TV. GOIANIA, N 134, BAIRRO Jardim COLORADO', 'TUCURUÍ', 'PA', '68488-000', 'tecnico', 'approved', 'PIX', '', '', '2025-08-13 12:28:31', '2025-08-13 12:29:03'),
(128, 'ava', 'AVA', 29, 'Técnico em Segurança do Trabalho', ' Odair José Guimarães ', 'Pinto Júnior', 'leaoj900@gmail.com', '(94) 99251-2274', '049.500.522-33', '', '', 'PA', '', '', 'approved', 'PIX', '', '', '2025-08-14 16:21:17', '2025-08-14 16:21:34'),
(129, 'ava', 'AVA', 28, 'Técnico em Eletrotécnica ', 'Felipe ', 'Rodrigues da silva ', 'phelliperodrigues57@gmail.com', '(94) 99155-4963', '049.029.832-06', '', '', 'PA', '', '', 'approved', 'PIX', '', '', '2025-08-15 00:03:59', '2025-08-16 17:44:27'),
(130, 'ava', 'AVA', 29, 'Técnico em Segurança do Trabalho', 'KARLA VITORIA ', 'SANTOS ', 'karlavitoriamix@gmail.com', '(94) 99173-5757', '043.600.432-10', '', '', 'PA', '', '', 'approved', 'Boleto', '', '', '2025-08-18 17:06:11', '2025-08-18 20:36:07'),
(131, 'ava', 'AVA', 28, 'Técnico em Eletrotécnica ', 'Rafael Moreira Vasconcelos ', 'Vasconcelos ', 'rafaelvasconcelos561@gmail.cm', '(94) 99223-7490', '011.616.752-13', '', '', 'PA', '', '', 'approved', 'Boleto', '', '', '2025-08-19 15:07:47', '2025-08-19 15:09:06'),
(132, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'RAURE', 'ANE SANTANA ALVES', 'raure.ane30@gmail.com', '(94) 99281-4079', '012.732.752-50', 'RUA RONDON DO PARÁ - QD 10 - LT 7', 'BREU BRANCO', 'PA', '68488-000', 'tecnico', 'approved', 'PIX', '', '', '2025-08-20 14:43:01', '2025-08-20 14:43:29'),
(133, 'tucurui', 'Tucuruí', 27, 'Técnico em Eletromecânica ', 'MAURO', 'DE SA', 'maurocarlos.ti@gmail.com', '(94) 98170-9809', '031.839.245-36', 'Rua Santo Antônio,842', 'Tucuruí', 'PA', '68458-471', 'pos', 'approved', 'PIX', '', '', '2025-08-20 18:58:00', '2025-08-20 18:58:34'),
(134, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Damião ', 'Simoes de Souza ', 'simoesdamiao90@gmail.com', '(94) 99150-7283', '509.749.982-49', 'Rua: Pastor Araújo n.42 Bairro Conquista ', 'Breu Branco ', 'PA', '68488-000', 'medio', 'approved', 'PIX', '', '', '2025-08-21 00:01:02', '2025-08-22 21:57:34'),
(135, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'Débora ', 'Dos santos da silva', 'debrinhanogueira@gmail.com', '(94) 99953-9358', '835.776.372-34', 'Zona rural vicinal tracajá Açu ', 'Breu Branco ', 'PA', '68488-000', 'medio', 'approved', 'PIX', '', '', '2025-08-21 09:55:00', '2025-08-22 21:57:49'),
(136, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'FLAVIA ', 'DA SILVA LIMA BARRADAS', 'flaviabarradas337@gmail.com', '(94) 99201-8529', '011.475.892-16', 'RUA ESPERANÇA, 47B, BAIRRO NOVO PARAISO.', 'breu branco', 'PA', '68488-000', 'tecnico', 'approved', 'PIX', '', '', '2025-08-25 12:25:03', '2025-08-25 12:25:31'),
(137, 'breu-branco', 'Breu Branco', 26, 'Técnico em Enfermagem ', 'DORANEI', 'NOGUEIRA COIMBRA', 'doraneinogueira043@gmail.com', '(91) 99270-8227', '659.816.002-25', 'RUA TRANCEDO NEVES, 29, CENTRO', 'BREU BRANCO', 'PA', '68488-000', 'tecnico', 'approved', 'PIX', '', '', '2025-08-25 22:26:18', '2025-08-25 22:27:08'),
(138, 'ava', 'AVA', 29, 'Técnico em Segurança do Trabalho', 'GABRIEL', 'HENRIQUE SANTOS DE SAMPAIO', 'gsampaio2311@gmail.com', '(94) 98448-4258', '035.904.892-78', 'Vicinal Três Torres, Zona Rural', 'BREU BRANCO', 'PA', '68488-000', 'tecnico', 'approved', 'PIX', '', '', '2025-08-26 21:29:44', '2025-08-26 21:30:01');

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=139;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
