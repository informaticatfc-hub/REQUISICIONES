-- ============================================================
-- Migracion 2026-05-18-006 :: Rol desarrollador
-- Crea el rol 'desarrollador' con acceso total y asigna
-- a mauro.ramos (user_id = 41)
-- ============================================================
-- INSTRUCCIONES: pegar todo en phpMyAdmin -> ejecutar UNA vez
-- ============================================================

-- 1) Crear el rol desarrollador (idempotente)
INSERT INTO `roles`
    (`role_codigo`, `role_nombre`, `role_descripcion`, `role_nivel`, `role_estatus`)
VALUES
    ('desarrollador', 'Desarrollador', 'Acceso total al sistema. Solo uso TI/desarrollo.', 100, 'ACTIVO')
ON DUPLICATE KEY UPDATE
    `role_nombre`      = VALUES(`role_nombre`),
    `role_descripcion` = VALUES(`role_descripcion`),
    `role_nivel`       = VALUES(`role_nivel`),
    `role_estatus`     = 'ACTIVO';

-- 2) Asignar TODOS los permisos al rol desarrollador (idempotente)
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`)
SELECT r.role_id, p.permission_id
FROM `roles` r
CROSS JOIN `permissions` p
WHERE r.role_codigo = 'desarrollador';

-- 3) Asignar rol desarrollador al usuario mauro.ramos (user_id = 41)
UPDATE `users`
SET `user_role_id` = (
    SELECT `role_id` FROM `roles` WHERE `role_codigo` = 'desarrollador' LIMIT 1
),
`user_estatus` = 'ACTIVO'
WHERE `user_id` = 41;

-- 4) Verificar resultado
SELECT
    u.user_id,
    u.user_name,
    r.role_codigo,
    r.role_nombre,
    r.role_nivel,
    u.user_estatus,
    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.role_id) AS total_permisos
FROM `users` u
INNER JOIN `roles` r ON r.role_id = u.user_role_id
WHERE u.user_id = 41;
