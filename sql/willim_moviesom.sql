-- phpMyAdmin SQL Dump
-- version 4.0.10
-- http://www.phpmyadmin.net
--
-- Machine: 127.0.0.1
-- Genereertijd: 13 jan 2015 om 21:33
-- Serverversie: 5.5.24-log
-- PHP-versie: 5.6.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Databank: `willim_moviesom`
--

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `contents`
--

CREATE TABLE IF NOT EXISTS `contents` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `content_fields`
--

CREATE TABLE IF NOT EXISTS `content_fields` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `content_id` bigint(20) NOT NULL,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `content_id_2` (`content_id`,`name`),
  KEY `content_id` (`content_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `login_tokens`
--

CREATE TABLE IF NOT EXISTS `login_tokens` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `token` varchar(255) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`ip`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `movies`
--

CREATE TABLE IF NOT EXISTS `movies` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `title` varchar(256) NOT NULL,
  `runtime` int(11) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `backdrop_path` varchar(255) DEFAULT NULL,
  `poster_path` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `title` (`title`(255))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `movie_ratings`
--

CREATE TABLE IF NOT EXISTS `movie_ratings` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `movie_id` bigint(20) NOT NULL,
  `source_id` varchar(32) NOT NULL,
  `rating` float DEFAULT NULL,
  `votes` int(11) DEFAULT NULL,
  `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `movie_id` (`movie_id`,`source_id`),
  KEY `rating` (`rating`),
  KEY `voters` (`votes`),
  KEY `updated` (`updated`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `movie_sources`
--

CREATE TABLE IF NOT EXISTS `movie_sources` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `movie_id` bigint(20) NOT NULL,
  `tmdb_id` int(11) NOT NULL,
  `imdb_id` varchar(32) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `movie_id` (`movie_id`),
  UNIQUE KEY `tmdb_id` (`tmdb_id`),
  UNIQUE KEY `imdb_id` (`imdb_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `page_settings`
--

CREATE TABLE IF NOT EXISTS `page_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci NOT NULL,
  `page` varchar(1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '/',
  `page_md5` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`,`page_md5`),
  UNIQUE KEY `name_2` (`name`,`page_md5`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `api` tinyint(1) NOT NULL DEFAULT '0',
  `add` tinyint(1) NOT NULL DEFAULT '0',
  `edit` tinyint(1) NOT NULL DEFAULT '0',
  `created_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `users_movies`
--

CREATE TABLE IF NOT EXISTS `users_movies` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) NOT NULL,
  `movie_id` bigint(20) NOT NULL,
  `tmdb_id` int(11) NOT NULL,
  `imdb_id` varchar(32) NOT NULL,
  `watched` int(11) NOT NULL,
  `want_to_watch` tinyint(4) NOT NULL,
  `blu_ray` tinyint(1) NOT NULL,
  `dvd` tinyint(1) NOT NULL,
  `digital` tinyint(1) NOT NULL,
  `other` tinyint(1) NOT NULL,
  `lend_out` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`,`movie_id`,`tmdb_id`,`imdb_id`),
  KEY `watched` (`watched`),
  KEY `blu_ray` (`blu_ray`),
  KEY `dvd` (`dvd`),
  KEY `digital` (`digital`),
  KEY `other` (`other`),
  KEY `lend_out` (`lend_out`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `widgets`
--

CREATE TABLE IF NOT EXISTS `widgets` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `parent_id` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `type` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `pos` int(11) NOT NULL DEFAULT '-1',
  `page` varchar(1024) COLLATE utf8_unicode_ci NOT NULL DEFAULT '/',
  PRIMARY KEY (`id`),
  KEY `page` (`page`(255)),
  KEY `parent_id` (`parent_id`),
  KEY `pos` (`pos`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Tabelstructuur voor tabel `widget_content`
--

CREATE TABLE IF NOT EXISTS `widget_content` (
  `widget_id` bigint(20) NOT NULL,
  `content_id` bigint(20) NOT NULL,
  UNIQUE KEY `widget_id` (`widget_id`,`content_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
