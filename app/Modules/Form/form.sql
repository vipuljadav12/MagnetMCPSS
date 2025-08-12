-- phpMyAdmin SQL Dump
-- version 4.9.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 24, 2020 at 06:59 AM
-- Server version: 10.4.10-MariaDB
-- PHP Version: 7.1.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `leanfrogmagnet`
--

-- --------------------------------------------------------

--
-- Table structure for table `form`
--

CREATE TABLE `form` (
  `id` int(11) NOT NULL,
  `district_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `url` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `thank_you_url` text DEFAULT NULL,
  `thank_you_msg` text DEFAULT NULL,
  `to_mail` varchar(255) DEFAULT NULL,
  `show_logo` enum('y','n') NOT NULL,
  `captcha` enum('y','n') NOT NULL,
  `form_source_code` longtext DEFAULT NULL,
  `status` enum('y','n','t') NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `form`
--

INSERT INTO `form` (`id`, `district_id`, `name`, `url`, `description`, `thank_you_url`, `thank_you_msg`, `to_mail`, `show_logo`, `captcha`, `form_source_code`, `status`, `created_at`, `updated_at`) VALUES
(27, NULL, 'hugevaboka', 'est-magna-qui-dolor', 'Ipsam facilis reicie', 'mypama', 'Alias labore sed ali', 'tyxoh@fgh.hj', 'y', 'y', '<div class=\"card\">\r\n                                    <div class=\"card-header\" contenteditable=\"true\">Dolores enim est rat.</div>\r\n                                    <div class=\"card-body input_container\" id=\"input_container\">\r\n                                        \r\n                                    <div class=\"form-group row\">\r\n<label class=\"control-label col-12 col-md-5\" contenteditable=\"true\">Vel sed est voluptas.</label>\r\n<div class=\"col-12 col-md-6 col-xl-6\">\r\n<input type=\"text\" class=\"form-control\">\r\n</div>\r\n<div class=\"col-md-1\"><span class=\"close\"><i class=\"fa fa-window-close\" aria-hidden=\"true\"></i></span></div></div><div class=\"form-group row checkbox\" id=\"checkbox\">\r\n<label class=\"control-label col-12 col-md-5\" contenteditable=\"true\">Lorem nobis dolorum .</label>\r\n<div class=\"col-12 col-md-6 col-xl-6 checkbox_container\">\r\n<div class=\"custom-control custom-checkbox d-inline\">\r\n<input value=\"\" type=\"checkbox\" class=\"custom-control-input\" id=\"checkbox3\" name=\"\" style=\"height: auto !important;\">\r\n<label for=\"checkbox3\" class=\"custom-control-label\" contenteditable=\"true\">Nostrud labore excep.</label>\r\n</div>\r\n<div class=\"custom-control custom-checkbox d-inline\">\r\n<input value=\"\" type=\"checkbox\" class=\"custom-control-input\" id=\"checkbox4\" name=\"\" style=\"height: auto !important;\">\r\n<label for=\"checkbox4\" class=\"custom-control-label\" contenteditable=\"true\">Nisi saepe et tenetu.</label>\r\n</div>\r\n</div>\r\n<div class=\"col-md-1\"><span class=\"close\"><i class=\"fa fa-window-close\" aria-hidden=\"true\"></i></span></div>\r\n</div><div class=\"form-group row\">\r\n<label class=\"control-label col-12 col-md-5\" contenteditable=\"true\">Debitis delectus, ip.</label>\r\n<div class=\"col-12 col-md-6 col-xl-6\">\r\n<input type=\"text\" class=\"form-control\">\r\n</div>\r\n<div class=\"col-md-1\"><span class=\"close\"><i class=\"fa fa-window-close\" aria-hidden=\"true\"></i></span></div></div><div class=\"form-group row checkbox\" id=\"checkbox\">\r\n<label class=\"control-label col-12 col-md-5\" contenteditable=\"true\">In obcaecati sunt qu.</label>\r\n<div class=\"col-12 col-md-6 col-xl-6 checkbox_container\">\r\n<div class=\"custom-control custom-checkbox d-inline\">\r\n<input value=\"\" type=\"checkbox\" class=\"custom-control-input\" id=\"checkbox5\" name=\"\" style=\"height: auto !important;\">\r\n<label for=\"checkbox5\" class=\"custom-control-label\" contenteditable=\"true\">Consequat. Voluptate.</label>\r\n</div>\r\n<div class=\"custom-control custom-checkbox d-inline\">\r\n<input value=\"\" type=\"checkbox\" class=\"custom-control-input\" id=\"checkbox6\" name=\"\" style=\"height: auto !important;\">\r\n<label for=\"checkbox6\" class=\"custom-control-label\" contenteditable=\"true\">Sit, nulla quis ipsu.</label>\r\n</div>\r\n</div>\r\n<div class=\"col-md-1\"><span class=\"close\"><i class=\"fa fa-window-close\" aria-hidden=\"true\"></i></span></div>\r\n</div><div class=\"form-group row\">\r\n<label class=\"control-label col-12 col-md-5\" contenteditable=\"true\">Ad fugiat, architect.</label>\r\n<div class=\"col-12 col-md-6 col-xl-6\">\r\n<textarea cols=\"40\" style=\"resize: none\">\r\n</textarea>\r\n</div>\r\n<div class=\"col-md-1\"><span class=\"close\"><i class=\"fa fa-window-close\" aria-hidden=\"true\"></i></span></div>\r\n</div></div>\r\n                                </div>', 'y', '2020-06-23 01:06:21', '2020-06-23 01:06:21');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `form`
--
ALTER TABLE `form`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `url` (`url`) USING HASH;

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `form`
--
ALTER TABLE `form`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
