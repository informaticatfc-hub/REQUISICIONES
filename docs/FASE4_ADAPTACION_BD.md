# Fase 4 — Adaptacion a BD real + Fix CSP

Rama: `genspark_ai_developer_v2_fase4` → PR de seguimiento de PR #4.

## 1) Fix CSP — desbloquea source maps de los CDN

### Problema reportado en produccion (al subir PR #3)

```
Connecting to 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js.map'
violates the following Content Security Policy directive: "connect-src 'self'".
The request has been blocked.

Connecting to 'https://cdn.jsdelivr.net/npm/axios@1.7.2/dist/axios.min.js.map'
violates the following Content Security Policy directive: "connect-src 'self'".
The request has been blocked.

Connecting to 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css.map'
violates the following Content Security Policy directive: "connect-src 'self'".
The request has been blocked.
```

### Causa raiz

Los archivos `.map` (source maps) que Bootstrap, axios y otras libs CDN solicitan
**no se cargan como `<script>`**: el navegador los pide por `fetch()` para mapear
codigo minificado al original al hacer debugging. Por eso caen bajo la directiva
`connect-src` del CSP, no bajo `script-src`. El CSP original tenia
`connect-src 'self'`, asi que los bloqueaba todos.

### Solucion

`api/rbac.php` -> `tf_security_headers()` ahora:

1. Anade los CDNs (`jsdelivr`, `datatables`, `unpkg`) a `connect-src`.
2. Anade `script-src-elem` y `style-src-elem` (CSP3, algunos navegadores los
   exigen aparte de `script-src` / `style-src`).
3. Permite `https://cdn.jsdelivr.net` en `img-src` para sprites/iconos del CDN.
4. Hardening extra: `base-uri 'self'`, `form-action 'self'`, `object-src 'none'`.

```php
$csp = "default-src 'self'; "
     . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.datatables.net https://code.jquery.com https://unpkg.com; "
     . "script-src-elem 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.datatables.net https://code.jquery.com https://unpkg.com; "
     . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.datatables.net https://fonts.googleapis.com; "
     . "style-src-elem 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.datatables.net https://fonts.googleapis.com; "
     . "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; "
     . "img-src 'self' data: https://cdn.jsdelivr.net; "
     . "connect-src 'self' https://cdn.jsdelivr.net https://cdn.datatables.net https://unpkg.com; "
     . "frame-ancestors 'self'; "
     . "base-uri 'self'; "
     . "form-action 'self'; "
     . "object-src 'none'";
```

### Verificacion en produccion

1. Refrescar la pagina y abrir DevTools -> Console.
2. **No deben aparecer mas warnings de `.map`** desde `cdn.jsdelivr.net`.
3. Network tab: las requests de `.map` deben retornar **200 OK** (o 404 si el CDN no las publica, pero **sin bloqueo CSP**).
4. Funcionalidad de la app: identica (los `.map` no afectan la ejecucion, solo el debugging).


## 2) Migracion SQL `2026_05_14_002_adaptacion_bd_real.sql`

### Contexto

Al importar el dump real `u701868959_TheFuentesBD_13_05_2026.sql` se detectaron
gaps entre la BD real y lo que el codigo de Fase 2 + Fase 3 espera. Esta
migracion los cierra de forma idempotente.

### Gaps cerrados

| Lo que el codigo espera | Estado previo a la migracion |
|---|---|
| Tabla `roles`, `permissions`, `role_permissions` | No existian |
| Tabla `audit_log` | No existia (solo `logs` legacy vacia) |
| `users.user_role_id` | Existia `id_rol` BIGINT (FK a `rol_usuario`) |
| `users.user_email` | Existia `Email` con mayuscula |
| `users.user_estatus` ENUM | Existia `id_estado_usuario` FK |
| `users.user_lastLogin` | Existia `ultimo_login` |
| `user_password` >= 255 chars (bcrypt/argon2) | Solo 100 chars |
| `hojaRequisicion_FechaSolicitud` NULLable | Era NOT NULL (rompia `crud_nueva_hoja.php`) |
| FK integra en `requisicionesligadas` | Filas con `hojaID` inexistentes |
| Indices en estatus/fechas | Faltaban en `provedores`, `obras`, `presiones`, `requisiciones`, etc. |

### Bloques de la migracion (13 secciones)

1. **Columnas RBAC en `users`** + ampliar `user_password` a `VARCHAR(255)` + indices `ix_users_estatus`, `ix_users_email`, `ix_users_nameUser`.
2. **Tablas RBAC** (`roles`, `permissions`, `role_permissions`) — idempotente con `CREATE TABLE IF NOT EXISTS` + `ON DUPLICATE KEY UPDATE` para los seeds.
3. **FK `users.user_role_id -> roles.role_id`** condicional via `information_schema`.
4. **Migracion de datos legacy:**
   - `id_rol` (1/2/4 Superadmin/Admin/Developer) -> `admin`
   - `id_rol` (3/5 Editor/Usuario) -> `residente`
   - `id_rol` (6 Invitado) -> `lector`
   - `user_directionAcess=1` sin rol -> `director` (compat Fase 2)
   - Resto sin rol -> `residente` (default seguro)
   - `Email` -> `user_email`
   - `id_estado_usuario` -> `user_estatus` (2/4 = INACTIVO)
   - `ultimo_login` -> `user_lastLogin`
5. **`audit_log`** creada con indices compuestos para queries por modulo/fecha. `logs` legacy marcada como DEPRECATED (no se borra).
6. **Fix schema `hojasrequisicion`** — `hojaRequisicion_FechaSolicitud` ahora `NULL` permitido + indices `ix_hojasreq_estatus`, `ix_hojasreq_fechapago`.
7. **Saneamiento `requisicionesligadas`** — mueve filas huerfanas a `_quarantine_requisicionesligadas` con motivo (`hoja_inexistente` / `requisicion_inexistente` / `presion_inexistente`) en lugar de borrarlas. **No se pierden datos.**
8. **Indices de rendimiento** en `provedores.proveedor_estatus`, `obras.obras_estatus`, `emisores.emisor_estatus`, `bancos.banco_activo`, `presiones.presiones_estatus`/`fechaCreacion`, `requisiciones.requisicion_estatus`/`fechaSolicitud`.
9. Verificacion de FKs de `users` (placeholder).
10. **Indices para `two_factor_tokens`** (`user_id`, `token`, `fecha_expiracion`, `utilizado`) — listo para activar 2FA en Fase 5.
11. **Permisos nuevos** que aparecieron al hardenear los CRUDs en Fase 3: `hojas.view/delete/changeProveedor/changeFormaPago/ligar/toRevision/toPendiente/pagada`, `items.create/edit/delete` — con asignaciones por rol.
12. **Vistas utiles para reportes:**
    - `v_users_full` — usuarios + rol + conteo de permisos (simplifica panel admin).
    - `v_presiones_summary` — presiones con totales/adeudo calculados desde las hojas reales (los campos `presiones_adeudo` y `presiones_gastosObra` estan en `0.00` en el dump).
    - `v_requisiciones_summary` — requisiciones con conteo real de hojas y desglose por estatus.
13. **Bootstrap admin** — si nadie tiene rol admin, asciende a `IrvinDev` automaticamente para no quedar bloqueado.

### Caracteristicas

- **Idempotente:** se puede correr varias veces sin romper nada (`IF NOT EXISTS`, `ON DUPLICATE KEY UPDATE`, comprobaciones via `information_schema`).
- **No destructiva:** ninguna columna ni tabla legacy se borra. `logs` queda como deprecated, `rol_usuario` coexiste con `roles`, `Email` coexiste con `user_email`.
- **Datos preservados:** las filas huerfanas en `requisicionesligadas` van a `_quarantine_requisicionesligadas` para revision manual.
- **Migracion incremental de hashes** de password ya prevista: `VARCHAR(255)` deja espacio para `password_hash()` (logica ya activa en `LoginAcces.php` desde Fase 2).

### Aplicacion

```bash
# Backup primero
mysqldump -u USUARIO -p u701868959_TheFuentesBD > backup_pre_002.sql

# Aplicar
mysql -u USUARIO -p u701868959_TheFuentesBD < api/migrations/2026_05_14_002_adaptacion_bd_real.sql

# Verificar
mysql -u USUARIO -p u701868959_TheFuentesBD -e "
SELECT 'roles' tabla, COUNT(*) FROM roles
UNION ALL SELECT 'permissions', COUNT(*) FROM permissions
UNION ALL SELECT 'role_permissions', COUNT(*) FROM role_permissions
UNION ALL SELECT 'audit_log', COUNT(*) FROM audit_log
UNION ALL SELECT 'users sin rol', COUNT(*) FROM users WHERE user_role_id IS NULL
UNION ALL SELECT 'cuarentena RL', COUNT(*) FROM _quarantine_requisicionesligadas;"
```


## 3) Pendiente (PR sucesora — Fase 4 continuacion)

- Migrar 11 paginas restantes al layout v4:
  - `presiones`, `all_presiones`, `presiones_detalles`
  - `hojas_requisicion`, `nueva_hoja`, `items_requisicion`
  - `enlazar_requisiciones`
  - `proveedores`, `agregar_proveedor`, `bancos`, `direccion`
  - `catalago`, `menu_catalago`
- Eliminar `localStorage.NameUser` de los ~17 JS legacy restantes.
- Refactorizar `crud_admin.php` para consumir `v_users_full`.
- Refactorizar `crud_all_presiones.php` para consumir `v_presiones_summary`.
- Deprecar `assets/js/layout_sidebar.js` y `assets/css/main.css`.
- Activar 2FA (tabla ya existe + indices) — Fase 5.
- Tests automatizados (PHPUnit + Playwright) — diferible.
- HSTS — solo produccion.
- Decision Vue 3 vs Alpine.js — Fase 5.
