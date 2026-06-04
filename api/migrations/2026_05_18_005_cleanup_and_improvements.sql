-- ============================================================
-- Migración 2026-05-18-005 :: Limpieza y mejoras de BD
-- The Fuentes Workspace
-- ============================================================
-- Objetivo:
--   1. Eliminar tablas sin uso (empleado, empleado_estado,
--      estado_usuario) — confirmadas sin referencias en la API.
--   2. Limpiar datos sucios en `bancos` (\r\n, registros vacíos).
--   3. Rastrear autor de cada hoja de requisición.
--   4. Crear `hoja_estatus_log` para historial estructurado
--      de cambios de estatus por hoja.
--   5. Agregar `audit_resultado` a `audit_log`.
--   6. Crear vista `v_actividad_usuario` para reportes.
--   7. Añadir índices que faltan para rendimiento.
--
-- Esta migración es IDEMPOTENTE. Se puede ejecutar varias
-- veces sin romper datos. Aplica DESPUÉS de la 004.
--
-- IMPORTANTE: ejecutar el archivo COMPLETO de una sola vez
-- (phpMyAdmin → Importar, o mysql < archivo.sql).
-- No correr los bloques por separado: FOREIGN_KEY_CHECKS=0
-- debe estar activo en la misma sesión que los DROP.
-- ============================================================

-- Desactivar FK checks a nivel de sesión ANTES de la
-- transacción, para que aplique a todos los DDL del archivo.
SET FOREIGN_KEY_CHECKS = 0;

START TRANSACTION;

-- ============================================================
-- BLOQUE 1: TABLAS SIN USO
-- ============================================================
-- Las siguientes tablas no tienen ninguna referencia en la API
-- ni en el código PHP. Sus estructuras existen en el dump pero
-- nunca se insertan ni consultan datos de ellas.
--
--   - empleado        : sin registros, sin endpoints
--   - empleado_estado : sin registros, sin endpoints
--   - estado_usuario  : redundante con users.user_estatus ENUM
--
-- NOTE: MySQL hace COMMIT implícito en cada DDL (DROP/ALTER/
-- CREATE). El bloque START TRANSACTION cubre los DML (UPDATE)
-- de Bloque 2. Los DDL son irreversibles por diseño de MySQL.
-- ============================================================

-- ---------------------------------------------------------
-- PRE-PASO OBLIGATORIO (ejecutar primero, una sola vez)
-- ---------------------------------------------------------
-- phpMyAdmin abre una conexión nueva por cada sentencia, por
-- lo que SET FOREIGN_KEY_CHECKS = 0 no sobrevive al siguiente
-- query. La solución es eliminar explícitamente el constraint
-- que apunta a estas tablas antes de hacer el DROP.
--
-- 1) Corre esta consulta para obtener el ALTER TABLE exacto:
--
--   SELECT CONCAT('ALTER TABLE `', kcu.TABLE_NAME,
--                 '` DROP FOREIGN KEY `', kcu.CONSTRAINT_NAME, '`;') AS fix_sql
--   FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu
--   WHERE kcu.REFERENCED_TABLE_NAME IN ('empleado','empleado_estado','estado_usuario')
--     AND kcu.TABLE_SCHEMA = DATABASE()
--     AND kcu.CONSTRAINT_NAME <> 'PRIMARY';
--
-- 2) Ejecuta el resultado (ej: ALTER TABLE `xxx` DROP FOREIGN KEY `yyy`;)
-- 3) Luego ejecuta los tres DROP TABLE de abajo.
-- ---------------------------------------------------------

DROP TABLE IF EXISTS `empleado`;
DROP TABLE IF EXISTS `empleado_estado`;
DROP TABLE IF EXISTS `estado_usuario`;

-- ============================================================
-- BLOQUE 2: LIMPIAR DATOS SUCIOS EN bancos
-- ============================================================

-- 2a. Eliminar retornos de carro (\r) y saltos de línea (\n)
--     que entraron en el import original (ids 69 y 80 tienen
--     \r\n dentro de los valores de texto).
UPDATE `bancos`
SET
    `banco_razonSocial`    = TRIM(REPLACE(REPLACE(`banco_razonSocial`,    CHAR(13), ''), CHAR(10), '')),
    `banco_nombreComercial`= TRIM(REPLACE(REPLACE(`banco_nombreComercial`,CHAR(13), ''), CHAR(10), ''))
WHERE `banco_razonSocial`     LIKE CONCAT('%', CHAR(13), '%')
   OR `banco_razonSocial`     LIKE CONCAT('%', CHAR(10), '%')
   OR `banco_nombreComercial` LIKE CONCAT('%', CHAR(13), '%')
   OR `banco_nombreComercial` LIKE CONCAT('%', CHAR(10), '%');

-- 2b. Desactivar registros con nombre vacío (banco_id 95 y 99).
--     No se borran para no romper FKs históricas; solo se
--     marcan como inactivos para que no aparezcan en selects.
UPDATE `bancos`
SET `banco_activo` = 0
WHERE (`banco_razonSocial` = '' OR `banco_razonSocial` IS NULL)
  AND `banco_activo` = 1;

-- 2c. Corregir banco_id=20 (Banca Mifel con nombre comercial
--     'INTERACCIONES', que es un error de captura; el banco
--     Interacciones correcto es el id=19). Ya tiene activo=0,
--     solo corregimos el nombre para consistencia.
UPDATE `bancos`
SET `banco_nombreComercial` = 'MIFEL (INACTIVO)'
WHERE `banco_id` = 20 AND `banco_nombreComercial` = 'INTERACCIONES';

-- ============================================================
-- BLOQUE 3: RASTREAR AUTOR DE CADA HOJA DE REQUISICIÓN
-- ============================================================
-- Migration 004 ya rastreó el autor de `requisiciones`.
-- Hacemos lo mismo para `hojasrequisicion` y también
-- aprovechamos para guardar a quién validó la hoja.
-- ============================================================

ALTER TABLE `hojasrequisicion`
    ADD COLUMN IF NOT EXISTS `hojaRequisicion_userCreado`
        INT NULL
        AFTER `hojaRequisicion_estatus`
        COMMENT 'FK users.user_id — quién creó la hoja',
    ADD COLUMN IF NOT EXISTS `hojaRequisicion_userCreadoNombre`
        VARCHAR(120) NULL
        AFTER `hojaRequisicion_userCreado`
        COMMENT 'Snapshot del nombre del usuario al crear',
    ADD COLUMN IF NOT EXISTS `hojaRequisicion_userValidado`
        INT NULL
        AFTER `hojaRequisicion_userCreadoNombre`
        COMMENT 'FK users.user_id — quién aprobó/rechazó la hoja',
    ADD COLUMN IF NOT EXISTS `hojaRequisicion_userValidadoNombre`
        VARCHAR(120) NULL
        AFTER `hojaRequisicion_userValidado`
        COMMENT 'Snapshot del nombre del validador';

-- ============================================================
-- BLOQUE 4: HISTORIAL DE ESTATUS DE HOJAS (hoja_estatus_log)
-- ============================================================
-- Cada vez que hojaRequisicion_estatus cambia, la API inserta
-- una fila en esta tabla. Permite ver la trayectoria completa
-- de una hoja: NUEVO → REVISION → PENDIENTE → AUTORIZADA, etc.
-- ============================================================

CREATE TABLE IF NOT EXISTS `hoja_estatus_log` (
    `log_id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `log_hojaId`       INT             NOT NULL
        COMMENT 'FK hojasrequisicion.hojaRequisicion_id',
    `log_estatusAntes` VARCHAR(20)     NULL
        COMMENT 'Estatus previo (NULL = primera inserción)',
    `log_estatusNuevo` VARCHAR(20)     NOT NULL
        COMMENT 'Estatus resultante',
    `log_comentario`   VARCHAR(500)    NULL
        COMMENT 'Observación opcional del responsable',
    `log_userId`       INT             NULL
        COMMENT 'FK users.user_id del responsable del cambio',
    `log_userName`     VARCHAR(120)    NULL
        COMMENT 'Snapshot del nombre para trazabilidad histórica',
    `log_ip`           VARCHAR(45)     NULL,
    `log_createdAt`    TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`log_id`),
    KEY `ix_hoja_log_hoja`      (`log_hojaId`),
    KEY `ix_hoja_log_userId`    (`log_userId`),
    KEY `ix_hoja_log_createdAt` (`log_createdAt`),
    KEY `ix_hoja_log_estatus`   (`log_estatusNuevo`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Historial de cambios de estatus de hojas de requisición';

-- ============================================================
-- BLOQUE 5: AMPLIAR audit_log CON RESULTADO
-- ============================================================
-- Agrega una columna para indicar si la acción fue exitosa,
-- tuvo un error o fue rechazada por validación. Permite
-- filtrar y alertar sobre fallas de forma estructurada.
-- ============================================================

ALTER TABLE `audit_log`
    ADD COLUMN IF NOT EXISTS `audit_resultado`
        ENUM('OK','ERROR','RECHAZADO') NULL
        AFTER `audit_accion`
        COMMENT 'Resultado de la acción registrada';

-- ============================================================
-- BLOQUE 6: VISTA v_actividad_usuario
-- ============================================================
-- Une audit_log con users para mostrar la actividad de cada
-- usuario de forma legible. Útil para el panel de admin.
-- ============================================================

CREATE OR REPLACE VIEW `v_actividad_usuario` AS
SELECT
    al.audit_id,
    al.audit_createdAt                                  AS fecha,
    COALESCE(al.audit_userName, '[sistema]')            AS usuario,
    al.audit_userId,
    al.audit_accion                                     AS accion,
    al.audit_resultado                                  AS resultado,
    al.audit_modulo                                     AS modulo,
    al.audit_entidadId                                  AS entidad_id,
    al.audit_detalle                                    AS detalle,
    al.audit_ip                                         AS ip,
    r.role_codigo                                       AS rol_codigo,
    r.role_nombre                                       AS rol_nombre
FROM `audit_log` al
LEFT JOIN `users`  u ON u.user_id   = al.audit_userId
LEFT JOIN `roles`  r ON r.role_id   = u.user_role_id;

-- ============================================================
-- BLOQUE 7: VISTA v_hoja_historial
-- ============================================================
-- Muestra el historial de estatus de cada hoja junto con los
-- datos principales de la hoja (número, requisición, obra).
-- Útil para el módulo de direccion y auditoría.
-- ============================================================

CREATE OR REPLACE VIEW `v_hoja_historial` AS
SELECT
    hel.log_id,
    hel.log_hojaId                                      AS hoja_id,
    h.hojaRequisicion_numero                            AS hoja_numero,
    req.requisicion_Numero                              AS req_numero,
    o.obras_nombre                                      AS obra,
    hel.log_estatusAntes                                AS estatus_antes,
    hel.log_estatusNuevo                                AS estatus_nuevo,
    hel.log_comentario                                  AS comentario,
    hel.log_userName                                    AS responsable,
    hel.log_createdAt                                   AS fecha_cambio
FROM `hoja_estatus_log` hel
LEFT JOIN `hojasrequisicion` h   ON h.hojaRequisicion_id   = hel.log_hojaId
LEFT JOIN `requisiciones`   req  ON req.requisicion_id     = h.hojaRequisicion_idReq
LEFT JOIN `obras`           o    ON o.obras_id             = req.requisicion_Obra;

-- ============================================================
-- BLOQUE 8: ÍNDICES FALTANTES
-- ============================================================
-- Índices para las consultas más frecuentes de la API.
-- Idempotentes: IF NOT EXISTS evita error si ya existen.
-- ============================================================

-- hojasrequisicion: filtros por requisición y estatus
CREATE INDEX IF NOT EXISTS `ix_hojasreq_idReq`
    ON `hojasrequisicion`(`hojaRequisicion_idReq`);
CREATE INDEX IF NOT EXISTS `ix_hojasreq_estatus`
    ON `hojasrequisicion`(`hojaRequisicion_estatus`);
CREATE INDEX IF NOT EXISTS `ix_hojasreq_userCreado`
    ON `hojasrequisicion`(`hojaRequisicion_userCreado`);

-- itemrequisicion: join frecuente por id de hoja
CREATE INDEX IF NOT EXISTS `ix_itemreq_idHoja`
    ON `itemrequisicion`(`itemRequisicion_idHoja`);

-- requisiciones: filtros por obra y estatus
CREATE INDEX IF NOT EXISTS `ix_requisiciones_obra`
    ON `requisiciones`(`requisicion_Obra`);
CREATE INDEX IF NOT EXISTS `ix_requisiciones_estatus`
    ON `requisiciones`(`requisicion_estatus`);
CREATE INDEX IF NOT EXISTS `ix_requisiciones_userCreado`
    ON `requisiciones`(`requisicion_userCreado`);

-- presiones: filtros comunes
CREATE INDEX IF NOT EXISTS `ix_presiones_obra`
    ON `presiones`(`presiones_obra`);
CREATE INDEX IF NOT EXISTS `ix_presiones_semana`
    ON `presiones`(`presiones_semana`);
CREATE INDEX IF NOT EXISTS `ix_presiones_estatus`
    ON `presiones`(`presiones_estatus`);

-- audit_log: filtros de reportes
CREATE INDEX IF NOT EXISTS `ix_audit_log_userId`
    ON `audit_log`(`audit_userId`);
CREATE INDEX IF NOT EXISTS `ix_audit_log_accion`
    ON `audit_log`(`audit_accion`);
CREATE INDEX IF NOT EXISTS `ix_audit_log_modulo`
    ON `audit_log`(`audit_modulo`);
CREATE INDEX IF NOT EXISTS `ix_audit_log_createdAt`
    ON `audit_log`(`audit_createdAt`);

-- provedores: búsqueda por estatus y nombre
CREATE INDEX IF NOT EXISTS `ix_provedores_estatus`
    ON `provedores`(`proveedor_estatus`);
CREATE INDEX IF NOT EXISTS `ix_provedores_nombre`
    ON `provedores`(`proveedor_nombre`(100));

COMMIT;

-- Restaurar FK checks al final de la sesión.
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- ROLLBACK (descomentar para revertir si algo falla)
-- ============================================================
-- ROLLBACK;

-- ============================================================
-- NOTAS PARA EL EQUIPO
-- ============================================================
-- 1. Las tablas DROP (empleado, empleado_estado, estado_usuario)
--    no tienen datos históricos según el dump u701868959_TFC.sql
--    del 2026-05-18. Si existieran datos en producción que no
--    aparecen en el dump, HACER BACKUP ANTES de ejecutar.
--
-- 2. El historial hoja_estatus_log se pobla desde PHP (api/bitacora.php).
--    Los registros históricos previos a esta migración NO se
--    rellenan automáticamente (política: no inventar datos).
--
-- 3. hojaRequisicion_userCreado se llenará en nuevas hojas
--    desde crud_nueva_hoja.php (modificado en el mismo commit).
--    Las hojas existentes conservan NULL en esa columna.
--
-- 4. La vista v_actividad_usuario reemplaza la consulta ad-hoc
--    de crud_admin.php caso 7. El código puede consultar la
--    vista directamente: SELECT * FROM v_actividad_usuario
--    WHERE audit_userId = ? ORDER BY fecha DESC LIMIT 50;
-- ============================================================
