<?php
/**
 * api/bitacora.php — Helpers de trazabilidad de negocio
 * -------------------------------------------------------
 * Complementa a audit_log (que cubre auth y acciones generales)
 * con funciones para registrar eventos estructurados de negocio:
 *
 *   tf_hoja_estatus_log()  — historial de estatus por hoja
 *   tf_bitacora_campo()    — cambio de un campo específico
 *
 * REQUISITO: migración 2026_05_18_005 aplicada en la BD.
 * REQUIERE:  api/rbac.php (ya incluido vía auth.php).
 * -------------------------------------------------------
 */

/**
 * Registra un cambio de estatus de una hoja de requisición.
 *
 * @param PDO    $pdo         Conexión activa
 * @param int    $hojaId      hojaRequisicion_id
 * @param string|null $antes  Estatus previo (null en la creación)
 * @param string $nuevo       Estatus nuevo
 * @param string|null $comentario  Texto libre del responsable
 * @param array  $user        Array del usuario actual (tf_current_user)
 */
function tf_hoja_estatus_log(
    PDO    $pdo,
    int    $hojaId,
    ?string $antes,
    string  $nuevo,
    ?string $comentario = null,
    array   $user       = []
): void {
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO `hoja_estatus_log`
                (log_hojaId, log_estatusAntes, log_estatusNuevo,
                 log_comentario, log_userId, log_userName, log_ip)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $hojaId,
            $antes,
            $nuevo,
            $comentario,
            $user['user_id'] ?? ($_SESSION['UsuarioId'] ?? null),
            $user['user_name'] ?? ($_SESSION['Usuario']   ?? null),
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Exception $e) {
        // No lanzamos — el log nunca debe romper el flujo principal
        error_log('tf_hoja_estatus_log fail: ' . $e->getMessage());
    }
}

/**
 * Registra la creación de una hoja (estatus inicial NUEVO).
 * Atajo semántico sobre tf_hoja_estatus_log.
 *
 * @param PDO   $pdo
 * @param int   $hojaId
 * @param array $user   Array del usuario actual (tf_current_user)
 */
function tf_hoja_creada_log(PDO $pdo, int $hojaId, array $user = []): void
{
    tf_hoja_estatus_log($pdo, $hojaId, null, 'NUEVO', 'Hoja creada', $user);
}

/** 
 * Guarda el autor de la hoja recién insertada en las columnas
 * hojaRequisicion_userCreado / hojaRequisicion_userCreadoNombre.
 *
 * Llámalo justo después del INSERT en crud_nueva_hoja.php.
 *
 * @param PDO   $pdo
 * @param int   $hojaId
 * @param array $user   Array del usuario actual (tf_current_user)
 */
function tf_hoja_set_creator(PDO $pdo, int $hojaId, array $user = []): void
{
    try {
        $stmt = $pdo->prepare(
            'UPDATE `hojasrequisicion`
             SET `hojaRequisicion_userCreado`       = ?,
                 `hojaRequisicion_userCreadoNombre` = ?
             WHERE `hojaRequisicion_id` = ?'
        );
        $stmt->execute([
            $user['user_id']   ?? ($_SESSION['UsuarioId'] ?? null),
            $user['user_name'] ?? ($_SESSION['Usuario']   ?? null),
            $hojaId,
        ]);
    } catch (Exception $e) {
        error_log('tf_hoja_set_creator fail: ' . $e->getMessage());
    }
}

/**
 * Guarda al validador/autorizador de la hoja.
 * Llámalo cuando el estatus cambia a AUTORIZADA, RECHAZADA
 * o PENDIENTE desde el módulo de Dirección.
 *
 * @param PDO   $pdo
 * @param int   $hojaId
 * @param array $user   Array del usuario actual (tf_current_user)
 */
function tf_hoja_set_validador(PDO $pdo, int $hojaId, array $user = []): void
{
    try {
        $stmt = $pdo->prepare(
            'UPDATE `hojasrequisicion`
             SET `hojaRequisicion_userValidado`       = ?,
                 `hojaRequisicion_userValidadoNombre` = ?
             WHERE `hojaRequisicion_id` = ?'
        );
        $stmt->execute([
            $user['user_id']   ?? ($_SESSION['UsuarioId'] ?? null),
            $user['user_name'] ?? ($_SESSION['Usuario']   ?? null),
            $hojaId,
        ]);
    } catch (Exception $e) {
        error_log('tf_hoja_set_validador fail: ' . $e->getMessage());
    }
}
