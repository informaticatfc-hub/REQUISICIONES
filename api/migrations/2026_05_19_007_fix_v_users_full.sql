-- ============================================================
-- Migración 2026-05-19-007 :: Corregir vista v_users_full
-- ============================================================
-- Problema: la vista referenciaba u.Email (columna legacy) que
-- puede no existir en todos los entornos, haciendo que
-- crud_admin.php accion=1 devuelva 500.
-- Solución: recrear la vista usando solo user_email (los datos
-- ya fueron migrados por 002/003) y sin depender del
-- campo legacy Email.
-- ============================================================

CREATE OR REPLACE VIEW `v_users_full` AS
SELECT
    u.user_id,
    u.user_nameUser,
    u.user_name,
    u.user_email                                                          AS email,
    COALESCE(u.user_estatus, 'ACTIVO')                                    AS user_estatus,
    u.user_lastLogin,
    u.user_directionAcess,
    r.role_codigo,
    r.role_nombre,
    r.role_nivel,
    (SELECT COUNT(*) FROM role_permissions rp WHERE rp.role_id = r.role_id) AS permisos_count
FROM `users` u
LEFT JOIN `roles` r ON r.role_id = u.user_role_id;

-- Verificar resultado
SHOW FULL TABLES WHERE TABLE_TYPE = 'VIEW' AND Tables_in_u701868959_TFC = 'v_users_full';
