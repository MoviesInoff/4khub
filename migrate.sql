-- Run this in phpMyAdmin if you already have the database set up
-- This adds the new season_num and episode_num columns to download_links

ALTER TABLE `download_links`
  ADD COLUMN IF NOT EXISTS `season_num` int(11) DEFAULT NULL COMMENT '0 or NULL = all seasons',
  ADD COLUMN IF NOT EXISTS `episode_num` int(11) DEFAULT NULL COMMENT '0 or NULL = full season';

-- Also add social link settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('social_telegram', ''),
('social_twitter', ''),
('social_instagram', ''),
('social_youtube', ''),
('social_facebook', ''),
('homepage_count', '20')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- v6 migrations: custom video URL on media, use_imdb_id on embed servers
ALTER TABLE `media`
  ADD COLUMN IF NOT EXISTS `custom_video_url` varchar(1000) DEFAULT NULL COMMENT 'Self-hosted video URL override';

ALTER TABLE `embed_servers`
  ADD COLUMN IF NOT EXISTS `use_imdb_id` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=use IMDb ID placeholder, 0=use TMDB ID';

-- Add codec column to download_links if missing
ALTER TABLE `download_links`
  ADD COLUMN IF NOT EXISTS `codec` varchar(20) DEFAULT NULL;

-- Add custom_video_url to media table
ALTER TABLE `media`
  ADD COLUMN IF NOT EXISTS `custom_video_url` varchar(1000) DEFAULT NULL COMMENT 'Self-hosted video URL';

-- Add id_type to embed_servers
ALTER TABLE `embed_servers`
  ADD COLUMN IF NOT EXISTS `use_imdb_id` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = use IMDb ID in embed URL, 0 = use TMDB ID';
