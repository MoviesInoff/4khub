-- CineHub Database Schema
-- Import into your database via phpMyAdmin
-- DO NOT include CREATE DATABASE line for InfinityFree

SET NAMES utf8;
SET foreign_key_checks = 0;

-- Settings
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Movies & Series (unified table)
CREATE TABLE IF NOT EXISTS `media` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tmdb_id` int(11) NOT NULL,
  `type` enum('movie','tv') NOT NULL DEFAULT 'movie',
  `title` varchar(300) NOT NULL,
  `slug` varchar(350) NOT NULL,
  `tagline` varchar(500) DEFAULT NULL,
  `overview` text,
  `poster_path` varchar(255) DEFAULT NULL,
  `backdrop_path` varchar(255) DEFAULT NULL,
  `release_date` varchar(20) DEFAULT NULL,
  `year` varchar(4) DEFAULT NULL,
  `runtime` int(11) DEFAULT NULL,
  `vote_average` decimal(3,1) DEFAULT NULL,
  `genres` text COMMENT 'JSON array',
  `cast_data` text COMMENT 'JSON array',
  `director` varchar(255) DEFAULT NULL,
  `trailer_key` varchar(100) DEFAULT NULL,
  `seasons_count` int(11) DEFAULT NULL,
  `episodes_count` int(11) DEFAULT NULL,
  `seasons_data` text COMMENT 'JSON array',
  `language` varchar(20) DEFAULT 'en',
  `status` enum('published','draft') NOT NULL DEFAULT 'published',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `tags` text COMMENT 'JSON array: 4K, HDR, etc',
  `quality_prints` text COMMENT 'JSON array of print options',
  `audio_languages` varchar(500) DEFAULT NULL,
  `imdb_id` varchar(20) DEFAULT NULL,
  `custom_video_url` varchar(1000) DEFAULT NULL COMMENT 'Self-hosted video URL',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tmdb_type` (`tmdb_id`,`type`),
  UNIQUE KEY `slug` (`slug`),
  KEY `type` (`type`),
  KEY `status` (`status`),
  KEY `featured` (`featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Download Links
CREATE TABLE IF NOT EXISTS `download_links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `media_id` int(11) NOT NULL,
  `title` varchar(300) NOT NULL COMMENT 'e.g. Thrash (2160p WEB-DL DV HDR)',
  `quality` varchar(50) DEFAULT NULL COMMENT 'e.g. 2160p, 1080p, 720p',
  `format` varchar(100) DEFAULT NULL COMMENT 'e.g. WEB-DL, BluRay',
  `codec` varchar(50) DEFAULT NULL COMMENT 'e.g. H265, x264',
  `hdr` varchar(50) DEFAULT NULL COMMENT 'e.g. DV HDR, HDR10',
  `file_size` varchar(30) DEFAULT NULL COMMENT 'e.g. 13.18 GB',
  `audio` varchar(300) DEFAULT NULL COMMENT 'e.g. Hindi, Tamil, English',
  `url` text,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `season_num` int(11) DEFAULT NULL COMMENT '0 or NULL = all seasons',
  `episode_num` int(11) DEFAULT NULL COMMENT '0 or NULL = full season',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `media_id` (`media_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Embed Servers
CREATE TABLE IF NOT EXISTS `embed_servers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `movie_url` varchar(500) DEFAULT NULL,
  `tv_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `use_imdb_id` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = use IMDb ID, 0 = use TMDB ID',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Watchlist
CREATE TABLE IF NOT EXISTS `watchlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `media_id` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_media` (`user_id`,`media_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Default settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('tmdb_api_key',      ''),
('site_name',         'CineHub'),
('site_tagline',      'Your Premium Entertainment Hub'),
('primary_color',     '#f97316'),
('items_per_page',    '20'),
('allow_registration','1'),
('maintenance_mode',  '0')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- Default embed servers
INSERT INTO `embed_servers` (`name`, `movie_url`, `tv_url`, `is_active`, `sort_order`) VALUES
('VidSrc', 'https://vidsrc.to/embed/movie/{tmdb_id}', 'https://vidsrc.to/embed/tv/{tmdb_id}/{season}/{episode}', 1, 1),
('2Embed', 'https://www.2embed.cc/embed/{tmdb_id}', 'https://www.2embed.cc/embedtv/{tmdb_id}&s={season}&e={episode}', 1, 2),
('SuperEmbed', 'https://multiembed.mov/?video_id={tmdb_id}&tmdb=1', 'https://multiembed.mov/?video_id={tmdb_id}&tmdb=1&s={season}&e={episode}', 1, 3)
ON DUPLICATE KEY UPDATE name = name;

-- Admin user (password: admin123)
INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES
('admin', 'admin@cinehub.com', '$2y$10$TKh8H1.PfbuNnJ3tmoMeYeRu4T8eBCb.5zQBQBKD.5.FHOH0.Gf6.', 'admin')
ON DUPLICATE KEY UPDATE email = email;
