-- ============================================================
-- Migracion 2026-05-14-002 :: Adaptacion a la BD real
-- The Fuentes Workspace - Fase 3 (post-import dump 13-05-2026)
-- ============================================================
-- Objetivo:
--   Cerrar el gap entre la BD real (dump u701868959_TheFuentesBD)
--   y lo que el codigo de Fase 2 + Fase 3 espera.
--
-- Esta migracion es IDEMPOTENTE: se puede ejecutar varias veces
-- sin romper datos. Usa IF NOT EXISTS / ON DUPLICATE KEY UPDATE
-- y comprobaciones dinamicas via information_schema.
--
-- Compatibilidad:
--   - MySQL 8+ / MariaDB 10.5+
--   - Conserva la tabla legacy `rol_usuario` (no la borra; queda
--     coexistiendo y se sincroniza con la nueva `roles`).
--   - Conserva el campo legacy `users.user_directionAcess`.
--
-- Aplicar DESPUES de:
--   - 2026_05_14_001_create_rbac.sql (ya aplicada en Fase 2)
--
-- ROLLBACK comentado al final.
-- ============================================================

START TRANSACTION;

-- ============================================================
-- BLOQUE 1: COLUMNAS FALTANTES EN `users`
-- ============================================================
-- El dump trae: id_rol, Email (mayuscula), id_estado_usuario,
-- ultimo_login. El codigo espera: user_role_id, user_email,
-- user_estatus, user_lastLogin. Anadimos las nuevas columnas
-- (sin borrar las viejas) y luego sincronizamos.
-- ------------------------------------------------------------

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `user_role_id`   INT UNSIGNED NULL                          AFTER `user_directionAcess`,
    ADD COLUMN IF NOT EXISTS `user_email`     VARCHAR(120) NULL                          AFTER `user_role_id`,
    ADD COLUMN IF NOT EXISTS `user_estatus`   ENUM('ACTIVO','INACTIVO') NOT NULL DEFAULT 'ACTIVO' AFTER `user_email`,
    ADD COLUMN IF NOT EXISTS `user_lastLogin` TIMESTAMP NULL DEFAULT NULL                AFTER `user_estatus`;

-- Subir capacidad de `user_password` para hashes argon2id/bcrypt largos
ALTER TABLE `users`
    MODIFY COLUMN `user_password` VARCHAR(255) NOT NULL
    COMMENT 'Hash de contrasena. Migracion progresiva: texto plano -> password_hash() al primer login.';

-- Indices utiles para login y filtros
CREATE INDEX IF NOT EXISTS `ix_users_estatus`     ON `users`(`user_estatus`);
CREATE INDEX IF NOT EXISTS `ix_users_email`       ON `users`(`user_email`);
CREATE INDEX IF NOT EXISTS `ix_users_nameUser`    ON `users`(`user_nameUser`);


-- ============================================================
-- BLOQUE 2: TABLAS RBAC (si por alguna razon no existen)
-- ============================================================
-- Estas tablas las crea 2026_05_14_001_create_rbac.sql. Las
-- repetimos con IF NOT EXISTS por seguridad de idempotencia.
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
    CONSTRAINT `fk_rp_role`       FOREIGN KEY (`role_id`)       REFERENCES `roles`(`role_id`)             ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`permission_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed roles base (idempotente)
INSERT INTO `roles` (`role_codigo`, `role_nombre`, `role_descripcion`, `role_nivel`) VALUES
    ('admin',     'Administrador',     'Acceso total al sistema',                         100),
    ('director',  'Direccion',         'Autoriza presiones y supervisa toda la operacion', 80),
    ('compras',   'Compras',           'Crea y gestiona requisiciones y proveedores',      60),
    ('finanzas',  'Finanzas',          'Gestiona presiones, bancos y conciliacion',        60),
    ('residente', 'Residente de obra', 'Genera requisiciones de su obra asignada',         40),
    ('lector',    'Solo lectura',      'Consulta de informacion sin permisos de cambio',   20)
ON DUPLICATE KEY UPDATE
    `role_nombre`      = VALUES(`role_nombre`),
    `role_descripcion` = VALUES(`role_descripcion`),
    `role_nivel`       = VALUES(`role_nivel`);


-- ============================================================
-- BLOQUE 3: FK users.user_role_id -> roles.role_id
-- ============================================================
-- Se anade solo si no existe. Idempotente.
-- ------------------------------------------------------------

SET @fk_exists := (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME        = 'users'
      AND CONSTRAINT_NAME   = 'fk_users_role'
);
SET @sql := IF(@fk_exists = 0,
    'ALTER TABLE `users` ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`user_role_id`) REFERENCES `roles`(`role_id`) ON DELETE SET NULL',
    'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ============================================================
-- BLOQUE 4: MIGRACION DE DATOS LEGACY A LAS NUEVAS COLUMNAS
-- ============================================================

-- 4.1 Mapeo `id_rol` (rol_usuario legacy) -> `user_role_id` (roles RBAC)
--     rol_usuario:  1 Superadmin, 2 Admin, 3 Editor, 4 Developer, 5 Usuario, 6 Invitado
--     roles RBAC:   admin, director, compras, finanzas, residente, lector
UPDATE `users` u
    JOIN `roles` r ON r.role_codigo = 'admin'
SET u.user_role_id = r.role_id
WHERE u.user_role_id IS NULL AND u.id_rol IN (1, 2, 4);  -- Superadmin/Admin/Developer -> admin

UPDATE `users` u
    JOIN `roles` r ON r.role_codigo = 'residente'
SET u.user_role_id = r.role_id
WHERE u.user_role_id IS NULL AND u.id_rol IN (3, 5);     -- Editor/Usuario -> residente

UPDATE `users` u
    JOIN `roles` r ON r.role_codigo = 'lector'
SET u.user_role_id = r.role_id
WHERE u.user_role_id IS NULL AND u.id_rol = 6;            -- Invitado -> lector

-- 4.2 Conservar la regla de Fase 2: user_directionAcess=1 -> director
--     SOLO para usuarios que aun no tienen rol asignado por 4.1
UPDATE `users` u
    JOIN `roles` r ON r.role_codigo = 'director'
SET u.user_role_id = r.role_id
WHERE u.user_role_id IS NULL AND u.user_directionAcess = 1;

-- 4.3 Resto: residente (default seguro)
UPDATE `users` u
    JOIN `roles` r ON r.role_codigo = 'residente'
SET u.user_role_id = r.role_id
WHERE u.user_role_id IS NULL;

-- 4.4 Email legacy `Email` -> `user_email`
UPDATE `users`
SET user_email = NULLIF(TRIM(`Email`), '')
WHERE user_email IS NULL
  AND `Email` IS NOT NULL
  AND TRIM(`Email`) <> '';

-- 4.5 Estado legacy `id_estado_usuario` -> `user_estatus`
--     estado_usuario:  1 Activo, 2 Inactivo, 3 Pendiente, 4 Suspendido
UPDATE `users`
SET user_estatus = CASE
    WHEN id_estado_usuario IN (2, 4) THEN 'INACTIVO'
    ELSE 'ACTIVO'
END
WHERE user_estatus IS NULL OR user_estatus = '';

-- 4.6 `ultimo_login` -> `user_lastLogin`
UPDATE `users`
SET user_lastLogin = ultimo_login
WHERE user_lastLogin IS NULL
  AND ultimo_login IS NOT NULL;


-- ============================================================
-- BLOQUE 5: AUDIT LOG (tabla nueva)
-- ============================================================
-- La tabla `logs` legacy (vacia, mal modelada) se conserva pero
-- queda DEPRECATED. `audit_log` es la oficial desde Fase 2.
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
    KEY `ix_audit_fecha`  (`audit_createdAt`),
    KEY `ix_audit_accion` (`audit_accion`),
    KEY `ix_audit_modulo_fecha` (`audit_modulo`, `audit_createdAt`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Marcar `logs` legacy como deprecated (solo comentario, no se borra)
ALTER TABLE `logs`
    COMMENT = 'DEPRECATED: reemplazada por audit_log desde 2026-05-14. No usar para escrituras nuevas.';


-- ============================================================
-- BLOQUE 6: FIX SCHEMA `hojasrequisicion`
-- ============================================================
-- El codigo permite que `hojaRequisicion_FechaSolicitud` sea NULL
-- cuando no llega del cliente, pero la columna esta NOT NULL en
-- el dump. La hacemos nullable.
-- ------------------------------------------------------------

ALTER TABLE `hojasrequisicion`
    MODIFY COLUMN `hojaRequisicion_FechaSolicitud` DATE NULL DEFAULT NULL;

-- Indices para acelerar filtros frecuentes
CREATE INDEX IF NOT EXISTS `ix_hojasreq_estatus`   ON `hojasrequisicion`(`hojaRequisicion_estatus`);
CREATE INDEX IF NOT EXISTS `ix_hojasreq_fechapago` ON `hojasrequisicion`(`hojaRequisicion_fechaPago`);


-- ============================================================
-- BLOQUE 7: SANEAR `requisicionesligadas` (integridad rota)
-- ============================================================
-- El dump trae filas con requisicionesLigadas_hojaID inexistente
-- en hojasrequisicion (p.ej. 683137, 144742, 397283...).
-- Estas filas violarian la FK definida en el dump.
--
-- Estrategia segura: copiar las filas huerfanas a una tabla
-- de cuarentena y borrarlas de la original. Asi conservamos
-- los datos para revision manual y dejamos la tabla operativa.
-- ------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `_quarantine_requisicionesligadas` (
    `q_id`                                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `requisicionesLigada_id`              INT NOT NULL,
    `requisicionesLigada_presionID`       INT NOT NULL,
    `requisicionesLigadas_requisicionID`  INT NOT NULL,
    `requisicionesLigadas_hojaID`         INT NOT NULL,
    `motivo`                              VARCHAR(120) NOT NULL,
    `movedAt`                             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`q_id`),
    KEY `ix_q_hoja`     (`requisicionesLigadas_hojaID`),
    KEY `ix_q_presion`  (`requisicionesLigada_presionID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7.1 Mover filas con hojaID inexistente
INSERT INTO `_quarantine_requisicionesligadas`
    (requisicionesLigada_id, requisicionesLigada_presionID,
     requisicionesLigadas_requisicionID, requisicionesLigadas_hojaID, motivo)
SELECT rl.requisicionesLigada_id,
       rl.requisicionesLigada_presionID,
       rl.requisicionesLigadas_requisicionID,
       rl.requisicionesLigadas_hojaID,
       'hoja_inexistente'
FROM `requisicionesligadas` rl
LEFT JOIN `hojasrequisicion` h
       ON h.hojaRequisicion_id = rl.requisicionesLigadas_hojaID
WHERE h.hojaRequisicion_id IS NULL;

DELETE rl FROM `requisicionesligadas` rl
LEFT JOIN `hojasrequisicion` h
       ON h.hojaRequisicion_id = rl.requisicionesLigadas_hojaID
WHERE h.hojaRequisicion_id IS NULL;

-- 7.2 Mover filas con requisicion inexistente
INSERT INTO `_quarantine_requisicionesligadas`
    (requisicionesLigada_id, requisicionesLigada_presionID,
     requisicionesLigadas_requisicionID, requisicionesLigadas_hojaID, motivo)
SELECT rl.requisicionesLigada_id,
       rl.requisicionesLigada_presionID,
       rl.requisicionesLigadas_requisicionID,
       rl.requisicionesLigadas_hojaID,
       'requisicion_inexistente'
FROM `requisicionesligadas` rl
LEFT JOIN `requisiciones` r
       ON r.requisicion_id = rl.requisicionesLigadas_requisicionID
WHERE r.requisicion_id IS NULL;

DELETE rl FROM `requisicionesligadas` rl
LEFT JOIN `requisiciones` r
       ON r.requisicion_id = rl.requisicionesLigadas_requisicionID
WHERE r.requisicion_id IS NULL;

-- 7.3 Mover filas con presion inexistente
INSERT INTO `_quarantine_requisicionesligadas`
    (requisicionesLigada_id, requisicionesLigada_presionID,
     requisicionesLigadas_requisicionID, requisicionesLigadas_hojaID, motivo)
SELECT rl.requisicionesLigada_id,
       rl.requisicionesLigada_presionID,
       rl.requisicionesLigadas_requisicionID,
       rl.requisicionesLigadas_hojaID,
       'presion_inexistente'
FROM `requisicionesligadas` rl
LEFT JOIN `presiones` p
       ON p.presiones_id = rl.requisicionesLigada_presionID
WHERE p.presiones_id IS NULL;

DELETE rl FROM `requisicionesligadas` rl
LEFT JOIN `presiones` p
       ON p.presiones_id = rl.requisicionesLigada_presionID
WHERE p.presiones_id IS NULL;


-- ============================================================
-- BLOQUE 8: INDICES DE RENDIMIENTO
-- ============================================================

-- 8.1 Filtros por estatus en catalogos
CREATE INDEX IF NOT EXISTS `ix_prov_estatus`     ON `provedores`(`proveedor_estatus`);
CREATE INDEX IF NOT EXISTS `ix_prov_nombre`      ON `provedores`(`proveedor_nombre`);
CREATE INDEX IF NOT EXISTS `ix_obras_estatus`    ON `obras`(`obras_estatus`);
CREATE INDEX IF NOT EXISTS `ix_emisores_estatus` ON `emisores`(`emisor_estatus`);
CREATE INDEX IF NOT EXISTS `ix_bancos_activo`    ON `bancos`(`banco_activo`);

-- 8.2 Filtros en presiones
CREATE INDEX IF NOT EXISTS `ix_presiones_estatus` ON `presiones`(`presiones_estatus`);
CREATE INDEX IF NOT EXISTS `ix_presiones_fecha`   ON `presiones`(`presiones_fechaCreacion`);

-- 8.3 Filtros en requisiciones
CREATE INDEX IF NOT EXISTS `ix_req_estatus` ON `requisiciones`(`requisicion_estatus`);
CREATE INDEX IF NOT EXISTS `ix_req_fecha`   ON `requisiciones`(`requisicion_fechaSolicitud`);


-- ============================================================
-- BLOQUE 9: ASEGURAR FKs DE LA TABLA `users`
-- ============================================================
-- FKs ya existen en el dump (fk_users_rol -> rol_usuario, etc.)
-- pero las dejamos asentadas y verificamos integridad.
-- (no creamos las que ya estan; solo dejamos un placeholder
-- por si se restaura sin las constraints)

-- ============================================================
-- BLOQUE 10: TABLA DE BITACORA DE 2FA (uso futuro)
-- ============================================================
-- El dump trae `two_factor_tokens` con FK a users. La completamos
-- con indices utiles para no romper performance en Fase 4 cuando
-- se active 2FA.

CREATE INDEX IF NOT EXISTS `ix_2fa_user`      ON `two_factor_tokens`(`user_id`);
CREATE INDEX IF NOT EXISTS `ix_2fa_token`     ON `two_factor_tokens`(`token`);
CREATE INDEX IF NOT EXISTS `ix_2fa_expira`    ON `two_factor_tokens`(`fecha_expiracion`);
CREATE INDEX IF NOT EXISTS `ix_2fa_utilizado` ON `two_factor_tokens`(`utilizado`);


-- ============================================================
-- BLOQUE 11: ACCIONES NUEVAS SOPORTADAS POR EL CODIGO
-- ============================================================
-- El codigo de Fase 3 utiliza estos permisos que no tenian
-- equivalente en la BD: el catalogo se sembro en la migracion
-- _001. Aqui anadimos por seguridad los permisos NUEVOS que
-- aparecieron al hardenear los CRUDs y que podrian faltar.
-- ------------------------------------------------------------

INSERT INTO `permissions` (`permission_codigo`, `permission_modulo`, `permission_descripcion`) VALUES
    ('hojas.view',              'requisiciones', 'Ver hojas de requisicion'),
    ('hojas.delete',            'requisiciones', 'Eliminar hojas de requisicion'),
    ('hojas.changeProveedor',   'requisiciones', 'Cambiar proveedor de una hoja'),
    ('hojas.changeFormaPago',   'requisiciones', 'Cambiar forma de pago de una hoja'),
    ('hojas.ligar',             'requisiciones', 'Ligar hojas a una presion'),
    ('hojas.toRevision',        'requisiciones', 'Enviar hoja a revision'),
    ('hojas.toPendiente',       'requisiciones', 'Mover hoja a pendiente'),
    ('hojas.pagada',            'presiones',     'Marcar hoja como pagada'),
    ('items.create',            'requisiciones', 'Agregar items a una hoja'),
    ('items.edit',              'requisiciones', 'Editar items de una hoja'),
    ('items.delete',            'requisiciones', 'Eliminar items de una hoja')
ON DUPLICATE KEY UPDATE
    `permission_modulo`      = VALUES(`permission_modulo`),
    `permission_descripcion` = VALUES(`permission_descripcion`);

-- Asignar los permisos nuevos a roles
-- ADMIN: todos
INSERT IGNORE INTO `role_permissions` (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM `roles` r CROSS JOIN `permissions` p
WHERE r.role_codigo = 'admin'
  AND p.permission_codigo IN
    ('hojas.view','hojas.delete','hojas.changeProveedor','hojas.changeFormaPago',
     'hojas.ligar','hojas.toRevision','hojas.toPendiente','hojas.pagada',
     'items.create','items.edit','items.delete');

-- COMPRAS: gestion completa de hojas e items, sin pagada
INSERT IGNORE INTO `role_permissions` (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM `roles` r CROSS JOIN `permissions` p
WHERE r.role_codigo = 'compras'
  AND p.permission_codigo IN
    ('hojas.view','hojas.changeProveedor','hojas.changeFormaPago','hojas.ligar',
     'hojas.toRevision','hojas.toPendiente',
     'items.create','items.edit','items.delete');

-- FINANZAS: ver hojas y marcar pagada, ligar
INSERT IGNORE INTO `role_permissions` (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM `roles` r CROSS JOIN `permissions` p
WHERE r.role_codigo = 'finanzas'
  AND p.permission_codigo IN ('hojas.view','hojas.pagada','hojas.ligar');

-- DIRECTOR: ver todo, autorizar
INSERT IGNORE INTO `role_permissions` (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM `roles` r CROSS JOIN `permissions` p
WHERE r.role_codigo = 'director'
  AND p.permission_codigo IN
    ('hojas.view','hojas.changeProveedor','hojas.changeFormaPago','hojas.ligar',
     'hojas.toRevision','hojas.toPendiente','hojas.pagada',
     'items.create','items.edit','items.delete');

-- RESIDENTE: crear/editar items en sus requisiciones
INSERT IGNORE INTO `role_permissions` (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM `roles` r CROSS JOIN `permissions` p
WHERE r.role_codigo = 'residente'
  AND p.permission_codigo IN ('hojas.view','items.create','items.edit');

-- LECTOR: solo ver
INSERT IGNORE INTO `role_permissions` (role_id, permission_id)
SELECT r.role_id, p.permission_id
FROM `roles` r CROSS JOIN `permissions` p
WHERE r.role_codigo = 'lector'
  AND p.permission_codigo = 'hojas.view';


-- ============================================================
-- BLOQUE 12: VISTAS UTILES PARA REPORTES (opcional)
-- ============================================================
-- Estas vistas las consume el nuevo dashboard / admin panel.
-- ------------------------------------------------------------

-- 12.1 Vista de usuarios enriquecida (rol + permisos count)
CREATE OR REPLACE VIEW `v_users_full` AS
SELECT
    u.user_id,
    u.user_nameUser,
    u.user_name,
    COALESCE(u.user_email, u.Email)                                    AS email,
    u.user_estatus,
    u.user_lastLogin,
    u.user_directionAcess,
    r.role_codigo,
    r.role_nombre,
    r.role_nivel,
    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.role_id) AS permisos_count
FROM `users` u
LEFT JOIN `roles` r ON r.role_id = u.user_role_id;

-- 12.2 Vista de presiones con totales calculados
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
    COUNT(DISTINCT rl.requisicionesLigadas_hojaID)                     AS hojas_ligadas,
    COALESCE(SUM(h.hojaRequisicion_total), 0)                          AS total_calculado,
    COALESCE(SUM(h.hojarequisicion_adeudo), 0)                         AS adeudo_calculado
FROM `presiones` p
LEFT JOIN `obras` o
       ON o.obras_id = p.presiones_obra
LEFT JOIN `requisicionesligadas` rl
       ON rl.requisicionesLigada_presionID = p.presiones_id
LEFT JOIN `hojasrequisicion` h
       ON h.hojaRequisicion_id = rl.requisicionesLigadas_hojaID
GROUP BY
    p.presiones_id, p.presiones_nombre, p.presiones_alias,
    p.presiones_semana, p.presiones_dia, p.presiones_fechaCreacion,
    p.presiones_obra, o.obras_nombre, p.presiones_estatus;

-- 12.3 Vista de requisiciones con conteo de hojas reales
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
    COUNT(h.hojaRequisicion_id)                                        AS hojas_reales,
    COALESCE(SUM(h.hojaRequisicion_total), 0)                          AS monto_total,
    SUM(CASE WHEN h.hojaRequisicion_estatus = 'PAGADA' THEN 1 ELSE 0 END) AS hojas_pagadas,
    SUM(CASE WHEN h.hojaRequisicion_estatus = 'NUEVO'  THEN 1 ELSE 0 END) AS hojas_nuevas
FROM `requisiciones` r
LEFT JOIN `obras` o
       ON o.obras_id = r.requisicion_Obra
LEFT JOIN `hojasrequisicion` h
       ON h.hojaRequisicion_idReq = r.requisicion_id
GROUP BY
    r.requisicion_id, r.requisicion_Clave, r.requisicion_Numero,
    r.requisicion_Nombre, r.requisicion_Obra, o.obras_nombre,
    r.requisicion_fechaSolicitud, r.requisicion_estatus;


-- ============================================================
-- BLOQUE 13: USUARIO ADMIN DE BOOTSTRAP (si no hay ninguno)
-- ============================================================
-- Si no existe ningun usuario con rol admin, asciende a admin
-- al usuario IrvinDev (user_id=1) para no quedar sin acceso.
-- ------------------------------------------------------------

SET @admin_count := (
    SELECT COUNT(*) FROM `users` u
    JOIN `roles` r ON r.role_id = u.user_role_id
    WHERE r.role_codigo = 'admin'
);

UPDATE `users` u
    JOIN `roles` r ON r.role_codigo = 'admin'
SET u.user_role_id = r.role_id
WHERE @admin_count = 0 AND u.user_nameUser = 'IrvinDev';


COMMIT;

-- ============================================================
-- VERIFICACION POST-MIGRACION (ejecutar por separado)
-- ============================================================
--
-- SELECT 'roles' tabla, COUNT(*) registros FROM roles
-- UNION ALL SELECT 'permissions',      COUNT(*) FROM permissions
-- UNION ALL SELECT 'role_permissions', COUNT(*) FROM role_permissions
-- UNION ALL SELECT 'audit_log',        COUNT(*) FROM audit_log
-- UNION ALL SELECT 'users sin rol',    COUNT(*) FROM users WHERE user_role_id IS NULL
-- UNION ALL SELECT 'cuarentena RL',    COUNT(*) FROM _quarantine_requisicionesligadas;
--
-- SELECT role_codigo, COUNT(*) usuarios
-- FROM users u
-- JOIN roles r ON r.role_id = u.user_role_id
-- GROUP BY role_codigo;
--
-- SHOW INDEX FROM users;
-- SHOW INDEX FROM hojasrequisicion;
-- DESCRIBE users;
--
-- ============================================================
-- ROLLBACK (manual; solo si hay que revertir)
-- ============================================================
--
-- START TRANSACTION;
-- DROP VIEW IF EXISTS v_users_full;
-- DROP VIEW IF EXISTS v_presiones_summary;
-- DROP VIEW IF EXISTS v_requisiciones_summary;
-- ALTER TABLE users DROP FOREIGN KEY fk_users_role;
-- ALTER TABLE users
--     DROP COLUMN user_role_id,
--     DROP COLUMN user_email,
--     DROP COLUMN user_estatus,
--     DROP COLUMN user_lastLogin;
-- ALTER TABLE hojasrequisicion
--     MODIFY COLUMN hojaRequisicion_FechaSolicitud DATE NOT NULL;
-- -- (audit_log, roles, permissions, role_permissions: drops opcionales)
-- COMMIT;
