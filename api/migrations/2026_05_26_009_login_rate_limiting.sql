-- ============================================================
-- Migración 009 — Rate limiting de login
-- Fecha: 2026-05-26
-- ============================================================
-- Tabla para registrar intentos de login fallidos por IP.
-- El campo ip es varchar(45) para soportar IPv6.
-- La tabla se limpia automáticamente con el procedimiento
-- de purga (o un evento programado en producción).
-- ============================================================

CREATE TABLE IF NOT EXISTS `login_attempts` (
    `attempt_id`  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `attempt_ip`  VARCHAR(45)  NOT NULL,
    `attempt_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`attempt_id`),
    INDEX `idx_ip_at` (`attempt_ip`, `attempt_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Evento opcional: purgar registros > 24h (requiere SUPER o EVENT)
-- CREATE EVENT IF NOT EXISTS `purge_login_attempts`
--   ON SCHEDULE EVERY 1 HOUR
--   DO DELETE FROM `login_attempts` WHERE `attempt_at` < NOW() - INTERVAL 24 HOUR;
