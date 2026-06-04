-- ============================================================
-- Migración 011 — Datos de pago en hojasrequisicion (F-M2)
-- Fecha: 2026-05-28
-- ============================================================
-- Agrega campos para registrar el pago efectivo de una hoja
-- autorizada: folio del comprobante, banco utilizado y fecha
-- de pago. Estos campos los llena el rol finanzas al marcar
-- la hoja como PAGADA.
-- ============================================================

ALTER TABLE `hojasrequisicion`
    ADD COLUMN IF NOT EXISTS `hojaRequisicion_folioPago` VARCHAR(100) NULL DEFAULT NULL
        COMMENT 'Folio o referencia del comprobante de pago'
        AFTER `hojaRequisicion_observaciones`,
    ADD COLUMN IF NOT EXISTS `hojaRequisicion_bancoPago` INT NULL DEFAULT NULL
        COMMENT 'FK a la tabla bancos — banco utilizado para el pago'
        AFTER `hojaRequisicion_folioPago`,
    ADD COLUMN IF NOT EXISTS `hojaRequisicion_fechaPago` DATE NULL DEFAULT NULL
        COMMENT 'Fecha en que se realizó el pago'
        AFTER `hojaRequisicion_bancoPago`;

-- Índice para consultas de hojas por estado PAGADA
ALTER TABLE `hojasrequisicion`
    ADD INDEX IF NOT EXISTS `idx_hojaRequisicion_estatus_pago`
        (`hojaRequisicion_estatus`, `hojaRequisicion_fechaPago`);

-- Permiso finanzas.pagar para registrar pagos de hojas autorizadas
INSERT IGNORE INTO `permissions` (`permission_codigo`, `permission_modulo`, `permission_descripcion`)
VALUES ('finanzas.pagar', 'finanzas', 'Registrar pago de hojas autorizadas (marcar como PAGADA)');

-- Asignar el permiso al rol finanzas
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.role_id, p.permission_id
FROM `roles` r
JOIN `permissions` p ON p.permission_codigo = 'finanzas.pagar'
WHERE r.role_codigo = 'finanzas';
