-- 工单处理系统表
-- 用于记录工单处理、开机处理、大户问题等常见问题及解决方案

CREATE TABLE `ticket_system` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `type` varchar(50) NOT NULL COMMENT '工单类型：ticket(工单处理)、startup(开机处理)、vip_issue(大户问题)',
  `title` varchar(255) NOT NULL COMMENT '问题标题',
  `problem_description` longtext COMMENT '问题描述（富文本）',
  `solution_description` longtext COMMENT '解决方案描述（富文本）',
  `tags` varchar(500) DEFAULT NULL COMMENT '标签，多个标签用逗号分隔，便于搜索',
  `priority` tinyint(1) DEFAULT 1 COMMENT '优先级：1-低，2-中，3-高，4-紧急',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态：1-启用，0-禁用',
  `category` varchar(100) DEFAULT NULL COMMENT '分类，如：网络问题、硬件问题、软件问题等',
  `keywords` varchar(500) DEFAULT NULL COMMENT '关键词，用于搜索匹配',
  `related_tickets` varchar(500) DEFAULT NULL COMMENT '相关工单ID，多个用逗号分隔',
  `creator_id` int(11) DEFAULT NULL COMMENT '创建人ID',
  `creator_name` varchar(100) DEFAULT NULL COMMENT '创建人姓名',
  `updater_id` int(11) DEFAULT NULL COMMENT '最后更新人ID',
  `updater_name` varchar(100) DEFAULT NULL COMMENT '最后更新人姓名',
  `view_count` int(11) DEFAULT 0 COMMENT '查看次数',
  `useful_count` int(11) DEFAULT 0 COMMENT '有用次数（点赞）',
  `not_useful_count` int(11) DEFAULT 0 COMMENT '无用次数',
  `last_used_time` datetime DEFAULT NULL COMMENT '最后使用时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_category` (`category`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_title` (`title`),
  FULLTEXT KEY `ft_search` (`title`, `problem_description`, `solution_description`, `tags`, `keywords`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='工单处理系统表';

-- 插入示例数据
INSERT INTO `ticket_system` (`type`, `title`, `problem_description`, `solution_description`, `tags`, `priority`, `category`, `keywords`, `creator_name`) VALUES
('ticket', '用户无法登录系统', '<p>用户反馈无法登录系统，提示用户名或密码错误。</p>', '<p>1. 检查用户名和密码是否正确<br/>2. 确认账户是否被锁定<br/>3. 重置密码<br/>4. 检查系统状态</p>', '登录,密码,账户', 2, '账户问题', '登录失败,密码错误,账户锁定', '系统管理员'),
('startup', '服务器启动失败', '<p>服务器启动时出现错误，无法正常启动服务。</p>', '<p>1. 检查系统日志<br/>2. 检查硬件状态<br/>3. 检查配置文件<br/>4. 重启服务</p>', '启动,服务器,错误', 3, '系统问题', '启动失败,服务器错误,系统故障', '技术员'),
('vip_issue', 'VIP客户数据同步问题', '<p>VIP客户数据在多个系统间同步出现延迟。</p>', '<p>1. 检查数据同步服务状态<br/>2. 验证网络连接<br/>3. 检查数据库连接<br/>4. 手动触发同步</p>', 'VIP,数据同步,延迟', 4, '数据问题', '数据同步,VIP客户,延迟', '高级技术员');
