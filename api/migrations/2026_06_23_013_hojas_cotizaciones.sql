-- ============================================================
-- Migration 013: Tabla hojas_cotizaciones
-- Fecha: 2026-06-23
-- Requerimiento ITF-3: almacenar referencias a PDFs de
-- cotizaciones vinculados a cada hoja de requisición.
-- ============================================================

CREATE TABLE IF NOT EXISTS `hojas_cotizaciones` (
    `cotizacion_id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `cotizacion_hoja_id`    INT UNSIGNED    NOT NULL
        COMMENT 'FK → hojasrequisicion.hojaRequisicion_id',
    `cotizacion_nombre`     VARCHAR(255)    NOT NULL
        COMMENT 'Nombre descriptivo de la cotización',
    `cotizacion_archivo`    VARCHAR(500)    NOT NULL
        COMMENT 'Ruta relativa dentro de uploads/',
    `cotizacion_size`       INT UNSIGNED    DEFAULT NULL
        COMMENT 'Tamaño del archivo en bytes',
    `cotizacion_fechaSubida` DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `cotizacion_userSubio`  INT UNSIGNED    DEFAULT NULL
        COMMENT 'FK → usuarios.user_id',
    `cotizacion_userNombre` VARCHAR(150)    DEFAULT NULL,
    PRIMARY KEY (`cotizacion_id`),
    INDEX `ix_cotizacion_hoja` (`cotizacion_hoja_id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Cotizaciones PDF adjuntas a hojas de requisición';

