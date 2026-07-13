<?php
include_once 'conexion.php';
include_once 'auth.php';

$objeto   = new Conexion();
$conexion = $objeto->Conectar();

// case 2 (upload) llega como multipart/form-data; el resto como JSON o GET.
// Leemos accion desde $_REQUEST para cubrir ambos casos.
$accionRaw = (int)($_REQUEST['accion'] ?? 0);

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

// Para JSON body (cases 1,3) usamos api_get_request_data(); para multipart (case 2) $_POST.
$jsonBody = ($accionRaw !== 2) ? api_get_request_data() : [];

$hojaId = (int)($_REQUEST['hoja_id'] ?? $jsonBody['hoja_id'] ?? 0);
$cotId  = (int)($jsonBody['cotizacion_id'] ?? $_GET['cotizacion_id'] ?? 0);

define('COT_UPLOADS_BASE', __DIR__ . '/../uploads/cotizaciones/');
define('COT_MAX_SIZE', 8 * 1024 * 1024); // 8 MB

$data = [];

switch ($accionRaw) {

    case 1: // Listar cotizaciones de una hoja
        if ($hojaId <= 0) { $data = []; break; }
        $st = $conexion->prepare(
            "SELECT `cotizacion_id`, `cotizacion_hoja_id`, `cotizacion_nombre`,
                    `cotizacion_size`, `cotizacion_fechaSubida`, `cotizacion_userNombre`
             FROM `hojas_cotizaciones`
             WHERE `cotizacion_hoja_id` = ?
             ORDER BY `cotizacion_fechaSubida` DESC"
        );
        $st->execute([$hojaId]);
        $data = $st->fetchAll(PDO::FETCH_ASSOC);
        break;

    case 2: // Subir PDF (multipart/form-data)
        if ($hojaId <= 0) {
            http_response_code(422);
            $data = ['error' => 'hoja_id requerido'];
            break;
        }
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
        // Validar tipo MIME real
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if ($mime !== 'application/pdf') {
            http_response_code(422);
            $data = ['error' => 'Solo se aceptan archivos PDF'];
            break;
        }
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
        $uniqueName = $safeBase . '_' . time() . '.pdf';
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

    case 4: // Servir PDF inline para visualizar e imprimir (GET con cotizacion_id)
        if ($cotId <= 0) { http_response_code(422); exit; }
        $stGet = $conexion->prepare(
            "SELECT `cotizacion_archivo`, `cotizacion_nombre`
             FROM `hojas_cotizaciones`
             WHERE `cotizacion_id` = ? LIMIT 1"
        );
        $stGet->execute([$cotId]);
        $row = $stGet->fetch(PDO::FETCH_ASSOC);
        if (!$row) { http_response_code(404); exit; }
        $fullPath = __DIR__ . '/../uploads/' . $row['cotizacion_archivo'];
        if (!file_exists($fullPath)) { http_response_code(404); exit; }

        $safeNombre = preg_replace('/[^a-zA-Z0-9_\-\. ]/', '_', $row['cotizacion_nombre']);
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $safeNombre . '.pdf"');
        header('Content-Length: ' . filesize($fullPath));
        header('Cache-Control: private, max-age=3600');
        readfile($fullPath);
        exit;
}

print json_encode($data, JSON_UNESCAPED_UNICODE);
$conexion = null;
