-- ============================================================
-- Migración 016 — Permiso faltante: presiones.authorize
-- Fecha: 2026-07-13
-- ------------------------------------------------------------
-- Contexto: la migración 001 (create_rbac) definió el permiso
-- 'presiones.authorize' ('Autorizar pago de presiones') y lo
-- asignó a finanzas (explícito) y a director/admin (via el CROSS
-- JOIN "todos los permisos"). La migración 014 ya documentó que
-- este permiso causaba 403 en presiones_detalles.php para el rol
-- desarrollador por no haberse sincronizado tras migraciones
-- posteriores.
--
-- Verificado en datos reales: el permiso NO EXISTE en la tabla
-- `permissions`, por lo que TODOS los roles (incluido admin y
-- desarrollador) reciben 403 al:
--   - cerrar/autorizar una presión (crud_presionDetail.php accion 7)
--   - marcar una hoja como pagada desde el detalle (accion 5)
--   - guardar el comentario del director (accion 12)
--   - autorizar/rechazar hojas en all_presiones.php
--
-- Esta migración recrea el permiso y sus asignaciones originales.
-- Idempotente: puede ejecutarse mas de una vez sin duplicar.
-- ============================================================

INSERT INTO `permissions` (`permission_codigo`, `permission_modulo`, `permission_descripcion`)
SELECT 'presiones.authorize', 'presiones', 'Autorizar pago de presiones'
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` WHERE `permission_codigo` = 'presiones.authorize'
);

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`role_id`, p.`permission_id`
FROM `roles` r
JOIN `permissions` p ON p.`permission_codigo` = 'presiones.authorize'
WHERE r.`role_codigo` IN ('admin', 'director', 'finanzas', 'desarrollador')
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` rp
      WHERE rp.`role_id` = r.`role_id` AND rp.`permission_id` = p.`permission_id`
  );
