# The Fuentes Workspace — Sistema de Requisiciones

**Versión:** v4.1  
**Rama activa:** `fase08-vistas-std`  
**Última revisión:** 23 de junio de 2026  
**BD:** `u701868959_TFC` (MariaDB 11.8.6, 25 tablas/vistas)  
**Hosting:** Hostinger (VPS)

---

## Descripción

Sistema web para gestión de requisiciones de compra en obras de construcción. Permite a residentes crear solicitudes, a compras administrar proveedores y ligar requisiciones a presiones de pago, al director autorizar pagos, y a finanzas registrar el pago efectivo. Incluye RBAC granular, auditoría, exportación XLSX/PDF y dashboard KPI.

---

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP (sin framework), PDO/MySQL |
| Frontend | Alpine.js 3, Bootstrap 5.3, Bootstrap Icons 1.11 |
| HTTP / AJAX | Axios (preconfigurado con CSRF + `withCredentials`) |
| PDF cliente | jsPDF + autoTable (`assets/lib/jspdf/`) |
| Excel | SheetJS (`assets/lib/xlsx/`) |
| Gráficas | Chart.js |
| Alertas UI | SweetAlert2 |
| CSS | `assets/css/v4.css` (activo), `assets/css/main.css` (legacy — eliminar) |
| Fuente | Inter (Google Fonts) |

---

## Estructura de archivos

```
REQUISICIONES/
├── index.html / index.php      Punto de entrada (redirige a login)
├── validarSesion.php           Guard de sesión PHP
├── api/
│   ├── rbac.php                RBAC central (tf_session_start, tf_csrf_token,
│   │                           tf_require_permission, tf_current_user, tf_audit_log)
│   ├── auth.php                Wrapper de rbac.php para CRUDs
│   ├── conexion.php            Clase Conexion (PDO)
│   ├── LoginAcces.php          Autenticación + rate limiting
│   ├── bitacora.php            Endpoint de consulta de audit_log
│   ├── crud_*.php              Endpoints por módulo (accion: N)
│   ├── notifications.php       (stub) Notificaciones in-app
│   └── migrations/             SQL versionado (001–011 aplicados)
├── pages/                      Vistas PHP (Alpine.js)
├── includes/
│   ├── layout_top.php          Cabecera HTML + topbar v4 + TF_CONTEXT
│   └── layout_bottom.php       Scripts + cierre HTML
├── assets/
│   ├── css/v4.css              Estilos activos v4
│   ├── js/*.js                 Alpine apps por página
│   ├── js/legacy/              Scripts Vue 2 (retirados, no usados en páginas activas)
│   └── lib/                    Dependencias locales (jsPDF, SheetJS, Bootstrap…)
└── docs/                       Documentación técnica (este directorio)
```

---

## Roles y permisos (RBAC)

| Rol | Nivel | Código | Descripción |
|---|:---:|---|---|
| Administrador | 100 | `admin` | Acceso total, gestión de usuarios y roles |
| Dirección | 80 | `director` | Visibilidad global, autoriza presiones de pago |
| Compras | 60 | `compras` | Crea/valida requisiciones, gestiona proveedores |
| Finanzas | 60 | `finanzas` | Marca presiones como pagadas, gestiona bancos |
| Residente | 40 | `residente` | Crea requisiciones en obras asignadas |
| Lector | 20 | `lector` | Solo lectura en módulos de sus obras asignadas |

### Matriz de permisos clave

| Permiso | admin | director | compras | finanzas | residente | lector |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| obras.view | ✅ | ✅ | ✅ | ✅ | Solo asignadas | ✅ |
| obras.create / edit | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| requisiciones.view | ✅ | ✅ | ✅ | ✅ | Solo asignadas | ✅ |
| requisiciones.create / edit | ✅ | ❌ | ✅ | ❌ | ✅ | ❌ |
| requisiciones.validate | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| presiones.view | ✅ | ✅ | ✅ | ✅ | Solo asignadas | ✅ |
| presiones.create | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ |
| presiones.authorize | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| presiones.pay | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| proveedores.manage | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| bancos.manage | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| admin.users.* | ✅ | Parcial | ❌ | ❌ | ❌ | ❌ |
| admin.audit.view | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |

**API RBAC (PHP):**

```php
tf_session_start();
tf_security_headers();
$token = tf_csrf_token();          // token CSRF de sesión
tf_csrf_validate($payload);        // valida o aborta 403
$user = tf_current_user($pdo);     // usuario + rol + permisos
tf_require_permission($pdo, 'presiones.authorize');
tf_audit_log($pdo, 'presion.auth', 'presiones', $id, $detalle);
```

**API RBAC (JS — `window.TF_CONTEXT`):**

```js
TF.can('requisiciones.create');   // true | false
// Axios preconfigurado con withCredentials y X-CSRF-Token automático
```

---

## Estado de módulos (junio 2026)

| Módulo | Estado | Pendiente principal |
|---|:---:|---|
| Autenticación / Sesión | 🟢 Completo | 2FA desactivado (tabla lista) |
| RBAC backend | 🟢 Completo | — |
| RBAC UI (ocultar controles) | 🟡 Parcial | Botones CRUD visibles para lector |
| Requisiciones | 🟢 Funcional | Timeline de aprobación (P3-4) |
| Hojas e ítems | 🟢 Funcional | Importar ítems desde Excel (P4-5) |
| Presiones | 🟡 Parcial | Modal de pago con folio (P2-1), UI comentario director (P2-2) |
| Catálogos (proveedores) | 🟢 Funcional | Búsqueda RFC+CLABE (P3-3) |
| Catálogos (bancos) | 🟢 Funcional | Vincular bancos a obras (F-M4) |
| Obras | 🟢 Funcional | Campo presupuesto en BD (migración 014) |
| Dashboard KPI | 🟡 Parcial | Filtro por período (P2-4), selector obra única (revisión 22-jun) |
| all_presiones (Director) | 🔴 Conflicto git | Resolver conflictos merge (crítico) |
| reportes_kpi | 🔴 Conflicto git | Resolver conflictos merge (crítico) |
| Panel Admin | 🟡 Parcial | Reactivar roles (P1-2), reset pwd (P2-5), filtros auditoría (P2-3) |
| Exportación XLSX | 🟢 Funcional | — |
| PDF (pdfGenerate.js) | 🟡 Bug | Watermark tapa el contenido (eliminar línea 60) |
| Command Palette (Ctrl+K) | 🟢 Implementado | — |
| Modo oscuro | 🟢 Implementado | — |
| Paginación server-side | 🟢 Implementado | presiones y all_presiones paginados |
| Notificaciones badge | 🔴 Sin implementar | Campana en topbar sin lógica |
| CFDI XML (facturas SAT) | 🔴 Sin implementar | Importación + campos BD (revisión 22-jun) |
| Pruebas automatizadas | 🔴 Mínimo | Solo `tests/smoke.php` (sin E2E) |

---

## Migraciones SQL

Directorio: `api/migrations/`

| Archivo | Descripción | Estado |
|---|---|:---:|
| `001_create_rbac.sql` | Tablas RBAC (roles, permissions, role_permissions, audit_log) | ✅ Aplicada |
| `002_adaptacion_bd_real.sql` | Adaptación BD real: columnas RBAC en users, vistas, índices | ✅ Aplicada |
| `003_schema_only_no_data_changes.sql` | Schema without data | ✅ Aplicada |
| `004_add_requisicion_creator.sql` | Columna creador en requisiciones | ✅ Aplicada |
| `005_cleanup_and_improvements.sql` | Limpieza general | ✅ Aplicada |
| `005_oneshoot.sql` | One-shot fixes | ✅ Aplicada |
| `006_rol_desarrollador.sql` | Rol desarrollador | ✅ Aplicada |
| `007_fix_v_users_full.sql` | Corrección vista v_users_full | ✅ Aplicada |
| `008_user_obras_multi.sql` | Tabla user_obras (N:M usuarios ↔ obras) | ✅ Aplicada |
| `009_login_rate_limiting.sql` | Rate limiting: 5 intentos / 15 min | ✅ Aplicada |
| `010_presiones_comentario_director.sql` | Columna comentario_director en presiones | ✅ Aplicada |
| `011_pago_hoja_requisicion.sql` | Campos folio_pago, banco_id, fecha_pago en presiones | ✅ Aplicada |
| `012_cfdi_fields.sql` | Campos CFDI en hojasrequisicion (UUID, RFC, fecha, total) | 🔴 Pendiente |
| `013_presiones_fechas_estado.sql` | Fechas de transición de estado en presiones | 🔴 Pendiente |
| `014_obras_presupuesto.sql` | Campo presupuesto + fechas inicio/fin en obras | 🔴 Pendiente |

**Aplicar:**
```bash
mysqldump -u USUARIO -p u701868959_TFC > backup_pre_migrate.sql
mysql -u USUARIO -p u701868959_TFC < api/migrations/012_cfdi_fields.sql
mysql -u USUARIO -p u701868959_TFC < api/migrations/013_presiones_fechas_estado.sql
mysql -u USUARIO -p u701868959_TFC < api/migrations/014_obras_presupuesto.sql
```

---

## Anomalías conocidas en BD

| # | Tabla | Problema | Acción |
|---|---|---|---|
| BD-1 | `hojasrequisicion` | Dos columnas para banco de pago: `hojaRequisicion_bancoPago` (INT FK) y `hojasRequisicion_bancoPago` (VARCHAR — typo con "s") | Verificar cuál tiene datos; deprecar el VARCHAR |
| BD-2 | `presiones` | Solo tiene `presiones_fechaCreacion`; sin fecha de envío ni de autorización | Migración 013 agrega `presiones_fechaEnviado` y `presiones_fechaAutorizado` |
| BD-3 | `obras` | Sin campos de presupuesto ni fechas de proyecto | Migración 014 agrega `obras_presupuesto`, `obras_fecha_inicio`, `obras_fecha_fin` |
| BD-4 | `requisicionesligadas` | IDs de hojas fuera de rango (valores como 683137 vs máx real ~118) — posibles datos de prueba o carga errónea | Ejecutar query de verificación (ver REVISION_2026_06_22.md §3.4) |
| BD-5 | `hojasrequisicion` | Sin campos para trazabilidad fiscal (UUID CFDI, RFC emisor) | Migración 012 |

---

## Pendientes por prioridad

### P1 — Crítico (antes de producción)

| ID | Tarea | Archivo / Línea | Esfuerzo |
|---|---|---|---|
| **GIT** | Resolver conflictos merge | `pages/all_presiones.php` (líneas 231, 524, 744), `pages/reportes_kpi.php` (línea 215) | 30 min |
| **PDF** | Eliminar watermark que tapa el contenido | `assets/js/pdfGenerate.js` líneas 4 y 60 | 5 min |
| **CDN** | Eliminar jsPDF duplicado (versión 1.5.3 legacy) | `pages/reportes_kpi.php` línea 701 | 5 min |
| P1-2 | Botón "Reactivar rol" en panel admin | `pages/admin.php`, `api/crud_admin.php` | 2 h |
| P1-3 | Eliminar `main.css` de `all_presiones.php` | `pages/all_presiones.php` línea 44 | 15 min |
| P1-4 | Validar alcance de obra en endpoints de escritura | `crud_Presiones.php`, `crud_hojas_requisicion.php`, `crud_items_requisiciones.php`, `crud_nueva_hoja.php` | 4 h |

### P2 — Alto (primer sprint post-lanzamiento)

| ID | Tarea | Archivos | Esfuerzo |
|---|---|---|---|
| P2-1 | Modal de pago para Finanzas (folio + banco + fecha) — campos ya están en BD (migración 011) | `pages/presiones.php`, `api/crud_Presiones.php` | 5 h |
| P2-2 | Mostrar/capturar `comentario_director` en UI — campo ya en BD (migración 010) | `pages/presiones_detalles.php`, `assets/js/presiones_detalles.js` | 3 h |
| P2-3 | Filtros + paginación server-side en log de auditoría del admin | `pages/admin.php`, `api/crud_admin.php` | 8 h |
| P2-4 | Selector de período en KPI (semana/mes/trimestre/año) | `pages/reportes_kpi.php`, `assets/js/reportes_kpi.js`, `api/crud_direccion.php` | 6 h |
| P2-5 | Restablecer contraseña desde panel admin | `pages/admin.php`, `api/crud_admin.php` | 5 h |
| P2-6 | Dropdown de obra única en KPI (UX — selector rápido) | `pages/reportes_kpi.php`, `assets/js/reportes_kpi.js` | 2 h |

### P3 — Medio (segundo sprint)

| ID | Tarea | Archivos | Esfuerzo |
|---|---|---|---|
| P3-1 | Lógica de Command Palette (Ctrl+K — HTML ya existe) | `assets/js/v4-layout.js`, `includes/layout_top.php` | 10 h |
| P3-2 | Notificaciones badge en topbar (polling 60 s) | `api/crud_notifications.php`, `assets/js/v4-layout.js` | 8 h |
| P3-3 | Búsqueda combinada de proveedor (nombre/RFC/CLABE) | `pages/nueva_requisicion.php`, `pages/hojas_requisicion.php` | 4 h |
| P3-4 | Timeline visual de estados en detalle de requisición | `pages/requisiciones.php` | 4 h |
| P3-5 | Alert de tope al ligar requisiciones a presiones | `pages/enlazar_requisiciones.php`, `assets/js/enlazar_requisiciones.js` | 2 h |
| P3-6 | Ocultar botones CRUD para roles sin permiso (generalizar `TF.can()`) | Todas las páginas | 4 h |
| P3-7 | Tab "Pendientes de autorización" en all_presiones | `pages/all_presiones.php`, `assets/js/all_presiones.js` | 3 h |
| P3-8 | Columna monto subtotal por hoja en hojas_requisicion | `pages/hojas_requisicion.php`, `api/crud_hojas_requisicion.php` | 3 h |
| P3-9 | Importación CFDI XML (SAT) en all_presiones | `pages/all_presiones.php`, `assets/js/all_presiones.js` + migración 012 | 5 h |

### P4 — Bajo (calidad y deuda técnica)

| ID | Tarea | Esfuerzo |
|---|---|---|
| P4-1 | Eliminar `assets/lib/vue/` y `assets/js/legacy/` del repo (ninguna página activa los usa) | 2 h |
| P4-2 | Pruebas E2E con Playwright (login, crear requisición, autorizar presión, pagar) | 12 h |
| P4-3 | Activar 2FA TOTP (tabla `two_factor_tokens` ya existe) | 8 h |
| P4-4 | PDF server-side con dompdf (reemplazar jsPDF cliente) | 8 h |
| P4-5 | Importar ítems desde Excel en items_requisicion | 6 h |
| P4-6 | Plantillas de requisición (guardar/reutilizar) | 8 h |
| P4-7 | Tablas responsivas en móvil (card-stack con CSS) | 4 h |
| P4-8 | Migraciones BD 013 (fechas estado presión) y 014 (presupuesto obras) + KPIs derivados | 2 h SQL + 4 h UI |

---

## KPIs propuestos para el dashboard (pendientes)

Una vez aplicadas las migraciones 013 y 014:

| KPI | Fórmula | Semáforo |
|---|---|---|
| Ciclo de autorización | `AVG(DATEDIFF(presiones_fechaAutorizado, presiones_fechaCreacion))` | Verde ≤5 días / Amarillo 5-10 / Rojo >10 |
| Ahorro por negociación | `SUM(presiones_gastosObra) - SUM(presiones_adeudo)` | Verde si ahorro > 0 |
| % Presupuesto ejecutado | `SUM(adeudo) / obras_presupuesto * 100` por obra | Verde <80% / Amarillo 80-100% / Rojo >100% |
| Adeudo por proveedor | `SUM(hojas) GROUP BY proveedor` | Tabla colapsable en KPI de obra única |

---

## Sprints sugeridos

### Sprint 1 — Estabilización (~24 h)
Resolver conflictos git · Fix PDF watermark · CDN duplicada · Reactivar roles · Validar alcance de obra · Modal de pago · UI comentario director · Ocultar botones CRUD para lector

### Sprint 2 — Flujos completos (~39 h)
Filtros auditoría · Filtro período KPI · Selector obra única KPI · Command Palette · Notificaciones badge · Timeline requisición · Tab "Pendientes autorización" · Monto subtotal por hoja

### Sprint 3 — Calidad y trazabilidad (~40 h)
Importación CFDI XML · Búsqueda proveedor RFC+CLABE · Pruebas E2E Playwright · Migraciones 012-014 · Eliminar Vue legacy · Tablas responsivas móvil

### Fases futuras
- Sprint 4: 2FA (P4-3), PDF server-side (P4-4), Plantillas (P4-6)
- Sprint 5+: API REST separada, colas de trabajo asíncronas, observabilidad (Monolog)

---

## Documentos de referencia

| Archivo | Descripción |
|---|---|
| `PROPUESTAS_MEJORA_2026.md` | Detalle completo P1–P4 con esfuerzo y archivos afectados (revisión 12-jun-2026) |
| `REVISION_2026_06_22.md` | Auditoría de trazabilidad logística: conflictos git, CFDI XML, anomalías BD, fixes de urgencia |
| `consultas_legacy.sql` | Consultas SQL de referencia (no se ejecutan en runtime) |
| `api/migrations/` | Historial de migraciones SQL versionadas |

---

*Documento generado el 23 de junio de 2026 — consolida FASE2–FASE08, STATUS_MEJORAS, REVISION_2026_05_27, MEJORAS y ROLES.*
