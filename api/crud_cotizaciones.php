<?php
include_once 'conexion.php';
include_once 'auth.php';

$objeto   = new Conexion();
$conexion = $objeto->Conectar();

// case 2 (upload) llega como multipart/form-data ($_POST); el resto como JSON
// (axios manda body JSON, que NO llena $_REQUEST) o GET (?accion=4).
// api_get_request_data() devuelve [] con multipart, asi que leer ambos es seguro.
$jsonBody  = api_get_request_data();
$accionRaw = (int)($_REQUEST['accion'] ?? $jsonBody['accion'] ?? 0);

// CSRF lo valida tf_csrf_validate() usando primero el header X-CSRF-Token,
// que axios adjunta automáticamente en todos los requests.
$writeActions = [2, 3];
if (in_array($accionRaw, $writeActions, true)) {
    api_require_csrf();
    tf_require_permission($conexion, 'requisiciones.create');
} else {
    tf_require_permission($conexion, 'requisiciones.view');
}

$currentUser = api_get_current_user($conexion);

$hojaId = (int)($_REQUEST['hoja_id'] ?? $jsonBody['hoja_id'] ?? 0);
$cotId  = (int)($jsonBody['cotizacion_id'] ?? $_GET['cotizacion_id'] ?? 0);

define('COT_UPLOADS_BASE', __DIR__ . '/../uploads/cotizaciones/');
define('COT_MAX_SIZE', 8 * 1024 * 1024); // 8 MB

// Tipos aceptados: PDF e imagenes (OBS-3). MIME real => extension segura.
$COT_MIME_EXT = [
    'application/pdf' => 'pdf',
    'image/jpeg'      => 'jpg',
    'image/png'       => 'png',
];

// --- Alcance de obra: toda accion opera sobre una hoja concreta ------------
function cotizacion_obra_por_hoja(PDO $conexion, $hojaId)
{
    $st = $conexion->prepare(
        "SELECT r.`requisicion_Obra`
         FROM `hojasrequisicion` h
         INNER JOIN `requisiciones` r ON r.`requisicion_id` = h.`hojaRequisicion_idReq`
         WHERE h.`hojaRequisicion_id` = ? LIMIT 1"
    );
    $st->execute([(int)$hojaId]);
    $obraId = $st->fetchColumn();
    return $obraId === false ? null : (int)$obraId;
}

function cotizacion_validar_hoja(PDO $conexion, $hojaId, $currentUser)
{
    $obraId = cotizacion_obra_por_hoja($conexion, $hojaId);
    if ($obraId === null) {
        tf_abort(404, 'Hoja no encontrada');
    }
    return tf_require_obra_access($conexion, $obraId, $currentUser);
}

// Pagina de error legible para el caso 4 (se abre con target="_blank",
// asi que un exit en blanco se veia como un 404 nativo del navegador).
function cotizacion_pagina_error($code, $mensaje)
{
    http_response_code($code);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Cotizacion no disponible</title>'
        . '<div style="font-family:system-ui,sans-serif;padding:48px;text-align:center;color:#334155">'
        . '<h1 style="margin:0 0 12px;font-size:1.5rem">No se pudo abrir la cotizacion</h1>'
        . '<p style="margin:0;color:#64748b">' . htmlspecialchars($mensaje) . '</p>'
        . '</div>';
    exit;
}

function cotizacion_validar_cotizacion(PDO $conexion, $cotId, $currentUser)
{
    $st = $conexion->prepare(
        "SELECT `cotizacion_hoja_id` FROM `hojas_cotizaciones` WHERE `cotizacion_id` = ? LIMIT 1"
    );
    $st->execute([(int)$cotId]);
    $hojaId = $st->fetchColumn();
    if ($hojaId === false) {
        tf_abort(404, 'Cotizacion no encontrada');
    }
    cotizacion_validar_hoja($conexion, (int)$hojaId, $currentUser);
}

$data = [];

switch ($accionRaw) {

    case 1: // Listar cotizaciones de una hoja
        if ($hojaId <= 0) { $data = []; break; }
        cotizacion_validar_hoja($conexion, $hojaId, $currentUser);
        $st = $conexion->prepare(
            "SELECT `cotizacion_id`, `cotizacion_hoja_id`, `cotizacion_nombre`, `cotizacion_archivo`,
                    `cotizacion_size`, `cotizacion_fechaSubida`, `cotizacion_userNombre`
             FROM `hojas_cotizaciones`
             WHERE `cotizacion_hoja_id` = ?
             ORDER BY `cotizacion_fechaSubida` DESC"
        );
        $st->execute([$hojaId]);
        $data = $st->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 2: // Subir cotizacion PDF o imagen (multipart/form-data)
        if ($hojaId <= 0) {
            http_response_code(422);
            $data = ['error' => 'hoja_id requerido'];
            break;
        }
        cotizacion_validar_hoja($conexion, $hojaId, $currentUser);
        $fileErr = $_FILES['cotizacion']['error'] ?? -1;
        if (!isset($_FILES['cotizacion']) || $fileErr !== UPLOAD_ERR_OK) {
            http_response_code(422);
            $data = ['error' => 'Archivo no recibido (código ' . $fileErr . ')'];
            break;
        }
        $file = $_FILES['cotizacion'];
        if ((int)$file['size'] > COT_MAX_SIZE) {
            http_response_code(413);
            $data = ['error' => 'El archivo supera el límite de 8 MB'];
            break;
        }
        // Validar tipo MIME real (no la extension del nombre)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!isset($COT_MIME_EXT[$mime])) {
            http_response_code(422);
            $data = ['error' => 'Solo se aceptan archivos PDF, JPG o PNG'];
            break;
        }
        $ext = $COT_MIME_EXT[$mime];
        // Crear directorio si no existe
        $dir = COT_UPLOADS_BASE . $hojaId . '/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            http_response_code(500);
            $data = ['error' => 'No se pudo crear el directorio de almacenamiento'];
            break;
        }
        // Nombre de archivo seguro + timestamp para evitar colisiones
        $origBase   = pathinfo($file['name'], PATHINFO_FILENAME);
        $safeBase   = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $origBase);
        $safeBase   = substr($safeBase, 0, 100);
        $uniqueName = $safeBase . '_' . time() . '.' . $ext;
        $destPath   = $dir . $uniqueName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            http_response_code(500);
            $data = ['error' => 'No se pudo mover el archivo al destino'];
            break;
        }
        $nombreDesc = trim((string)($_POST['nombre'] ?? ''));
        if ($nombreDesc === '') {
            $nombreDesc = pathinfo($file['name'], PATHINFO_FILENAME);
        }
        $nombreDesc = substr($nombreDesc, 0, 255);

        $st = $conexion->prepare(
            "INSERT INTO `hojas_cotizaciones`
             (`cotizacion_hoja_id`, `cotizacion_nombre`, `cotizacion_archivo`,
              `cotizacion_size`, `cotizacion_userSubio`, `cotizacion_userNombre`)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $st->execute([
            $hojaId,
            $nombreDesc,
            'cotizaciones/' . $hojaId . '/' . $uniqueName,
            (int)$file['size'],
            (int)($currentUser['user_id'] ?? 0),
            $currentUser['user_name'] ?? null,
        ]);
        $newId = (int)$conexion->lastInsertId();
        $data  = ['ok' => true, 'cotizacion_id' => $newId, 'nombre' => $nombreDesc];
        tf_audit_log($conexion, 'cotizaciones.upload', 'hojas_cotizaciones', $newId, [
            'hoja_id' => $hojaId,
            'nombre'  => $nombreDesc,
        ]);
        break;

    case 3: // Eliminar cotizacion (JSON POST)
        if ($cotId <= 0) {
            http_response_code(422);
            $data = ['error' => 'cotizacion_id requerido'];
            break;
        }
        cotizacion_validar_cotizacion($conexion, $cotId, $currentUser);
        $stGet = $conexion->prepare(
            "SELECT `cotizacion_archivo`
             FROM `hojas_cotizaciones`
             WHERE `cotizacion_id` = ? LIMIT 1"
        );
        $stGet->execute([$cotId]);
        $row = $stGet->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            $data = ['error' => 'Cotizacion no encontrada'];
            break;
        }
        $fullPath = __DIR__ . '/../uploads/' . $row['cotizacion_archivo'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        $conexion->prepare("DELETE FROM `hojas_cotizaciones` WHERE `cotizacion_id` = ?")
                 ->execute([$cotId]);
        $data = ['ok' => true];
        tf_audit_log($conexion, 'cotizaciones.delete', 'hojas_cotizaciones', $cotId, null);
        break;

    case 4: // Servir archivo inline para visualizar e imprimir (GET con cotizacion_id)
        if ($cotId <= 0) { cotizacion_pagina_error(422, 'Identificador de cotizacion invalido.'); }
        cotizacion_validar_cotizacion($conexion, $cotId, $currentUser);
        $stGet = $conexion->prepare(
            "SELECT `cotizacion_archivo`, `cotizacion_nombre`
             FROM `hojas_cotizaciones`
             WHERE `cotizacion_id` = ? LIMIT 1"
        );
        $stGet->execute([$cotId]);
        $row = $stGet->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            cotizacion_pagina_error(404, 'Esta cotizacion ya no existe (pudo haber sido eliminada).');
        }
        $fullPath = __DIR__ . '/../uploads/' . $row['cotizacion_archivo'];
        if (!file_exists($fullPath)) {
            cotizacion_pagina_error(404, 'El archivo de esta cotizacion no se encuentra en el servidor. Contacta a soporte.');
        }

        $extArchivo = strtolower(pathinfo($row['cotizacion_archivo'], PATHINFO_EXTENSION));
        $contentTypes = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png'];
        $contentType = $contentTypes[$extArchivo] ?? 'application/octet-stream';

        $safeNombre = preg_replace('/[^a-zA-Z0-9_\-\. ]/', '_', $row['cotizacion_nombre']);
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: inline; filename="' . $safeNombre . '.' . $extArchivo . '"');
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: private, max-age=3600');
        readfile($fullPath);
        exit;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = null;
