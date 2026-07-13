# Actualizaciones Pendientes — 23 de junio de 2026

**Sistema:** The Fuentes Workspace — Requisiciones v4.1  
**Rama activa:** `fase08-vistas-std`  
**Elaborado por:** Revisión técnica + observaciones Informática The Fuentes

---

## Estado general

El sistema está funcional (v4.1). Los módulos críticos de autenticación, RBAC, requisiciones, presiones y exportaciones están operativos. Lo que sigue son correcciones urgentes, flujos incompletos, nuevas funcionalidades solicitadas y deuda técnica.

---

## URGENTE — Resolver antes de cualquier otra tarea

Estos ítems rompen el sistema o afectan la integridad de los datos en producción.

| # | Problema | Archivo | Acción |
|---|---|---|---|
| U-1 | **Conflictos git sin resolver** — los marcadores `<<<<<<<` en dos archivos PHP rompen el parser y producen errores 500 en producción | `pages/all_presiones.php` (líneas 231, 524, 744) `pages/reportes_kpi.php` (línea 215) | Mantener bloque `Stashed changes` (Alpine.js moderno); descartar bloque `Updated upstream` (Vue 2 legacy) |
| U-2 | **Watermark tapa el contenido del PDF** — `addImage(watermark)` se dibuja encima de la tabla generada por autoTable | `assets/js/pdfGenerate.js` líneas 4 y 60 | Eliminar línea 60 (`doc.addImage(watermark...)`) y línea 4 (variable `watermark`) |
| U-3 | **jsPDF duplicado** — dos versiones en el mismo contexto generan conflictos silenciosos en `window.jsPDF` | `pages/reportes_kpi.php` línea 701 | Eliminar la línea del CDN externo v1.5.3; conservar la librería local UMD |

---

## P1 — Crítico (antes de ir a producción)

### P1-1 · Botón "Reactivar rol" en panel Admin

**Problema:** La UI de `pages/admin.php` tiene botón para desactivar roles pero no para reactivarlos. Un rol desactivado por error solo se recupera con acceso directo a la BD.

**Solución:**
- En la tabla de roles del panel admin: mostrar botón "Reactivar" cuando `role_estatus = 'INACTIVO'`
- Agregar `accion: X` en `api/crud_admin.php` que cambie el estatus del rol
- Registrar en `audit_log` con `action = 'role.reactivar'`

**Archivos:** `pages/admin.php`, `api/crud_admin.php`  
**Esfuerzo estimado:** 2 h

---

### P1-2 · Eliminar `main.css` legacy de `all_presiones.php`

**Problema:** `pages/all_presiones.php` incluye `assets/css/main.css` (v3 legacy) junto con `v4.css`. Genera conflictos en modo oscuro y sobreescribe estilos del layout activo.

**Solución:** Eliminar la línea de inclusión de `main.css` en esa página. Verificar visualmente el layout después.

**Archivo:** `pages/all_presiones.php` línea 44  
**Esfuerzo estimado:** 15 min

---

### P1-3 · Validar alcance de obra en endpoints de escritura

**Problema:** Los endpoints de escritura validan permisos RBAC pero no siempre verifican que la obra del recurso pertenezca a las obras asignadas al usuario. Un residente podría modificar ítems de obras no asignadas si conoce los IDs.

**Solución:** En cada endpoint de modificación, después de verificar permisos RBAC:

```php
$user_obras = tf_get_user_obras($pdo, $user['id']);
if (!in_array($obra_del_recurso, $user_obras) && $user['nivel'] < 60) {
    http_response_code(403);
    die(json_encode(['error' => 'Acceso denegado a esta obra']));
}
```

**Archivos afectados:** `api/crud_Requisiciones.php`, `api/crud_hojas_requisicion.php`, `api/crud_items_requisiciones.php`, `api/crud_nueva_hoja.php`, `api/crud_Presiones.php`  
**Esfuerzo estimado:** 4 h

---

## P2 — Alto (primer sprint)

### P2-1 · Modal de pago completo para Finanzas

**Problema:** El botón "Marcar como pagada" en `pages/presiones.php` cambia el estado directamente sin capturar datos bancarios. La migración `011_pago_hoja_requisicion.sql` ya agregó los campos `folio_pago`, `banco_id` y `fecha_pago` a la tabla `presiones`, pero la UI no los utiliza.

**Solución:**
1. Reemplazar la acción directa por un modal con:
   - `Folio de pago` — referencia bancaria (requerido)
   - `Banco` — dropdown cargado desde `crud_bancos.php`
   - `Fecha de pago` — date picker, default hoy
   - `Notas` — textarea opcional
2. El endpoint de `crud_Presiones.php` debe recibir y guardar estos campos
3. Mostrar los datos en `pages/presiones_detalles.php`
4. Registrar en `audit_log` con `action = 'presion.pagar'`

**Archivos:** `pages/presiones.php`, `assets/js/presiones.js`, `api/crud_Presiones.php`, `pages/presiones_detalles.php`  
**Esfuerzo estimado:** 5 h

---

### P2-2 · UI para comentario del director en presiones

**Problema:** La columna `comentario_director` existe en la tabla `presiones` desde la migración `010`, pero ni `pages/presiones_detalles.php` lo muestra ni el modal de autorizar/rechazar permite capturarlo.

**Solución:**
1. En `presiones_detalles.php`: bloque de cita visual si el comentario existe
2. En el modal de autorizar/rechazar: `<textarea>` para capturar el comentario
3. En `all_presiones.php`: ícono de "tiene comentario" con tooltip en la fila

**Archivos:** `pages/presiones_detalles.php`, `assets/js/presiones_detalles.js`, `pages/all_presiones.php`  
**Esfuerzo estimado:** 3 h

---

### P2-3 · Filtros y paginación en log de auditoría

**Problema:** La sección de auditoría en `pages/admin.php` carga todos los registros sin filtros. Inoperante en producción con muchos eventos.

**Solución:**
1. Barra de filtros: rango de fechas, usuario, tipo de acción, búsqueda libre
2. Paginación server-side (20 registros por página)
3. Botón "Exportar CSV" de los resultados filtrados

**Archivos:** `pages/admin.php`, `api/crud_admin.php`  
**Esfuerzo estimado:** 8 h

---

### P2-4 · Filtro de período en dashboard KPI

**Problema:** El dashboard de `pages/reportes_kpi.php` muestra totales históricos acumulados. No hay forma de filtrar por semana, mes, trimestre o año.

**Solución:**
1. Selector de período en la topbar del dashboard
2. Pasar `fecha_inicio` y `fecha_fin` como parámetros a `api/crud_direccion.php`
3. Añadir `WHERE fecha BETWEEN :inicio AND :fin` en las consultas SQL
4. Reactivar las gráficas al cambiar el período (Alpine reactivo)

**Archivos:** `pages/reportes_kpi.php`, `assets/js/reportes_kpi.js`, `api/crud_direccion.php`  
**Esfuerzo estimado:** 6 h

---

### P2-5 · Dropdown de obra única en KPI (UX)

**Problema:** Para ver el drilldown de una sola obra en el KPI, el director debe dejar exactamente 1 checkbox marcado. No hay control explícito de "ver solo esta obra".

**Solución:** Agregar un `<select>` de selección rápida encima del selector de checkboxes. Al elegir una obra, limpia el filtro a esa sola obra. Al elegir "Todas", restaura el multi-selección.

**Archivos:** `pages/reportes_kpi.php`, `assets/js/reportes_kpi.js`  
**Esfuerzo estimado:** 2 h

---

### P2-6 · Restablecer contraseña desde panel Admin

**Problema:** No hay forma de restablecer la contraseña de un usuario desde la UI. Requiere acceso directo a la BD.

**Solución:**
1. Botón "Resetear contraseña" en el modal de edición de usuario
2. Genera contraseña temporal de alta entropía (12 caracteres)
3. La muestra UNA VEZ en pantalla con opción de copiar
4. Guarda el hash en BD y activa `requiere_cambio_password = 1`
5. En `login.php`: si el flag está activo, redirige a pantalla de cambio obligatorio

**Archivos:** `pages/admin.php`, `api/crud_admin.php`, `pages/login.php`  
**Esfuerzo estimado:** 5 h

---

## Observaciones Informática The Fuentes

Solicitudes específicas del área de sistemas para incluir en el backlog activo.

---

### ITF-1 · Ajustar folios — Generación automática de folios consecutivos

> *[Informática The Fuentes] Implementar la generación automática de folios consecutivos independientes para cada categoría de requisición. Eliminar la necesidad de ingreso manual.*

**Contexto:** Actualmente los folios de requisición se ingresan de forma manual, lo que genera duplicados, saltos en la numeración y errores de captura.

**Solución propuesta:**
1. Agregar columna `requisicion_folio` en la tabla correspondiente (o en `hojasrequisicion` según la categoría):
   ```sql
   ALTER TABLE requisiciones
       ADD COLUMN requisicion_folio VARCHAR(20) GENERATED ALWAYS AS (
           CONCAT(requisicion_categoria, '-', LPAD(requisicion_id, 6, '0'))
       ) STORED AFTER requisicion_id;
   ```
   O bien, manejo en PHP al crear la requisición:
   ```php
   // Obtener el siguiente consecutivo por categoría
   $stmt = $pdo->prepare("SELECT MAX(requisicion_folio_num) FROM requisiciones WHERE requisicion_categoria = ?");
   $stmt->execute([$categoria]);
   $siguiente = ($stmt->fetchColumn() ?? 0) + 1;
   $folio = strtoupper($categoria) . '-' . str_pad($siguiente, 6, '0', STR_PAD_LEFT);
   ```
2. El campo folio se muestra como solo lectura en la UI
3. El folio se incluye en el PDF generado y en los listados

**Archivos afectados:** `api/crud_Requisiciones.php` (acción crear), `pages/nueva_requisicion.php`, `assets/js/nueva_requisicion.js`, `assets/js/pdfGenerate.js`  
**BD:** Nueva columna `requisicion_folio_num` (INT) + `requisicion_folio` (VARCHAR) o campo calculado  
**Esfuerzo estimado:** 4 h + migración SQL

---

### ITF-2 · Optimizar proveedores — Fecha de creación y validación de duplicados

> *[Informática The Fuentes] Agregar un campo de fecha de creación a los registros de proveedores. Implementar una validación de duplicados basada en el número de cuenta o clave interbancaria.*

**Contexto:** El catálogo de proveedores no tiene fecha de alta, lo que dificulta auditorías. Además, no existe validación que impida registrar el mismo proveedor dos veces con el mismo número de cuenta o CLABE.

**Solución propuesta:**

*Parte A — Campo de fecha de creación:*
```sql
ALTER TABLE provedores
    ADD COLUMN proveedor_fechaCreacion DATETIME DEFAULT CURRENT_TIMESTAMP
        COMMENT 'Fecha y hora de alta del proveedor';
```
Mostrar en la tabla de `pages/proveedores.php` como columna "Fecha de alta".

*Parte B — Validación de duplicados:*
1. En `api/crud_addProveedor.php` y `api/crud_proveedor.php` (acción crear/editar), antes de insertar:
   ```php
   $stmt = $pdo->prepare(
       "SELECT proveedor_id, proveedor_nombre
        FROM provedores
        WHERE (proveedor_cuenta = ? OR proveedor_clabe = ?)
          AND proveedor_id != ?
          AND proveedor_estatus = 'ACTIVO'
        LIMIT 1"
   );
   $stmt->execute([$cuenta, $clabe, $idActual ?? 0]);
   $duplicado = $stmt->fetch();
   if ($duplicado) {
       // Retornar advertencia al frontend con nombre del duplicado
   }
   ```
2. En la UI (`pages/agregar_proveedor.php`): mostrar alerta visual con nombre del proveedor duplicado encontrado antes de confirmar el guardado

**Archivos afectados:** `api/crud_addProveedor.php`, `api/crud_proveedor.php`, `pages/agregar_proveedor.php`, `assets/js/agregar_proveedor.js`, `pages/proveedores.php`  
**BD:** `ALTER TABLE provedores ADD COLUMN proveedor_fechaCreacion` + índice `UNIQUE KEY` sobre `(proveedor_cuenta, proveedor_clabe)` (condicional, con revisión de datos actuales primero)  
**Esfuerzo estimado:** 3 h + migración SQL

---

### ITF-3 · Adjuntar cotizaciones — Carga de PDF por hoja de requisición

> *[Informática The Fuentes] Habilitar la carga de archivos PDF con cotizaciones vinculadas directamente a cada hoja de requisición. Asegurar que los documentos sean accesibles al imprimir.*

**Contexto:** Las cotizaciones de proveedor actualmente se comparten por correo o WhatsApp fuera del sistema, sin trazabilidad. Se solicita adjuntarlas directamente a cada hoja de requisición.

**Solución propuesta:**

*Parte A — Almacenamiento:*
```
uploads/
└── cotizaciones/
    └── {hojaRequisicion_id}/
        └── {timestamp}_{nombre_original}.pdf
```
Crear directorio `uploads/` protegido con `.htaccess` (solo acceso autenticado).

*Parte B — Migración BD:*
```sql
CREATE TABLE hojas_cotizaciones (
    cotizacion_id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hoja_id             INT UNSIGNED NOT NULL,
    cotizacion_nombre   VARCHAR(255) NOT NULL,
    cotizacion_ruta     VARCHAR(500) NOT NULL,
    cotizacion_mime     VARCHAR(80)  NOT NULL DEFAULT 'application/pdf',
    cotizacion_size     INT UNSIGNED NULL,
    user_id_subida      INT UNSIGNED NOT NULL,
    cotizacion_fecha    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hoja_id)        REFERENCES hojasrequisicion(hojaRequisicion_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id_subida) REFERENCES users(user_id)
);
```

*Parte C — Endpoint de carga:*
```
api/crud_cotizaciones.php
  accion: 1 → listar por hoja_id
  accion: 2 → subir PDF (validar MIME server-side, tamaño ≤ 10 MB)
  accion: 3 → eliminar (solo el usuario que subió o admin)
  accion: 4 → descargar / inline view
```

*Parte D — UI en hojas_requisicion:*
- Sección "Cotizaciones" debajo de la tabla de ítems
- Botón "Adjuntar PDF" (input `type=file accept=".pdf"`)
- Lista de cotizaciones adjuntas con nombre, fecha, botones "Ver" y "Eliminar"
- Validación en frontend: solo `.pdf`, máximo 10 MB

*Parte E — Accesibilidad al imprimir:*
- En `assets/js/pdfGenerate.js`: incluir listado de cotizaciones adjuntas como sección final del PDF generado (nombre del archivo + fecha de carga + URL de acceso o nota "Ver en sistema")
- Opcionalmente: botón "Imprimir con cotizaciones" que abre cada PDF adjunto en pestaña nueva antes de imprimir

**Archivos afectados:** `api/crud_cotizaciones.php` (nuevo), `pages/hojas_requisicion.php`, `assets/js/hojas_requisicion.js`, `assets/js/pdfGenerate.js`  
**BD:** Nueva tabla `hojas_cotizaciones` + directorio `uploads/cotizaciones/`  
**Esfuerzo estimado:** 8–10 h + migración SQL

---

## P3 — Medio (segundo sprint)

| ID | Tarea | Archivos | Esfuerzo |
|---|---|---|---|
| P3-1 | Lógica de Command Palette (Ctrl+K — HTML ya existe, sin lógica) | `assets/js/v4-layout.js`, `includes/layout_top.php` | 10 h |
| P3-2 | Notificaciones badge en topbar (polling 60 s a endpoint ligero) | `api/crud_notifications.php`, `assets/js/v4-layout.js` | 8 h |
| P3-3 | Búsqueda combinada de proveedor (nombre / RFC / CLABE) en formularios | `pages/nueva_requisicion.php`, `pages/hojas_requisicion.php` | 4 h |
| P3-4 | Timeline visual de estados en detalle de requisición | `pages/requisiciones.php` | 4 h |
| P3-5 | Alert de tope presupuestal al ligar requisiciones a presiones | `pages/enlazar_requisiciones.php`, `assets/js/enlazar_requisiciones.js` | 2 h |
| P3-6 | Ocultar botones CRUD para roles sin permiso (generalizar `TF.can()`) | Todas las páginas activas | 4 h |
| P3-7 | Tab "Pendientes de autorización" en all_presiones (filtro `estatus = PENDIENTE`) | `pages/all_presiones.php`, `assets/js/all_presiones.js` | 3 h |
| P3-8 | Columna monto subtotal por hoja en hojas_requisicion | `pages/hojas_requisicion.php`, `api/crud_hojas_requisicion.php` | 3 h |
| P3-9 | Importación CFDI XML del SAT en all_presiones | `pages/all_presiones.php`, `assets/js/all_presiones.js` + migración 012 | 5 h |

---

## P4 — Bajo (calidad y deuda técnica)

| ID | Tarea | Esfuerzo |
|---|---|---|
| P4-1 | Eliminar `assets/lib/vue/` y `assets/js/legacy/` del repo | 2 h |
| P4-2 | Pruebas E2E básicas con Playwright (login, crear requisición, autorizar presión, pagar) | 12 h |
| P4-3 | Activar 2FA TOTP — tabla `two_factor_tokens` ya existe en BD | 8 h |
| P4-4 | PDF server-side con dompdf (reemplazar jsPDF cliente) | 8 h |
| P4-5 | Importar ítems desde Excel en `pages/items_requisicion.php` (SheetJS ya disponible) | 6 h |
| P4-6 | Plantillas de requisición (guardar / reutilizar conjuntos de ítems frecuentes) | 8 h |
| P4-7 | Tablas responsivas en móvil — patrón card-stack con CSS puro | 4 h |
| P4-8 | Migración 013: fechas de estado en `presiones` (habilita KPI ciclo de autorización) | 1 h SQL + 3 h UI |
| P4-9 | Migración 014: `obras_presupuesto` + fechas inicio/fin (habilita KPI % ejecutado) | 1 h SQL + 3 h UI |
| P4-10 | Auditar columna `bancoPago` duplicada en `hojasrequisicion` (FK vs VARCHAR con typo) | 1 h |
| P4-11 | Verificar y limpiar IDs huérfanos en `requisicionesligadas` | 30 min |

---

## Migraciones SQL pendientes

| Archivo | Contenido | Prioridad |
|---|---|:---:|
| `012_cfdi_fields.sql` | Campos CFDI en `hojasrequisicion` (UUID, RFC emisor, fecha, total) | P3-9 |
| `013_presiones_fechas_estado.sql` | `presiones_fechaEnviado` + `presiones_fechaAutorizado` | P4-8 |
| `014_obras_presupuesto.sql` | `obras_presupuesto`, `obras_fecha_inicio`, `obras_fecha_fin` | P4-9 |
| *(nueva)* `015_proveedores_fecha_creacion.sql` | `proveedor_fechaCreacion` en `provedores` | ITF-2 |
| *(nueva)* `016_folio_requisicion.sql` | `requisicion_folio_num` + `requisicion_folio` en `requisiciones` | ITF-1 |
| *(nueva)* `017_hojas_cotizaciones.sql` | Tabla `hojas_cotizaciones` + directorio `uploads/cotizaciones/` | ITF-3 |

---

## Resumen ejecutivo

| Categoría | Ítems urgentes | Ítems P1 | Ítems P2 | Ítems P3 | Ítems P4 |
|---|:---:|:---:|:---:|:---:|:---:|
| Estabilidad / Bugs | 3 | — | — | — | — |
| Seguridad / RBAC | — | 2 | — | — | — |
| Panel Admin | — | 1 | 2 | — | — |
| Módulo Presiones | — | — | 2 | 1 | — |
| Dashboard KPI | — | — | 2 | — | 2 |
| Requisiciones | — | — | — | 2 | 2 |
| Proveedores | — | — | — | 1+ITF-2 | — |
| Cotizaciones | — | — | — | ITF-3 | — |
| Folios automáticos | — | — | — | ITF-1 | — |
| Frontend / UX | — | — | — | 3 | 2 |
| BD / Migraciones | — | — | — | 1 | 4 |
| Testing | — | — | — | — | 1 |
| Deuda técnica | — | — | — | — | 3 |

**Total estimado:**
- Urgentes: ~40 min
- P1: ~6 h
- P2: ~29 h
- Observaciones ITF: ~17 h
- P3: ~43 h
- P4: ~57 h

---

*Ver detalle adicional en `PROPUESTAS_MEJORA_2026.md` (12-jun) y `REVISION_2026_06_22.md` (22-jun).*
