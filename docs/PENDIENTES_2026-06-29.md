# Pendientes, Actualizaciones e Implementaciones — 2026-06-29

**Fuente:** `docs/Observaciones_requisiciones_presiones.docx`
**Prueba realizada con:** Usuario Desarrollador
**Procesado por:** Informática The Fuentes
**Continuación de:** [`PENDIENTES_2026-06-24.md`](PENDIENTES_2026-06-24.md)

---

## Resumen ejecutivo

La revisión con rol Desarrollador detectó observaciones en 4 vistas (`presiones.php`,
`presiones_detalles.php`, `nueva_hoja.php`, `hojas_requisicion.php`) más el flujo de
`items_requisicion.php` y el selector de obras del navbar.

> ⛔ **Hallazgo crítico — 2 conflictos de merge quedaron commiteados en el último commit
> (`ff88f30`)** y rompían funcionalidad en producción. No aparecían en `git status`
> porque ya estaban dentro del commit, no en el árbol de trabajo:
>
> 1. `assets/js/presiones_detalles.js` (líneas 244–253) → rompía **toda** la vista de detalle de presiones.
> 2. `includes/legacy_navbar.php` (líneas 14–75) → rompía el **navbar** de las vistas legacy.

✅ **Ambos conflictos se resolvieron en esta sesión (2026-06-29)** — ver [Acciones aplicadas](#acciones-aplicadas-en-esta-sesión-2026-06-29). El resto de observaciones queda documentado como pendiente.

---

## Tabla de estado

| # | Sección | Observación | Causa verificada | Estado | Severidad |
|---|---------|-------------|------------------|--------|-----------|
| 1 | `presiones.php` | Presión duplicada da aviso de éxito en vez de error; nueva presión aparece como AUTORIZADA y no al inicio de la lista | Toast incondicional **ya corregido** (24-jun). Falta: estatus por defecto **EN REVISIÓN** y orden de la lista | ⚠️ **PARCIAL** | Media |
| 2 | `presiones_detalles.php` | No cargan presiones pagadas; `Uncaught SyntaxError: Unexpected token '<<'` + cascada de "is not defined" | **Conflicto de merge commiteado** en `presiones_detalles.js:244` | ✅ **CORREGIDO** (29-jun) | Crítica |
| 3 | `nueva_hoja.php` | Incorporar cotizaciones (imagen/PDF) con límite de peso razonable; diseño mal distribuido, no respeta dark/light | Cotizaciones PDF ya existen (8 MB). Falta: **imágenes**, integrar alta en esta vista, rediseño + dark mode | ⚠️ **PARCIAL** | Media |
| 4a | `hojas_requisicion.php` | Cotización ya anexada no se puede **ver** | **Ya existía** enlace "Ver/Imprimir" (`crud_cotizaciones.php?accion=4`); tabla `hojas_cotizaciones` con datos reales en el dump `_02` | ✅ **YA FUNCIONA** | Media |
| 4b | `hojas_requisicion.php` | No debería poder **duplicar** una hoja | Botón "Duplicar" eliminado de la vista y del JS (`duplicarHoja`) | ✅ **CORREGIDO** (29-jun) | Baja |
| 4c | `hojas_requisicion.php` | Estatus / botón de historial | Por decisión del usuario se **quitó el botón de historial de estatus** (y su modal) | ✅ **HECHO** (29-jun) | Media |
| 4e | `hojas_requisicion.php` | Mal diseño del encabezado (datos apilados con mucho espacio) + tabla no respeta dark/light | Migrado a sistema de diseño v4 (`tf-page-header` + meta-strip compacto + `tf-admin-table`); eliminado DataTables (mismo conflicto que ítems) | ✅ **CORREGIDO** (29-jun) | Media |
| 4d | `hojas_requisicion.php` | Error **500** al abrir el estatus (`verHistorialHoja`) | `case 10` consultaba la columna **`log_fecha`** que no existe (la real es `log_createdAt`) → `PDOException` → 500 | ✅ **CORREGIDO** (29-jun) | Alta |
| 5 | Transversal | **Trazabilidad**: registrar usuario que crea cada presión/requisición/hoja/cotización (solo auditoría) | Parcial: cotizaciones y requisiciones ya lo guardan; falta presiones y nueva hoja | ⚠️ **PARCIAL** | Media |
| 6 | `items_requisicion.php` | El PDF muestra quién **genera** el PDF, no quién **creó** la hoja | `pdfGenerate.js` usa el usuario de sesión, no el creador almacenado | ⏳ **PENDIENTE** | Media |
| 7 | `items_requisicion.php` | No muestra los ítems en la tabla, solo el subtotal | **DataTables (jQuery) se inicializaba sobre la misma tabla que Alpine renderiza con `x-for`** → conflicto que ocultaba las filas | ✅ **CORREGIDO** (29-jun) | Alta |
| 8 | `navbar` (obras) | Selector de obras se desborda con >5 obras | **Conflicto de merge commiteado** en `legacy_navbar.php:14` (✅ resuelto 29-jun). Rediseño >5 obras → catálogo/búsqueda (pendiente) | ✅ conflicto + ⏳ rediseño | Crítica + Media |

Leyenda: ⛔ crítico (rompe prod) · 🔴 alta · ⚠️ parcial · ⏳ pendiente · ✅ hecho

---

## Detalle por observación

### OBS-1 · presiones.php — Estatus por defecto y orden de la lista
**Síntoma:**
Al intentar crear una presión que ya existe (misma obra, semana y día), el sistema responde
`{success: false, message: 'Ya existe una presion para la obra, semana y dia indicados.', presion_id: 610}`
pero la UI lo trata como éxito. Además, una presión recién creada debe nacer **EN REVISIÓN** y aparecer
al **inicio** de la lista; hoy aparece como AUTORIZADA y fuera de orden.

**Causa / estado:**
- El toast de éxito incondicional **ya se corrigió** el 24-jun (`presiones.js` → `agregarPresion` async,
  el toast solo dispara con `ok === true`). ✅
- Pendiente: confirmar el **estatus por defecto** al insertar la presión (debe ser EN REVISIÓN, no
  AUTORIZADA) y el **ordenamiento** de la lista (más reciente primero).

**Acción propuesta:**
1. Revisar el `INSERT` de presiones (en `api/crud_nueva_hoja.php` / lógica de presiones) y forzar
   estatus inicial **EN REVISIÓN**.
2. Verificar el `ORDER BY` del listado para que la presión nueva aparezca al inicio.
3. Confirmar que el mensaje `success:false` por duplicado se muestra como advertencia (no como éxito).

---

### OBS-2 · presiones_detalles.php — SyntaxError por conflicto de merge ⛔
**Síntoma:**
```
presiones_detalles.js:244 Uncaught SyntaxError: Unexpected token '<<'
Alpine Expression Error: presionDetailApp is not defined
... (cascada: init, semana, dia, obraActiva, presiones, formatearMoneda, canClosePresion, ...)
```
Ningún dato de presiones pagadas se renderiza.

**Causa (verificada):**
Conflicto de merge **sin resolver y commiteado** en
[`assets/js/presiones_detalles.js`](../assets/js/presiones_detalles.js) líneas **244–253**:
```js
243
244  <<<<<<< Updated upstream
245          exportarExcel: function () {
246              if (!window.XLSX || !window.XLSX.utils) {
...
248  =======
249          exportarExcel: async function () {
250              if (!this.excelJsDisponible()) {
...
253  >>>>>>> Stashed changes
```
Los marcadores `<<<<<<<`, `=======`, `>>>>>>>` son sintaxis inválida → el archivo entero no parsea →
`presionDetailApp` nunca se define → Alpine falla en cada expresión.

**✅ Acción aplicada (29-jun):**
Se resolvió el conflicto conservando la rama `Stashed changes` (`async` + ExcelJS + fallback a
`exportarCsv()`), por ser consistente con el cuerpo de la función (usa `await`/ExcelJS). Se eliminaron
los 3 marcadores y la variante SheetJS duplicada. Además se **implementó `exportarCsv()`**, que el
fallback invocaba pero no existía (referencia rota). Validado con `node --check`.

---

### OBS-3 · nueva_hoja.php — Cotizaciones (imagen/PDF) + rediseño + dark mode
**Síntoma:**
La vista de creación de hoja debe permitir **adjuntar cotizaciones** (pueden venir como **imagen o PDF**)
con un límite de peso **no tan restrictivo**. Además el diseño/distribución de datos está mal estructurado
y no respeta el modo claro/oscuro.

**Estado:**
- Backend de cotizaciones **ya existe**: [`api/crud_cotizaciones.php`](../api/crud_cotizaciones.php) +
  tabla `hojas_cotizaciones` (migración
  [`2026_06_23_013_hojas_cotizaciones.sql`](../api/migrations/2026_06_23_013_hojas_cotizaciones.sql)).
  Límite actual **8 MB** (`COT_MAX_SIZE`).
- Falta: aceptar **imágenes** (jpg/png) además de PDF; integrar el alta de cotización dentro del flujo de
  `nueva_hoja.php`; rediseñar la distribución de datos y aplicar dark/light.

**Acción propuesta:**
1. Ampliar tipos permitidos en `crud_cotizaciones.php` a imágenes (validando MIME real).
2. Revisar el límite de peso (mantener 8 MB o ajustar según criterio del usuario).
3. Integrar el componente de carga en `pages/nueva_hoja.php` y rediseñar la vista respetando el tema.

---

### OBS-4a · hojas_requisicion.php — No se puede ver la cotización anexada
**Síntoma:**
El alta de cotización **funciona**, pero una vez anexada a la hoja no hay forma de **visualizar** el
archivo guardado.

**Causa:** Existe el alta (insert + guardado en `uploads/`) pero no una acción/endpoint para listar y
abrir las cotizaciones de una hoja.

**Acción propuesta:** Agregar acción "Ver cotizaciones" que liste `hojas_cotizaciones` por
`cotizacion_hoja_id` y abra/descargue el archivo desde `uploads/` (con control de acceso por obra).

---

### OBS-4b · hojas_requisicion.php — No debe permitir duplicar requisición
**Síntoma:** Existe acción de **duplicar** una requisición; el usuario indica que no debería existir,
ya que cada hoja es única.

**Acción propuesta:** Eliminar (o condicionar a un rol específico) el botón/acción "Duplicar" en
`pages/hojas_requisicion.php` y `assets/js/hojas_requisicion.js`.

---

### OBS-4c · hojas_requisicion.php — Estados intermedios del proceso
**Síntoma:** El estatus solo cambia entre **LIGADA / RECHAZADA / PAGADA**; los demás estados del proceso
no quedan reflejados, por lo que el badge de estatus aporta poco.

**Acción propuesta:** Revisar la máquina de estados de la hoja, definir y exponer los estados intermedios
(p. ej. EN REVISIÓN, AUTORIZADA, etc.) para que el seguimiento sea significativo.

---

### OBS-4d · hojas_requisicion.php — Error 500 al abrir el historial de estatus 🔴
**Síntoma:**
```
POST https://thefuentescorp.com/TheFuentesApp/api/crud_hojas_requisicion.php 500 (Internal Server Error)
verHistorialHoja @ hojas_requisicion.js:242
```

**Causa raíz (verificada):**
El `case 10` de [`api/crud_hojas_requisicion.php`](../api/crud_hojas_requisicion.php) consultaba una
columna **inexistente**:
```sql
SELECT ... log_userName AS usuario, log_fecha AS fecha
FROM `hoja_estatus_log` WHERE `log_hojaId` = ? ORDER BY `log_fecha` ASC
```
La tabla `hoja_estatus_log` (creada en migración `005` y presente en el dump de producción
`DataBase/u701868959_TFC.sql`) **no tiene** la columna `log_fecha`; la columna real es **`log_createdAt`**.
Con PDO en `ERRMODE_EXCEPTION`, `Unknown column 'log_fecha'` lanza una `PDOException` no capturada en el
`case 10` → error fatal → **HTTP 500**. No era un problema de migración ni de permisos.

**✅ Acción aplicada (29-jun):**
Se cambió `log_fecha` → `log_createdAt` en el `SELECT` (manteniendo el alias `AS fecha`) y en el
`ORDER BY`. El frontend (`hojas_requisicion.js`, `verHistorialHoja`) consume `r.fecha`/`r.nuevo`/`r.antes`/
`r.usuario`/`r.comentario`, que siguen intactos. Se verificó además que `tf_hoja_estatus_log()` en
`api/bitacora.php` inserta con las columnas correctas (`log_createdAt` toma `CURRENT_TIMESTAMP`).
Validado con `php -l`.

---

### OBS-5 · Trazabilidad de usuario (transversal)
**Síntoma:**
Cada presión, requisición, nueva hoja o cotización debe registrar **quién** realizó la acción. No necesita
mostrarse en la UI normal — es solo para **auditoría** — pero **sí** debe verse al **imprimir** (ver OBS-6).

**Estado:**
- Cotizaciones: ✅ ya guardan `cotizacion_userSubio` y `cotizacion_userNombre`.
- Requisiciones: ✅ creador agregado en migración `2026_05_14_004_add_requisicion_creator.sql`.
- Presiones y nueva hoja: ⏳ falta registrar el creador de forma consistente.

**Acción propuesta:** Asegurar que el alta de presiones y de hojas almacene el `user_id`/nombre del
usuario autenticado (reutilizar el patrón de cotizaciones).

---

### OBS-6 · items_requisicion.php — PDF debe mostrar al creador real de la hoja
**Síntoma:**
Al generar el PDF, se imprime el nombre de **quien genera el PDF**, no el de **quien realmente creó** la
hoja/presión.

**Causa:** [`assets/js/pdfGenerate.js`](../assets/js/pdfGenerate.js) toma el usuario de la sesión actual
en vez del creador almacenado de la hoja.

**Acción propuesta:** Pasar el creador persistido (de OBS-5) al PDF y mostrarlo en el documento. El usuario
de sesión puede mantenerse como "impreso por", pero el dato de trazabilidad debe ser el **creador**.

---

### OBS-7 · items_requisicion.php — No se muestran los ítems en la tabla 🔴
**Síntoma:**
La tabla no muestra los ítems; solo aparece el subtotal a pagar. En consola:
```
Tracking Prevention blocked access to storage for
https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js
```

**Causa raíz (verificada en código):**
El mensaje de *Tracking Prevention* era un **falso positivo** (solo bloquea `storage`, no el script; Alpine
sí carga — por eso el encabezado y el Total se ven). El problema real está en
[`item_requisicion.js`](../assets/js/item_requisicion.js): `listarItems()` asignaba `this.itemsHoja` y
**acto seguido inicializaba DataTables** (`$('#example').DataTable()`) sobre la **misma** `<tbody>` que
Alpine renderiza con `<template x-for>`. jQuery DataTables y el `x-for` reactivo se pelean por el DOM de esa
tabla → las filas desaparecen. El Total del `<tfoot>` está ligado a `hojaActiva` (no a los ítems), por eso
seguía visible. Es la incompatibilidad clásica **DataTables + Alpine x-for sobre la misma tabla**.

Bug latente adicional: `items_requisicion.php` usaba `$__canReqEdit`/`$__canDireccion` (línea ~158) que
**nunca se definían** → los botones de edición/validación nunca aparecían (mismo patrón corregido el 24-jun
en `hojas_requisicion.php`).

**✅ Acción aplicada (29-jun):**
1. Se eliminó la inicialización de DataTables: **Alpine es el único dueño** de la tabla `#example`. Se quitó
   el método `initDataTable()` y la llamada en `listarItems()` (que ahora normaliza a arreglo y captura
   errores). Los botones editar/eliminar siguen funcionando porque usan handlers Alpine `x-on:click`.
2. Se desactivaron `jQuery` y `DataTables` en la página (`$tf_use_jquery`/`$tf_use_datatables = false`) y se
   quitaron sus `<link>` CSS (ya no se usan en esta vista).
3. Se definieron `$__canReqEdit`/`$__canDireccion` con `tf_has_permission(...)`.

Validado con `php -l` y `node --check`. **Pendiente:** verificación visual en navegador con una hoja que
tenga ítems (la lista de una hoja suele ser corta, por lo que no se pierde paginación/buscador relevantes).

---

### OBS-8 · navbar (obras) — Conflicto de merge + rediseño del selector ⛔
**Síntoma:**
El selector de obras del navbar se ve bien con pocas obras, pero **se desborda** cuando un usuario tiene
muchas (como en modo desarrollador con todas las obras). La vista no alcanza a cubrirlas.

**Causa A (verificada — crítica):**
Conflicto de merge **commiteado** en [`includes/legacy_navbar.php`](../includes/legacy_navbar.php)
líneas **14–75**. La rama `Updated upstream` define `$legacy_actions` (que es lo que el `<nav>` itera más
abajo, línea ~89), mientras que la rama `Stashed changes` usa `$legacy_links` y referencia
`$__legacyPerms` / `$__legacyShowAdmin`, **que no están definidas** en este archivo. El archivo no parsea
(marcadores de conflicto) → cualquier página legacy que incluya el navbar falla.

**Causa B (rediseño):** Aun resuelto el conflicto, el selector necesita manejar >5 obras.

**✅ Acción aplicada (29-jun) — Causa A:**
Se resolvió el conflicto conservando la rama `Updated upstream` (`$legacy_actions`), que es la que el
`<nav>` realmente itera (línea ~89). Se descartó la rama `$legacy_links` por depender de
`$__legacyPerms`/`$__legacyShowAdmin` indefinidos. Validado con `php -l` (sin errores).

**⏳ Acción pendiente — Causa B (rediseño):**
Cuando el usuario tenga **más de 5 obras**, redirigir a una pestaña tipo **catálogo de obras** con
búsqueda por nombre, en lugar de listarlas todas en el navbar.

---

## Acciones aplicadas en esta sesión (2026-06-29)

| # | Acción | Archivo | Resultado |
|---|--------|---------|-----------|
| 1 | Resuelto conflicto de merge en `exportarExcel`: se conservó la versión **async + ExcelJS** (coherente con el cuerpo que usa `await`/ExcelJS) y se descartó la variante SheetJS | `assets/js/presiones_detalles.js` | ✅ `node --check` OK |
| 2 | Implementado el método `exportarCsv()` que faltaba (lo invocaba el fallback de `exportarExcel` pero no existía → referencia rota) | `assets/js/presiones_detalles.js` | ✅ Fallback completo |
| 3 | Resuelto conflicto de merge del navbar: se conservó la rama que define `$legacy_actions` (la que el `<nav>` realmente itera). Se descartó la rama `$legacy_links` por usar `$__legacyPerms`/`$__legacyShowAdmin` **indefinidos** | `includes/legacy_navbar.php` | ✅ `php -l` OK |

**Verificación realizada:** `php -l includes/legacy_navbar.php` → sin errores; `node --check
assets/js/presiones_detalles.js` → OK; `grep` de marcadores de conflicto en todo `*.php`/`*.js` → 0
coincidencias.

> Nota: la rama descartada del navbar incluía un modelo de navegación **por permisos**
> (`$legacy_links` + `obras.view`, `requisiciones.view`, etc.) y una decisión funcional de "ocultar
> Requisiciones y Presiones". Si ese era el diseño deseado, requiere un refactor aparte: definir
> `$__legacyPerms`/`$__legacyShowAdmin` y actualizar el `<nav>` para iterar `$legacy_links`. Por ahora se
> priorizó restaurar producción con el modelo `$legacy_actions` que ya estaba en uso.

| 4 | Corregido el **HTTP 500** del historial de estatus: el `case 10` consultaba la columna inexistente `log_fecha` → cambiada a `log_createdAt` (alias `AS fecha` conservado) | `api/crud_hojas_requisicion.php` | ✅ `php -l` OK |
| 5 | Corregido **OBS-7** (ítems no se muestran): eliminada la inicialización de DataTables sobre la tabla Alpine `x-for` (conflicto de DOM); desactivados jQuery/DataTables en la vista | `assets/js/item_requisicion.js`, `pages/items_requisicion.php` | ✅ `php -l` + `node --check` |
| 6 | Definidos `$__canReqEdit`/`$__canDireccion` (estaban indefinidos → botones de edición/validación nunca aparecían) | `pages/items_requisicion.php` | ✅ |
| 7 | **Requisiciones**: quitado el botón duplicado "Nueva requisicion" del `tf-page-header`, el `div` de alerta de abiertas y las 4 tarjetas KPI | `pages/requisiciones.php` | ✅ `php -l` |
| 8 | **Hojas**: rediseño del encabezado (meta-strip compacto theme-aware), tabla migrada a `tf-admin-table` (dark/light), eliminado DataTables, quitados botones **Duplicar** y **Historial de estatus** (+ su modal) | `pages/hojas_requisicion.php`, `assets/js/hojas_requisicion.js` | ✅ `php -l` + `node --check` |

### Verificación de BD (dump `DataBase/u701868959_TFC_02.sql`)

- ✅ La tabla **`hojas_cotizaciones` ya existe** (con `cotizacion_hoja_id`, `cotizacion_archivo`, `cotizacion_userNombre`) y **tiene datos reales** → el vínculo archivo↔hoja↔usuario ya está en la BD. **No requiere migración nueva.**
- ✅ `hojasrequisicion` ya tiene `hojaRequisicion_userCreado` y `hojaRequisicion_userCreadoNombre`; `crud_nueva_hoja.php` los rellena vía `tf_hoja_set_creator()`. **No requiere migración nueva.**

---

## Roadmap de implementación priorizado

**Crítica (rompe producción):** ✅ **completado en esta sesión**
- OBS-2 — ~~resolver conflicto `presiones_detalles.js`~~ ✅ hecho.
- OBS-8A — ~~resolver conflicto `legacy_navbar.php`~~ ✅ hecho.

**Alta:** ✅ **completado en esta sesión**
- OBS-4d — ~~error 500 historial (`hoja_estatus_log`)~~ ✅ hecho (29-jun).
- OBS-7 — ~~ítems no se muestran~~ ✅ hecho (29-jun) — falta verificación visual en navegador.

**Media:**
- OBS-1 — estatus inicial EN REVISIÓN + orden de lista.
- OBS-3 — cotizaciones con imágenes + integración + rediseño/dark mode.
- OBS-4a — visualizar cotización anexada.
- OBS-4c — estados intermedios de la hoja.
- OBS-5 — trazabilidad en presiones y hojas.
- OBS-6 — PDF con creador real.
- OBS-8B — rediseño selector de obras (>5 → catálogo/búsqueda).

**Baja:**
- OBS-4b — quitar acción "duplicar" requisición.

---

## Continuidad con PENDIENTES_2026-06-24.md

Siguen vigentes de la sesión anterior:
- **P-DARK** — tablas en modo oscuro (`presiones_detalles.php`, `hojas_requisicion.php`,
  `items_requisicion.php`). Se refuerza con OBS-3.
- **P-PROV-UI** — `proveedores.php`: quitar tabs inactivos y tarjetas KPI.
- **P-EXCEL** — `all_presiones.php`: importar valores sin fórmulas, quitar CSV por obra, quitar marca de
  agua del Excel.
- **P-ITEMS** — `items_requisicion.php`: mejorar presentación visual (se relaciona con OBS-6/OBS-7).

Migraciones pendientes de aplicar (de la sesión 24-jun): `012`, `013`, `014`.
