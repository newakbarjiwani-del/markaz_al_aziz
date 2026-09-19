-- Reset all data except users + role/permission related tables.
-- Target: MySQL / MariaDB
-- Effect: all truncated tables will have AUTO_INCREMENT reset to 1.
--
-- Usage:
--   mysql -u <user> -p <database_name> < database/sql/reset_except_users_roles.sql

SET @db := DATABASE();
SET @old_fk_checks := @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

DROP PROCEDURE IF EXISTS reset_except_users_roles;
DELIMITER $$
CREATE PROCEDURE reset_except_users_roles()
BEGIN
    DECLARE done INT DEFAULT 0;
    DECLARE v_table VARCHAR(255);

    DECLARE cur CURSOR FOR
        SELECT table_name
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
          AND table_type = 'BASE TABLE'
          AND table_name NOT IN (
              'users',
              'roles',
              'permissions',
              'model_has_roles',
              'model_has_permissions',
              'role_has_permissions',
              'migrations'
          );

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    OPEN cur;
    read_loop: LOOP
        FETCH cur INTO v_table;
        IF done = 1 THEN
            LEAVE read_loop;
        END IF;

        SET @sql = CONCAT('TRUNCATE TABLE `', v_table, '`');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END LOOP;
    CLOSE cur;
END$$
DELIMITER ;

CALL reset_except_users_roles();
DROP PROCEDURE IF EXISTS reset_except_users_roles;

SET FOREIGN_KEY_CHECKS = @old_fk_checks;

-- Optional: show preserved table counts
SELECT 'users' AS table_name, COUNT(*) AS row_count FROM users
UNION ALL SELECT 'roles', COUNT(*) FROM roles
UNION ALL SELECT 'permissions', COUNT(*) FROM permissions
UNION ALL SELECT 'model_has_roles', COUNT(*) FROM model_has_roles
UNION ALL SELECT 'model_has_permissions', COUNT(*) FROM model_has_permissions
UNION ALL SELECT 'role_has_permissions', COUNT(*) FROM role_has_permissions;
