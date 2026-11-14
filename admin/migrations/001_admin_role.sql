-- Idempotently add the role and per-moderator read-only permission columns.
SET @role_sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_user' AND COLUMN_NAME = 'role') = 0,
  'ALTER TABLE `admin_user` ADD COLUMN `role` VARCHAR(20) NOT NULL DEFAULT ''admin''',
  'SELECT 1'
);
PREPARE role_stmt FROM @role_sql;
EXECUTE role_stmt;
DEALLOCATE PREPARE role_stmt;

SET @permission_sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'admin_user' AND COLUMN_NAME = 'moderator_permissions') = 0,
  'ALTER TABLE `admin_user` ADD COLUMN `moderator_permissions` TEXT NULL DEFAULT NULL',
  'SELECT 1'
);
PREPARE permission_stmt FROM @permission_sql;
EXECUTE permission_stmt;
DEALLOCATE PREPARE permission_stmt;
