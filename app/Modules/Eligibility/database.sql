-- --------------------------------------------------------
-- Host:                         10.0.10.57
-- Server version:               10.1.16-MariaDB - mariadb.org binary distribution
-- Server OS:                    Win32
-- HeidiSQL Version:             10.2.0.5599
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


-- Dumping database structure for leanfrogmagnet
CREATE DATABASE IF NOT EXISTS `leanfrogmagnet` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `leanfrogmagnet`;

-- Dumping structure for table leanfrogmagnet.eligibiility
CREATE TABLE IF NOT EXISTS `eligibiility` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `district_id` int(11) NOT NULL DEFAULT '1',
  `store_for` enum('DO','MS') DEFAULT NULL,
  `status` enum('Y','N','T') NOT NULL DEFAULT 'Y',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;

-- Dumping data for table leanfrogmagnet.eligibiility: ~12 rows (approximately)
/*!40000 ALTER TABLE `eligibiility` DISABLE KEYS */;
REPLACE INTO `eligibiility` (`id`, `template_id`, `name`, `type`, `district_id`, `store_for`, `status`, `created_at`, `updated_at`) VALUES
	(1, 1, 'Interview Score 1', NULL, 1, 'MS', 'Y', '2020-06-10 07:41:26', '2020-06-10 12:53:24'),
	(2, 5, 'Writing Prompt 1', NULL, 2, 'MS', 'Y', '2020-06-10 07:42:57', '2020-06-10 12:54:11'),
	(3, 7, 'CS - 1', NULL, 1, 'DO', 'Y', '2020-06-10 12:22:07', '2020-06-10 12:22:07'),
	(4, 1, 'Interview Option - HCS', NULL, 1, 'MS', 'Y', '2020-06-10 13:54:46', '2020-06-10 13:54:46'),
	(5, 1, 'Interview score HSC', NULL, 1, 'MS', 'Y', '2020-06-15 08:35:03', '2020-06-15 08:35:03'),
	(6, 0, 'Recommednation Form Edit', NULL, 1, 'DO', 'Y', '2020-06-15 09:56:36', '2020-06-15 09:57:15'),
	(7, 1, 'IS - 1', NULL, 3, 'MS', 'Y', '2020-06-15 17:36:19', '2020-06-15 17:36:19'),
	(8, 1, 'IS - 2', NULL, 3, 'MS', 'Y', '2020-06-15 17:37:31', '2020-06-15 17:37:31'),
	(9, 6, 'AU - 1', NULL, 3, 'MS', 'Y', '2020-06-15 17:38:23', '2020-06-15 17:38:23'),
	(10, 6, 'AU - 2', NULL, 3, 'DO', 'Y', '2020-06-15 17:39:12', '2020-06-15 17:39:12'),
	(11, 10, 'ST-1', NULL, 0, 'MS', 'Y', '2020-06-19 10:30:21', '2020-06-19 11:33:32'),
	(12, 8, 'CDI - 1 edited', NULL, 0, 'MS', 'Y', '2020-06-19 13:15:30', '2020-06-19 13:16:32');
/*!40000 ALTER TABLE `eligibiility` ENABLE KEYS */;

-- Dumping structure for table leanfrogmagnet.eligibility_content
CREATE TABLE IF NOT EXISTS `eligibility_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `eligibility_id` int(11) DEFAULT NULL,
  `content` longtext,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=latin1;

-- Dumping data for table leanfrogmagnet.eligibility_content: ~12 rows (approximately)
/*!40000 ALTER TABLE `eligibility_content` DISABLE KEYS */;
REPLACE INTO `eligibility_content` (`id`, `eligibility_id`, `content`, `created_at`, `updated_at`) VALUES
	(3, 3, '{"eligibility_type":{"type":"YN","YN":["Eligible","Not Eligible"],"NR":[null,null,null,null]},"allow_spreadsheet":"Y"}', '2020-06-10 12:22:07', '2020-06-10 12:22:07'),
	(7, 1, '{"eligibility_type":{"type":"YN","YN":["Approved","Not Approved"],"NR":[null,null,null,null]},"allow_spreadsheet":"N"}', '2020-06-10 12:53:24', '2020-06-10 12:53:24'),
	(10, 2, '{"eligibility_type":{"type":"YN","YN":["Yes","No"],"NR":["9","8","7","6","5"]},"allow_spreadsheet":"Y"}', '2020-06-10 12:54:11', '2020-06-10 12:54:11'),
	(11, 4, '{"eligibility_type":{"type":"YN","YN":["School","College"],"NR":[null,null,null,null]},"allow_spreadsheet":"N"}', '2020-06-10 13:54:46', '2020-06-10 13:54:46'),
	(12, 5, '{"eligibility_type":{"type":"NR","YN":[null,null],"NR":["A","B","C","D"]},"allow_spreadsheet":"Y"}', '2020-06-15 08:35:03', '2020-06-15 08:35:03'),
	(14, 6, '{"subjects":["math","ss","o"],"calc_score":"1","header":{"1":{"name":"Oih aita","questions":{"1":{"name":"Atnrka","options":{"1":"Hrupa","2":"Ehmaul"},"points":{"1":"AkRkRhab","2":"Akla"}}}},"2":{"name":"Mh tu h","questions":{"1":{"name":"Mfogala aalua","options":{"1":"Uyuaa iktbb"},"points":{"1":"MAmu"}},"2":{"name":"Kerhamuaaf","options":{"1":"TEst","2":"Tla"},"points":{"1":"Htaan","2":"Lnaaa"}},"3":{"name":"Ymihg","options":{"1":"Tth","2":"Gkn taa  ua","3":"Oaf ikggrehba"},"points":{"1":"Vkatetgm","2":"AaOuahlge ar","3":"Eaah"}}}},"3":{"name":"Inlgmti","questions":{"1":{"name":"Uyta","options":{"1":"Me","2":"Ayneugwa"},"points":{"1":"Onbaho","2":"U aalinea g"}},"2":{"name":"Tribno edited","options":{"1":"Ieegg lfnaal rb","2":"Oh","3":"Saeunmhln t","4":"Kahgrawh\'vu","5":"A edited"},"points":{"1":"Ia","2":"Hunpakttlp","3":"Phnatao iu","4":"Aaaaul nR a A","5":"NugbCh hbh"}}}}}}', '2020-06-15 09:57:15', '2020-06-15 09:57:15'),
	(15, 7, '{"eligibility_type":{"type":"YN","YN":["Eligible","Not Eligible"],"NR":[null,null,null,null]},"allow_spreadsheet":"Y"}', '2020-06-15 17:36:19', '2020-06-15 17:36:19'),
	(16, 8, '{"eligibility_type":{"type":"NR","YN":[null,null],"NR":["100-90","89-80","79-70","69-60"]},"allow_spreadsheet":"Y"}', '2020-06-15 17:37:31', '2020-06-15 17:37:31'),
	(17, 9, '{"eligibility_type":{"type":"YN","YN":["Audition Pass","Audition Fail"],"NR":[null,null,null,null]},"allow_spreadsheet":"Y"}', '2020-06-15 17:38:23', '2020-06-15 17:38:23'),
	(18, 10, '{"eligibility_type":{"type":"NR","YN":[null,null],"NR":["Interview Appear","Interview Not Appear","Fail in Interview","Pass in Interview"]},"allow_spreadsheet":"Y"}', '2020-06-15 17:39:12', '2020-06-15 17:39:12'),
	(24, 11, '{"scoring":{"type":"SC","method":"CO","YN":[null,null],"NR":["A","b","C","D","E"],"CO":"RS"},"subjects":["re","eng","sci","ss","o"]}', '2020-06-19 11:33:33', '2020-06-19 11:33:33'),
	(27, 12, '{"scoring":{"type":"SC","method":"YN","YN":["Granted","Denied"],"NR":["A","B","C","D","E"]}}', '2020-06-19 13:16:32', '2020-06-19 13:16:32');
/*!40000 ALTER TABLE `eligibility_content` ENABLE KEYS */;

-- Dumping structure for table leanfrogmagnet.eligibility_template
CREATE TABLE IF NOT EXISTS `eligibility_template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `type` varchar(2000) NOT NULL DEFAULT '0',
  `content_html` text NOT NULL,
  `district_id` int(11) NOT NULL DEFAULT '1',
  `status` enum('Y','N','T') DEFAULT 'Y',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;

-- Dumping data for table leanfrogmagnet.eligibility_template: ~10 rows (approximately)
/*!40000 ALTER TABLE `eligibility_template` DISABLE KEYS */;
REPLACE INTO `eligibility_template` (`id`, `name`, `type`, `content_html`, `district_id`, `status`, `created_at`, `updated_at`) VALUES
	(1, 'Interview Score', '1', 'interview', 1, 'Y', '2020-06-05 12:13:27', '2020-06-05 12:13:27'),
	(2, 'Grades', '1', 'grades', 1, 'T', '2020-06-05 12:13:27', '2020-06-05 12:13:27'),
	(3, 'Academic Grade Calculation', '1', 'academic_grade_calculation', 1, 'T', '2020-06-05 12:13:27', '2020-06-09 12:42:17'),
	(4, 'Recommendation Form', '1', '', 1, 'T', '2020-06-05 12:13:27', '2020-06-05 12:13:27'),
	(5, 'Writing Prompt', '1', 'writing_prompt', 1, 'Y', '2020-06-05 12:13:28', '2020-06-05 12:13:28'),
	(6, 'Audition', '1', 'audition', 1, 'Y', '2020-06-05 12:13:28', '2020-06-05 12:13:28'),
	(7, 'Committee Score', '1', 'committee_score', 1, 'Y', '2020-06-05 12:13:28', '2020-06-05 12:13:28'),
	(8, 'Conduct Disciplinary Info', '1', 'conduct_disciplinary', 1, 'Y', '2020-06-05 12:13:28', '2020-06-10 06:09:58'),
	(9, 'Special Ed Indicators', '1', '', 1, 'T', '2020-06-05 12:13:28', '2020-06-05 12:13:28'),
	(10, 'Standardized Testing', '1', 'standardized_testing', 1, 'Y', '2020-06-05 12:13:28', '2020-06-05 12:13:28');
/*!40000 ALTER TABLE `eligibility_template` ENABLE KEYS */;

/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
