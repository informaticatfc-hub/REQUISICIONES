-- ============================================================
-- Migración 2026-05-19-008 :: Asignación múltiple de obras
-- The Fuentes Workspace
-- ============================================================
-- Objetivo:
--   1. Crear tabla pivot `user_obras` para que un usuario
--      pueda tener asignadas N obras (en lugar de 1).
--   2. Migrar datos existentes de `users.user_obra_id`.
--   3. Mantener `users.user_obra_id` para compatibilidad
--      (se usa como "obra activa por defecto").
--
-- Es IDEMPOTENTE — se puede ejecutar varias veces.
-- ============================================================

-- ============================================================
-- 1. Tabla pivot user_obras
-- ============================================================
CREATE TABLE IF NOT EXISTS `user_obras` (
    `user_id`    INT NOT NULL,
    `obras_id`   INT NOT NULL,
    `asignado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `asignado_por` INT NULL,
    PRIMARY KEY (`user_id`, `obras_id`),
    CONSTRAINT `fk_uo_user`  FOREIGN KEY (`user_id`)      REFERENCES `users`(`user_id`)  ON DELETE CASCADE,
    CONSTRAINT `fk_uo_obra`  FOREIGN KEY (`obras_id`)     REFERENCES `obras`(`obras_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_uo_asign` FOREIGN KEY (`asignado_por`) REFERENCES `users`(`user_id`)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. Agregar columna user_obra_id si aún no existe
--    (compatible con MySQL y MariaDB)
-- ============================================================
SET @col_exists := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'users'
      AND COLUMN_NAME  = 'user_obra_id'
);
SET @add_col := IF(@col_exists = 0,
    'ALTER TABLE `users` ADD COLUMN `user_obra_id` INT UNSIGNED NULL',
    'SELECT 1 -- columna ya existe'
);
PREPARE _stmt FROM @add_col;
EXECUTE _stmt;
DEALLOCATE PREPARE _stmt;

-- ============================================================
-- 3. Migrar asignaciones existentes de users.user_obra_id
--    (INSERT IGNORE para idempotencia; se salta si la columna
--     estaba vacía o recién creada)
-- ============================================================
SET @mig_sql := (
    SELECT IF(COUNT(*) > 0,
        'INSERT IGNORE INTO `user_obras` (`user_id`, `obras_id`) SELECT `user_id`, `user_obra_id` FROM `users` WHERE `user_obra_id` IS NOT NULL AND EXISTS (SELECT 1 FROM `obras` WHERE `obras`.`obras_id` = `users`.`user_obra_id`)',
        'SELECT 1'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'users'
      AND COLUMN_NAME  = 'user_obra_id'
);
PREPARE _mig FROM @mig_sql;
EXECUTE _mig;
DEALLOCATE PREPARE _mig;

-- ============================================================
-- Fin de migración
-- ============================================================
-- NOTA: La columna users.user_obra_id se crea aquí si no
-- existía (la migración 002 usa ADD COLUMN IF NOT EXISTS,
-- sintaxis sólo soportada en MariaDB; este script es
-- compatible con MySQL estándar).
-- El sistema actualiza user_obra_id cuando el usuario
-- selecciona una obra activa.
-- ============================================================
