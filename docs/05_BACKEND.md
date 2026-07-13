# BACKEND — Arquitectura, Datos y Seguridad
## The Fuentes Workspace — Sistema de Requisiciones v4.1

| Campo | Valor |
|---|---|
| **Fecha** | 13 de julio de 2026 |
| **Stack** | PHP (sin framework) + PDO · MariaDB 11.8.6 · Apache (Hostinger) |
| **Documentos relacionados** | [04_FLUJOS.md](04_FLUJOS.md) (flujos) · [02_TDR.md](02_TDR.md) (alcance) |

---

## 1. Arquitectura general

Monolito PHP de dos capas servido por Apache, sin build step ni framework:

```
Navegador (Alpine.js 3 + Axios)
   │  HTML server-rendered + JSON (AJAX)
   ▼
Apache / PHP
   ├── pages/*.php ............ Vistas (server-rendered + app Alpine por página)
   ├── includes/ .............. Layout v4 (layout_top/bottom) y navbar legacy
   ├── validarSesion.php ...... Guard de sesión para páginas
   └── api/
       ├── conexion.php ....... Clase Conexion (PDO, ERRMODE_EXCEPTION)
       ├── rbac.php ........... Núcleo transversal: sesión, CSRF, permisos, auditoría
       ├── auth.php ........... Wrapper de rbac.php para los CRUD
       ├── LoginAcces.php ..... Autenticación + rate limiting
       ├── bitacora.php ....... Helpers y consulta de audit_log / hoja_estatus_log
       ├── crud_*.php ......... Un endpoint por módulo, dispatch por `accion: N`
       └── migrations/ ........ SQL versionado (001–014 aplicadas)
   │
   ▼
MariaDB u701868959_TFC (21 tablas + 5 vistas)
```

**Decisión vigente**: se mantiene el patrón `crud_*.php?accion=N`. La migración a router REST (Slim o custom) queda como fase futura A1; mientras tanto, toda mejora respeta el patrón actual.

---

## 2. Núcleo transversal (`api/rbac.php`)

| Función | Responsabilidad |
|---|---|
| `tf_session_start()` | Sesión `TF_SESSID` httpOnly + SameSite=Lax + Secure (si HTTPS); regeneración de ID al crear y cada 30 min |
| `tf_csrf_token()` / `tf_csrf_validate()` | Token de 32 bytes por sesión; se valida desde header `X-CSRF-Token` o `_csrf` en body; 403 al fallar |
| `tf_current_user($pdo)` | Usuario + rol + permisos + obras asignadas; fallback legacy a `user_directionAcess` si RBAC no existe |
| `tf_has_permission()` / `tf_require_permission()` | Verificación de permiso por código (`modulo.accion`); aborta 403 |
| `tf_require_role()` | Restricción por rol cuando el permiso no basta |
| `tf_audit_log($pdo, accion, modulo, entidadId, detalle)` | Inserción en `audit_log` con usuario e IP |
| `tf_security_headers()` | Cabeceras de seguridad (extender a todos los endpoints — P1-1) |

**Contrato de todo endpoint mutante** (pipeline S-01 de [04_FLUJOS.md](04_FLUJOS.md)): sesión → CSRF → usuario → permiso → **alcance de obra** → validación de payload → PDO preparado (transacción si aplica) → auditoría → JSON `{success, message, data}`.

---

## 3. Endpoints principales

| Endpoint | Módulo | Operaciones (`accion: N`) |
|---|---|---|
| `LoginAcces.php` | Autenticación | Login, verificación de rate limiting |
| `crud_Requisiciones.php` | Requisiciones | Listar (con alcance por obra), crear, editar, validar, cerrar |
| `crud_nueva_hoja.php` | Hojas | Crear hoja (estatus NUEVO, consecutivo, creador vía `tf_hoja_set_creator()`) |
| `crud_hojas_requisicion.php` | Hojas | Listar por requisición, editar, cambiar estatus, historial (`hoja_estatus_log`) |
| `crud_items_requisiciones.php` | Ítems | CRUD de líneas, recálculo de subtotal/total de hoja |
| `crud_cotizaciones.php` | Cotizaciones | Subir (valida MIME/8 MB), listar por hoja, ver/descargar (`accion=4`) |
| `crud_Presiones.php` | Presiones | Crear (única por obra/semana/día), autorizar/rechazar, pagar, paginación server-side |
| `crud_presionDetail.php` / `crud_all_presiones.php` | Presiones | Detalle con hojas ligadas; vista global paginada para Dirección |
| `crud_enlazar_requisiciones.php` | Enlaces | Ligar/desligar hojas ↔ presión (`requisicionesligadas`) |
| `crud_proveedor.php` / `crud_addProveedor.php` | Catálogo | CRUD proveedores (RFC, CLABE) |
| `crud_bancos.php` | Catálogo | CRUD bancos |
| `crud_obras.php` / `crud_catalogos.php` | Catálogo | CRUD obras y catálogos generales |
| `crud_admin.php` | Admin | Usuarios, roles, asignación de obras, consulta de auditoría |
| `crud_direccion.php` | KPI | Agregados para dashboard (totales, adeudos, por estatus/obra/período) |
| `bitacora.php` | Auditoría | Consulta de `audit_log` |
| `crud_notifications.php` | Notificaciones | Stub — implementar en N-11 |

**Nuevos/ampliados en esta actualización**: reset de contraseña (en `crud_admin.php`), importación CFDI XML (nuevo endpoint), conteo/lista de notificaciones, filtros de auditoría con paginación.

---

## 4. Modelo de datos

### 4.1 Entidades de negocio

```
obras 1───N requisiciones 1───N hojasrequisicion 1───N itemrequisicion
                │                    │ 1───N hojas_cotizaciones (archivos en uploads/)
                │                    │ 1───N hoja_estatus_log
                │                    N
                │                    │ (requisicionesligadas)
                │                    N
obras 1───N presiones ───────────────┘
provedores 1───N hojasrequisicion          bancos 1───N presiones (banco de pago)
```

### 4.2 Tablas (21) y vistas (5)

| Grupo | Tablas |
|---|---|
| Negocio | `requisiciones`, `hojasrequisicion`, `itemrequisicion`, `hojas_cotizaciones`, `presiones`, `requisicionesligadas`, `obras`, `estadosobra`, `provedores`, `bancos`, `emisores` |
| Seguridad/RBAC | `users`, `roles`, `permissions`, `role_permissions`, `user_obras`, `login_attempts`, `two_factor_tokens` |
| Auditoría | `audit_log`, `hoja_estatus_log`, `logs` |
| Vistas | `v_requisiciones_summary` (hojas y montos por requisición), `v_presiones_summary` (hojas ligadas, total, adeudo), `v_hoja_historial`, `v_users_full`, `v_actividad_usuario` |

### 4.3 Migraciones

Aplicadas: `001`–`011` (RBAC, adaptación BD, creador de requisición, rol desarrollador, multi-obra, rate limiting, comentario director, campos de pago) y `012`–`014` reales del repo (`proveedores_fecha_creacion`, `hojas_cotizaciones`, `sync_desarrollador_permisos`).

> ⚠️ **Nota de numeración**: `docs/README.md` planeaba 012–014 como CFDI/fechas-estado/presupuesto, pero esos números ya fueron consumidos por otras migraciones. Las tres pendientes se renumeran:

| Nueva | Contenido | Habilita |
|---|---|---|
| `015_cfdi_fields.sql` | UUID CFDI, RFC emisor, fecha y total de factura en `hojasrequisicion` | N-12 conciliación fiscal |
| `016_presiones_fechas_estado.sql` | `presiones_fechaEnviado`, `presiones_fechaAutorizado`, `presiones_fechaPagado` | KPI ciclo de autorización |
| `017_obras_presupuesto.sql` | `obras_presupuesto`, `obras_fecha_inicio`, `obras_fecha_fin` | KPI % presupuesto ejecutado |
| `018_notificaciones.sql` | Tabla `notificaciones` (user_id, tipo, mensaje, leida, created_at + índice user/leida) | N-11 |
| `019_users_password_reset.sql` | Flag `requiere_cambio_password` | N-08 |

### 4.4 Anomalías a sanear (previo a KPIs)

| ID | Problema | Acción |
|---|---|---|
| BD-1 | `hojasrequisicion` con dos columnas de banco de pago (`hojaRequisicion_bancoPago` INT FK vs `hojasRequisicion_bancoPago` VARCHAR con errata) | Determinar cuál tiene datos; deprecar el VARCHAR |
| BD-4 | `requisicionesligadas` con IDs de hoja fuera de rango (p. ej. 683137 vs máx ~118) | Query de verificación y limpieza de registros huérfanos |
| Estatus | Presiones nacen AUTORIZADA (OBS-1) | Corregir `INSERT` a EN REVISIÓN + backfill si aplica |

---

## 5. Lógica de negocio (reglas duras)

1. **Unicidad de presión**: una por (obra, semana, día); duplicado responde `success:false` y la UI lo muestra como advertencia.
2. **Elegibilidad de ligado**: solo hojas de requisiciones validadas pueden ligarse a una presión; una hoja ligada no se liga a otra presión activa.
3. **Autorización exclusiva de Dirección** (`presiones.authorize`); rechazo exige `comentario_director`.
4. **Pago solo sobre AUTORIZADA** (`presiones.pay`) y exige folio + banco + fecha; propaga PAGADA a las hojas ligadas.
5. **Alcance por obra**: usuarios con nivel < 60 solo leen/escriben recursos de sus obras (`user_obras`); niveles ≥ 60 según permiso.
6. **Trazabilidad**: creador persistido en requisición, hoja, presión y cotización; transiciones de estatus en `hoja_estatus_log`; acciones en `audit_log`.
7. **Totales derivados**: los montos de presión/requisición se calculan de las hojas (vistas summary), nunca se capturan a mano.

---

## 6. Estrategia de seguridad

| Capa | Implementado | Pendiente (esta actualización) |
|---|---|---|
| Autenticación | Hash de contraseña, rate limiting 5/15 min (`login_attempts`), sesión httpOnly/SameSite/Secure, rotación de ID | 2FA TOTP (N-14, tabla lista), reset administrado con cambio obligatorio (N-08) |
| Autorización | RBAC en BD, `tf_require_permission` en endpoints, `TF.can()` en UI | **Alcance de obra en endpoints de escritura (N-03/P1-4)** — crítico |
| Anti-CSRF | Token por sesión validado en toda mutación (Axios lo envía automático) | — |
| Cabeceras HTTP | Parcial (`.htaccess`) | `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `Referrer-Policy`, `Permissions-Policy` centralizadas en `layout_top.php` y todos los `crud_*.php` (P1-1); evaluar CSP |
| SQL | PDO con prepared statements, ERRMODE_EXCEPTION | Revisión de que ningún endpoint concatene entrada en SQL |
| Archivos | Validación de tamaño; `uploads/` con `.htaccess` | Validación de MIME real para imágenes (OBS-3); nombres generados por sistema; descarga solo vía endpoint con permiso |
| Auditoría | `audit_log` + `hoja_estatus_log` + vistas | Filtros/paginación para investigación (N-08) |
| Dependencias | Librerías vendorizadas en `assets/lib/` | Eliminar Vue 2 EOL (P4-1); vendorizar Alpine (hoy CDN jsDelivr) |

---

## 7. Entorno de despliegue

| Ambiente | Detalle |
|---|---|
| **Producción** | Hostinger VPS · Apache + PHP · MariaDB 11.8.6 (`u701868959_TFC`) · ruta `thefuentescorp.com/TheFuentesApp` · despliegue por copia de archivos + migraciones manuales |
| **Desarrollo** | Docker (`deploy/docker-compose.yml`: PHP+Apache en 8080 y MySQL 8) o stack local; BD desde dumps `DataBase/u701868959_TFC_0*.sql` |
| **Alternativa contenedores** | `deploy/Dockerfile` + `deploy/cloudrun.yml` disponibles (no es la vía activa) |

### Procedimiento de release

1. Rama `FaseNN_*` → PR a `main` con revisión.
2. Gate pre-merge: `php -l` en PHP tocados, `node --check` en JS tocados, `grep` de marcadores de conflicto (`<<<<<<<`) = 0, `tests/smoke.php` y (cuando exista) suite E2E.
3. Respaldo de BD (`mysqldump`) → aplicar migraciones en orden → verificación con queries de control.
4. Copia de archivos a producción → prueba de humo manual (login + un flujo por rol) en ambos temas.
5. Rollback: restaurar respaldo de BD + revertir commit (las migraciones aditivas minimizan la necesidad).

---

## 8. Evolución futura (fuera de alcance, decisiones ya perfiladas)

- **A1 — API REST separada** (Slim/router custom, rutas declarativas, OpenAPI): habilita SPA/app móvil; migración módulo por módulo (~40 h+).
- **A2 — Cola de trabajos sobre MySQL** (tabla `job_queue` + worker cron): emails, PDFs pesados, reportes.
- **A3 — Observabilidad**: Monolog JSON, tiempos de respuesta, alerta de endpoints > 2 s.
- **PDF server-side** (dompdf) para reemplazar jsPDF cliente (P4-4).
