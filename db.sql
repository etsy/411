DROP TABLE IF EXISTS `searches`;
DROP INDEX IF EXISTS `searches_type_idx`;
DROP INDEX IF EXISTS `searches_category_idx`;
DROP INDEX IF EXISTS `searches_tags_idx`;
DROP INDEX IF EXISTS `searches_priority_idx`;
DROP INDEX IF EXISTS `searches_enabled_idx`;
DROP INDEX IF EXISTS `searches_assignee_type_idx`;
DROP INDEX IF EXISTS `searches_assignee_idx`;
DROP INDEX IF EXISTS `searches_owner_idx`;

DROP INDEX IF EXISTS `searches_site_id`;
DROP INDEX IF EXISTS `searches_archived`;
DROP INDEX IF EXISTS `searches_create_date`;
DROP INDEX IF EXISTS `searches_update_date`;

CREATE TABLE `searches` (
    `search_id` INTEGER PRIMARY KEY,
    `site_id` INTEGER NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `type` VARCHAR(64) NOT NULL,
    `query_data` TEXT NOT NULL,
    `state_data` TEXT NOT NULL,
    `renderer_data` TEXT NOT NULL,
    `description` TEXT NOT NULL,
    `category` VARCHAR(64) NOT NULL,
    `tags` VARCHAR(255) NOT NULL,
    `priority` UNSIGNED INTEGER NOT NULL,
    `schedule_type` UNSIGNED INTEGER NOT NULL,
    `frequency` UNSIGNED INTEGER NOT NULL,
    `cron_expression` VARCHAR(255) NOT NULL,
    `range` UNSIGNED INTEGER NOT NULL,
    `enabled` UNSIGNED INTEGER NOT NULL, /* bool */
    `assignee_type` INTEGER NOT NULL,
    `assignee` INTEGER NOT NULL,
    `owner` INTEGER NOT NULL,
    `flap_rate` DOUBLE NOT NULL,
    `notif_type` UNSIGNED INTEGER NOT NULL,
    `notif_format` UNSIGNED INTEGER NOT NULL,
    `notif_data` TEXT NOT NULL,
    `autoclose_threshold` INTEGER NOT NULL,
    `last_status` TEXT NOT NULL,
    `last_execution_date` INTEGER NOT NULL,
    `last_success_date` INTEGER NOT NULL,
    `last_failure_date` INTEGER NOT NULL,
    `last_error_email_date` INTEGER NOT NULL,

    `archived` UNSIGNED INTEGER NOT NULL,
    `create_date` UNSIGNED INTEGER NOT NULL,
    `update_date` UNSIGNED INTEGER NOT NULL
);
CREATE INDEX `searches_type_idx` ON `searches`(`type`);
CREATE INDEX `searches_category_idx` ON `searches`(`category`);
CREATE INDEX `searches_tags_idx` ON `searches`(`tags`);
CREATE INDEX `searches_priority_idx` ON `searches`(`priority`);
CREATE INDEX `searches_enabled_idx` ON `searches`(`enabled`);
CREATE INDEX `searches_assignee_type_idx` ON `searches`(`assignee_type`);
CREATE INDEX `searches_assignee_idx` ON `searches`(`assignee`);
CREATE INDEX `searches_owner_idx` ON `searches`(`owner`);

CREATE INDEX `searches_site_id` ON `searches`(`site_id`);
CREATE INDEX `searches_archived` ON `searches`(`archived`);
CREATE INDEX `searches_create_date` ON `searches`(`create_date`);
CREATE INDEX `searches_update_date` ON `searches`(`update_date`);


DROP TABLE IF EXISTS `alerts`;
DROP INDEX IF EXISTS `alerts_alert_date_idx`;
DROP INDEX IF EXISTS `alerts_search_id_idx`;
DROP INDEX IF EXISTS `alerts_assignee_idx`;
DROP INDEX IF EXISTS `alerts_assignee_type_idx`;
DROP INDEX IF EXISTS `alerts_escalated_idx`;
DROP INDEX IF EXISTS `alerts_state_idx`;
DROP INDEX IF EXISTS `alerts_content_hash_idx`;

DROP INDEX IF EXISTS `alerts_site_id`;
DROP INDEX IF EXISTS `alerts_archived`;
DROP INDEX IF EXISTS `alerts_create_date`;
DROP INDEX IF EXISTS `alerts_update_date`;

CREATE TABLE `alerts` (
    `alert_id` INTEGER PRIMARY KEY,
    `site_id` INTEGER NOT NULL,
    `alert_date` UNSIGNED INTEGER NOT NULL,
    `content` TEXT NOT NULL,
    `content_hash` VARCHAR(64) NOT NULL,
    `renderer_data` TEXT NOT NULL,
    `assignee_type` INTEGER NOT NULL,
    `assignee` INTEGER NOT NULL,
    `search_id` INTEGER NOT NULL,
    `escalated` UNSIGNED INTEGER NOT NULL, /* bool */
    `state` UNSIGNED INTEGER NOT NULL,
    `resolution` UNSIGNED INTEGER NOT NULL,

    `archived` UNSIGNED INTEGER NOT NULL,
    `create_date` UNSIGNED INTEGER NOT NULL,
    `update_date` UNSIGNED INTEGER NOT NULL
);
CREATE INDEX `alerts_alert_date_idx` ON `alerts`(`alert_date`);
CREATE INDEX `alerts_search_id_idx` ON `alerts`(`search_id`);
CREATE INDEX `alerts_assignee_idx` ON `alerts`(`assignee`);
CREATE INDEX `alerts_assignee_type_idx` ON `alerts`(`assignee_type`);
CREATE INDEX `alerts_escalated_idx` ON `alerts`(`escalated`);
CREATE INDEX `alerts_state_idx` ON `alerts`(`state`);
CREATE INDEX `alerts_content_hash_idx` ON `alerts`(`content_hash`);

CREATE INDEX `alerts_site_id` ON `alerts`(`site_id`);
CREATE INDEX `alerts_archived` ON `alerts`(`archived`);
CREATE INDEX `alerts_create_date` ON `alerts`(`create_date`);
CREATE INDEX `alerts_update_date` ON `alerts`(`update_date`);


DROP TABLE IF EXISTS `alert_logs`;

DROP INDEX IF EXISTS `alert_logs_alert_id_idx`;
DROP INDEX IF EXISTS `alert_logs_user_id_idx`;

DROP INDEX IF EXISTS `alert_logs_site_id`;
DROP INDEX IF EXISTS `alert_logs_archived`;
DROP INDEX IF EXISTS `alert_logs_create_date`;
DROP INDEX IF EXISTS `alert_logs_update_date`;

CREATE TABLE `alert_logs` (
    `log_id` INTEGER PRIMARY KEY,
    `site_id` INTEGER NOT NULL,
    `alert_id` INTEGER NOT NULL,
    `user_id` INTEGER NOT NULL,
    `action` UNSIGNED INTEGER NOT NULL,
    `note` TEXT NOT NULL,
    `a` INTEGER NOT NULL,
    `b` INTEGER NOT NULL,

    `archived` UNSIGNED INTEGER NOT NULL,
    `create_date` UNSIGNED INTEGER NOT NULL,
    `update_date` UNSIGNED INTEGER NOT NULL
);
CREATE INDEX `alert_logs_alert_id_idx` ON `alert_logs`(`alert_id`);
CREATE INDEX `alert_logs_user_id_idx` ON `alert_logs`(`user_id`);
CREATE INDEX `alert_logs_action_idx` ON `alert_logs`(`action`);
CREATE INDEX `alert_logs_a_idx` ON `alert_logs`(`a`);
CREATE INDEX `alert_logs_b_idx` ON `alert_logs`(`b`);

CREATE INDEX `alert_logs_site_id` ON `alert_logs`(`site_id`);
CREATE INDEX `alert_logs_archived` ON `alert_logs`(`archived`);
CREATE INDEX `alert_logs_create_date` ON `alert_logs`(`create_date`);
CREATE INDEX `alert_logs_update_date` ON `alert_logs`(`update_date`);


DROP TABLE IF EXISTS `users`;

DROP INDEX IF EXISTS `users_site_id`;
DROP INDEX IF EXISTS `users_archived`;
DROP INDEX IF EXISTS `users_create_date`;
DROP INDEX IF EXISTS `users_update_date`;

CREATE TABLE `users` (
    `user_id` INTEGER PRIMARY KEY,
    `site_id` INTEGER NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `real_name` VARCHAR(255) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `admin` BOOLEAN NOT NULL, /* bool */
    `settings` TEXT NOT NULL,

    `archived` UNSIGNED INTEGER NOT NULL,
    `create_date` UNSIGNED INTEGER NOT NULL,
    `update_date` UNSIGNED INTEGER NOT NULL,

    UNIQUE(`site_id`, `name`)
);
CREATE INDEX `users_site_id` ON `users`(`site_id`);
CREATE INDEX `users_archived` ON `users`(`archived`);
CREATE INDEX `users_create_date` ON `users`(`create_date`);
CREATE INDEX `users_update_date` ON `users`(`update_date`);


DROP TABLE IF EXISTS `groups`;
DROP INDEX IF EXISTS `groups_type_idx`;

DROP INDEX IF EXISTS `groups_site_id`;
DROP INDEX IF EXISTS `groups_archived`;
DROP INDEX IF EXISTS `groups_create_date`;
DROP INDEX IF EXISTS `groups_update_date`;

CREATE TABLE `groups` (
    `group_id` INTEGER PRIMARY KEY,
    `site_id` INTEGER NOT NULL,
    `type` UNSIGNED INTEGER NOT NULL,
    `state` UNSIGNED INTEGER NOT NULL,
    `name` VARCHAR(255) NOT NULL,

    `archived` UNSIGNED INTEGER NOT NULL,
    `create_date` UNSIGNED INTEGER NOT NULL,
    `update_date` UNSIGNED INTEGER NOT NULL
);
CREATE INDEX `groups_type_idx` ON `searches`(`type`);

CREATE INDEX `groups_site_id` ON `groups`(`site_id`);
CREATE INDEX `groups_archived` ON `groups`(`archived`);
CREATE INDEX `groups_create_date` ON `groups`(`create_date`);
CREATE INDEX `groups_update_date` ON `groups`(`update_date`);


DROP TABLE IF EXISTS `group_targets`;
DROP INDEX IF EXISTS `group_targets_group_id_idx`;

DROP INDEX IF EXISTS `group_targets_site_id`;
DROP INDEX IF EXISTS `group_targets_archived`;
DROP INDEX IF EXISTS `group_targets_create_date`;
DROP INDEX IF EXISTS `group_targets_update_date`;

CREATE TABLE `group_targets` (
    `group_target_id` INTEGER PRIMARY KEY,
    `site_id` INTEGER NOT NULL,
    `group_id` INTEGER NOT NULL,
    `type` UNSIGNED INTEGER NOT NULL,
    `user_id` INTEGER NOT NULL,
    `data` VARCHAR(255) NOT NULL,

    `archived` UNSIGNED INTEGER NOT NULL,
    `create_date` UNSIGNED INTEGER NOT NULL,
    `update_date` UNSIGNED INTEGER NOT NULL
);
CREATE INDEX `group_targets_group_id_idx` ON `group_targets`(`group_id`);

CREATE INDEX `group_targets_site_id` ON `group_targets`(`site_id`);
CREATE INDEX `group_targets_archived` ON `group_targets`(`archived`);
CREATE INDEX `group_targets_create_date` ON `group_targets`(`create_date`);
CREATE INDEX `group_targets_update_date` ON `group_targets`(`update_date`);


DROP TABLE IF EXISTS `reports`;
DROP INDEX IF EXISTS `reports_type_idx`;

DROP INDEX IF EXISTS `reports_site_id`;
DROP INDEX IF EXISTS `reports_archived`;
DROP INDEX IF EXISTS `reports_create_date`;
DROP INDEX IF EXISTS `reports_update_date`;

CREATE TABLE `reports` (
    `report_id` INTEGER PRIMARY KEY,
    `site_id` INTEGER NOT NULL,
    `type` UNSIGNED INTEGER NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `frequency` UNSIGNED INTEGER NOT NULL,
    `range` UNSIGNED INTEGER NOT NULL,
    `assignee_type` UNSIGNED INTEGER NOT NULL,
    `assignee` INTEGER NOT NULL,
    `enabled` UNSIGNED INTEGER NOT NULL, /* bool */
    `start_date` INTEGER NOT NULL,

    `archived` UNSIGNED INTEGER NOT NULL,
    `create_date` UNSIGNED INTEGER NOT NULL,
    `update_date` UNSIGNED INTEGER NOT NULL
);
CREATE INDEX `reports_type_idx` ON `searches`(`type`);

CREATE INDEX `reports_site_id` ON `reports`(`site_id`);
CREATE INDEX `reports_archived` ON `reports`(`archived`);
CREATE INDEX `reports_create_date` ON `reports`(`create_date`);
CREATE INDEX `reports_update_date` ON `reports`(`update_date`);


DROP TABLE IF EXISTS `report_targets`;
DROP INDEX IF EXISTS `report_targets_report_id_idx`;

DROP INDEX IF EXISTS `report_targets_site_id`;
DROP INDEX IF EXISTS `report_targets_archived`;
DROP INDEX IF EXISTS `report_targets_create_date`;
DROP INDEX IF EXISTS `report_targets_update_date`;

CREATE TABLE `report_targets` (
    `report_target_id` INTEGER PRIMARY KEY,
    `site_id` INTEGER NOT NULL,
    `report_id` INTEGER NOT NULL,
    `search_id` INTEGER NOT NULL,
    `position` UNSIGNED INTEGER NOT NULL,

    `archived` UNSIGNED INTEGER NOT NULL,
    `create_date` UNSIGNED INTEGER NOT NULL,
    `update_date` UNSIGNED INTEGER NOT NULL
);
CREATE INDEX `report_targets_report_id_idx` ON `report_targets`(`report_id`);

CREATE INDEX `report_targets_site_id` ON `report_targets`(`site_id`);
CREATE INDEX `report_targets_archived` ON `report_targets`(`archived`);
CREATE INDEX `report_targets_create_date` ON `report_targets`(`create_date`);
CREATE INDEX `report_targets_update_date` ON `report_targets`(`update_date`);


DROP TABLE IF EXISTS `lists`;
DROP INDEX IF EXISTS `lists_type_idx`;

DROP INDEX IF EXISTS `lists_site_id`;
DROP INDEX IF EXISTS `lists_archived`;
DROP INDEX IF EXISTS `lists_create_date`;
DROP INDEX IF EXISTS `lists_update_date`;

CREATE TABLE `lists` (
    `list_id` INTEGER PRIMARY KEY,
    `site_id` INTEGER NOT NULL,
    `type` UNSIGNED INTEGER NOT NULL,
    `url` VARCHAR(255) NOT NULL,
    `name` VARCHAR(255) NOT NULL,

    `archived` UNSIGNED INTEGER NOT NULL,
    `create_date` UNSIGNED INTEGER NOT NULL,
    `update_date` UNSIGNED INTEGER NOT NULL
);
CREATE INDEX `lists_type_idx` ON `searches`(`type`);

CREATE INDEX `lists_site_id` ON `lists`(`site_id`);
CREATE INDEX `lists_archived` ON `lists`(`archived`);
CREATE INDEX `lists_create_date` ON `lists`(`create_date`);
CREATE INDEX `lists_update_date` ON `lists`(`update_date`);


DROP TABLE IF EXISTS `search_filters`;
DROP INDEX IF EXISTS `search_filters_search_id_idx`;

DROP INDEX IF EXISTS `search_filters_site_id`;
DROP INDEX IF EXISTS `search_filters_archived`;
DROP INDEX IF EXISTS `search_filters_create_date`;
DROP INDEX IF EXISTS `search_filters_update_date`;

CREATE TABLE `search_filters` (
    `filter_id` INTEGER PRIMARY KEY,
    `site_id` INTEGER NOT NULL,
    `search_id` INTEGER NOT NULL,
    `type` VARCHAR(64) NOT NULL,
    `position` UNSIGNED INTEGER NOT NULL,
    `lifetime` UNSIGNED INTEGER NOT NULL,
    `description` TEXT NOT NULL,
    `data` TEXT NOT NULL,

    `archived` UNSIGNED INTEGER NOT NULL,
    `create_date` UNSIGNED INTEGER NOT NULL,
    `update_date` UNSIGNED INTEGER NOT NULL
);
CREATE INDEX `search_filters_search_id_idx` ON `search_filters`(`search_id`);

CREATE INDEX `search_filters_site_id` ON `search_filters`(`site_id`);
CREATE INDEX `search_filters_archived` ON `search_filters`(`archived`);
CREATE INDEX `search_filters_create_date` ON `search_filters`(`create_date`);
CREATE INDEX `search_filters_update_date` ON `search_filters`(`update_date`);


DROP TABLE IF EXISTS `search_targets`;
DROP INDEX IF EXISTS `search_targets_search_id_idx`;

DROP INDEX IF EXISTS `search_targets_site_id`;
DROP INDEX IF EXISTS `search_targets_archived`;
DROP INDEX IF EXISTS `search_targets_create_date`;
DROP INDEX IF EXISTS `search_targets_update_date`;

CREATE TABLE `search_targets` (
    `target_id` INTEGER PRIMARY KEY,
    `site_id` INTEGER NOT NULL,
    `search_id` INTEGER NOT NULL,
    `type` VARCHAR(64) NOT NULL,
    `lifetime` UNSIGNED INTEGER NOT NULL,
    `description` TEXT NOT NULL,
    `data` TEXT NOT NULL,

    `archived` UNSIGNED INTEGER NOT NULL,
    `create_date` UNSIGNED INTEGER NOT NULL,
    `update_date` UNSIGNED INTEGER NOT NULL
);
CREATE INDEX `search_targets_search_id_idx` ON `search_targets`(`search_id`);

CREATE INDEX `search_targets_site_id` ON `search_targets`(`site_id`);
CREATE INDEX `search_targets_archived` ON `search_targets`(`archived`);
CREATE INDEX `search_targets_create_date` ON `search_targets`(`create_date`);
CREATE INDEX `search_targets_update_date` ON `search_targets`(`update_date`);


DROP TABLE IF EXISTS `search_logs`;
DROP INDEX IF EXISTS `search_logs_search_id_idx`;

DROP INDEX IF EXISTS `search_logs_site_id`;
DROP INDEX IF EXISTS `search_logs_archived`;
DROP INDEX IF EXISTS `search_logs_create_date`;
DROP INDEX IF EXISTS `search_logs_update_date`;

CREATE TABLE `search_logs` (
    `log_id` INTEGER PRIMARY KEY,
    `site_id` INTEGER NOT NULL,
    `search_id` INTEGER NOT NULL,
    `user_id` INTEGER NOT NULL,
    `data` TEXT NOT NULL,
    `description` TEXT NOT NULL,

    `archived` UNSIGNED INTEGER NOT NULL,
    `create_date` UNSIGNED INTEGER NOT NULL,
    `update_date` UNSIGNED INTEGER NOT NULL
);
CREATE INDEX `search_logs_search_id_idx` ON `search_logs`(`search_id`);

CREATE INDEX `search_logs_site_id` ON `search_logs`(`site_id`);
CREATE INDEX `search_logs_archived` ON `search_logs`(`archived`);
CREATE INDEX `search_logs_create_date` ON `search_logs`(`create_date`);
CREATE INDEX `search_logs_update_date` ON `search_logs`(`update_date`);


DROP TABLE IF EXISTS `jobs`;
DROP INDEX IF EXISTS `jobs_target_id_idx`;
DROP INDEX IF EXISTS `jobs_state_idx`;
DROP INDEX IF EXISTS `jobs_tries_idx`;
DROP INDEX IF EXISTS `jobs_last_execution_date_idx`;
DROP INDEX IF EXISTS `jobs_target_date_idx`;
DROP INDEX IF EXISTS `jobs_type_target_id_site_id_archived_idx`;

DROP INDEX IF EXISTS `jobs_site_id`;
DROP INDEX IF EXISTS `jobs_archived`;
DROP INDEX IF EXISTS `jobs_create_date`;
DROP INDEX IF EXISTS `jobs_update_date`;

CREATE TABLE `jobs` (
    `job_id` INTEGER PRIMARY KEY,
    `site_id` INTEGER NOT NULL,
    `type` INTEGER NOT NULL,
    `target_id` INTEGER NOT NULL,
    `state` INTEGER NOT NULL,
    `completion` INTEGER NOT NULL,
    `tries` INTEGER NOT NULL,
    `target_date` INTEGER NOT NULL,
    `last_execution_date` INTEGER NOT NULL,

    `archived` UNSIGNED INTEGER NOT NULL,
    `create_date` UNSIGNED INTEGER NOT NULL,
    `update_date` UNSIGNED INTEGER NOT NULL
);
CREATE INDEX `jobs_type_idx` ON `jobs`(`type`);
CREATE INDEX `jobs_target_id_idx` ON `jobs`(`target_id`);
CREATE INDEX `jobs_state_idx` ON `jobs`(`state`);
CREATE INDEX `jobs_tries_idx` ON `jobs`(`tries`);
CREATE INDEX `jobs_target_date_idx` ON `jobs`(`target_date`);
CREATE INDEX `jobs_last_execution_date_idx` ON `jobs`(`last_execution_date`);
CREATE INDEX `jobs_type_target_id_site_id_archived_idx` ON `jobs`(type, target_id, site_id, archived);

CREATE INDEX `jobs_site_id` ON `jobs`(`site_id`);
CREATE INDEX `jobs_archived` ON `jobs`(`archived`);
CREATE INDEX `jobs_create_date` ON `jobs`(`create_date`);
CREATE INDEX `jobs_update_date` ON `jobs`(`update_date`);


DROP TABLE IF EXISTS `sites`;
DROP INDEX IF EXISTS `sites_name_idx`;
DROP INDEX IF EXISTS `sites_archived`;
DROP INDEX IF EXISTS `sites_create_date`;
DROP INDEX IF EXISTS `sites_update_date`;

CREATE TABLE `sites` (
    `site_id` INTEGER PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL,
    `host` VARCHAR(128) NOT NULL,

    `archived` UNSIGNED INTEGER NOT NULL,
    `create_date` UNSIGNED INTEGER NOT NULL,
    `update_date` UNSIGNED INTEGER NOT NULL
);
CREATE INDEX `sites_name_idx` ON `sites`(`name`);

CREATE INDEX `sites_archived` ON `sites`(`archived`);
CREATE INDEX `sites_create_date` ON `sites`(`create_date`);
CREATE INDEX `sites_update_date` ON `sites`(`update_date`);


DROP TABLE IF EXISTS `meta`;

CREATE TABLE `meta` (
    `site_id` INTEGER NOT NULL,
    `key` VARCHAR(64) NOT NULL,
    `value` VARCHAR(255) NOT NULL,
    PRIMARY KEY(`site_id`, `key`)
);


DROP TABLE IF EXISTS `config`;

CREATE TABLE `config` (
    `site_id` INTEGER NOT NULL,
    `key` VARCHAR(64) NOT NULL,
    `value` VARCHAR(255) NOT NULL,
    PRIMARY KEY(`site_id`, `key`)
);


DROP TABLE IF EXISTS `slogs`;
DROP INDEX IF EXISTS `slogs_type_idx`;
DROP INDEX IF EXISTS `slogs_action_idx`;
DROP INDEX IF EXISTS `slogs_target_idx`;
DROP INDEX IF EXISTS `slogs_actor_idx`;
DROP INDEX IF EXISTS `slogs_a_idx`;
DROP INDEX IF EXISTS `slogs_b_idx`;

DROP INDEX IF EXISTS `slogs_site_id`;
DROP INDEX IF EXISTS `slogs_archived`;
DROP INDEX IF EXISTS `slogs_create_date`;
DROP INDEX IF EXISTS `slogs_update_date`;

CREATE TABLE `slogs` (
    `slog_id` INTEGER PRIMARY KEY,
    `site_id` INTEGER NOT NULL,
    `type` UNSIGNED INTEGER NOT NULL,
    `action` UNSIGNED INTEGER NOT NULL,
    `target` UNSIGNED INTEGER NOT NULL,
    `actor` UNSIGNED INTEGER NOT NULL,
    `a` UNSIGNED INTEGER NOT NULL,
    `b` UNSIGNED INTEGER NOT NULL,

    `archived` UNSIGNED INTEGER NOT NULL,
    `create_date` UNSIGNED INTEGER NOT NULL,
    `update_date` UNSIGNED INTEGER NOT NULL
);
CREATE INDEX `slogs_type_idx` ON `slogs`(`type`);
CREATE INDEX `slogs_action_idx` ON `slogs`(`action`);
CREATE INDEX `slogs_target_idx` ON `slogs`(`target`);
CREATE INDEX `slogs_actor_idx` ON `slogs`(`actor`);
CREATE INDEX `slogs_a_idx` ON `slogs`(`a`);
CREATE INDEX `slogs_b_idx` ON `slogs`(`b`);

CREATE INDEX `slogs_site_id` ON `slogs`(`site_id`);
CREATE INDEX `slogs_archived` ON `slogs`(`archived`);
CREATE INDEX `slogs_create_date` ON `slogs`(`create_date`);
CREATE INDEX `slogs_update_date` ON `slogs`(`update_date`);


