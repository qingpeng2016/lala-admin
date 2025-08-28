-- 创建数据库
CREATE DATABASE IF NOT EXISTS telegram_bot CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE telegram_bot;

-- 用户行为记录表
CREATE TABLE system_new_user_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL COMMENT 'Telegram用户ID',
    username VARCHAR(255) DEFAULT NULL COMMENT 'Telegram用户名',
    action VARCHAR(100) NOT NULL COMMENT '用户操作类型',
    chat_id BIGINT NOT NULL COMMENT '聊天ID',
    message_id INT DEFAULT NULL COMMENT '消息ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    INDEX idx_chat_id (chat_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户行为记录表';

-- 用户统计表
CREATE TABLE system_new_user_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL COMMENT 'Telegram用户ID',
    username VARCHAR(255) DEFAULT NULL COMMENT 'Telegram用户名',
    first_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '首次出现时间',
    last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '最后出现时间',
    total_actions INT DEFAULT 0 COMMENT '总操作次数',
    kefu_clicks INT DEFAULT 0 COMMENT '客服点击次数',
    usergroup_clicks INT DEFAULT 0 COMMENT '用户群点击次数',
    website_clicks INT DEFAULT 0 COMMENT '官网点击次数',
    app_clicks INT DEFAULT 0 COMMENT 'APP点击次数',
    UNIQUE KEY unique_user_id (user_id),
    INDEX idx_username (username),
    INDEX idx_last_seen (last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户统计表';
