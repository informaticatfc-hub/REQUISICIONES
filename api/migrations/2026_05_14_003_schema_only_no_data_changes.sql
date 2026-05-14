-- ============================================================
-- Migracion 2026-05-14-003 :: Alineacion de esquema SIN tocar datos existentes
-- The Fuentes Workspace
-- ============================================================
-- Objetivo:
--   Alinear la BD legacy al runtime actual sin UPDATE/DELETE de
--   registros ya insertados. Solo DDL + catalogos faltantes.
--
-- Politica de esta migracion:
--   - NO modifica filas existentes de tablas de negocio.
--   - NO elimina registros.
--   - NO reasigna roles de usuarios existentes.
--   - Solo crea/ajusta estructura faltante y semillas RBAC faltantes.
-- ============================================================

START TRANSACTION;

-- ------------------------------------------------------------
-- 1) Extensiones de users requeridas por auth/admin
-- ------------------------------------------------------------
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `user_role_id`   INT UNSIGNED NULL AFTER `user_directionAcess`,
    ADD COLUMN IF NOT EXISTS `user_email`     VARCHAR(120) NULL AFTER `user_role_id`,
    ADD COLUMN IF NOT EXISTS `user_estatus`   ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO' AFTER `user_email`,
    ADD COLUMN IF NOT EXISTS `user_lastLogin` TIMESTAMP NULL DEFAULT NULL AFTER `user_estatus`;

ALTER TABLE `users`
    MODIFY COLUMN `user_password` VARCHAR(255) NOT NULL
    COMMENT 'Hash de contrasena. Compatible con password_hash()';

CREATE INDEX IF NOT EXISTS `ix_users_estatus`  ON `users`(`user_estatus`);
CREATE INDEX IF NOT EXISTS `ix_users_email`    ON `users`(`user_email`);
CREATE INDEX IF NOT EXISTS `ix_users_nameUser` ON `users`(`user_nameUser`);

-- ------------------------------------------------------------
-- 2) Tablas RBAC base
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `role_id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `role_codigo`      VARCHAR(32)  NOT NULL,
    `role_nombre`      VARCHAR(80)  NOT NULL,
    `role_descripcion` VARCHAR(255) NULL,
    `role_nivel`       TINYINT UNSIGNED NOT NULL DEFAULT 10,
    `role_estatus`     ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO',
    `role_createdAt`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`role_id`),
    UNIQUE KEY `uk_role_codigo` (`role_codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
    `permission_id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `permission_codigo`      VARCHAR(80)  NOT NULL,
    `permission_modulo`      VARCHAR(40)  NOT NULL,
    `permission_descripcion` VARCHAR(255) NULL,
    PRIMARY KEY (`permission_id`),
    UNIQUE KEY `uk_permission_codigo` (`permission_codigo`),
    KEY `ix_permission_modulo` (`permission_modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
    `role_id`       INT UNSIGNED NOT NULL,
    `permission_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`role_id`, `permission_id`),
    CONSTRAINT `fk_rp_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`role_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`permission_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FK users.user_role_id -> roles.role_id
-- Se agrega solo si:
--   1) aun no existe
--   2) no hay referencias invalidas en users.user_role_id
SET @fk_exists := (
    SELECT COUNT(*)
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'users'
      AND CONSTRAINT_NAME = 'fk_users_role'
);

SET @invalid_role_refs := (
    SELECT COUNT(*)
    FROM `users` u
    LEFT JOIN `roles` r ON r.role_id = u.user_role_id
    WHERE u.user_role_id IS NOT NULL
      AND r.role_id IS NULL
);

SET @sql := IF(
    @fk_exists = 0 AND @invalid_role_refs = 0,
    'ALTER TABLE `users` ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`user_role_id`) REFERENCES `roles`(`role_id`) ON DELETE SET NULL',
    'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ------------------------------------------------------------
-- 3) Catalogos RBAC (solo insercion faltante, sin sobrescribir)
-- ------------------------------------------------------------
INSERT IGNORE INTO `roles` (`role_codigo`, `role_nombre`, `role_descripcion`, `role_nivel`) VALUES
    ('admin',     'Administrador',     'Acceso total al sistema',                          100),
    ('director',  'Direccion',         'Autoriza presiones y supervisa operacion',          80),
    ('compras',   'Compras',           'Gestion de requisiciones y proveedores',            60),
    ('finanzas',  'Finanzas',          'Gestion de presiones, bancos y conciliacion',       60),
    ('residente', 'Residente de obra', 'Genera requisiciones de su obra asignada',          40),
    ('lector',    'Solo lectura',      'Consulta sin permisos de modificacion',             20);

INSERT IGNORE INTO `permissions` (`permission_codigo`, `permission_modulo`, `permission_descripcion`) VALUES
    ('obras.view', 'obras', 'Ver obras'),
    ('requisiciones.view', 'requisiciones', 'Ver requisiciones'),
    ('requisiciones.create', 'requisiciones', 'Crear requisiciones'),
    ('requisiciones.edit', 'requisiciones', 'Editar requisiciones'),
    ('presiones.view', 'presiones', 'Ver presiones'),
    ('presiones.create', 'presiones', 'Crear presiones'),
    ('catalogos.view', 'catalogos', 'Ver catalogos'),
    ('direccion.view', 'direccion', 'Ver panel direccion'),
    ('admin.users.view', 'admin', 'Ver usuarios'),
    ('admin.users.create', 'admin', 'Crear usuarios'),
    ('admin.users.edit', 'admin', 'Editar usuarios'),
    ('admin.roles.manage', 'admin', 'Gestionar roles'),
    ('admin.audit.view', 'admin', 'Ver auditoria'),
    ('hojas.view', 'requisiciones', 'Ver hojas de requisicion'),
    ('hojas.delete', 'requisiciones', 'Eliminar hojas'),
    ('hojas.changeProveedor', 'requisiciones', 'Cambiar proveedor de hoja'),
    ('hojas.changeFormaPago', 'requisiciones', 'Cambiar forma de pago de hoja'),
    ('hojas.ligar', 'requisiciones', 'Ligar hojas'),
    ('hojas.toRevision', 'requisiciones', 'Enviar hoja a revision'),
    ('hojas.toPendiente', 'requisiciones', 'Mover hoja a pendiente'),
    ('hojas.pagada', 'presiones', 'Marcar hoja como pagada'),
    ('items.create', 'requisiciones', 'Crear items'),
    ('items.edit', 'requisiciones', 'Editar items'),
    ('items.delete', 'requisiciones', 'Eliminar items');

-- ------------------------------------------------------------
-- 4) Relaciones role-permissions (solo insercion faltante)
-- ------------------------------------------------------------
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.role_id, p.permission_id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.role_codigo = 'admin';

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.role_id, p.permission_id
FROM `roles` r
JOIN `permissions` p ON p.permission_codigo IN (
    'hojas.view','hojas.changeProveedor','hojas.changeFormaPago','hojas.ligar',
    'hojas.toRevision','hojas.toPendiente','items.create','items.edit','items.delete',
    'requisiciones.view','requisiciones.create','requisiciones.edit','obras.view','catalogos.view'
)
WHERE r.role_codigo = 'compras';

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.role_id, p.permission_id
FROM `roles` r
JOIN `permissions` p ON p.permission_codigo IN (
    'hojas.view','hojas.pagada','hojas.ligar','presiones.view','presiones.create','obras.view','catalogos.view'
)
WHERE r.role_codigo = 'finanzas';

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.role_id, p.permission_id
FROM `roles` r
JOIN `permissions` p ON p.permission_codigo IN (
    'hojas.view','hojas.changeProveedor','hojas.changeFormaPago','hojas.ligar',
    'hojas.toRevision','hojas.toPendiente','hojas.pagada','items.create','items.edit','items.delete',
    'obras.view','requisiciones.view','presiones.view','direccion.view'
)
WHERE r.role_codigo = 'director';

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.role_id, p.permission_id
FROM `roles` r
JOIN `permissions` p ON p.permission_codigo IN (
    'hojas.view','items.create','items.edit','obras.view','requisiciones.view','requisiciones.create','requisiciones.edit','catalogos.view'
)
WHERE r.role_codigo = 'residente';

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.role_id, p.permission_id
FROM `roles` r
JOIN `permissions` p ON p.permission_codigo LIKE '%.view'
WHERE r.role_codigo = 'lector';

-- ------------------------------------------------------------
-- 5) Tabla de auditoria y mejoras de esquema usadas por backend
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
    KEY `ix_audit_user` (`audit_userId`),
    KEY `ix_audit_modulo` (`audit_modulo`),
    KEY `ix_audit_fecha` (`audit_createdAt`),
    KEY `ix_audit_accion` (`audit_accion`),
    KEY `ix_audit_modulo_fecha` (`audit_modulo`, `audit_createdAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `logs`
    COMMENT = 'DEPRECATED: reemplazada por audit_log desde 2026-05-14. No usar para escrituras nuevas.';

ALTER TABLE `hojasrequisicion`
    MODIFY COLUMN `hojaRequisicion_FechaSolicitud` DATE NULL DEFAULT NULL;

CREATE INDEX IF NOT EXISTS `ix_hojasreq_estatus`   ON `hojasrequisicion`(`hojaRequisicion_estatus`);
CREATE INDEX IF NOT EXISTS `ix_hojasreq_fechapago` ON `hojasrequisicion`(`hojaRequisicion_fechaPago`);
CREATE INDEX IF NOT EXISTS `ix_prov_estatus`       ON `provedores`(`proveedor_estatus`);
CREATE INDEX IF NOT EXISTS `ix_prov_nombre`        ON `provedores`(`proveedor_nombre`);
CREATE INDEX IF NOT EXISTS `ix_obras_estatus`      ON `obras`(`obras_estatus`);
CREATE INDEX IF NOT EXISTS `ix_emisores_estatus`   ON `emisores`(`emisor_estatus`);
CREATE INDEX IF NOT EXISTS `ix_bancos_activo`      ON `bancos`(`banco_activo`);
CREATE INDEX IF NOT EXISTS `ix_presiones_estatus`  ON `presiones`(`presiones_estatus`);
CREATE INDEX IF NOT EXISTS `ix_presiones_fecha`    ON `presiones`(`presiones_fechaCreacion`);
CREATE INDEX IF NOT EXISTS `ix_req_estatus`        ON `requisiciones`(`requisicion_estatus`);
CREATE INDEX IF NOT EXISTS `ix_req_fecha`          ON `requisiciones`(`requisicion_fechaSolicitud`);

CREATE INDEX IF NOT EXISTS `ix_2fa_user`      ON `two_factor_tokens`(`user_id`);
CREATE INDEX IF NOT EXISTS `ix_2fa_token`     ON `two_factor_tokens`(`token`);
CREATE INDEX IF NOT EXISTS `ix_2fa_expira`    ON `two_factor_tokens`(`fecha_expiracion`);
CREATE INDEX IF NOT EXISTS `ix_2fa_utilizado` ON `two_factor_tokens`(`utilizado`);

-- ------------------------------------------------------------
-- 6) Vistas requeridas por admin/dashboard
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW `v_users_full` AS
SELECT
    u.user_id,
    u.user_nameUser,
    u.user_name,
    COALESCE(u.user_email, u.Email) AS email,
    u.user_estatus,
    u.user_lastLogin,
    u.user_directionAcess,
    r.role_codigo,
    r.role_nombre,
    r.role_nivel,
    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.role_id) AS permisos_count
FROM `users` u
LEFT JOIN `roles` r ON r.role_id = u.user_role_id;

CREATE OR REPLACE VIEW `v_presiones_summary` AS
SELECT
    p.presiones_id,
    p.presiones_nombre,
    p.presiones_alias,
    p.presiones_semana,
    p.presiones_dia,
    p.presiones_fechaCreacion,
    p.presiones_obra,
    o.obras_nombre,
    p.presiones_estatus,
    COUNT(DISTINCT rl.requisicionesLigadas_hojaID) AS hojas_ligadas,
    COALESCE(SUM(h.hojaRequisicion_total), 0) AS total_calculado,
    COALESCE(SUM(h.hojarequisicion_adeudo), 0) AS adeudo_calculado
FROM `presiones` p
LEFT JOIN `obras` o ON o.obras_id = p.presiones_obra
LEFT JOIN `requisicionesligadas` rl ON rl.requisicionesLigada_presionID = p.presiones_id
LEFT JOIN `hojasrequisicion` h ON h.hojaRequisicion_id = rl.requisicionesLigadas_hojaID
GROUP BY
    p.presiones_id,
    p.presiones_nombre,
    p.presiones_alias,
    p.presiones_semana,
    p.presiones_dia,
    p.presiones_fechaCreacion,
    p.presiones_obra,
    o.obras_nombre,
    p.presiones_estatus;

CREATE OR REPLACE VIEW `v_requisiciones_summary` AS
SELECT
    r.requisicion_id,
    r.requisicion_Clave,
    r.requisicion_Numero,
    r.requisicion_Nombre,
    r.requisicion_Obra,
    o.obras_nombre,
    r.requisicion_fechaSolicitud,
    r.requisicion_estatus,
    COUNT(h.hojaRequisicion_id) AS hojas_reales,
    COALESCE(SUM(h.hojaRequisicion_total), 0) AS monto_total,
    SUM(CASE WHEN h.hojaRequisicion_estatus = 'PAGADA' THEN 1 ELSE 0 END) AS hojas_pagadas,
    SUM(CASE WHEN h.hojaRequisicion_estatus = 'NUEVO' THEN 1 ELSE 0 END) AS hojas_nuevas
FROM `requisiciones` r
LEFT JOIN `obras` o ON o.obras_id = r.requisicion_Obra
LEFT JOIN `hojasrequisicion` h ON h.hojaRequisicion_idReq = r.requisicion_id
GROUP BY
    r.requisicion_id,
    r.requisicion_Clave,
    r.requisicion_Numero,
    r.requisicion_Nombre,
    r.requisicion_Obra,
    o.obras_nombre,
    r.requisicion_fechaSolicitud,
    r.requisicion_estatus;

COMMIT;

-- ============================================================
-- Verificacion sugerida (ejecutar manualmente)
-- ============================================================
-- SELECT COUNT(*) AS users_total FROM users;
-- SELECT COUNT(*) AS roles_total FROM roles;
-- SELECT COUNT(*) AS perms_total FROM permissions;
-- SELECT COUNT(*) AS role_perms_total FROM role_permissions;
-- SHOW FULL TABLES WHERE TABLE_TYPE = 'VIEW' AND Tables_in_u701868959_TFC IN ('v_users_full','v_presiones_summary','v_requisiciones_summary');
