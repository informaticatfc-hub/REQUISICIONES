-- ============================================================
-- Migration 012: Agregar fecha de creación a proveedores
-- Fecha: 2026-06-23
-- Requerimiento ITF-2: campo proveedor_fechaCreacion para
-- trazabilidad de cuándo fue registrado cada proveedor.
-- ============================================================

ALTER TABLE `provedores`
    ADD COLUMN IF NOT EXISTS `proveedor_fechaCreacion` DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        COMMENT 'Fecha y hora en que se registró el proveedor'
        AFTER `proveedor_estatus`;

