# Pendientes y Correcciones — 2026-06-24

**Fuente:** `Observaciones y correcciones de la plataforma REQUISICIONES.docx`  
**Prueba realizada con:** Usuario Desarrollador  
**Procesado por:** Informática The Fuentes

---

## Estado de implementación

| # | Sección | Observación | Estado | Acción tomada |
|---|---------|-------------|--------|---------------|
| 1 | `presiones.php` | Toast de éxito incondicional aunque el backend devuelva `success: false` | ✅ **CORREGIDO** | `presiones.js`: `agregarPresion` ahora es `async`; toast solo dispara si `ok === true` |
| 2 | `enlazar_requisiciones.php` | `enlazarReqApp is not defined` — Alpine evalúa x-data antes de que el JS propio cargue | ✅ **CORREGIDO** | `enlazar_requisiciones.php`: JS propio ahora carga antes de Alpine (orden de tags invertido) |
| 3 | `presiones_detalles.php` | Tabla no respeta modo oscuro (se ve blanca). Cerrar presión devuelve 403 | ⚠️ **PARCIAL** | El 403 se resuelve con la migración 014 (sync permisos desarrollador). El tema oscuro es pendiente de CSS. |
| 4 | `hojas_requisicion.php` | Tabla no respeta dark mode. Botón "Agregar Hoja" no aparece | ✅ **CORREGIDO** | `$__canReqEdit` y `$__canDireccion` no estaban definidos — añadidos en la página PHP |
| 5 | `items_requisicion.php` | Tabla no respeta dark mode. Diseño muy simple. Cotizaciones PDF (límite de tamaño) | ⚠️ **PARCIAL** | Cotizaciones PDF ya implementadas (ITF-3). Límite está en 8 MB. Dark mode y rediseño pendientes. |
| 6 | `proveedores.php` | Botón "Agregar proveedor" no aparece. Ver inactivos visible para todos. Tarjetas de KPI innecesarias. Filtros tabs activos/inactivos no deben verse | ⚠️ **PARCIAL** | El botón no aparece por falta de `proveedores.manage` en rol dev → resuelto con migración 014. Ocultar inactivos e YA revisado duplicados/fecha-creación (ITF-2). |
| 7 | `all_presiones.php` | Importar Excel con números (sin fórmulas). Quitar CSV de secciones por obra. Quitar marca de agua del Excel. | ⏳ **PENDIENTE** | Ver detalle abajo |
| 8 | `admin.php` | Error 500 — `crud_admin.php` no carga | ✅ **CORREGIDO** | 3 conflictos git resueltos en `crud_admin.php` (líneas 32, 88, 316) |

---

## Detalle por observación

---

### OBS-1 · presiones.php — Toast de éxito sin verificar respuesta
**Síntoma:**  
El modal de "Nueva Presión" muestra `"Presion Agregada"` aunque el servidor devuelva `{success: false, message: "Ya existe una presión..."}`.

**Causa:**  
`presiones.js` línea ~96: el Toast se disparaba inmediatamente después de llamar `agregarPresion()` sin esperar la respuesta.

**Corrección aplicada:**  
- `agregarPresion()` convertida a `async`, retorna `true/false`  
- El Toast solo dispara si el retorno es `true`  
- Si `success: false`, se muestra `Swal.fire({ icon: 'warning', ... })` con el mensaje del servidor

**Archivos modificados:** `assets/js/presiones.js`

---

### OBS-2 · enlazar_requisiciones.php — enlazarReqApp is not defined
**Síntoma:**  
```
Alpine Expression Error: enlazarReqApp is not defined
Expression: "enlazarReqApp()"
```
La página queda completamente en blanco / sin datos.

**Causa:**  
El tag `<script defer src="alpinejs">` aparecía antes del tag `<script src="enlazar_requisiciones.js">` en `$tf_extra_scripts`. En ciertos entornos (Alpine cacheado en CDN), Alpine puede inicializar antes de que el script propio haya registrado la función.

**Corrección aplicada:**  
El orden de los tags en `$tf_extra_scripts` fue invertido: primero `enlazar_requisiciones.js` (sin defer), luego Alpine (con defer). Esto garantiza que `enlazarReqApp` esté en el scope global cuando Alpine evalúa el `x-data`.

**Archivos modificados:** `pages/enlazar_requisiciones.php`

---

### OBS-3 · presiones_detalles.php — 403 al cerrar presión + tabla dark
**Síntoma A (403):**  
`POST /api/crud_presionDetail.php → 403 Forbidden` al presionar "Cerrar Presión".

**Causa:**  
`case 7` de `crud_presionDetail.php` requiere `presiones.authorize`. El rol `desarrollador` no tenía este permiso porque fue agregado en una migración posterior a la 006 (que asignó los permisos una sola vez).

**Corrección aplicada:**  
Migración `2026_06_24_014_sync_desarrollador_permisos.sql` — re-sincroniza todos los permisos actuales al rol desarrollador con `INSERT IGNORE`.

**Síntoma B (dark mode):**  
La tabla se muestra en blanco en modo oscuro.

**Causa probable:**  
La tabla usa `table-light` de Bootstrap sin override en el tema oscuro. Requiere ajuste CSS.

**Estado:** Pendiente de ajuste CSS global de tablas en dark mode (ver OBS-DARK más abajo).

---

### OBS-4 · hojas_requisicion.php — Botón "Agregar Hoja" ausente + tabla dark
**Síntoma:**  
El botón para agregar hojas no aparece en ningún rol.

**Causa:**  
`pages/hojas_requisicion.php` inyecta `window.TF_LEGACY_PERMS = {canReqEdit: $__canReqEdit, ...}` pero `$__canReqEdit` nunca fue definida — PHP evalúa la variable como `null` → el botón siempre queda oculto.

**Corrección aplicada:**  
```php
$__canReqEdit   = tf_has_permission('requisiciones.create', $__user) || tf_has_permission('requisiciones.edit', $__user);
$__canDireccion = tf_has_permission('direccion.view', $__user) || tf_user_has_direction_access($__user);
```

**Archivos modificados:** `pages/hojas_requisicion.php`

**Dark mode:** Pendiente (mismo problema de `table-light`).

---

### OBS-5 · items_requisicion.php — Dark mode + diseño simple + cotizaciones
**Síntoma A (dark):**  
Tabla se muestra blanca en modo oscuro.

**Síntoma B (diseño):**  
Vista muy simple; se pide mejorar la presentación de datos en coherencia con el resto del sistema.

**Síntoma C (cotizaciones):**  
Solicitud de adjuntar PDFs de cotizaciones con límite de tamaño.

**Estado cotizaciones:**  
✅ Ya implementado en ITF-3 (`crud_cotizaciones.php`, `hojas_cotizaciones` table, modal en `hojas_requisicion.php`). El límite es **8 MB** configurado en `crud_cotizaciones.php` constante `COT_MAX_SIZE`.

**Estado dark + rediseño:**  
⏳ Pendiente — ver OBS-DARK.

---

### OBS-6 · proveedores.php — Botón agregar, inactivos visibles, tarjetas KPI, tabs
**Síntoma A:**  
Botón "Agregar proveedor" no aparece para el rol desarrollador.

**Causa:**  
`$canManage` requiere `proveedores.manage` — permiso agregado después de la migración 006.  
**Corrección:** Migración 014 (sync permisos desarrollador).

**Síntoma B — Inactivos visibles:**  
Los tabs "Todos / Activos / Inactivos" no deben aparecer — los inactivos no deben ser visibles.

**Acción requerida:**  
- Eliminar el filtro de "INACTIVO" de la UI — dejar solo proveedores ACTIVOS por defecto  
- Mantener el botón "Desactivar" (no eliminar, solo inhabilitar)  
- El botón "Reactivar" es de uso exclusivo `admin`/`director`

**Síntoma C — Tarjetas KPI:**  
Las tarjetas con totales de proveedores ("Total", "Disponibles", etc.) son de poco valor. Se solicita quitarlas.

**Estado:** ⏳ Pendiente de cambios en `pages/proveedores.php` y `assets/js/proveedores.js`.

---

### OBS-7 · all_presiones.php — Importación Excel, CSV, marca de agua
**Requisito A — Importar Excel (solo valores, sin fórmulas):**  
Al importar un archivo `.xlsx`, extraer únicamente los valores numéricos de las celdas (no evaluar fórmulas). SheetJS ya lo hace por defecto con `cellFormula: false` o leyendo `.v` (valor calculado).

**Requisito B — Eliminar exportación CSV de secciones por obra:**  
En los botones de exportación por obra, quitar el botón CSV; dejar solo XLSX.

**Requisito C — Eliminar marca de agua en Excel:**  
Los archivos Excel exportados incluyen una marca de agua que no se posiciona correctamente. Quitarla de `all_presiones.php`.

**Requisito D — Import/export general o por obra:**  
Confirmar que tanto la importación como la exportación funcionen en modo "general" (todas las obras) y en modo "por obra".

**Estado:** ⏳ Pendiente — requiere revisar `pages/all_presiones.php` y `assets/js/reportes_kpi.js`.

---

### OBS-8 · admin.php — Error 500 (RESUELTO)
**Síntoma:**  
`crud_admin.php` devolvía 500 en todos los endpoints. El panel de administración quedaba completamente vacío.

**Causa:**  
3 conflictos git sin resolver en `api/crud_admin.php` (marcadores `<<<<<<`, `======`, `>>>>>>` en líneas 32, 88 y 316). PHP no puede parsear el archivo con conflictos activos.

**Corrección aplicada:**  
Se resolvieron los tres conflictos manteniendo la versión `stashed` (más completa):
- Bloque 1 → se conservaron las funciones `tf_admin_can_assign_obra()` y `tf_admin_forbidden()`  
- Bloque 2 → se conservó la consulta directa a `users` (sin depender de la vista `v_users_full`)  
- Bloque 3 → se conservaron los cases 8–17 (obras, roles, permisos, asignaciones multi-obra)

**Archivos modificados:** `api/crud_admin.php`

---

## OBS-DARK · Tablas en modo oscuro (múltiples vistas)

**Vistas afectadas:** `presiones_detalles.php`, `hojas_requisicion.php`, `items_requisicion.php`

**Causa probable:**  
Las tablas usan la clase Bootstrap `table-light` en `<tbody>`, que fuerza fondo blanco independientemente del tema. En modo oscuro, el color de texto también es claro, haciendo el contenido ilegible o invisible.

**Solución propuesta:**  
1. Reemplazar `class="table-light"` por nada (hereda del tema) o usar `table-striped`  
2. O agregar en el CSS global una regla:
   ```css
   [data-bs-theme="dark"] .table-light { --bs-table-bg: var(--bs-table-bg-type, transparent); color: inherit; }
   ```
3. Revisar si el toggle de tema en `v4-layout.js` aplica `data-bs-theme="dark"` al `<html>` o al `<body>` — Bootstrap 5.3 lo requiere en `<html>`.

**Estado:** ⏳ Pendiente — requiere prueba visual.

---

## Migraciones generadas en esta sesión

| Archivo | Descripción | Estado |
|---------|-------------|--------|
| `2026_06_23_012_proveedores_fecha_creacion.sql` | Agrega `proveedor_fechaCreacion` a `provedores` | **Aplicar** |
| `2026_06_23_013_hojas_cotizaciones.sql` | Crea tabla `hojas_cotizaciones` | **Aplicar** |
| `2026_06_24_014_sync_desarrollador_permisos.sql` | Sincroniza todos los permisos al rol `desarrollador` | **Aplicar** |

---

## Pendientes de implementación (próxima sesión)

| ID | Prioridad | Descripción |
|----|-----------|-------------|
| P-DARK | Media | Corregir tablas en modo oscuro (3 vistas) |
| P-PROV-UI | Media | `proveedores.php`: quitar tabs inactivos, quitar tarjetas KPI, rediseño |
| P-EXCEL | Media | `all_presiones.php`: import valores sin fórmulas, quitar CSV por obra, quitar watermark Excel |
| P-ITEMS | Baja | `items_requisicion.php`: mejorar presentación visual |
