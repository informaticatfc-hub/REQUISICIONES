# Fase 2 — RBAC, sesión segura, CSRF y administración

Documento técnico de la Fase 2 del rediseño de **The Fuentes Workspace**.

## 1. Objetivos cumplidos

| Objetivo | Estado | Archivos |
|---|---|---|
| RBAC en backend (roles + permisos) | ✅ | `api/migrations/2026_05_14_001_create_rbac.sql`, `api/rbac.php` |
| Sesión segura (httpOnly, SameSite, regeneración) | ✅ | `api/rbac.php` → `tf_session_start()` |
| Eliminar dependencia de `localStorage.NameUser` en páginas migradas | ✅ | `pages/index.php`, `pages/obras.php`, `pages/menu_catalago.php`, `pages/admin.php` |
| CSRF tokens en formularios y peticiones AJAX | ✅ | `api/rbac.php` → `tf_csrf_token()`, `tf_csrf_validate()` |
| Limpieza de BOM en archivos PHP/JS/CSS | ✅ | 34 archivos limpiados |
| Cabeceras de seguridad (CSP, X-Frame, etc.) | ✅ | `.htaccess`, `api/rbac.php` → `tf_security_headers()` |
| Migrar `pages/obras.php` | ✅ | layout v4 + verificación de permisos |
| Migrar `pages/menu_catalago.php` | ✅ | layout v4 + permisos `proveedores.manage` / `bancos.manage` |
| Pantalla de administración de usuarios y roles | ✅ | `pages/admin.php`, `api/crud_admin.php` |
| Audit log de acciones críticas | ✅ | Tabla `audit_log` + `tf_audit_log()` |

## 2. Modelo de datos RBAC

### Tablas nuevas

```text
roles              → 6 roles base (admin, director, compras, finanzas, residente, lector)
permissions        → 25 permisos en 6 módulos
role_permissions   → pivot (n a n)
audit_log          → bitácora de eventos
```

### Columnas añadidas a `users`

| Columna | Tipo | Propósito |
|---|---|---|
| `user_role_id` | `INT UNSIGNED NULL` | FK a `roles.role_id` |
| `user_email` | `VARCHAR(120) NULL` | Contacto opcional |
| `user_estatus` | `ENUM('ACTIVO','INACTIVO')` | Permite desactivar sin borrar |
| `user_lastLogin` | `TIMESTAMP NULL` | Último acceso correcto |

### Migración automática de usuarios existentes

- `user_directionAcess = 1` → rol **director**
- Resto → rol **residente** (default seguro, ajustable desde `pages/admin.php`)

### Cómo aplicar la migración

```bash
mysql -u <user> -p <db> < api/migrations/2026_05_14_001_create_rbac.sql
```

La migración es **idempotente** (usa `IF NOT EXISTS`, `ON DUPLICATE KEY UPDATE`). El rollback está al final del archivo, comentado.

## 3. Matriz de permisos por rol

| Permiso | admin | director | compras | finanzas | residente | lector |
|---|:-:|:-:|:-:|:-:|:-:|:-:|
| obras.view | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| obras.create / edit / delete | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| requisiciones.view | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| requisiciones.create / edit | ✅ | ✅ | ✅ | ❌ | ✅ | ❌ |
| requisiciones.validate | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| requisiciones.authorize | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| presiones.view | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| presiones.create / edit | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| presiones.authorize | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| catalogos.view | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| proveedores.manage | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| bancos.manage | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| direccion.* | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| admin.users.view | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| admin.users.create / delete | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| admin.users.edit | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| admin.roles.manage | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| admin.audit.view | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |

## 4. API de RBAC (PHP)

```php
require_once __DIR__ . '/../api/rbac.php';

// Sesión segura
tf_session_start();
tf_security_headers();

// CSRF
$token = tf_csrf_token();      // generar/obtener
echo tf_csrf_field();          // <input hidden> para forms
tf_csrf_validate($payload);    // valida y aborta 403 si falla

// Usuario actual con rol y permisos
$user = tf_current_user($pdo);
// $user['role']['code']        => 'admin' | 'director' | ...
// $user['permissions']         => ['obras.view', 'requisiciones.create', ...]

// Helpers de autorización
tf_has_permission('obras.edit', $user);          // bool
tf_require_permission($pdo, 'obras.edit');       // aborta 403 si no
tf_require_role($pdo, ['admin','director']);     // aborta 403 si no
tf_require_min_level($pdo, 60);                  // aborta si nivel < 60

// Auditoría
tf_audit_log($pdo, 'requisicion.create', 'requisiciones', $reqId, ['monto' => 12345]);
```

## 5. API JS (cliente)

El template inyecta automáticamente:

```js
window.TF_CONTEXT = {
    user: {
        id: 7,
        name: "Alan Fuentes",
        role: "Direccion",
        roleCode: "director",
        initials: "AF",
        permissions: ["obras.view", "requisiciones.create", ...]
    },
    page:  { title: "Inicio", active_nav: "inicio" },
    csrf:  "abc123..."
};

// Helper rápido
TF.can('requisiciones.create');   // true | false
```

Axios queda preconfigurado con:

- `withCredentials: true` (envía cookie de sesión)
- `X-CSRF-Token` automático en cada petición

## 6. Pantalla de administración (`pages/admin.php`)

Características:

- **KPIs** de usuarios totales / activos / inactivos / roles
- **Tabla de usuarios** con búsqueda (`v-model="filterText"`)
- **Cambio de rol en línea** (select dropdown, solo con `admin.roles.manage`)
- **Activar / desactivar** usuarios (no permite auto-desactivarse)
- **Modal de creación / edición** con validación de contraseña ≥ 8 chars
- **Bitácora de auditoría** (últimos 50 eventos, scroll vertical)
- **Permisos contextuales**: botones se ocultan si el usuario no los tiene

## 7. Cabeceras de seguridad (`.htaccess`)

```apache
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
Referrer-Policy: strict-origin-when-cross-origin
Permissions-Policy: geolocation=(), microphone=(), camera=()
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net …
```

Además bloquea acceso directo a:

- `*.sql`, `*.md`, `*.log`, `*.env`, `.htaccess`, `.htpasswd`
- `/api/migrations/*` (devuelve 403)

## 8. Endpoints `api/crud_admin.php`

| Acción | Permiso requerido | CSRF | Descripción |
|---|---|:-:|---|
| 1 | `admin.users.view` | ❌ | Listar usuarios + rol |
| 2 | `admin.users.view` | ❌ | Listar roles |
| 3 | `admin.users.create` | ✅ | Crear usuario |
| 4 | `admin.users.edit` | ✅ | Actualizar nombre/email/password |
| 5 | `admin.roles.manage` | ✅ | Cambiar rol (no auto-cambio) |
| 6 | `admin.users.edit` | ✅ | Activar/desactivar (no auto-desactivación) |
| 7 | `admin.audit.view` | ❌ | Listar audit_log reciente |

## 9. Compatibilidad y deuda técnica

### Conservado para retrocompat (eliminar en Fase 3)
- `localStorage.NameUser`: 18 archivos JS legacy aún lo leen; `login.js` lo sigue grabando.
- Vue 2.7.16: se mantiene durante la migración de páginas restantes.

### Páginas todavía con sidebar antiguo (pendientes Fase 3)
`agregar_proveedor.php`, `all_presiones.php`, `bancos.php`, `catalago.php`, `direccion.php`, `enlazar_requisiciones.php`, `hojas_requisicion.php`, `items_requisicion.php`, `nueva_hoja.php`, `nueva_requisicion.php`, `presiones.php`, `presiones_detalles.php`, `proveedores.php`, `requisiciones.php`.

## 10. Próximos pasos (Fase 3)

1. Migrar el resto de páginas al layout v4
2. Aplicar `tf_require_permission()` en cada `crud_*.php`
3. Eliminar definitivamente `localStorage.NameUser`
4. Sustituir Vue 2 → Vue 3 o Alpine.js (Vue 2 está EOL desde dic-2023)
5. Tests automatizados (PHPUnit + Playwright)
6. Activar HSTS en producción
