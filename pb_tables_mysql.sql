-- phpMyAdmin SQL Dump

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


-- --------------------------------------------------------

--
-- Table structure for table `pb_logs`
--

CREATE TABLE IF NOT EXISTS `pb_logs` (
`id` int(10) unsigned NOT NULL,
  `user_id` mediumint(8) unsigned NOT NULL,
  `proj_id` mediumint(8) unsigned NOT NULL,
  `action` varchar(25) NOT NULL,
  `params` varchar(50) DEFAULT NULL,
  `ok` tinyint(1) NOT NULL,
  `tstamp` int(10) unsigned NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pb_projects`
--

CREATE TABLE IF NOT EXISTS `pb_projects` (
`id` mediumint(8) unsigned NOT NULL,
  `randkey` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `author` mediumint(8) unsigned NOT NULL,
  `type` enum('basic_z80','native_z80','native_eZ80','lua_nspire','sprite','var_z80','numworks_os') CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` varchar(30) CHARACTER SET utf8 NOT NULL,
  `internal_name` varchar(30) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `multiuser` tinyint(1) NOT NULL DEFAULT '0',
  `multi_readwrite` tinyint(1) NOT NULL DEFAULT '0',
  `chat_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `created` int(10) unsigned NOT NULL,
  `updated` int(10) unsigned NOT NULL,
  `fork_of` mediumint(8) unsigned DEFAULT NULL,
  `deleted` int(10) unsigned DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `pb_tokens`
--

CREATE TABLE IF NOT EXISTS `pb_tokens` (
`id` mediumint(8) unsigned NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(1030) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `created` varchar(10) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `expires` varchar(10) COLLATE armscii8_bin NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=armscii8 COLLATE=armscii8_bin COMMENT='Tokens Firebase';

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pb_logs`
--
ALTER TABLE `pb_logs`
 ADD PRIMARY KEY (`id`), ADD KEY `user_id` (`user_id`), ADD KEY `proj_id` (`proj_id`), ADD KEY `tstamp` (`tstamp`);

--
-- Indexes for table `pb_projects`
--
ALTER TABLE `pb_projects`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `randkey` (`author`,`created`,`randkey`), ADD KEY `author` (`author`), ADD KEY `type` (`type`), ADD KEY `fork_of` (`fork_of`), ADD KEY `deleted` (`deleted`);

--
-- Indexes for table `pb_tokens`
--
ALTER TABLE `pb_tokens`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `user_id` (`user_id`), ADD KEY `user_id_2` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pb_logs`
--
ALTER TABLE `pb_logs`
MODIFY `id` int(10) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `pb_projects`
--
ALTER TABLE `pb_projects`
MODIFY `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT for table `pb_tokens`
--
ALTER TABLE `pb_tokens`
MODIFY `id` mediumint(8) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `pb_logs`
--
ALTER TABLE `pb_logs`
ADD CONSTRAINT `pb_logs_ibfk_1` FOREIGN KEY (`proj_id`) REFERENCES `pb_projects` (`id`);

--
-- Constraints for table `pb_projects`
--
ALTER TABLE `pb_projects`
ADD CONSTRAINT `pb_projects_ibfk_1` FOREIGN KEY (`fork_of`) REFERENCES `pb_projects` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
