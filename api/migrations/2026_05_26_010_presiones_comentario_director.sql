-- ============================================================
-- Migración 010 — Comentario del Director en Presiones
-- Fecha: 2026-05-26
-- ============================================================
-- Agrega campo opcional de comentario/observación del director
-- en la tabla presiones, para comunicar razones de rechazo
-- o aprobación parcial sin salir del sistema.
-- ============================================================

ALTER TABLE `presiones`
    ADD COLUMN IF NOT EXISTS `presiones_comentario_director` TEXT NULL DEFAULT NULL
        COMMENT 'Comentario del director al autorizar o rechazar la presion'
    AFTER `presiones_estatus`;
