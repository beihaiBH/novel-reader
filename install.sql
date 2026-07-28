-- =====================================================================
--  小说在线阅读平台 · 数据库安装脚本  (install.sql)
--  Online Novel Reading Platform - Database Schema
--  Version: 3.0
--  字符集: utf8mb4
--
--  用法：
--    mysql -u root -p < install.sql
--  或在 phpMyAdmin 中导入本文件。
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 如需自动建库，可取消下面两行注释（默认库名 novel_reader）
-- CREATE DATABASE IF NOT EXISTS `novel_reader` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
-- USE `novel_reader`;

-- ---------------------------------------------------------------------
-- 用户表
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '登录用户名',
  `password` varchar(255) NOT NULL COMMENT 'bcrypt 密码哈希',
  `nickname` varchar(50) DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(255) DEFAULT NULL COMMENT '头像路径',
  `bio` text COMMENT '个人简介',
  `bind_email` varchar(100) DEFAULT '' COMMENT '绑定邮箱',
  `tags` varchar(255) DEFAULT '' COMMENT '用户标签(逗号分隔)',
  `score` int DEFAULT '0' COMMENT '当前积分(与 total_points 同步)',
  `total_points` int DEFAULT '0' COMMENT '累计获得总积分',
  `reading_minutes` int DEFAULT '0' COMMENT '累计阅读分钟数',
  `last_checkin_date` date DEFAULT NULL COMMENT '最后签到日期',
  `consecutive_checkin_days` int DEFAULT '0' COMMENT '连续签到天数',
  `level` int DEFAULT '0' COMMENT '等级(冗余缓存)',
  `uuid` varchar(64) NOT NULL COMMENT '用户唯一标识',
  `token` varchar(64) DEFAULT NULL COMMENT '登录令牌',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 用户偏好 / 阅读数据表
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `user_prefs`;
CREATE TABLE `user_prefs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uuid` varchar(64) NOT NULL,
  `nickname` varchar(50) DEFAULT '',
  `avatar` varchar(255) DEFAULT '',
  `liked_comments` text COMMENT 'JSON: [1,2,3]',
  `liked_novel` tinyint(1) DEFAULT '0',
  `settings` text COMMENT 'JSON: {"fontSize":"17","theme":"light"}',
  `tags` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `follows` json DEFAULT NULL COMMENT '追更列表',
  `bookmarks` json DEFAULT NULL COMMENT '收藏列表',
  `progress` json DEFAULT NULL COMMENT '阅读进度',
  `comment_count` int DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 评论表
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nickname` varchar(50) NOT NULL DEFAULT '匿名读者',
  `avatar` varchar(255) DEFAULT '',
  `email` varchar(100) DEFAULT '',
  `content` text NOT NULL,
  `tags` text,
  `likes` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `parent_id` int DEFAULT '0' COMMENT '父评论id(0为主楼)',
  `uuid` varchar(64) DEFAULT '',
  `source` varchar(20) DEFAULT 'reading',
  `novel_id` varchar(10) DEFAULT '001',
  PRIMARY KEY (`id`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_novel_id` (`novel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 积分变动日志表
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `user_points_log`;
CREATE TABLE `user_points_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uuid` varchar(64) NOT NULL COMMENT '用户UUID',
  `points` int NOT NULL COMMENT '变动积分(+增加/-扣除)',
  `balance` int NOT NULL COMMENT '变动后总积分余额',
  `action_type` varchar(32) NOT NULL COMMENT '类型: reading/checkin/review/bookmark/admin',
  `detail` text COMMENT '变动详情JSON',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_uuid` (`uuid`),
  KEY `idx_created` (`created_at`),
  KEY `idx_type` (`action_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 每日签到表
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `user_checkins`;
CREATE TABLE `user_checkins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uuid` varchar(64) NOT NULL COMMENT '用户UUID',
  `checkin_date` date NOT NULL COMMENT '签到日期',
  `consecutive_days` int DEFAULT '1' COMMENT '本次签到时的连续天数',
  `points_earned` int DEFAULT '0' COMMENT '本次签到获得积分',
  `bonus_points` int DEFAULT '0' COMMENT '额外奖励积分(如7天满贯奖)',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_uuid_date` (`uuid`,`checkin_date`),
  KEY `idx_uuid` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 投稿表
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `submissions`;
CREATE TABLE `submissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `uuid` varchar(64) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `filename` varchar(200) NOT NULL,
  `original_name` varchar(200) DEFAULT NULL,
  `status` tinyint DEFAULT '0' COMMENT '0待审/1通过/2拒绝',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 站点统计表
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `stats`;
CREATE TABLE `stats` (
  `stat_key` varchar(20) NOT NULL,
  `stat_value` int DEFAULT '0',
  `novel_id` varchar(10) NOT NULL DEFAULT '001',
  PRIMARY KEY (`stat_key`,`novel_id`),
  KEY `idx_novel_stats` (`novel_id`,`stat_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 邮箱验证码表
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `verify_codes`;
CREATE TABLE `verify_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `code` varchar(6) NOT NULL,
  `used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
-- 访问日志表
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `logs`;
CREATE TABLE `logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip` varchar(45) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `detail` text,
  `page` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_time` (`created_at`),
  KEY `idx_logs_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---- 阅读笔记（登录用户永久保存高亮笔记）----
CREATE TABLE IF NOT EXISTS `reading_notes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `novel_id` varchar(10) NOT NULL,
  `chapter` int NOT NULL,
  `text_content` text NOT NULL,
  `note` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `novel_chapter` (`novel_id`, `chapter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- 默认管理员账号
--   用户名: admin
--   密码:   admin123   （首次登录后请立即修改！）
--   说明: 系统以用户名 admin 作为站长身份判定。
-- ---------------------------------------------------------------------
INSERT INTO `users`
  (`username`, `password`, `nickname`, `uuid`, `score`, `total_points`, `level`)
VALUES
  ('admin', '__ADMIN_HASH__', '站长', 'admin-0000-0000-0000-000000000001', 0, 0, 0);

SET FOREIGN_KEY_CHECKS = 1;
-- =====================================================================
--  安装完成。请在 config/database.php 中填写数据库连接信息。
-- =====================================================================
