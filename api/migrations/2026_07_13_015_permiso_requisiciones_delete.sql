-- ============================================================
-- Migración 015 — Permiso faltante: requisiciones.delete
-- Fecha: 2026-07-13
-- ------------------------------------------------------------
-- Contexto: api/crud_Requisiciones.php (accion 9, eliminar
-- requisición) exige el permiso 'requisiciones.delete', pero el
-- catálogo RBAC nunca lo incluyó -> la acción respondía 403 para
-- TODOS los roles, incluido admin.
-- Se crea el permiso y se asigna solo a admin y desarrollador
-- (consistente con hojas.delete, que también es admin-only).
-- Idempotente: puede ejecutarse más de una vez sin duplicar.
-- ============================================================

INSERT INTO `permissions` (`permission_codigo`, `permission_modulo`, `permission_descripcion`)
SELECT 'requisiciones.delete', 'requisiciones', 'Eliminar requisición completa (cascada hojas e items)'
WHERE NOT EXISTS (
    SELECT 1 FROM `permissions` WHERE `permission_codigo` = 'requisiciones.delete'
);

INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.`role_id`, p.`permission_id`
FROM `roles` r
JOIN `permissions` p ON p.`permission_codigo` = 'requisiciones.delete'
WHERE r.`role_codigo` IN ('admin', 'desarrollador')
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` rp
      WHERE rp.`role_id` = r.`role_id` AND rp.`permission_id` = p.`permission_id`
  );
