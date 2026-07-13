# FLUJO — User & System Flows
## The Fuentes Workspace — Sistema de Requisiciones v4.1

| Campo | Valor |
|---|---|
| **Fecha** | 13 de julio de 2026 |
| **Documentos relacionados** | [01_PRD.md](01_PRD.md) (casos de uso) · [05_BACKEND.md](05_BACKEND.md) (endpoints y modelo de datos) |

---

## 1. Visión general del proceso de negocio

```
CAPTURA                PRE-APROBACIÓN           PROGRAMACIÓN DE PAGO        APROBACIÓN         PAGO
────────               ──────────────           ────────────────────        ──────────         ────
Residente/Compras  →   Compras valida       →   Compras/Finanzas liga   →   Dirección      →   Finanzas registra
crea requisición       requisición y            hojas a una "presión"       autoriza o         folio, banco y
con hojas, ítems       cotizaciones             (obra + semana + día)       rechaza (con       fecha de pago
y cotizaciones                                                              comentario)
```

Entidades involucradas (detalle en [05_BACKEND.md §4](05_BACKEND.md)):
**Requisición** (solicitud por obra) → contiene **Hojas** (una por proveedor, con **ítems** y **cotizaciones** adjuntas) → las hojas se **ligan** a una **Presión de pago** (corte semanal por obra) → la presión se autoriza y se paga.

---

## 2. Flujo de entrada al sistema

### F-00 · Login y establecimiento de sesión

1. Usuario abre la aplicación → `index.php` redirige a `pages/login.php`.
2. Ingresa usuario y contraseña → `assets/js/login.js` envía credenciales a `api/LoginAcces.php`.
3. **Sistema**: verifica rate limiting (tabla `login_attempts`, máx. 5 intentos/15 min); valida hash de contraseña; verifica `user_estatus = ACTIVO`.
4. **Sistema**: crea sesión segura (`TF_SESSID`, httpOnly, SameSite=Lax, regeneración de ID), genera token CSRF, registra `LOGIN` en `audit_log`, actualiza `user_lastLogin`.
5. Redirige al dashboard (`pages/index.php`); `layout_top.php` inyecta `window.TF_CONTEXT` (usuario, rol, permisos, obras asignadas, token CSRF para Axios).
6. **Fallo**: mensaje genérico de error; al 5.º intento fallido, bloqueo temporal de 15 min con registro en auditoría.
7. **Futuro (N-14)**: si el usuario tiene 2FA activo, paso intermedio de código TOTP antes de crear la sesión.

**Cierre de sesión**: `pages/closeSesion.php` destruye la sesión y regresa a login. Toda página protegida pasa por `validarSesion.php`; sin sesión válida → redirección a login.

---

## 3. Flujos principales por rol

### F-01 · Crear requisición (Residente / Compras)

1. Menú → **Requisiciones** (`requisiciones.php`) → botón "Nueva requisición" (visible solo con `requisiciones.create`).
2. En `nueva_requisicion.php` captura: obra (limitada a sus obras asignadas vía `user_obras`), nombre/concepto, fecha de solicitud.
3. **Sistema** (`crud_Requisiciones.php`): valida CSRF + permiso + **alcance de obra**; genera clave y número consecutivo; persiste con creador (`requisicion_userCreado`, migración 004); registra en `audit_log`.
4. La requisición aparece en el listado con estatus inicial y monto 0 (sin hojas aún).

### F-02 · Agregar hojas, ítems y cotización

1. Desde la requisición → **Hojas** (`hojas_requisicion.php`) → "Nueva hoja" (`nueva_hoja.php`).
2. Captura: proveedor (búsqueda por nombre; objetivo N/P3-3: nombre/RFC/CLABE), condiciones y datos de la compra.
3. **Sistema** (`crud_nueva_hoja.php`): crea la hoja con estatus **NUEVO**, número consecutivo dentro de la requisición y creador (`tf_hoja_set_creator()`).
4. En `items_requisicion.php` agrega ítems línea a línea (descripción, cantidad, unidad, precio); **sistema** (`crud_items_requisiciones.php`) recalcula subtotal y total de la hoja.
5. Adjunta cotización: archivo PDF (objetivo OBS-3: también JPG/PNG) → `crud_cotizaciones.php` valida MIME y tamaño (8 MB), guarda en `uploads/cotizaciones/` y registra en `hojas_cotizaciones` con usuario que subió.
6. La cotización queda consultable desde la hoja ("Ver/Imprimir", `crud_cotizaciones.php?accion=4`).

### F-03 · Validar requisición (Compras)

1. Compras revisa el listado de requisiciones pendientes (permiso `requisiciones.validate`).
2. Verifica hojas, ítems, montos y cotizaciones adjuntas.
3. Valida (o regresa con observaciones); **sistema** cambia estatus, registra transición en `hoja_estatus_log` y `audit_log`.
4. Solo las hojas de requisiciones validadas son elegibles para ligarse a una presión.

### F-04 · Programar presión de pago (Compras / Finanzas)

1. Menú → **Presiones** (`presiones.php`) → "Nueva presión": obra + semana + día.
2. **Sistema** (`crud_Presiones.php`): rechaza duplicados (misma obra/semana/día) con mensaje de error visible como advertencia; crea la presión con estatus inicial **EN REVISIÓN** (regla OBS-1 — corregir origen AUTORIZADA) y creador registrado (pendiente OBS-5); la lista se ordena con la más reciente primero.
3. En `enlazar_requisiciones.php` liga hojas elegibles a la presión; **sistema** (`crud_enlazar_requisiciones.php`) inserta en `requisicionesligadas`, cambia hoja a **LIGADA** y recalcula totales de la presión (vista `v_presiones_summary`: hojas ligadas, total, adeudo).
4. Al completar el corte, la presión pasa a **PENDIENTE** de autorización.

### F-05 · Autorizar / rechazar presión (Dirección)

1. Menú → **Vista global** (`all_presiones.php`); tab "Requieren autorización" con conteo y orden por antigüedad (P3-7).
2. Abre el detalle (`presiones_detalles.php`): hojas ligadas, montos, adeudo, cotizaciones.
3. Decide en modal (permiso `presiones.authorize`):
   - **Autorizar** → estatus **AUTORIZADA**; comentario opcional.
   - **Rechazar** → estatus **RECHAZADA**; `comentario_director` **obligatorio** (campo de migración 010; UI pendiente N-02).
4. **Sistema**: registra fecha de transición (requiere migración de fechas de estado), auditoría y (futuro N-11) notificación a Compras/Finanzas.
5. Una presión rechazada regresa a Compras: puede corregirse (desligar/ajustar hojas) y reenviarse.

### F-06 · Registrar pago (Finanzas)

1. En `presiones.php`, sobre una presión **AUTORIZADA**, botón "Marcar como pagada" (permiso `presiones.pay`).
2. **Modal de pago** (N-01): folio de pago (requerido), banco (catálogo `bancos`), fecha (default hoy), notas.
3. **Sistema** (`crud_Presiones.php`): persiste `folio_pago`, `banco_id`, `fecha_pago` (migración 011); presión → **PAGADA**; hojas ligadas → **PAGADA**; transición en `hoja_estatus_log`; auditoría.
4. Los datos del pago quedan visibles en `presiones_detalles.php`.

### F-07 · Consultar KPI (Dirección)

1. Dashboard (`reportes_kpi.php`) con selector de período (semana/mes/trimestre/año — N-09) y selector de obra única.
2. **Sistema** (`crud_direccion.php`): agrega totales, adeudos, presiones por estatus; con migraciones de fechas/presupuesto: ciclo de autorización, % presupuesto ejecutado, adeudo por proveedor.
3. Exportación XLSX del corte visible.

### F-08 · Administración (Admin)

1. `admin.php`: usuarios (alta, rol, obras asignadas, activar/desactivar, **reactivar rol** P1-2, **reset de contraseña** N-08 con temporal de un solo uso y cambio obligatorio en el siguiente login).
2. Auditoría: consulta de `audit_log` con filtros por fecha/usuario/acción/texto y paginación server-side (N-08); export CSV.

### F-09 · Exportar documentos

1. Desde ítems/hoja: **PDF** (jsPDF + autoTable) con logo corporativo; debe imprimir el **creador real** de la hoja y "impreso por" el usuario de sesión (OBS-6).
2. Desde listados: **XLSX** (ExcelJS con fallback CSV) con valores planos, sin fórmulas ni marca de agua (P-EXCEL).

---

## 4. Máquinas de estado

### 4.1 Hoja de requisición

```
NUEVO ──(validación Compras)──► [estados intermedios a definir — OBS-4c: EN REVISIÓN / VALIDADA]
  │                                        │
  │                                        ▼
  │                              LIGADA (enlazada a presión)
  │                                        │
  │              ┌─────────────────────────┤
  ▼              ▼                         ▼
RECHAZADA ◄──(rechazo Dirección)       PAGADA (presión pagada)
(regresa a ajuste y puede religarse)
```

Toda transición se registra en `hoja_estatus_log` (antes, nuevo, comentario, responsable, fecha) y es consultable vía la vista `v_hoja_historial`.

### 4.2 Presión de pago

```
EN REVISIÓN ──► PENDIENTE ──► AUTORIZADA ──► PAGADA
 (creación)     (corte           │  (Dirección)   (Finanzas: folio+banco+fecha)
                 enviado)        ▼
                             RECHAZADA ──► (ajuste por Compras) ──► PENDIENTE
```

Regla vigente a corregir (OBS-1): el INSERT actual nace **AUTORIZADA**; debe nacer **EN REVISIÓN**.

### 4.3 Requisición

Estatus agregado derivado de sus hojas (vista `v_requisiciones_summary`: hojas nuevas, pagadas, monto total). Una requisición se considera **CERRADA** cuando todas sus hojas están pagadas o rechazadas definitivamente.

---

## 5. Flujos de sistema transversales

### S-01 · Toda petición mutante (POST a `crud_*.php`)

```
Petición Axios (withCredentials + X-CSRF-Token)
  → tf_session_start()               sesión válida, si no → 401/redirect
  → tf_csrf_validate()               token válido, si no → 403
  → tf_current_user($pdo)            usuario + rol + permisos
  → tf_require_permission()          permiso del módulo, si no → 403
  → validación de alcance de obra    recurso ∈ obras del usuario (niveles < 60) — N-03
  → validación de payload            tipos, requeridos, límites
  → operación PDO (prepared)         transacción si toca varias tablas
  → tf_audit_log()                   acción, entidad, detalle, IP
  → respuesta JSON {success, message, data}
```

### S-02 · Carga de archivos (cotizaciones)

Validar MIME real (no solo extensión) → límite de tamaño → nombre de archivo generado por el sistema (nunca el original directo) → guardado fuera del alcance ejecutable (`uploads/` con `.htaccess`) → registro en BD con hoja, usuario y fecha → descarga solo vía endpoint con control de permiso y alcance de obra.

### S-03 · Notificaciones (N-11, futuro)

Cambio de estado (validada/autorizada/rechazada/pagada) → INSERT en `notificaciones` para los roles/usuarios relevantes → frontend hace polling cada 60 s del conteo no leídas → panel lateral lista y marca como leídas.

---

## 6. Diagrama de responsabilidad por etapa

| Etapa | Residente | Compras | Finanzas | Dirección | Admin |
|---|:---:|:---:|:---:|:---:|:---:|
| Crear requisición/hojas/ítems | ✅ | ✅ | — | — | ✅ |
| Adjuntar cotización | ✅ | ✅ | — | — | ✅ |
| Validar requisición | — | ✅ | — | — | ✅ |
| Crear presión y ligar hojas | — | ✅ | ✅ | — | ✅ |
| Autorizar / rechazar presión | — | — | — | ✅ | ✅ |
| Registrar pago | — | — | ✅ | ✅* | ✅ |
| Ver KPI global | — | — | — | ✅ | ✅ |
| Administrar usuarios/roles | — | — | — | Parcial | ✅ |

\* Dirección conserva `presiones.pay` como respaldo según matriz RBAC vigente.
