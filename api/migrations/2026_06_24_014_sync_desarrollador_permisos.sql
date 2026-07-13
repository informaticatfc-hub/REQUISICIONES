-- ============================================================
-- Migration 014: Sincronizar permisos del rol desarrollador
-- Fecha: 2026-06-24
-- La migración 006 asignó todos los permisos existentes al rol
-- desarrollador, pero los permisos añadidos en migraciones
-- posteriores (007-013) no se asignaron. Esto producía 403 en
-- presiones_detalles (presiones.authorize), botones ocultos en
-- proveedores (proveedores.manage) y hojas (requisiciones.edit).
-- ============================================================

INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.role_id, p.permission_id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.role_codigo = 'desarrollador';
