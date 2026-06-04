-- ============================================================
-- 2026-05-18-005 :: Script único para phpMyAdmin
-- ============================================================
-- INSTRUCCIONES:
--   1. Abre phpMyAdmin → selecciona la base u701868959_TFC
--   2. Haz clic en "SQL"
--   3. Pega TODO este contenido en el cuadro de texto
--   4. Haz clic en "Continuar" UNA sola vez
--
-- NO ejecutes sentencias sueltas: SET FOREIGN_KEY_CHECKS=0
-- debe estar activo en la misma sesión que los DROP.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. ELIMINAR TABLAS SIN USO (idempotente — se puede re-ejecutar)
-- ============================================================
-- Se usan prepared statements para simular "DROP FOREIGN KEY IF EXISTS"
-- porque MariaDB no soporta esa sintaxis directamente.
-- ============================================================

-- 1a. FK fk_users_empleado (users → empleado)
SET @_fk1 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
               AND CONSTRAINT_NAME = 'fk_users_empleado');
SET @_sql = IF(@_fk1 > 0,
              'ALTER TABLE `users` DROP FOREIGN KEY `fk_users_empleado`',
              'SELECT ''fk_users_empleado ya no existe'' AS info');
PREPARE _s FROM @_sql; EXECUTE _s; DEALLOCATE PREPARE _s;

-- 1b. FK fk_users_estado (users → estado_usuario)
SET @_fk2 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
               AND CONSTRAINT_NAME = 'fk_users_estado');
SET @_sql = IF(@_fk2 > 0,
              'ALTER TABLE `users` DROP FOREIGN KEY `fk_users_estado`',
              'SELECT ''fk_users_estado ya no existe'' AS info');
PREPARE _s FROM @_sql; EXECUTE _s; DEALLOCATE PREPARE _s;

-- 1c. Columna id_empleado (huerfana)
SET @_c1 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
              AND COLUMN_NAME = 'id_empleado');
SET @_sql = IF(@_c1 > 0,
              'ALTER TABLE `users` DROP COLUMN `id_empleado`',
              'SELECT ''id_empleado ya no existe'' AS info');
PREPARE _s FROM @_sql; EXECUTE _s; DEALLOCATE PREPARE _s;

-- 1d. Columna id_estado_usuario (huerfana)
SET @_c2 = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
              AND COLUMN_NAME = 'id_estado_usuario');
SET @_sql = IF(@_c2 > 0,
              'ALTER TABLE `users` DROP COLUMN `id_estado_usuario`',
              'SELECT ''id_estado_usuario ya no existe'' AS info');
PREPARE _s FROM @_sql; EXECUTE _s; DEALLOCATE PREPARE _s;

-- 1e. DROP tablas sin uso
DROP TABLE IF EXISTS `empleado`;
DROP TABLE IF EXISTS `empleado_estado`;
DROP TABLE IF EXISTS `estado_usuario`;

-- ============================================================
-- 2. LIMPIAR DATOS SUCIOS EN bancos
-- ============================================================

-- 2a. Eliminar \r\n incrustados en banco_id 69 y 80
UPDATE `bancos`
SET
    `banco_razonSocial`     = TRIM(REPLACE(REPLACE(`banco_razonSocial`,     CHAR(13), ''), CHAR(10), '')),
    `banco_nombreComercial` = TRIM(REPLACE(REPLACE(`banco_nombreComercial`, CHAR(13), ''), CHAR(10), ''))
WHERE `banco_razonSocial`     LIKE CONCAT('%', CHAR(13), '%')
   OR `banco_razonSocial`     LIKE CONCAT('%', CHAR(10), '%')
   OR `banco_nombreComercial` LIKE CONCAT('%', CHAR(13), '%')
   OR `banco_nombreComercial` LIKE CONCAT('%', CHAR(10), '%');

-- 2b. Desactivar bancos con nombre vacío (ids 95, 99)
UPDATE `bancos`
SET `banco_activo` = 0
WHERE (`banco_razonSocial` = '' OR `banco_razonSocial` IS NULL)
  AND `banco_activo` = 1;

-- 2c. Corregir banco_id=20 (Mifel con nombre 'INTERACCIONES')
UPDATE `bancos`
SET `banco_nombreComercial` = 'MIFEL (INACTIVO)'
WHERE `banco_id` = 20 AND `banco_nombreComercial` = 'INTERACCIONES';

-- ============================================================
-- 3. COLUMNAS DE AUTOR EN hojasrequisicion
-- ============================================================

ALTER TABLE `hojasrequisicion`
    ADD COLUMN IF NOT EXISTS `hojaRequisicion_userCreado`        INT          NULL AFTER `hojaRequisicion_estatus`,
    ADD COLUMN IF NOT EXISTS `hojaRequisicion_userCreadoNombre`  VARCHAR(120) NULL AFTER `hojaRequisicion_userCreado`,
    ADD COLUMN IF NOT EXISTS `hojaRequisicion_userValidado`      INT          NULL AFTER `hojaRequisicion_userCreadoNombre`,
    ADD COLUMN IF NOT EXISTS `hojaRequisicion_userValidadoNombre` VARCHAR(120) NULL AFTER `hojaRequisicion_userValidado`;

-- ============================================================
-- 4. TABLA hoja_estatus_log
-- ============================================================

CREATE TABLE IF NOT EXISTS `hoja_estatus_log` (
    `log_id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `log_hojaId`       INT             NOT NULL,
    `log_estatusAntes` VARCHAR(20)     NULL,
    `log_estatusNuevo` VARCHAR(20)     NOT NULL,
    `log_comentario`   VARCHAR(500)    NULL,
    `log_userId`       INT             NULL,
    `log_userName`     VARCHAR(120)    NULL,
    `log_ip`           VARCHAR(45)     NULL,
    `log_createdAt`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`),
    KEY `ix_hoja_log_hoja`      (`log_hojaId`),
    KEY `ix_hoja_log_userId`    (`log_userId`),
    KEY `ix_hoja_log_createdAt` (`log_createdAt`),
    KEY `ix_hoja_log_estatus`   (`log_estatusNuevo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historial de cambios de estatus de hojas de requisición';

-- ============================================================
-- 5. COLUMNA audit_resultado EN audit_log
-- ============================================================

ALTER TABLE `audit_log`
    ADD COLUMN IF NOT EXISTS `audit_resultado` ENUM('OK','ERROR','RECHAZADO') NULL AFTER `audit_accion`;

-- ============================================================
-- 6. VISTA v_actividad_usuario
-- ============================================================

CREATE OR REPLACE VIEW `v_actividad_usuario` AS
SELECT
    al.audit_id,
    al.audit_createdAt                       AS fecha,
    COALESCE(al.audit_userName, '[sistema]') AS usuario,
    al.audit_userId,
    al.audit_accion                          AS accion,
    al.audit_resultado                       AS resultado,
    al.audit_modulo                          AS modulo,
    al.audit_entidadId                       AS entidad_id,
    al.audit_detalle                         AS detalle,
    al.audit_ip                              AS ip,
    r.role_codigo                            AS rol_codigo,
    r.role_nombre                            AS rol_nombre
FROM `audit_log` al
LEFT JOIN `users` u ON u.user_id  = al.audit_userId
LEFT JOIN `roles` r ON r.role_id  = u.user_role_id;

-- ============================================================
-- 7. VISTA v_hoja_historial
-- ============================================================

CREATE OR REPLACE VIEW `v_hoja_historial` AS
SELECT
    hel.log_id,
    hel.log_hojaId               AS hoja_id,
    h.hojaRequisicion_numero     AS hoja_numero,
    req.requisicion_Numero       AS req_numero,
    o.obras_nombre               AS obra,
    hel.log_estatusAntes         AS estatus_antes,
    hel.log_estatusNuevo         AS estatus_nuevo,
    hel.log_comentario           AS comentario,
    hel.log_userName             AS responsable,
    hel.log_createdAt            AS fecha_cambio
FROM `hoja_estatus_log` hel
LEFT JOIN `hojasrequisicion` h  ON h.hojaRequisicion_id  = hel.log_hojaId
LEFT JOIN `requisiciones`   req ON req.requisicion_id    = h.hojaRequisicion_idReq
LEFT JOIN `obras`           o   ON o.obras_id            = req.requisicion_Obra;

-- ============================================================
-- 8. ÍNDICES FALTANTES
-- ============================================================

CREATE INDEX IF NOT EXISTS `ix_hojasreq_idReq`          ON `hojasrequisicion`(`hojaRequisicion_idReq`);
CREATE INDEX IF NOT EXISTS `ix_hojasreq_estatus`         ON `hojasrequisicion`(`hojaRequisicion_estatus`);
CREATE INDEX IF NOT EXISTS `ix_hojasreq_userCreado`      ON `hojasrequisicion`(`hojaRequisicion_userCreado`);
CREATE INDEX IF NOT EXISTS `ix_itemreq_idHoja`           ON `itemrequisicion`(`itemRequisicion_idHoja`);
CREATE INDEX IF NOT EXISTS `ix_requisiciones_obra`       ON `requisiciones`(`requisicion_Obra`);
CREATE INDEX IF NOT EXISTS `ix_requisiciones_estatus`    ON `requisiciones`(`requisicion_estatus`);
CREATE INDEX IF NOT EXISTS `ix_requisiciones_userCreado` ON `requisiciones`(`requisicion_userCreado`);
CREATE INDEX IF NOT EXISTS `ix_presiones_obra`           ON `presiones`(`presiones_obra`);
CREATE INDEX IF NOT EXISTS `ix_presiones_semana`         ON `presiones`(`presiones_semana`);
CREATE INDEX IF NOT EXISTS `ix_presiones_estatus`        ON `presiones`(`presiones_estatus`);
CREATE INDEX IF NOT EXISTS `ix_audit_log_userId`         ON `audit_log`(`audit_userId`);
CREATE INDEX IF NOT EXISTS `ix_audit_log_accion`         ON `audit_log`(`audit_accion`);
CREATE INDEX IF NOT EXISTS `ix_audit_log_modulo`         ON `audit_log`(`audit_modulo`);
CREATE INDEX IF NOT EXISTS `ix_audit_log_createdAt`      ON `audit_log`(`audit_createdAt`);
CREATE INDEX IF NOT EXISTS `ix_provedores_estatus`       ON `provedores`(`proveedor_estatus`);
CREATE INDEX IF NOT EXISTS `ix_provedores_nombre`        ON `provedores`(`proveedor_nombre`(100));

SET FOREIGN_KEY_CHECKS = 1;
