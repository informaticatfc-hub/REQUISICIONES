# Sistema de Roles y Permisos — The Fuentes Workspace v4

> Propuesta de RBAC (Role-Based Access Control) para reemplazar la bandera
> única `users.user_directionAcess` por un sistema de roles granular.

---

## 1. Roles definidos

| Código | Nombre | Descripción |
|---|---|---|
| `admin` | Administrador | Acceso total al sistema, gestión de usuarios y roles. |
| `director` | Dirección / CEO | Visibilidad global y autorización de presiones. |
| `compras` | Compras | Crea/valida requisiciones, gestiona catálogo de proveedores. |
| `finanzas` | Finanzas / Tesorería | Marca presiones como pagadas, controla bancos. |
| `residente` | Residente de obra | Crea requisiciones de las obras que le fueron asignadas. |
| `lector` | Lector / Auditor | Sólo lectura sobre toda la información. |

Un usuario puede tener **varios roles** simultáneamente (ej. `compras + residente`).

---

## 2. Matriz de permisos

Leyenda: ✅ = permitido · ✏️ = sólo en sus obras asignadas · ❌ = denegado · 👁️ = sólo lectura

| Recurso / Acción | admin | director | compras | finanzas | residente | lector |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| **Usuarios** |  |  |  |  |  |  |
| Ver usuarios | ✅ | 👁️ | ❌ | ❌ | ❌ | 👁️ |
| Crear / editar usuarios | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Asignar roles | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Obras** |  |  |  |  |  |  |
| Ver todas las obras | ✅ | ✅ | ✅ | ✅ | ✏️ | 👁️ |
| Crear obra | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Editar obra | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Asignar residentes | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Requisiciones** |  |  |  |  |  |  |
| Ver requisiciones | ✅ | ✅ | ✅ | ✅ | ✏️ | 👁️ |
| Crear requisición | ✅ | ❌ | ✅ | ❌ | ✏️ | ❌ |
| Editar borrador propio | ✅ | ❌ | ✅ | ❌ | ✏️ | ❌ |
| Validar requisición | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Rechazar | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ |
| Eliminar | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| **Presiones (pagos)** |  |  |  |  |  |  |
| Ver presiones | ✅ | ✅ | ✅ | ✅ | ✏️ | 👁️ |
| Crear presión | ✅ | ❌ | ✅ | ✅ | ❌ | ❌ |
| Autorizar presión | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| Marcar como pagada | ✅ | ✅ | ❌ | ✅ | ❌ | ❌ |
| **Catálogos** |  |  |  |  |  |  |
| Ver proveedores | ✅ | ✅ | ✅ | ✅ | 👁️ | 👁️ |
| Crear / editar proveedor | ✅ | ❌ | ✅ | ❌ | ❌ | ❌ |
| Ver bancos | ✅ | ✅ | 👁️ | ✅ | ❌ | 👁️ |
| Editar bancos | ✅ | ❌ | ❌ | ✅ | ❌ | ❌ |
| **Reportes / PDF** |  |  |  |  |  |  |
| Generar PDF requisición | ✅ | ✅ | ✅ | ✅ | ✏️ | 👁️ |
| Exportar Excel | ✅ | ✅ | ✅ | ✅ | ✏️ | ✅ |
| Dashboard KPIs globales | ✅ | ✅ | ✅ | ✅ | ❌ | ✅ |
| **Auditoría** |  |  |  |  |  |  |
| Ver log de cambios | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| **Sistema** |  |  |  |  |  |  |
| Configuración general | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |
| Ver logs técnicos | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 3. Modelo de datos propuesto

```sql
-- Catálogo de roles
CREATE TABLE roles (
    role_id      INT PRIMARY KEY AUTO_INCREMENT,
    role_code    VARCHAR(40)  NOT NULL UNIQUE,   -- 'admin', 'compras', ...
    role_name    VARCHAR(80)  NOT NULL,
    role_desc    VARCHAR(255),
    role_active  TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Catálogo de permisos atómicos
CREATE TABLE permissions (
    permission_id    INT PRIMARY KEY AUTO_INCREMENT,
    permission_code  VARCHAR(80) NOT NULL UNIQUE, -- 'requisicion.create'
    permission_group VARCHAR(40) NOT NULL,        -- 'requisiciones'
    permission_desc  VARCHAR(255)
);

-- Relación N:M roles ↔ permisos
CREATE TABLE role_permissions (
    role_id        INT NOT NULL,
    permission_id  INT NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id)       REFERENCES roles(role_id)       ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(permission_id) ON DELETE CASCADE
);

-- Relación N:M usuarios ↔ roles
CREATE TABLE user_roles (
    user_id     INT NOT NULL,
    role_id     INT NOT NULL,
    assigned_by INT NULL,
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id)     REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id)     REFERENCES roles(role_id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Asignación de obras a usuarios (residentes)
CREATE TABLE user_obras (
    user_id  INT NOT NULL,
    obras_id INT NOT NULL,
    PRIMARY KEY (user_id, obras_id),
    FOREIGN KEY (user_id)  REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (obras_id) REFERENCES obras(obras_id) ON DELETE CASCADE
);

-- Bitácora de auditoría
CREATE TABLE audit_log (
    log_id      BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id     INT NOT NULL,
    action      VARCHAR(80)  NOT NULL,   -- 'requisicion.update'
    entity      VARCHAR(40)  NOT NULL,   -- 'requisicion'
    entity_id   INT          NULL,
    payload     JSON         NULL,
    ip          VARCHAR(45)  NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_user (user_id),
    INDEX idx_audit_entity (entity, entity_id)
);
```

### 3.1. Migración desde el esquema actual

```sql
-- 1. Crear roles base
INSERT INTO roles (role_code, role_name) VALUES
  ('admin',     'Administrador'),
  ('director',  'Direccion'),
  ('compras',   'Compras'),
  ('finanzas',  'Finanzas'),
  ('residente', 'Residente de obra'),
  ('lector',    'Lector / Auditor');

-- 2. Migrar usuarios existentes:
--    quienes tengan user_directionAcess = 1  →  rol director
--    el resto                                →  rol residente (default)
INSERT INTO user_roles (user_id, role_id)
SELECT u.user_id, r.role_id
FROM   users u
JOIN   roles r ON r.role_code = 'director'
WHERE  u.user_directionAcess = 1;

INSERT INTO user_roles (user_id, role_id)
SELECT u.user_id, r.role_id
FROM   users u
JOIN   roles r ON r.role_code = 'residente'
WHERE  u.user_directionAcess = 0
   OR  u.user_directionAcess IS NULL;

-- 3. Mantener user_directionAcess deprecated (no eliminar aún por compatibilidad).
```

---

## 4. Catálogo de permisos atómicos

Convención: `recurso.accion`

```
usuarios.view, usuarios.create, usuarios.update, usuarios.delete, usuarios.assign_role
obras.view, obras.view_all, obras.create, obras.update, obras.assign_user
requisicion.view, requisicion.view_all, requisicion.create, requisicion.update_own,
requisicion.update_any, requisicion.validate, requisicion.reject, requisicion.delete
presion.view, presion.view_all, presion.create, presion.authorize, presion.pay
proveedor.view, proveedor.create, proveedor.update, proveedor.delete
banco.view, banco.create, banco.update, banco.delete
reporte.pdf, reporte.excel, reporte.dashboard
auditoria.view
sistema.config, sistema.logs
```

---

## 5. Implementación PHP

### 5.1. Middleware `Auth.php`

```php
<?php
// api/Auth.php
class Auth {
    private static $user = null;
    private static $perms = null;

    public static function user(PDO $pdo): array {
        if (self::$user) return self::$user;
        if (empty($_SESSION['UsuarioId'])) self::deny(401, 'Sesion no valida');

        $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ? LIMIT 1');
        $stmt->execute([$_SESSION['UsuarioId']]);
        self::$user = $stmt->fetch(PDO::FETCH_ASSOC) ?: self::deny(401, 'Usuario no encontrado');
        return self::$user;
    }

    public static function permissions(PDO $pdo): array {
        if (self::$perms !== null) return self::$perms;
        $u = self::user($pdo);
        $sql = "SELECT DISTINCT p.permission_code
                FROM   user_roles ur
                JOIN   role_permissions rp ON rp.role_id = ur.role_id
                JOIN   permissions p       ON p.permission_id = rp.permission_id
                WHERE  ur.user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([(int)$u['user_id']]);
        self::$perms = array_column($stmt->fetchAll(), 'permission_code');
        return self::$perms;
    }

    public static function can(PDO $pdo, string $permission): bool {
        return in_array($permission, self::permissions($pdo), true);
    }

    public static function require(PDO $pdo, string $permission): void {
        if (!self::can($pdo, $permission)) {
            self::deny(403, "No tienes permiso: {$permission}");
        }
    }

    public static function requireAny(PDO $pdo, array $permissions): void {
        foreach ($permissions as $p) if (self::can($pdo, $p)) return;
        self::deny(403, 'No tienes ninguno de los permisos requeridos');
    }

    public static function ownsObra(PDO $pdo, int $obraId): bool {
        $u = self::user($pdo);
        // admin/director/compras/finanzas ven todas
        if (self::can($pdo, 'obras.view_all')) return true;
        $stmt = $pdo->prepare('SELECT 1 FROM user_obras WHERE user_id = ? AND obras_id = ?');
        $stmt->execute([(int)$u['user_id'], $obraId]);
        return (bool)$stmt->fetchColumn();
    }

    private static function deny(int $code, string $msg) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => true, 'message' => $msg]);
        exit;
    }
}
```

### 5.2. Uso en endpoints

```php
// api/crud_Requisiciones.php
require_once 'conexion.php';
require_once 'auth.php';
require_once 'Auth.php';

$pdo = (new Conexion())->Conectar();
$data = api_get_request_data();
$accion = $data['accion'] ?? '';

switch ($accion) {
    case 'list':
        Auth::require($pdo, 'requisicion.view');
        // …
        break;

    case 'create':
        Auth::require($pdo, 'requisicion.create');
        $obraId = api_require_positive_int($data['obra_id'] ?? 0, 'Obra invalida');
        if (!Auth::ownsObra($pdo, $obraId)) {
            Auth::deny(403, 'No tienes esa obra asignada');
        }
        // …
        break;

    case 'validate':
        Auth::require($pdo, 'requisicion.validate');
        // …
        break;
}
```

### 5.3. Endpoint `GET /api/me`

Reemplaza el patrón inseguro `localStorage.setItem("NameUser", user_id)`.

```php
// api/me.php
require_once 'conexion.php';
require_once 'Auth.php';
$pdo = (new Conexion())->Conectar();
$u   = Auth::user($pdo);
$perms = Auth::permissions($pdo);
$roles = $pdo->prepare(
    'SELECT r.role_code, r.role_name
     FROM   user_roles ur JOIN roles r ON r.role_id = ur.role_id
     WHERE  ur.user_id = ?'
);
$roles->execute([(int)$u['user_id']]);

echo json_encode([
    'user' => [
        'id'    => (int)$u['user_id'],
        'name'  => $u['user_name'],
        'login' => $u['user_nameUser'],
    ],
    'roles'       => $roles->fetchAll(PDO::FETCH_ASSOC),
    'permissions' => $perms,
]);
```

En el front:

```js
// app.js — al cargar
const { data } = await axios.get('/api/me.php');
window.tfUser = data;            // disponible globalmente
window.tfCan = (p) => data.permissions.includes(p);
```

Y los `v-if` en plantillas ahora son:

```html
<button v-if="tfCan('requisicion.create')" @click="nuevaRequisicion">Nueva</button>
<a v-if="tfCan('presion.authorize')" href="./direccion.php">Direccion</a>
```

---

## 6. Vistas por rol (UI condicional)

| Página | Visible para |
|---|---|
| `/` (Dashboard) | Todos (contenido cambia según permisos) |
| `obras.php` | Todos (filtra por `user_obras` si no es admin/director/compras) |
| `requisiciones.php` | Todos (filtra por obra) |
| `nueva_requisicion.php` | `requisicion.create` |
| `presiones.php` | `presion.view` |
| `direccion.php` | `presion.authorize` |
| `menu_catalago.php` | `proveedor.view` ó `banco.view` |
| `proveedores.php` | `proveedor.view` |
| `bancos.php` | `banco.view` |
| `admin/usuarios.php` | `usuarios.view` (sólo admin) |
| `admin/roles.php` | `usuarios.assign_role` |
| `admin/auditoria.php` | `auditoria.view` |

En la **topbar**, los enlaces que el usuario no puede usar **no se renderizan**
(no se ocultan con CSS, se omiten del DOM).

---

## 7. Gestión de roles desde la UI

Nueva sección `Admin → Usuarios` para administradores:

- Tabla con: nombre, login, roles asignados (badges), última sesión.
- Botones: editar, asignar roles, asignar obras, desactivar.
- Modal "Asignar roles": checkboxes con los 6 roles, descripción al lado.
- Modal "Asignar obras" (para `residente`): selector múltiple de obras.
- Confirmación con SweetAlert2.

---

## 8. Auditoría

Cada acción sensible escribe en `audit_log`:

```php
function audit(PDO $pdo, string $action, string $entity, ?int $entityId, $payload = null) {
    $stmt = $pdo->prepare(
        'INSERT INTO audit_log (user_id, action, entity, entity_id, payload, ip)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $_SESSION['UsuarioId'] ?? 0,
        $action, $entity, $entityId,
        $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}
```

Eventos auditados:
- Login / logout / login fallido.
- Crear / actualizar / eliminar requisición.
- Validar / autorizar / rechazar.
- Marcar como pagada.
- Asignar / quitar rol.
- Cambiar contraseña.

Vista `admin/auditoria.php` con filtros (usuario, fecha, acción) y export
a Excel.

---

## 9. Reglas adicionales

- **Sesión efímera**: token de sesión válido sólo 8 h, refresco automático
  si hay actividad. Inactividad de 30 min → logout.
- **2FA opcional** para roles `admin`, `director`, `finanzas` (TOTP).
- **Cambio obligatorio de contraseña** en primer login.
- **Bloqueo** tras 5 intentos fallidos en 10 min.
- **Política de contraseñas**: 10 caracteres mínimo, alfanumérica + símbolo.

---

## 10. Estado de migración

| Tarea | Estado |
|---|---|
| Documento de roles | ✅ Listo (este archivo) |
| Tablas SQL nuevas | 🟡 Pendiente de aplicar |
| Middleware `Auth.php` | 🟡 Listo el diseño, pendiente implementar |
| Endpoint `/api/me` | 🟡 Pendiente |
| UI condicional con `tfCan()` | 🟡 Pendiente |
| Sección Admin → Usuarios | 🟡 Pendiente |
| Auditoría | 🟡 Pendiente |

---

*Forma parte de la propuesta de modernización v4 — ver [`MEJORAS.md`](./MEJORAS.md).*
