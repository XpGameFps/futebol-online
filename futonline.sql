-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 11/07/2025 às 16:19
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `futonline`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `active_sessions`
--

CREATE TABLE `active_sessions` (
  `session_id` varchar(255) NOT NULL COMMENT 'PHP Session ID',
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() COMMENT 'Timestamp of the last recorded activity for this session'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `active_sessions`
--

INSERT INTO `active_sessions` (`session_id`, `last_activity`) VALUES
('0134fbf81237501e27f91d4fc12b4172', '2025-06-10 19:10:56'),
('033f4df76b6bc2dd175c1cfc5b6421f8', '2025-06-15 14:52:23'),
('0342d25a7c6ba8cd45d0a33e2abc192a', '2025-06-14 08:36:33'),
('09dab33756dfd8dae0cef6968e158899', '2025-06-10 17:03:07'),
('0da9f456ad46269c664b973886d4b852', '2025-06-17 06:04:09'),
('0e58c713b72fc93b0b2e10c03a17b567', '2025-06-13 11:44:12'),
('0fc0104e29be68558d2ba893f84d733e', '2025-07-06 17:13:36'),
('10ccf77862cccce5008f55f9c9bcaba5', '2025-06-15 13:06:07'),
('1562fab8397acc26b8362e8e082ea19b', '2025-06-09 15:40:22'),
('1f69620cd6c4ca852742188faed77e8a', '2025-06-15 12:35:22'),
('24eeea25c4ed4cffb9b6aa3f170bfbca', '2025-06-15 13:53:56'),
('2803701d5acda49d481fd88e726c5caa', '2025-07-05 02:46:27'),
('28d8823fff9e871bec5dc88ad2153baa', '2025-06-11 00:10:37'),
('2c8accfd8b22922b9469c1c16fbc14fc', '2025-06-15 07:00:46'),
('2e035b0805687222a7d993592d660999', '2025-06-24 17:01:12'),
('2f34d5ac0fd662fdbeaa8403b414fb42', '2025-06-15 13:54:03'),
('2f72c3c77ce56eb1dcf11374d8ee3eed', '2025-06-14 02:01:02'),
('31f73c0b2c422609cda4d2ce98ce2bc5', '2025-06-10 18:59:27'),
('3d67324178586d3c1b8a5d0aec31e9b3', '2025-06-10 09:33:12'),
('3e3bfc69461b7ebfcc445e25ee023a9c', '2025-06-28 18:34:26'),
('43f4b0d6403cb2415091cbb8fa39a3e5', '2025-06-13 20:19:01'),
('446c16acad612b839cee9a5ce290ab48', '2025-06-12 03:15:33'),
('45b8a698ddf1bce688ca3a1ae46def3f', '2025-06-21 14:52:48'),
('47405bd54c1b5ddbcd25b52e553b7b28', '2025-06-13 01:54:29'),
('48d34910427635ce659a3c72d5dfbca2', '2025-07-01 16:10:13'),
('4a43000b35a130a6de03c5442d11bbc6', '2025-07-05 03:19:31'),
('4e4979e7c8975626c6ef64b96aae2a49', '2025-07-02 11:45:01'),
('4ece95715b00f8228e6cc63ebc13d1dd', '2025-06-15 13:06:07'),
('5155100eac64488c1de2d3c71fe26f3e', '2025-06-17 03:00:35'),
('51c23af79cd66a953469ff0d2b7aab8a', '2025-07-05 07:32:29'),
('52cf462fbe32f3dce06fdc1c6cd85c4d', '2025-06-15 13:10:47'),
('53c1bd502b272752ccbec6e2e161246e', '2025-06-18 00:02:18'),
('56155b17f1b6ac823dda97f2102b4464', '2025-06-10 19:57:23'),
('574f4277c61d71cd5fa7ca4ef075a519', '2025-06-14 17:33:35'),
('5a224ced8b46530348d29bc9b8eb7568', '2025-07-05 12:41:17'),
('605739433cfc09fb9c1c4c22a7ffd524', '2025-06-15 12:46:04'),
('6361767fff51693662270ba113e68f16', '2025-06-10 18:03:27'),
('68e0dda0a30108f6aca9064d0490ef70', '2025-06-11 08:48:27'),
('6b1e041edf581100f98bdb60d7104531', '2025-06-15 13:54:03'),
('6b3a0513bb2ffaa0307e76f983f1eff8', '2025-06-10 02:47:38'),
('7218eae0baf1d2d98ecc8e7b5f37dfc7', '2025-07-04 13:15:19'),
('721f3268903fb5667863173330945f84', '2025-06-14 17:46:51'),
('7426b707a9a307e798dcabb5743dc521', '2025-06-15 13:53:56'),
('745b4b33a3f2617506e4a371010f7cc3', '2025-06-13 00:25:41'),
('76ffb4b247745fb71f8a047babcd6134', '2025-06-10 16:29:57'),
('7b2785c961f0d81ad5b663de219739ce', '2025-06-12 15:34:48'),
('7ce4f75ae9c4f7a467e5c1a3a740471e', '2025-06-10 22:36:17'),
('82e438bcdd299abefadf5076fc1e14f1', '2025-06-17 03:00:35'),
('84a989ca39d20926c04e7034bf59b1b2', '2025-06-11 05:02:02'),
('86901eda38091996199ce5febcb93096', '2025-06-22 21:09:53'),
('89147558944c740aedc379a982509e5d', '2025-06-14 17:44:16'),
('8c9f31a817f69263008f7eaae8fb695f', '2025-07-04 12:02:11'),
('9f51c748e4ae4f6fe8163136deb2f322', '2025-06-11 03:01:13'),
('9fe7a60cead7a80f608e7b4436571187', '2025-06-17 02:57:47'),
('a09fe0fd35107a6ac5b2546eb017fc9c', '2025-07-01 16:58:19'),
('a3792b5e364b2198118df9634dbada8e', '2025-06-15 10:34:55'),
('a60c027279821201f6a1a8357c3813e4', '2025-06-20 22:14:03'),
('a6360f13ad586d4bb97eb5817428e0fa', '2025-07-08 12:11:36'),
('a774b4840bad95735b27a738d4389c88', '2025-06-10 17:06:42'),
('ad1845cb015dd2f88ca89ae5b88fcb6f', '2025-07-09 12:22:50'),
('b27f013aa7949cd53b647f18bbf674f2', '2025-07-05 17:50:27'),
('b27f984c2719266eba99b1ec23940fa8', '2025-06-14 18:54:00'),
('b30993c01e3ebe049ccc152d7f76b4c3', '2025-06-20 02:02:51'),
('bbffca8b63b60486656a63ac1692afcd', '2025-06-11 17:21:34'),
('bd166b85da09a4ab53d6452d10c44cc5', '2025-07-01 21:28:15'),
('c23b9de0443b13c410001c746756c423', '2025-06-10 16:30:25'),
('c2ff77ba2c1b2a25a51a1f4f846212fa', '2025-06-10 17:04:42'),
('c4250a32804c938129c0f30353aad136', '2025-07-01 16:10:26'),
('c5eca97dfd45b5c98eb7d025db0af83c', '2025-06-26 18:21:34'),
('c72ff2ef8426a7b7f0b3a3f78e58b5c0', '2025-07-03 09:31:44'),
('ca0aa72e1b4252458028e41852e9d606', '2025-06-14 02:01:06'),
('ce32ba74761cac49ac054dd8999aca4f', '2025-07-10 02:42:51'),
('d0fa0445b069af5ef790ed8eb9d50f21', '2025-06-10 20:32:48'),
('d123209f5d62d8df04c31ec620ff73dd', '2025-06-11 00:09:58'),
('d23836da8e06f55480b41080aef0d561', '2025-06-11 15:09:44'),
('d276620bb7673c3da6e938139b2e6635', '2025-06-14 15:34:48'),
('d28d260024e03aee4614b10e5ca0de27', '2025-06-13 19:40:23'),
('d3c934fe64f4077346001d2584c70b0d', '2025-06-12 19:46:32'),
('d87eda04c70d10a46dbe928110a98cf2', '2025-06-15 16:27:03'),
('d8f501d8b8f5a9e54e8277777e196091', '2025-07-01 16:10:25'),
('dcba504e2c0c6bc898f77462d5efee85', '2025-06-15 00:24:28'),
('dcd3df59188fc8e108017e998f8710b5', '2025-06-21 04:38:22'),
('e1ab2c573ec3dd06b01fe8bc249dc0e8', '2025-07-05 10:58:34'),
('e3f274ab03615bb6daaf3fa7b2ab9339', '2025-06-15 15:46:46'),
('e45d2b146d30548ccf2bf30c5addb1de', '2025-06-09 19:08:56'),
('e4967d66405d6079652895f3c94a6b0c', '2025-06-15 13:06:07'),
('eaf3698c7c3e00570a5963ef2d9d154c', '2025-06-10 17:02:56'),
('ee6d037e980f71311d5001ed8fb81ae0', '2025-07-05 02:46:27'),
('f8141b32b5e0481d4dfcbe5a7e97b67b', '2025-07-06 19:27:48'),
('mhk9aj6sg035pi5h5toje8oo5m', '2025-07-11 14:18:50');

-- --------------------------------------------------------

--
-- Estrutura para tabela `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `is_superadmin` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 para superadmin, 0 para admin normal',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `admins`
--

INSERT INTO `admins` (`id`, `username`, `password_hash`, `email`, `is_superadmin`, `created_at`) VALUES
(5, 'admin', '$2y$10$cLGE596QyFSGcLKaSIAlYuDk7.JAJelg4o9Ad68fsy5.lQb5ik7oy', 'admin@admin.com', 1, '2025-07-11 14:06:43');

-- --------------------------------------------------------

--
-- Estrutura para tabela `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `target_url` varchar(2048) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_on_homepage` tinyint(1) NOT NULL DEFAULT 0,
  `display_on_match_page` tinyint(1) NOT NULL DEFAULT 0,
  `display_on_tv_page` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ad_code` text DEFAULT NULL,
  `ad_type` varchar(20) NOT NULL DEFAULT 'image',
  `display_match_player_left` tinyint(1) NOT NULL DEFAULT 0,
  `display_match_player_right` tinyint(1) NOT NULL DEFAULT 0,
  `display_tv_player_left` tinyint(1) NOT NULL DEFAULT 0,
  `display_tv_player_right` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `banners`
--

INSERT INTO `banners` (`id`, `image_path`, `target_url`, `alt_text`, `is_active`, `display_on_homepage`, `display_on_match_page`, `display_on_tv_page`, `created_at`, `updated_at`, `ad_code`, `ad_type`, `display_match_player_left`, `display_match_player_right`, `display_tv_player_left`, `display_tv_player_right`) VALUES
(2, 'banner_684b5460d83ce_1749767264.jpg', 'https://bit.ly/3HHjmbJ', 'Olá, pensei que você estaria interessado em fazer parte da Betano. Se você se cadastrar e completar a verificação, ambos receberemos uma aposta grátis de R$30. Confira mais detalhes aqui:', 1, 1, 1, 1, '2025-06-12 22:27:44', '2025-06-14 15:37:00', NULL, 'image', 0, 0, 0, 0),
(3, 'banner_684b54e569d15_1749767397.png', 'https://bit.ly/3HHjmbJ', 'Olá, pensei que você estaria interessado em fazer parte da Betano. Se você se cadastrar e completar a verificação, ambos receberemos uma aposta grátis de R$30. Confira mais detalhes aqui:', 1, 1, 1, 1, '2025-06-12 22:29:57', '2025-06-14 15:36:52', NULL, 'image', 0, 0, 0, 0),
(4, NULL, '', '', 1, 0, 1, 1, '2025-06-14 16:47:47', '2025-06-14 17:23:04', '<script type=\'text/javascript\' src=\'//harshstipulatesemblance.com/9d/95/fd/9d95fd8664b3bcc4d12666b1e5a5aca5.js\'></script>', 'popup_script', 0, 0, 0, 0),
(6, NULL, '', '', 1, 0, 1, 1, '2025-06-14 17:30:54', '2025-06-14 17:35:05', '<script type=\"text/javascript\">\r\n	atOptions = {\r\n		\'key\' : \'e4df20f54d09f18862dccda44836344f\',\r\n		\'format\' : \'iframe\',\r\n		\'height\' : 600,\r\n		\'width\' : 160,\r\n		\'params\' : {}\r\n	};\r\n</script>\r\n<script type=\"text/javascript\" src=\"//harshstipulatesemblance.com/e4df20f54d09f18862dccda44836344f/invoke.js\"></script>', 'banner_script', 1, 1, 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `leagues`
--

CREATE TABLE `leagues` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `logo_filename` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `leagues`
--

INSERT INTO `leagues` (`id`, `name`, `logo_filename`) VALUES
(5, 'Brasileirão Série A', 'league_6848f0511d7b40.65998690.png'),
(6, 'Mundial de Clubes', 'league_684c3de756b7d5.75771226.png');

-- --------------------------------------------------------

--
-- Estrutura para tabela `featured_items`
--

CREATE TABLE IF NOT EXISTS `featured_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_type` enum('match','league','custom') NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `custom_title` varchar(255) DEFAULT NULL,
  `custom_image` varchar(255) DEFAULT NULL,
  `custom_url` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `matches`
--

CREATE TABLE `matches` (
  `id` int(11) NOT NULL,
  `match_time` datetime NOT NULL,
  `home_team_id` int(11) DEFAULT NULL COMMENT 'Foreign key to teams.id for home team',
  `away_team_id` int(11) DEFAULT NULL COMMENT 'Foreign key to teams.id for away team',
  `description` text DEFAULT NULL,
  `league_id` int(11) DEFAULT NULL,
  `cover_image_filename` varchar(255) DEFAULT NULL COMMENT 'Filename of the uploaded cover image for the match',
  `meta_description` text DEFAULT NULL COMMENT 'Meta description for SEO for the match',
  `meta_keywords` varchar(255) DEFAULT NULL COMMENT 'Comma-separated meta keywords for SEO for the match'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `matches`
--

INSERT INTO `matches` (`id`, `match_time`, `home_team_id`, `away_team_id`, `description`, `league_id`, `cover_image_filename`, `meta_description`, `meta_keywords`) VALUES
(22, '2025-06-14 21:00:00', 27, 28, 'Um confronto eletrizante no Super Mundial de Clubes! O gigante africano Al Ahly FC, com sua rica história e paixão, enfrenta o Inter Miami CF, a equipe da MLS que traz o brilho de Lionel Messi. Uma batalha de estilos e continentes em busca da glória global.', 6, 'default_match_cover.png', 'Super Mundial de Clubes: Al Ahly FC enfrenta Inter Miami CF de Messi. Uma batalha imperdível entre África e MLS pela supremacia mundial.', 'Al Ahly FC, Inter Miami CF, Messi, Super Mundial de Clubes, futebol, jogo, partida, confronto, África, MLS, Mundial de Clubes, Al Ahly, Inter Miami, Messi Inter Miami, futebol internacional.'),
(23, '2025-06-15 13:00:00', 33, 34, 'Um duelo de Davi e Golias no Super Mundial de Clubes! O poderoso FC Bayern München, gigante europeu, enfrenta o Auckland City FC, o tradicional campeão da Oceania e única equipe semiprofissional do torneio. Uma batalha de continentes e realidades em busca da glória global.', 6, 'default_match_cover.png', 'Super Mundial de Clubes: FC Bayern München x Auckland City FC. O gigante europeu contra o azarão da Oceania. Uma partida imperdível pela supremacia mundial.', 'FC Bayern München, Auckland City FC, Super Mundial de Clubes, Mundial de Clubes, futebol, jogo, partida, confronto, Bayern, Auckland, Oceania, Europa, FIFA Club World Cup.'),
(24, '2025-06-15 16:00:00', 29, 30, 'Um embate de titãs no Super Mundial de Clubes! O Paris Saint-Germain, com seu ataque galáctico, desafia a solidez tática e a garra do Atlético de Madrid. Um confronto de estilos e filosofias em busca da supremacia global neste torneio de elite.', 6, 'default_match_cover.png', 'Super Mundial de Clubes: PSG x Atlético de Madrid. O ataque estelar de Paris contra a muralha espanhola. Um duelo tático pela glória mundial.', 'Paris Saint-Germain, Atlético de Madrid, PSG, Atlético, Super Mundial de Clubes, Mundial de Clubes, futebol, jogo, partida, confronto, Europa, Champions League, FIFA Club World Cup.'),
(25, '2025-06-15 19:00:00', 25, 26, 'Um clássico intercontinental no Super Mundial de Clubes! O SE Palmeiras, gigante sul-americano e bicampeão da Libertadores, enfrenta o FC Porto, tradicional potência europeia. Uma batalha de camisas pesadas e histórias vitoriosas em busca da glória global.', 6, 'default_match_cover.png', 'Super Mundial de Clubes: Palmeiras x Porto. O campeão sul-americano contra a potência europeia. Um confronto de gigantes pela supremacia mundial.', 'SE Palmeiras, FC Porto, Palmeiras, Porto, Super Mundial de Clubes, Mundial de Clubes, futebol, jogo, partida, confronto, Brasil, Portugal, Libertadores, Champions League, FIFA Club World Cup.'),
(26, '2025-06-15 23:00:00', 31, 32, 'Um confronto inédito no Super Mundial de Clubes! O Botafogo, tradicional clube brasileiro com sua torcida apaixonada, enfrenta o Seattle Sounders FC, uma das forças emergentes da MLS. Uma batalha de estilos e continentes em busca de uma vaga na próxima fase do torneio.', 6, 'default_match_cover.png', 'Super Mundial de Clubes: Botafogo x Seattle Sounders FC. O Fogão contra a força da MLS. Um duelo de continentes pela glória mundial.', 'Botafogo, Seattle Sounders FC, Super Mundial de Clubes, Mundial de Clubes, futebol, jogo, partida, confronto, Brasil, MLS, FIFA Club World Cup, Fogão, Sounders.'),
(27, '2025-06-16 16:00:00', 39, 40, 'Um embate de gigantes no Super Mundial de Clubes! O Chelsea FC, potência europeia e campeão da Champions League, enfrenta o Los Angeles FC, um dos clubes mais vitoriosos e em ascensão da MLS. Um confronto de estilos e continentes em busca da supremacia global.', 6, 'default_match_cover.png', 'Super Mundial de Clubes: Chelsea FC x Los Angeles FC. O gigante inglês contra a força da MLS. Um duelo de campeões pela glória mundial.', 'Chelsea FC, Los Angeles FC, Chelsea, LAFC, Super Mundial de Clubes, Mundial de Clubes, futebol, jogo, partida, confronto, Inglaterra, MLS, Champions League, FIFA Club World Cup.'),
(28, '2025-06-16 19:00:00', 35, 36, 'Um clássico intercontinental no Super Mundial de Clubes! O CA Boca Juniors, gigante sul-americano com sua mística e paixão, enfrenta o SL Benfica, tradicional potência europeia com uma rica história. Uma batalha de camisas pesadas e torcidas apaixonadas em busca da glória global.', 6, 'default_match_cover.png', 'Super Mundial de Clubes: Boca Juniors x Benfica. O gigante argentino contra a potência portuguesa. Um duelo de tradição pela supremacia mundial.', 'CA Boca Juniors, SL Benfica, Boca Juniors, Benfica, Super Mundial de Clubes, Mundial de Clubes, futebol, jogo, partida, confronto, Argentina, Portugal, Libertadores, Champions League, FIFA Club World Cup.'),
(29, '2025-06-16 22:00:00', 37, 38, 'Um duelo de campeões continentais no Super Mundial de Clubes! O CR Flamengo, gigante sul-americano e campeão da Libertadores, enfrenta o Espérance Sportive de Tunis, potência africana. Uma batalha de estilos e continentes em busca da glória global.', 6, 'default_match_cover.png', 'Super Mundial de Clubes: Flamengo x Espérance. O campeão sul-americano contra a força africana. Um confronto de continentes pela glória mundial.', 'CR Flamengo, Espérance Sportive de Tunis, Flamengo, Espérance, Super Mundial de Clubes, Mundial de Clubes, futebol, jogo, partida, confronto, Brasil, Tunísia, África, Libertadores, FIFA Club World Cup.'),
(30, '2025-06-17 13:00:00', 45, 46, 'Um confronto de estilos no Super Mundial de Clubes! O Fluminense FC, campeão da Libertadores com seu futebol envolvente, enfrenta o Borussia Dortmund, potência europeia conhecida por seu ataque rápido e vibrante. Uma batalha tática e emocionante em busca da glória global.', 6, 'default_match_cover.png', 'Super Mundial de Clubes: Fluminense x Borussia Dortmund. O campeão sul-americano contra a força alemã. Um duelo de estilos pela glória mundial.', 'Fluminense FC, Borussia Dortmund, Fluminense, Dortmund, Super Mundial de Clubes, Mundial de Clubes, futebol, jogo, partida, confronto, Brasil, Alemanha, Libertadores, Champions League, FIFA Club World Cup.'),
(31, '2025-06-17 16:27:00', 41, 42, 'Um duelo intercontinental no Super Mundial de Clubes! O CA River Plate, gigante sul-americano e campeão da Libertadores, enfrenta o Urawa Red Diamonds, potência japonesa e campeão asiático. Uma batalha de continentes e estilos em busca da glória global.', 6, 'default_match_cover.png', 'Super Mundial de Clubes: River Plate x Urawa Red Diamonds. O gigante argentino contra o campeão asiático. Um duelo de continentes pela glória mundial.', 'CA River Plate, Urawa Red Diamonds, River Plate, Urawa, Super Mundial de Clubes, Mundial de Clubes, futebol, jogo, partida, confronto, Argentina, Japão, Libertadores, Liga dos Campeões da Ásia, FIFA Club World Cup.'),
(32, '2025-06-17 19:00:00', 47, 48, 'Um confronto de campeões continentais no Super Mundial de Clubes! O Ulsan HD, potência asiática, enfrenta o Mamelodi Sundowns FC, gigante africano. Uma batalha de estilos e continentes em busca de uma vaga na próxima fase do torneio.', 6, 'default_match_cover.png', 'Super Mundial de Clubes: Ulsan HD x Mamelodi Sundowns FC. O campeão asiático contra a força africana. Um duelo de continentes pela glória mundial.', 'Ulsan HD, Mamelodi Sundowns FC, Ulsan, Sundowns, Super Mundial de Clubes, Mundial de Clubes, futebol, jogo, partida, confronto, Coreia do Sul, África do Sul, Ásia, África, FIFA Club World Cup.'),
(33, '2025-06-17 22:00:00', 43, 44, 'Um embate de peso no Super Mundial de Clubes! O CF Monterrey, gigante mexicano e potência da Concacaf, enfrenta o FC Internazionale Milano, tradicional força europeia e campeão da Champions League. Um confronto de estilos e continentes em busca da glória global.', 6, 'default_match_cover.png', 'Super Mundial de Clubes: Monterrey x Inter de Milão. O gigante mexicano contra a potência italiana. Um duelo de continentes pela glória mundial.', 'CF Monterrey, FC Internazionale Milano, Monterrey, Inter de Milão, Super Mundial de Clubes, Mundial de Clubes, futebol, jogo, partida, confronto, México, Itália, Concacaf, Europa, Champions League, FIFA Club World Cup.'),
(34, '2025-06-01 16:42:00', 27, 33, '', NULL, 'default_match_cover.png', '', ''),
(35, '2025-06-14 17:00:00', 57, 58, 'Um confronto direto no futebol brasileiro! A Ponte Preta, tradicional equipe paulista, enfrenta o ABC, clube potiguar em busca de afirmação. Uma partida crucial na disputa por pontos, prometendo emoção e rivalidade em campo.', NULL, 'default_match_cover.png', 'Ponte Preta x ABC: Duelo por pontos no campeonato. Uma partida com rivalidade e emoção garantidas entre o time paulista e o potiguar.', 'Ponte Preta, ABC, futebol, jogo, partida, confronto, Campeonato Brasileiro, Série B, Copa do Brasil, futebol nacional, Campinas, Natal.'),
(36, '2025-06-14 18:30:00', 59, 60, 'Um duelo de tradição no futebol brasileiro! O Athletico Paranaense, gigante do sul e com histórico de títulos, enfrenta o Remo, tradicional clube do norte com uma torcida apaixonada. Uma partida que promete intensidade e busca por pontos importantes na competição.', NULL, 'default_match_cover.png', 'Athletico Paranaense x Remo: Duelo de gigantes regionais no futebol brasileiro. Uma partida intensa em busca de pontos cruciais na competição.', 'Athletico Paranaense, Remo, futebol, jogo, partida, confronto, Campeonato Brasileiro, Copa do Brasil, futebol nacional, Curitiba, Belém, Furacão, Leão Azul.');

-- --------------------------------------------------------

--
-- Estrutura para tabela `match_tv_channels`
--

CREATE TABLE `match_tv_channels` (
  `match_id` int(11) NOT NULL COMMENT 'Foreign key to matches.id',
  `channel_id` int(11) NOT NULL COMMENT 'Foreign key to tv_channels.id',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Links matches to the TV channels broadcasting them';

-- --------------------------------------------------------

--
-- Estrutura para tabela `player_reports`
--

CREATE TABLE `player_reports` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` enum('channel','match') NOT NULL,
  `report_timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_ip` varchar(45) DEFAULT NULL,
  `status` enum('new','viewed','resolved') DEFAULT 'new'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `player_reports`
--

INSERT INTO `player_reports` (`id`, `item_id`, `item_type`, `report_timestamp`, `user_ip`, `status`) VALUES
(8, 5, 'channel', '2025-06-10 23:47:59', '131.108.127.122', 'resolved'),
(9, 24, 'match', '2025-06-14 01:20:16', '131.108.127.122', 'resolved');

-- --------------------------------------------------------

--
-- Estrutura para tabela `saved_stream_urls`
--

CREATE TABLE `saved_stream_urls` (
  `id` int(11) NOT NULL,
  `stream_name` varchar(255) NOT NULL COMMENT 'A friendly name for the stream source, e.g., Main HD, Alt SD',
  `stream_url_value` text NOT NULL COMMENT 'The actual URL of the stream',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `saved_stream_urls`
--

INSERT INTO `saved_stream_urls` (`id`, `stream_name`, `stream_url_value`, `created_at`) VALUES
(1, 'Disney +', 'https://embedcanaistv.com/disneyplus/', '2025-06-09 14:34:56'),
(2, 'Espn 4', 'https://embedmax.site/tvl/espn4.php', '2025-06-09 18:16:08'),
(3, 'Espn 4 HD', 'https://embedcanaistv.com/espn4/', '2025-06-09 18:17:41'),
(4, 'SporTV', 'https://embedcanaistv.com/sportv/', '2025-06-10 00:49:22'),
(6, 'Globo SP', 'https://embedmax.site/tvl/globoSP.php', '2025-06-10 00:50:58'),
(7, 'SporTV 3', 'https://embedcanaistv.com/sportv3/', '2025-06-10 20:23:51'),
(8, 'SporTV 3 HD', 'https://embedmax.site/tvl/sportv3.php', '2025-06-10 20:24:27'),
(9, 'SporTV 2', 'https://embedmax.site/tvl/sportv2.php', '2025-06-10 20:25:48'),
(10, 'SporTV 2 HD', 'https://embedcanaistv.com/sportv2', '2025-06-10 20:30:23'),
(11, 'Combate', 'https://embedcanaistv.com/combate/', '2025-06-10 23:46:18'),
(12, 'Amazon Prime Video', 'https://embedcanaistv.com/amazonprimevideo/', '2025-06-10 23:48:57'),
(13, 'Cazé TV', 'https://embedcanaistv.com/cazetv/', '2025-06-10 23:56:56'),
(14, 'Max', 'https://embedcanaistv.com/max/', '2025-06-11 00:02:31'),
(15, 'Nosso Futebol', 'https://embedcanaistv.com/nossofutebol/', '2025-06-11 00:04:55'),
(16, 'Premiere Clubes', 'https://embedcanaistv.com/premiereclubes', '2025-06-12 19:12:15'),
(17, 'Premiere 2', 'https://embedcanaistv.com/premiere2', '2025-06-12 19:12:29'),
(18, 'Premiere 3', 'https://embedcanaistv.com/premiere3', '2025-06-12 19:12:48'),
(19, 'Premiere 4', 'https://embedcanaistv.com/premiere4', '2025-06-12 19:13:09'),
(20, 'Premiere 5', 'https://embedcanaistv.com/premiere5', '2025-06-12 19:13:33'),
(21, 'Premiere 6', 'https://embedcanaistv.com/premiere6', '2025-06-12 19:13:53'),
(22, 'Premiere 7', 'https://embedcanaistv.com/premiere7', '2025-06-12 19:14:08'),
(23, 'Premiere 8', 'https://embedcanaistv.com/premiere8', '2025-06-12 19:14:25'),
(24, 'Globo BA', 'https://embedcanaistv.com/globoba/', '2025-06-12 19:14:47'),
(25, 'Globo DF', 'https://embedcanaistv.com/globodf', '2025-06-12 19:15:02'),
(26, 'Globo MG', 'https://embedcanaistv.com/globomg', '2025-06-12 19:15:16'),
(27, 'Globo RJ', 'https://embedcanaistv.com/globorj', '2025-06-12 19:16:25'),
(28, 'Globo RS', 'https://embedcanaistv.com/globors', '2025-06-12 19:16:38'),
(29, 'Premiere 4 HD', 'https://embedmax.site/canais/premiere4/', '2025-06-12 19:23:08'),
(30, 'Premiere Clubes HD', 'https://embedmax.site/canais/premiere/', '2025-06-12 19:23:43'),
(31, 'Premiere 3 HD', 'https://embedmax.site/canais/premiere3/', '2025-06-12 19:23:56'),
(32, 'Premiere 2 HD', 'https://embedmax.site/canais/premiere2/', '2025-06-12 19:24:31'),
(33, 'Record MG', 'https://embedcanaistv.com/recordmg', '2025-06-12 19:29:10'),
(34, 'Record RJ', 'https://embedcanaistv.com/recordrj', '2025-06-12 19:29:40'),
(35, 'Record SP', 'https://embedcanaistv.com/recordsp', '2025-06-12 19:29:54'),
(36, 'Cazé TV YT', 'https://www.youtube.com/@CazeTV/streams', '2025-06-14 16:53:33');

-- --------------------------------------------------------

--
-- Estrutura para tabela `site_settings`
--

CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `site_settings`
--

INSERT INTO `site_settings` (`id`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
(1, 'seo_homepage_title', 'Assista Futebol Ao Vivo Grátis – Brasileiro, Série A, 24h', '2025-06-15 16:01:32', '2025-06-15 16:27:55'),
(2, 'seo_homepage_description', 'Assista futebol ao vivo grátis em HD! Brasileiro, Série A, Mundial de Clubes e mais. Transmissão 24h com qualidade – entre agora!', '2025-06-15 16:01:32', '2025-06-15 16:12:43'),
(3, 'seo_homepage_keywords', 'futebol ao vivo, assistir grátis, Brasileiro, Série A, Mundial de Clubes, transmissão 24h, jogos online, futebol grátis, esportes ao vivo', '2025-06-15 16:01:32', '2025-06-15 16:12:43'),
(4, 'seo_homepage_h1', 'teste', '2025-06-15 16:01:32', '2025-06-15 16:01:32'),
(5, 'max_concurrent_users', '3', '2025-06-15 16:01:36', '2025-06-17 03:00:35'),
(6, 'default_match_cover', 'default_match_cover.png', '2025-06-15 16:26:59', '2025-06-15 16:26:59');

-- --------------------------------------------------------

--
-- Estrutura para tabela `streams`
--

CREATE TABLE `streams` (
  `id` int(11) NOT NULL,
  `match_id` int(11) NOT NULL,
  `stream_url` text NOT NULL,
  `stream_label` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `streams`
--

INSERT INTO `streams` (`id`, `match_id`, `stream_url`, `stream_label`) VALUES
(40, 22, 'https://embedcanaistv.com/cazetv/', 'Cazé TV'),
(41, 22, 'https://www.youtube.com/embed/VXz_VPTNmlQ', 'Cazé TV YT'),
(42, 22, 'https://embedcanaistv.com/sportv/', 'SporTV'),
(43, 35, 'https://embedcanaistv.com/nossofutebol/', 'Nosso Futebol'),
(44, 36, 'https://embedcanaistv.com/disneyplus/', 'Disney +'),
(45, 23, 'https://youtu.be/vV0Av7QfHqg', 'Cazé TV YT'),
(46, 24, 'https://www.youtube.com/watch?v=a5NwxCpwJ0s&ab_channel=Caz%C3%A9TV', 'Cazé TV YT');

-- --------------------------------------------------------

--
-- Estrutura para tabela `teams`
--

CREATE TABLE `teams` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Official name of the team',
  `logo_filename` varchar(255) DEFAULT NULL COMMENT 'Filename of the uploaded team logo',
  `primary_color_hex` varchar(7) DEFAULT NULL COMMENT 'Primary color of the team in HEX format (e.g., #RRGGBB)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `teams`
--

INSERT INTO `teams` (`id`, `name`, `logo_filename`, `primary_color_hex`, `created_at`) VALUES
(1, 'Itália', 'team_logo_68472183ecead1.03122316.png', '#007FFF', '2025-06-09 18:01:39'),
(2, 'Moldávia', 'team_logo_684721be8093f1.79838885.png', '#C8102E', '2025-06-09 18:02:38'),
(3, 'Croácia', 'team_logo_684722883ec0c1.65723326.png', '#F10000', '2025-06-09 18:06:00'),
(4, 'República Tcheca', 'team_logo_684722e5a53cd2.88679802.jpg', '#DA291C', '2025-06-09 18:07:33'),
(5, 'Brasil', 'team_logo_68477ed77dc413.15303161.png', '#FFCC00', '2025-06-10 00:39:51'),
(6, 'Paraguai', 'team_logo_68477fa8218ec5.07439613.png', '#D52B1E', '2025-06-10 00:43:20'),
(7, 'Peru', 'team_logo_68488fbea20de2.74429749.png', '#D80000', '2025-06-10 20:04:14'),
(8, 'Equador', 'team_logo_684890e8c3dbb1.25211471.png', '#FFD100', '2025-06-10 20:09:12'),
(9, 'Argentina', 'team_logo_6848b1a2804637.33999433.png', '#75AADB', '2025-06-10 20:10:35'),
(10, 'Colômbia', 'team_logo_68489181e5ecd4.66677507.png', '#FCD116', '2025-06-10 20:11:46'),
(11, 'Uruguai', 'team_logo_684892bba46c43.86753420.png', '#5BC2E7', '2025-06-10 20:16:59'),
(12, 'Venezuela', 'team_logo_684892f29a7c22.27595397.png', '#FAD201', '2025-06-10 20:17:54'),
(13, 'Red Bull Bragantino', 'team_logo_684a13619cef29.84464635.png', '#FFFFFF', '2025-06-11 23:38:09'),
(14, 'Bahia', 'team_logo_684a13a27652c7.55094845.png', '#0046AE', '2025-06-11 23:39:14'),
(15, 'Vitória', 'team_logo_684a13d84d6203.48263040.png', '#D00000', '2025-06-11 23:40:08'),
(16, 'Cruzeiro', 'team_logo_684a1420501994.92087033.png', '#0033A0', '2025-06-11 23:41:20'),
(17, 'Fortaleza', 'team_logo_684a147c4f9676.87055330.png', '#0046AE', '2025-06-11 23:42:52'),
(18, 'Santos', 'team_logo_684a14ae0240e6.15976660.png', '#FFFFFF', '2025-06-11 23:43:42'),
(19, 'Grêmio', 'team_logo_684a14e40e25f7.23225068.png', '#0099DB', '2025-06-11 23:44:36'),
(20, 'Corinthians', 'team_logo_684a1518001178.20826494.png', '#000000', '2025-06-11 23:45:28'),
(21, 'São Paulo', 'team_logo_684a1579d83173.17123113.png', '#FFFFFF', '2025-06-11 23:47:05'),
(22, 'Vasco', 'team_logo_684a15cd081249.15039208.png', '#000000', '2025-06-11 23:48:29'),
(23, 'Atlético Mineiro', 'team_logo_684a160951d495.15943611.png', '#000000', '2025-06-11 23:49:29'),
(24, 'Internacional', 'team_logo_684a16341435b7.96299939.png', '#E60012', '2025-06-11 23:50:12'),
(25, 'Palmeiras', 'team_logo_684c3830b1c0b4.36599032.png', '#1D9F34', '2025-06-13 14:39:44'),
(26, 'Porto', 'team_logo_684c3854b22c27.09204043.png', '#002F6C', '2025-06-13 14:40:20'),
(27, 'Al Ahly', 'team_logo_684c38ab7756c4.79957617.png', '#E32636', '2025-06-13 14:41:47'),
(28, 'Inter Miami', 'team_logo_684c38d8257945.67205447.png', '#F5B6CD', '2025-06-13 14:42:32'),
(29, 'Paris Saint-Germain', 'team_logo_684c390abe2d71.90431948.png', '#004170', '2025-06-13 14:43:22'),
(30, 'Atlético de Madrid', 'team_logo_684c393c076729.97216853.png', '#C8102E', '2025-06-13 14:44:12'),
(31, 'Botafogo', 'team_logo_684c3964b939b7.14872661.png', '#000000', '2025-06-13 14:44:52'),
(32, 'Seattle Sounders', 'team_logo_684c3987354a49.04504510.png', '#65B32E', '2025-06-13 14:45:27'),
(33, 'Bayern de Munique', 'team_logo_684c39ba881ad9.97591830.png', '#DC052D', '2025-06-13 14:46:18'),
(34, 'Auckland City', 'team_logo_684c39daed0d93.78931383.png', '#002B5C', '2025-06-13 14:46:50'),
(35, 'Boca Juniors', 'team_logo_684c3a0557d9a2.03285496.png', '#0033A0', '2025-06-13 14:47:33'),
(36, 'Benfica', 'team_logo_684c3a32351a56.02854309.png', '#E10600', '2025-06-13 14:48:18'),
(37, 'Flamengo', 'team_logo_684c3a53efee04.09746152.png', '#EF1C27', '2025-06-13 14:48:51'),
(38, 'Espérance de Tunis', 'team_logo_684c3a79ccee23.27876411.png', '#E2231A', '2025-06-13 14:49:29'),
(39, 'Chelsea', 'team_logo_684c3a99754f50.44275847.png', '#034694', '2025-06-13 14:50:01'),
(40, 'Los Angeles FC', 'team_logo_684c3abe93e9b9.40954847.png', '#000000', '2025-06-13 14:50:38'),
(41, 'River Plate', 'team_logo_684c3b1790bcf2.46143676.png', '#FFFFFF', '2025-06-13 14:52:07'),
(42, 'Urawa Red Diamonds', 'team_logo_684c3b361c7a34.00496998.png', '#E60012', '2025-06-13 14:52:38'),
(43, 'Monterrey', 'team_logo_684c3b53ef3547.11677302.png', '#003399', '2025-06-13 14:53:07'),
(44, 'Inter de Milão', 'team_logo_684c3b77bffb39.06931529.png', '#004C99', '2025-06-13 14:53:43'),
(45, 'Fluminense', 'team_logo_684c3b9a94a1d4.76158374.png', '#006A4E', '2025-06-13 14:54:18'),
(46, 'Borussia Dortmund', 'team_logo_684c3bba8f97c7.70235407.png', '#FFEE00', '2025-06-13 14:54:50'),
(47, 'Ulsan', 'team_logo_684c3bf31ea070.59674611.png', '#3A8EDE', '2025-06-13 14:55:47'),
(48, 'Mamelodi Sundowns', 'team_logo_684c3c14cd9cc6.35251139.png', '#FCDD09', '2025-06-13 14:56:20'),
(49, 'Manchester City', 'team_logo_684c3c320ef982.94555562.png', '#6CABDD', '2025-06-13 14:56:50'),
(50, 'Wydad', 'team_logo_684c3c536b4488.78290431.png', '#E30613', '2025-06-13 14:57:23'),
(51, 'Al Ain', 'team_logo_684c3c74cee9e3.37737180.png', '#5B2B8A', '2025-06-13 14:57:56'),
(52, 'Juventus', 'team_logo_684c3ca96bd4f9.38944397.png', '#000000', '2025-06-13 14:58:49'),
(53, 'Real Madrid', 'team_logo_684c3cd8490122.56952131.png', '#FFFFFF', '2025-06-13 14:59:36'),
(54, 'Al-Hilal', 'team_logo_684c3d7fae94e5.04787196.png', '#004AAD', '2025-06-13 15:00:31'),
(55, 'Pachuca', 'team_logo_684c3d398f6a20.87675587.png', '#0D77B7', '2025-06-13 15:01:13'),
(56, 'Red Bull Salzburg', 'team_logo_684c3d5c8fcbc2.76825457.png', '#EF3340', '2025-06-13 15:01:48'),
(57, 'Ponte Preta', 'team_logo_684dd9bb7d1d10.53950953.png', '#FFFFFF', '2025-06-14 20:21:15'),
(58, 'ABC', 'team_logo_684dda0b0f7bb5.81225183.png', '#000000', '2025-06-14 20:22:35'),
(59, 'Athletico Paranaense', 'team_logo_684ddb5bb14323.93773248.png', '#C2001A', '2025-06-14 20:28:11'),
(60, 'Remo', 'team_logo_684ddb97583712.54022920.png', '#002147', '2025-06-14 20:29:11');

-- --------------------------------------------------------

--
-- Estrutura para tabela `tv_channels`
--

CREATE TABLE `tv_channels` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `logo_filename` varchar(255) DEFAULT NULL,
  `stream_url` text NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `meta_description` text DEFAULT NULL COMMENT 'Meta description for SEO for the TV channel',
  `meta_keywords` varchar(255) DEFAULT NULL COMMENT 'Comma-separated meta keywords for SEO for the TV channel'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tv_channels`
--

INSERT INTO `tv_channels` (`id`, `name`, `logo_filename`, `stream_url`, `sort_order`, `meta_description`, `meta_keywords`) VALUES
(5, 'Combate', 'channel_6848c416e2e787.42301372.png', 'https://embedcanaistv.com/combate/', 1, 'O Canal Combate é o maior palco das artes marciais no Brasil. Aqui você acompanha os maiores eventos de MMA do mundo, como o UFC, além de transmissões ao vivo, reprises, programas especiais, bastidores, entrevistas exclusivas e cobertura completa do universo da luta. Para quem respira artes marciais, o Combate é mais que um canal — é uma paixão', 'MMA, UFC, lutas ao vivo, canal Combate, artes marciais, jiu-jitsu, Muay Thai, boxe, luta livre, eventos de luta, combate ao vivo, luta no Brasil, pay-per-view UFC, bastidores do MMA, cinturão UFC, octógono, transmissão de lutas, programação de lutas, cana'),
(6, 'Amazon Prime Vídeo', 'channel_684a42255f8d83.64936876.png', 'https://embedcanaistv.com/amazonprimevideo/', 0, 'Amazon Prime Video é o serviço de streaming da Amazon que oferece uma ampla variedade de filmes, séries, documentários e produções originais premiadas. Com conteúdos exclusivos como The Boys, Jack Ryan, Invincible e muitos outros, o Prime Video permite que você assista onde e quando quiser, com qualidade e praticidade. Experimente o melhor do entretenimento com apenas um clique', 'Amazon Prime Video, Prime Video, filmes online, séries online, streaming, filmes Amazon, séries Amazon, assistir online, conteúdo exclusivo, Amazon Originals, filmes em alta, séries premiadas, TV por assinatura, serviço de streaming, lançamentos Prime Vid'),
(7, 'Cazé TV', 'channel_6848c6f355c5e6.27096145.png', 'https://embedcanaistv.com/cazetv/', 3, 'Cazé TV é o canal do Casimiro com transmissões ao vivo, jogos, entrevistas e muito entretenimento esportivo — tudo com bom humor e resenha.', 'Cazé TV, Casimiro Miguel, Casimito, futebol ao vivo, jogos ao vivo, transmissão online, Copa do Brasil, Cazé reações, futebol na internet, canal esportivo, YouTube Casimiro, resenha esportiva, lives de futebol, entretenimento esportivo, Copa do Mundo Casi'),
(8, 'Disney +', 'channel_6848c73213e726.74362063.png', 'https://embedcanaistv.com/disneyplus/', 4, 'Disney+ é a plataforma oficial de streaming da The Walt Disney Company. Com um catálogo completo e exclusivo, reúne os maiores sucessos da Disney, Pixar, Marvel, Star Wars e National Geographic em um só lugar. Assista a filmes clássicos, séries premiadas, lançamentos e produções originais com qualidade, segurança e acesso fácil em todos os seus dispositivos. Perfeito para toda a família.', 'Disney+, Disney Plus, streaming Disney, filmes Disney, séries Disney, Marvel, Star Wars, Pixar, National Geographic, assistir online, conteúdo infantil, produções originais Disney, catálogo Disney+, desenhos animados, filmes para a família, serviço de str'),
(9, 'Max', 'channel_6848c7bd3b97c7.24144175.png', 'https://embedcanaistv.com/max/', 5, 'Max é a evolução da HBO Max — uma plataforma de streaming completa que reúne séries icônicas, filmes de sucesso, documentários, realities e conteúdo ao vivo. Com títulos da HBO, Warner Bros., DC, Cartoon Network, Discovery, CNN e outros grandes estúdios, Max oferece entretenimento para todos os gostos, com qualidade e experiência premium. Assista quando e onde quiser, com produções originais e os maiores lançamentos do cinema e da TV.', 'Max, HBO Max, streaming Max, filmes online, séries HBO, Warner Bros, DC, Discovery+, documentários, reality shows, assistir online, Max Brasil, conteúdo exclusivo Max, séries premiadas, filmes HBO, catálogo Max, serviço de streaming, lançamentos Max, séri'),
(10, 'Nosso Futebol', 'channel_6848c85b1febe2.07465465.png', 'https://embedcanaistv.com/nossofutebol/', 6, 'Nosso Futebol é a casa dos campeonatos regionais no Brasil. A plataforma transmite ao vivo os principais jogos do futebol nacional, com destaque para o Brasileirão Série C, Copa do Nordeste, Campeonato Carioca, entre outros. Com cobertura exclusiva, conteúdos especiais, análises e bastidores, Nosso Futebol valoriza as raízes do esporte mais amado do país. Para quem vive a paixão do futebol brasileiro em todos os cantos.', 'Nosso Futebol, futebol brasileiro, campeonatos regionais, Copa do Nordeste, Campeonato Carioca, Brasileirão Série C, jogos ao vivo, transmissão de futebol, futebol ao vivo, canal de futebol, futebol nacional, streaming de futebol, futebol raiz, times do B');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `active_sessions`
--
ALTER TABLE `active_sessions`
  ADD PRIMARY KEY (`session_id`);

--
-- Índices de tabela `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active_display_homepage` (`is_active`,`display_on_homepage`);

--
-- Índices de tabela `leagues`
--
ALTER TABLE `leagues`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Índices de tabela `featured_items`
--
ALTER TABLE `featured_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item_type` (`item_type`),
  ADD KEY `idx_item_id` (`item_id`),
  ADD KEY `idx_sort_order` (`sort_order`),
  ADD KEY `idx_active` (`active`);

--
-- Índices de tabela `matches`
--
ALTER TABLE `matches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_match_league` (`league_id`),
  ADD KEY `fk_matches_home_team` (`home_team_id`),
  ADD KEY `fk_matches_away_team` (`away_team_id`),
  ADD KEY `idx_match_time` (`match_time`);

--
-- Índices de tabela `match_tv_channels`
--
ALTER TABLE `match_tv_channels`
  ADD PRIMARY KEY (`match_id`,`channel_id`),
  ADD KEY `fk_match_tv_channels_channel` (`channel_id`);

--
-- Índices de tabela `player_reports`
--
ALTER TABLE `player_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item_type` (`item_type`),
  ADD KEY `idx_item_id` (`item_id`);

--
-- Índices de tabela `saved_stream_urls`
--
ALTER TABLE `saved_stream_urls`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_stream_name` (`stream_name`) COMMENT 'Ensure stream names are unique for easier management';

--
-- Índices de tabela `site_settings`
--
ALTER TABLE `site_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Índices de tabela `streams`
--
ALTER TABLE `streams`
  ADD PRIMARY KEY (`id`),
  ADD KEY `match_id` (`match_id`);

--
-- Índices de tabela `teams`
--
ALTER TABLE `teams`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_team_name` (`name`) COMMENT 'Ensure team names are unique';

--
-- Índices de tabela `tv_channels`
--
ALTER TABLE `tv_channels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sort_order_name` (`sort_order`,`name`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `leagues`
--
ALTER TABLE `leagues`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `featured_items`
--
ALTER TABLE `featured_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `matches`
--
ALTER TABLE `matches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de tabela `player_reports`
--
ALTER TABLE `player_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `saved_stream_urls`
--
ALTER TABLE `saved_stream_urls`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de tabela `site_settings`
--
ALTER TABLE `site_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `streams`
--
ALTER TABLE `streams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT de tabela `teams`
--
ALTER TABLE `teams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT de tabela `tv_channels`
--
ALTER TABLE `tv_channels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `featured_items`
--
ALTER TABLE `featured_items`
  ADD CONSTRAINT `fk_featured_items_match` FOREIGN KEY (`item_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_featured_items_league` FOREIGN KEY (`item_id`) REFERENCES `leagues` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `matches`
--
ALTER TABLE `matches`
  ADD CONSTRAINT `fk_match_league` FOREIGN KEY (`league_id`) REFERENCES `leagues` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matches_away_team` FOREIGN KEY (`away_team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_matches_home_team` FOREIGN KEY (`home_team_id`) REFERENCES `teams` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `match_tv_channels`
--
ALTER TABLE `match_tv_channels`
  ADD CONSTRAINT `fk_match_tv_channels_channel` FOREIGN KEY (`channel_id`) REFERENCES `tv_channels` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_match_tv_channels_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Restrições para tabelas `streams`
--
ALTER TABLE `streams`
  ADD CONSTRAINT `streams_ibfk_1` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
