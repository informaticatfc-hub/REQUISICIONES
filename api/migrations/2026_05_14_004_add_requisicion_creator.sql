-- ============================================================
-- Migracion 2026-05-14-004 :: Autor de requisicion
-- The Fuentes Workspace
-- ============================================================
-- Objetivo:
--   Guardar quien creo cada requisicion, tanto por ID de usuario
--   como por nombre visible, para trazabilidad y auditoria.
--
-- Politica:
--   - No borra datos existentes.
--   - Es idempotente.
--   - No rellena historicos con datos ficticios.
-- ============================================================

START TRANSACTION;

-- ------------------------------------------------------------
-- 1) Campos nuevos en `requisiciones`
-- ------------------------------------------------------------
ALTER TABLE `requisiciones`
  ADD COLUMN IF NOT EXISTS `requisicion_userCreado` INT NULL AFTER `requisicion_Obra`,
  ADD COLUMN IF NOT EXISTS `requisicion_userCreadoNombre` VARCHAR(120) NULL AFTER `requisicion_userCreado`;

-- ------------------------------------------------------------
-- 2) Indices utiles para consultas y reportes
-- ------------------------------------------------------------
CREATE INDEX IF NOT EXISTS `ix_requisiciones_user_creado` ON `requisiciones`(`requisicion_userCreado`);
CREATE INDEX IF NOT EXISTS `ix_requisiciones_user_creado_nombre` ON `requisiciones`(`requisicion_userCreadoNombre`);

COMMIT;
