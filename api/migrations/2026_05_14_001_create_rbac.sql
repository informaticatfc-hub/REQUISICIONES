-- ============================================================
-- Migracion 2026-05-14-001 :: RBAC inicial
-- The Fuentes Workspace - Fase 2
-- ============================================================
-- Objetivo:
--   1. Crear tabla `roles` con los 6 roles del proyecto
--   2. Crear tabla `permissions` con las acciones del sistema
--   3. Crear tabla pivot `role_permissions`
--   4. Anadir columnas a `users`:
--        - user_role_id    (FK a roles)
--        - user_email      (email opcional)
--        - user_estatus    (ACTIVO/INACTIVO)
--        - user_lastLogin  (timestamp del ultimo acceso)
--   5. Sembrar datos iniciales:
--        - 6 roles base
--        - permisos por modulo
--        - asignar permisos a cada rol
--   6. Conservar compatibilidad con `user_directionAcess`:
--        - usuarios con user_directionAcess=1 -> rol "director"
--        - resto -> rol "residente" (default seguro)
--
-- ROLLBACK al final del archivo (comentado).
-- ============================================================

START TRANSACTION;

-- ------------------------------------------------------------
-- 1) Tabla `roles`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `role_id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_codigo`      VARCHAR(32)  NOT NULL,
    `role_nombre`      VARCHAR(80)  NOT NULL,
    `role_descripcion` VARCHAR(255) NULL,
    `role_nivel`       TINYINT UNSIGNED NOT NULL DEFAULT 10
        COMMENT 'Jerarquia: 100=admin, 80=director, 60=compras/finanzas, 40=residente, 20=lector',
    `role_estatus`     ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
    `role_createdAt`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`),
    UNIQUE KEY `uk_role_codigo` (`role_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2) Tabla `permissions`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permissions` (
    `permission_id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `permission_codigo`      VARCHAR(80)  NOT NULL
        COMMENT 'Formato: modulo.accion (ej: obras.view, requisiciones.create)',
    `permission_modulo`      VARCHAR(40)  NOT NULL,
    `permission_descripcion` VARCHAR(255) NULL,
    PRIMARY KEY (`permission_id`),
    UNIQUE KEY `uk_permission_codigo` (`permission_codigo`),
    KEY `ix_permission_modulo` (`permission_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3) Pivot `role_permissions`
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id`       INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    CONSTRAINT `fk_rp_role`
        FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission`
        FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`permission_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4) Ampliar tabla `users`
-- ------------------------------------------------------------
-- Anadimos columnas si no existen (compatibles con MySQL 8 y MariaDB 10.5+)

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `user_role_id` INT UNSIGNED NULL AFTER `user_directionAcess`,
    ADD COLUMN IF NOT EXISTS `user_email`   VARCHAR(120) NULL AFTER `user_role_id`,
    ADD COLUMN IF NOT EXISTS `user_estatus` ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO' AFTER `user_email`,
    ADD COLUMN IF NOT EXISTS `user_lastLogin` TIMESTAMP NULL DEFAULT NULL AFTER `user_estatus`;

-- FK opcional (si no existe ya)
SET @fk_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND CONSTRAINT_NAME = 'fk_users_role'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE `users` ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`user_role_id`) REFERENCES `roles`(`role_id`) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 5) Seed: roles base
-- ------------------------------------------------------------
INSERT INTO `roles` (`role_codigo`, `role_nombre`, `role_descripcion`, `role_nivel`) VALUES
    ('admin',     'Administrador',          'Acceso total al sistema, gestion de usuarios y roles',          100),
    ('director',  'Direccion',              'Autoriza presiones y supervisa toda la operacion',               80),
    ('compras',   'Compras',                'Crea y gestiona requisiciones y proveedores',                    60),
    ('finanzas',  'Finanzas',               'Gestiona presiones de pago, bancos y conciliacion',              60),
    ('residente', 'Residente de obra',      'Genera requisiciones de su obra asignada',                       40),
    ('lector',    'Solo lectura',           'Consulta de informacion sin permisos de modificacion',           20)
ON DUPLICATE KEY UPDATE
    `role_nombre`      = VALUES(`role_nombre`),
    `role_descripcion` = VALUES(`role_descripcion`),
    `role_nivel`       = VALUES(`role_nivel`);

-- ------------------------------------------------------------
-- 6) Seed: permisos por modulo
-- ------------------------------------------------------------
INSERT INTO `permissions` (`permission_codigo`, `permission_modulo`, `permission_descripcion`) VALUES
    -- Obras
    ('obras.view',           'obras',         'Ver lista de obras y detalles'),
    ('obras.create',         'obras',         'Crear obras nuevas'),
    ('obras.edit',           'obras',         'Editar obras existentes'),
    ('obras.delete',         'obras',         'Eliminar / archivar obras'),

    -- Requisiciones
    ('requisiciones.view',   'requisiciones', 'Consultar requisiciones'),
    ('requisiciones.create', 'requisiciones', 'Crear requisiciones de compra'),
    ('requisiciones.edit',   'requisiciones', 'Editar requisiciones propias o asignadas'),
    ('requisiciones.delete', 'requisiciones', 'Eliminar requisiciones'),
    ('requisiciones.validate','requisiciones','Validar requisiciones (paso intermedio)'),
    ('requisiciones.authorize','requisiciones','Autorizar requisiciones (final)'),

    -- Presiones (pagos)
    ('presiones.view',       'presiones',     'Consultar presiones de pago'),
    ('presiones.create',     'presiones',     'Crear presiones de pago'),
    ('presiones.edit',       'presiones',     'Editar presiones'),
    ('presiones.authorize',  'presiones',     'Autorizar pago de presiones'),

    -- Catalogos
    ('catalogos.view',       'catalogos',     'Consultar catalogos'),
    ('proveedores.manage',   'catalogos',     'Gestionar proveedores (crear/editar/eliminar)'),
    ('bancos.manage',        'catalogos',     'Gestionar bancos'),

    -- Direccion
    ('direccion.view',       'direccion',     'Acceso al panel de direccion'),
    ('direccion.authorize',  'direccion',     'Autorizar acciones de direccion'),

    -- Admin
    ('admin.users.view',     'admin',         'Ver lista de usuarios'),
    ('admin.users.create',   'admin',         'Crear usuarios nuevos'),
    ('admin.users.edit',     'admin',         'Editar usuarios'),
    ('admin.users.delete',   'admin',         'Eliminar / desactivar usuarios'),
    ('admin.roles.manage',   'admin',         'Asignar roles a usuarios'),
    ('admin.audit.view',     'admin',         'Ver bitacora de auditoria')
ON DUPLICATE KEY UPDATE
    `permission_modulo`      = VALUES(`permission_modulo`),
    `permission_descripcion` = VALUES(`permission_descripcion`);

-- ------------------------------------------------------------
-- 7) Seed: role -> permissions
-- ------------------------------------------------------------

-- Limpiar relaciones previas (idempotente)
DELETE rp FROM `role_permissions` rp
    INNER JOIN `roles` r ON r.role_id = rp.role_id
    WHERE r.role_codigo IN ('admin','director','compras','finanzas','residente','lector');

-- ADMIN: todo
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.role_id, p.permission_id
FROM `roles` r CROSS JOIN `permissions` p
WHERE r.role_codigo = 'admin';

-- DIRECTOR: todo excepto admin.users.delete y admin.users.create
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.role_id, p.permission_id
FROM `roles` r CROSS JOIN `permissions` p
WHERE r.role_codigo = 'director'
  AND p.permission_codigo NOT IN ('admin.users.delete','admin.users.create','admin.roles.manage');

-- COMPRAS: requisiciones (full), obras.view, catalogos.view, proveedores.manage
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.role_id, p.permission_id
FROM `roles` r CROSS JOIN `permissions` p
WHERE r.role_codigo = 'compras'
  AND p.permission_codigo IN (
    'obras.view',
    'requisiciones.view','requisiciones.create','requisiciones.edit','requisiciones.validate',
    'catalogos.view','proveedores.manage',
    'presiones.view'
  );

-- FINANZAS: presiones (full), bancos, requisiciones.view, obras.view
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.role_id, p.permission_id
FROM `roles` r CROSS JOIN `permissions` p
WHERE r.role_codigo = 'finanzas'
  AND p.permission_codigo IN (
    'obras.view',
    'requisiciones.view',
    'presiones.view','presiones.create','presiones.edit','presiones.authorize',
    'catalogos.view','bancos.manage'
  );

-- RESIDENTE: requisiciones (crear/editar propias), obras.view
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.role_id, p.permission_id
FROM `roles` r CROSS JOIN `permissions` p
WHERE r.role_codigo = 'residente'
  AND p.permission_codigo IN (
    'obras.view',
    'requisiciones.view','requisiciones.create','requisiciones.edit',
    'catalogos.view'
  );

-- LECTOR: todo view
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.role_id, p.permission_id
FROM `roles` r CROSS JOIN `permissions` p
WHERE r.role_codigo = 'lector'
  AND p.permission_codigo LIKE '%.view';

-- ------------------------------------------------------------
-- 8) Migrar usuarios existentes a roles
-- ------------------------------------------------------------
-- user_directionAcess=1 -> director
UPDATE `users` u
    INNER JOIN `roles` r ON r.role_codigo = 'director'
    SET u.user_role_id = r.role_id
    WHERE u.user_directionAcess = 1
      AND u.user_role_id IS NULL;

-- resto -> residente (default seguro, ajustable manualmente luego)
UPDATE `users` u
    INNER JOIN `roles` r ON r.role_codigo = 'residente'
    SET u.user_role_id = r.role_id
    WHERE u.user_role_id IS NULL;

-- ------------------------------------------------------------
-- 9) Tabla de auditoria (opcional pero recomendada)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit_log` (
    `audit_id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `audit_userId`    INT UNSIGNED NULL,
    `audit_userName`  VARCHAR(80)  NULL,
    `audit_accion`    VARCHAR(80)  NOT NULL,
    `audit_modulo`    VARCHAR(40)  NULL,
    `audit_entidadId` BIGINT UNSIGNED NULL,
    `audit_detalle`   TEXT NULL,
    `audit_ip`        VARCHAR(45)  NULL,
    `audit_userAgent` VARCHAR(255) NULL,
    `audit_createdAt` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`audit_id`),
    KEY `ix_audit_user`   (`audit_userId`),
    KEY `ix_audit_modulo` (`audit_modulo`),
    KEY `ix_audit_fecha`  (`audit_createdAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

-- ============================================================
-- ROLLBACK (manual, ejecutar solo si se quiere revertir)
-- ============================================================
-- START TRANSACTION;
-- ALTER TABLE `users` DROP FOREIGN KEY `fk_users_role`;
-- ALTER TABLE `users`
--     DROP COLUMN `user_lastLogin`,
--     DROP COLUMN `user_estatus`,
--     DROP COLUMN `user_email`,
--     DROP COLUMN `user_role_id`;
-- DROP TABLE IF EXISTS `audit_log`;
-- DROP TABLE IF EXISTS `role_permissions`;
-- DROP TABLE IF EXISTS `permissions`;
-- DROP TABLE IF EXISTS `roles`;
-- COMMIT;
