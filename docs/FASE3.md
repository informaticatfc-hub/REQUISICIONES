# Fase 3 — Hardening de CRUDs + migracion progresiva de paginas al layout v4 (PARCIAL)

Rama: `genspark_ai_developer_v2_fase3` → PR #4.

## 1) Hardening de los 14 `api/crud_*.php` (COMPLETO)

Para cada CRUD se aplico el siguiente patron:

1. `include_once 'auth.php'` (wrapper de `rbac.php` con helpers `api_*`).
2. Lectura de payload via `api_get_request_data()` (en vez de `json_decode(file_get_contents)`).
3. `tf_require_permission($conexion, '<modulo.action>')` por accion (read vs write).
4. `api_require_csrf($_POST)` en todas las acciones de escritura.
5. Sustitucion de `id_user` aceptado por POST por usuario derivado de sesion
   (`api_get_current_user($conexion)`), eliminando riesgo de impersonacion.
6. `tf_audit_log($conexion, 'modulo.accion', 'entidad', $id, $detalle)` en cada escritura.

Matriz resumen:

| Archivo | Permiso lectura | Permiso escritura | Audit |
|---|---|---|---|
| `crud_Requisiciones.php` | `requisiciones.view` | `requisiciones.create/edit/delete` | si |
| `crud_Presiones.php` | `presiones.view` | `presiones.create` | si |
| `crud_all_presiones.php` | `presiones.view` | `presiones.authorize` | si |
| `crud_presionDetail.php` | `presiones.view` | `presiones.authorize` | si |
| `crud_hojas_requisicion.php` | `requisiciones.view` | `requisiciones.create/delete` | si |
| `crud_items_requisiciones.php` | `requisiciones.view` | `requisiciones.edit` (+`requisiciones.authorize` en case 11) | si |
| `crud_nueva_hoja.php` | `requisiciones.view` | `requisiciones.create` | si |
| `crud_enlazar_requisiciones.php` | `requisiciones.view` | — | — |
| `crud_proveedor.php` | `catalogos.view` | `proveedores.manage` | si |
| `crud_addProveedor.php` | `catalogos.view` | `proveedores.manage` | si |
| `crud_bancos.php` | `catalogos.view` | `bancos.manage` | si |
| `crud_direccion.php` | `direccion.view` | — | — |
| `crud_catalago.php` | `catalogos.view` | — | — |
| `crud_menu_catalagos.php` | `catalogos.view` | — | — |

### Bugs criticos corregidos en este pase

- **SQL injection** en `crud_catalago.php` (linea 15) y `crud_menu_catalagos.php` (linea 14):
  `WHERE user_id = '$id_user'` venia desde POST sin sanitizar. Reescritos para usar
  el usuario derivado de la sesion (`api_get_current_user`).
- **Impersonacion via `id_user` en POST** eliminada en 5 CRUDs.
- **Sin CSRF en escrituras** corregido en los 14 CRUDs.
- **Sin audit log** corregido en todas las escrituras relevantes.

Commit: `4b37d01` — `feat(api): RBAC + CSRF + audit hardening en los 14 crud_*.php (Fase 3)`.

## 2) Migracion de paginas al layout v4 (EN PROGRESO — 2 de 13)

Patron aplicado a cada pagina:

```php
<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';
$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);
if (!tf_has_permission('<modulo.action>', $__user)) {
    tf_abort(403, 'No tienes permisos...');
}
// $tf_page_title, $tf_active_nav, $tf_breadcrumb, $tf_user,
// $tf_show_direccion, $tf_show_admin, $canCreate/$canEdit/$canDelete
include __DIR__ . '/../includes/layout_top.php';
?>
<div id="AppX" class="tf-page-inner">
    <header class="tf-page-header">...</header>
    <section class="tf-card">...</section>
</div>
<?php
$tf_inline_script = <<<JS
    new Vue({ el: '#AppX', data: {}, methods: {}, mounted: function(){} });
JS;
include __DIR__ . '/../includes/layout_bottom.php';
```

### Paginas migradas

- ✅ `pages/requisiciones.php` — KPI grid (total/abiertas/cerradas/filtradas),
  tf-card con filtro, tf-admin-table con edit-in-row, sin DataTables ni
  `localStorage.NameUser`. Gates `canCreate/canEdit/canDelete`.
- ✅ `pages/nueva_requisicion.php` — tf-card por seccion (Emisor / Proveedor /
  Items), Vue 2 inline con CSRF automatico via axios, retenciones por item,
  eliminacion de items, totales por computed. **Corrige** la URL del endpoint:
  legacy apuntaba al inexistente `crud_new_requisicion.php`; ahora usa
  `crud_nueva_hoja.php` (action 1 con `requisiciones.create` + CSRF).

Commit: `6b7375e` — `feat(ui): migrar requisiciones.php y nueva_requisicion.php al layout v4 (Fase 3)`.

## 3) Pendientes (a continuar en proximo turno o PR #5)

### Paginas legacy aun por migrar (11)

- `pages/presiones.php`
- `pages/all_presiones.php`
- `pages/presiones_detalles.php`
- `pages/hojas_requisicion.php`
- `pages/nueva_hoja.php`
- `pages/items_requisicion.php`
- `pages/enlazar_requisiciones.php`
- `pages/proveedores.php`
- `pages/agregar_proveedor.php`
- `pages/bancos.php`
- `pages/direccion.php`
- `pages/catalago.php`
- `pages/menu_catalago.php`

### Limpieza adicional

- Quitar `localStorage.NameUser` de los ~17 JS legacy restantes
  (reemplazar por `window.TF_CONTEXT.user.id` / `.name`).
- `assets/js/layout_sidebar.js` retirado en Fase 08 (navbar legacy unificada).
- Mantener `assets/css/main.css` como legacy temporal hasta completar migracion total a layout v4.
- Tests automatizados (PHPUnit + Playwright) — diferible.
- Activar HSTS en produccion — opcional.

### Decision tecnica diferida

- Vue 3 vs Alpine.js para la migracion de los apps Vue 2 legacy. Recomendacion
  actual: mantener Vue 2.7.16 mientras se inlinea cada app en su `$tf_inline_script`
  y migrar a Vue 3 (o Alpine para los apps mas simples) en una Fase 4 dedicada.
