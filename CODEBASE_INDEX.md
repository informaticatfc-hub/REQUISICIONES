---

name: codebase-index
description: "Índice completo del codebase REQUISICIONES con estructura, módulos y dependencias"
metadata:
  node_type: memory
  type: reference
  originSessionId: 823cee4b-b46f-4cfe-a2c0-29481558ac8c
-------------------------------------------------------

# Índice de Codebase — REQUISICIONES

**Generado:** 2026-07-14
**Total líneas de código:** ~20,832
**Archivos analizados:** 120+

## 📊 Estructura General

### `/api/` — Backend CRUD & Auth (2,500+ líneas)

**Propósito:** Endpoints JSON que manejan la lógica de negocio

#### Autenticación & RBAC

- `auth.php` — Validación de sesión, permisos, CSRF
- `LoginAcces.php` — Login, sesiones, rate limiting (011-2026_05_26)
- `rbac.php` — Control de acceso basado en roles (6 roles)

#### Presiones (Estado: Presión ≈ Semana/Día)

- `crud_Presiones.php` — CRUD presiones (accion 1-7)
- `crud_presionDetail.php` — Detalles, cierre, comentarios director
- `crud_all_presiones.php` — Listado con autorización

#### Requisiciones (Documentos Requisición)

- `crud_Requisiciones.php` — Crear, listar, buscar requisiciones
- `crud_hojas_requisicion.php` — Hojas dentro de requisición
- `crud_items_requisiciones.php` — Items/líneas dentro de hoja
- `crud_enlazar_requisiciones.php` — Vincular hojas a presión

#### Cotizaciones (Archivos PDF/IMG adjuntos)

- `crud_cotizaciones.php` — Upload, list, serve (PDF|JPG|PNG, 8MB máx)
  - Validación MIME tipo real (no extensión)
  - Scope-based access (usuario ve solo cotizaciones de su obra)

#### Maestros/Catálogos

- `crud_proveedor.php` — Proveedores (RFC, CLABE, banco)
- `crud_addProveedor.php` — Agregar proveedor en línea
- `crud_obras.php` — Obras activas y su información
- `crud_bancos.php` / `crud_catalago.php` — Catálogos

#### Utilidades

- `conexion.php` — PDO connection con variables env
- `bitacora.php` — Audit logging (anti-tampering)

### `/pages/` — Frontend (100+ líneas por página)

**Stack:** PHP + Alpine.js 3.x + Bootstrap 5.3 + Axios

#### Presiones (semana/día)

- `presiones.php` — Listado presiones con estado badge
- `presiones_detalles.php` — Detalles, hojas ligadas, cerrar presión
  - **Fixed (Fase10):** Removió DataTables que rompía Alpine scope
  - **Fixed:** Dark mode colors + "mostrar más" expandible rows

#### Requisiciones

- `requisiciones.php` — Listar requisiciones por obra
- `hojas_requisicion.php` — Hojas dentro de una requisición
  - File input validación: .pdf/.jpg/.png aceptados
  - Cotizaciones adjuntas (modal)
- `items_requisicion.php` — Items/líneas de una hoja
  - Cambiar proveedor con confirmación
  - Status color-coding (NUEVO/PENDIENTE/LIGADA/PAGADA)
- `nueva_hoja.php` — Crear hoja en requisición
  - Provider dropdown dark mode fixed
  - Cálculo server-side de total

#### Admin

- `direccion.php` — Vista director (comentarios, autorización)
- `admin.php` / `menu_catalago.php` — Administración

### `/assets/` — Frontend Static

#### CSS

- `v4.css` — Sistema de temas (light/dark) con CSS variables
  - `--tf-surface`, `--tf-border`, `--tf-text`, `--tf-shadow-*`
  - `.tf-admin-table` — Tabla estándar que respeta tema
  - SweetAlert2 dark mode overrides

#### JavaScript (Alpine.js 3.x)

- `hojas_requisicion.js` — App state para hojas
  - Cotizaciones upload/delete/view
  - Detección tipo archivo (PDF vs imagen)
- `item_requisicion.js` — Items CRUD + cambiar proveedor
  - **Fixed (Fase10):** await promises en changeProveedor()
- `presiones_detalles.js` — Detalles presión + cerrar
  - **Fixed:** Race condition en cerrar (await POST antes de reload)
- `all_presiones.js` / `admin.js` — Vistas admin

### `/tests/` — Test Suites (70+ pruebas)

- `integration_presiones_requisiciones.php` — 25 asserts
  - Login, crear presión, requisición automática, RBAC, alcance obra
- `integration_hojas_items_cotizaciones.php` — 45 asserts
  - Server-side totals, anti-IDOR, cotizaciones (MIME), link presión, scope
- `lib/ApiClient.php` — Cliente HTTP compartido
  - Cookie jar persistence
  - CSRF token extraction & injection
  - Multipart file uploads

### `/api/migrations/` — Schema & Data

**Estrategia:** Idempotente (puede correr múltiples veces)

| #       | Cambio                                                      | Estado           |
| ------- | ----------------------------------------------------------- | ---------------- |
| 001     | RBAC inicial (6 roles, permisos)                            | ✅               |
| 002-014 | Schema fixes, audit log, presiones comentario               | ✅               |
| 015     | **requisiciones.delete** (admin/dev)                  | **Fase10** |
| 016     | **presiones.authorize** (admin/director/finanzas/dev) | **Fase10** |

---

## 🔐 Seguridad — Eslabón 1 Verificado

### RBAC (6 Roles)

- **admin** — Acceso total
- **director** — Autorizar presiones, ver comentarios
- **finanzas** — Crear presiones, autorizar
- **desarrollador** — Debug + permisos completos
- **residente** — Crear requisiciones en su obra
- **coordinador** — (vacío, extensible)

### Validaciones

- ✅ CSRF token (header X-CSRF-Token)
- ✅ Sesión server-side (httpOnly cookies)
- ✅ Alcance de obra (user_obras pivot table)
- ✅ Anti-IDOR (validar permisos antes de acceder)
- ✅ Audit logging en access.denied + mutations

### Archivos Sensibles

- `.env` — DB credentials (NO commitear, en .gitignore)
- `/uploads/cotizaciones/` — User-uploaded files

---

## 📋 Módulos Principales & Dependencias

### Presiones → Requisiciones → Hojas → Items

```
Presión (semana/día/estado)
  └─ Hojas Requisición (ligadas a presión)
     └─ Items (líneas de la hoja)
     └─ Cotizaciones (archivos PDF/IMG)
     └─ Proveedor (RFC, CLABE, banco)
```

### Workflows Principales

#### 1️⃣ Crear & Ligar Requisición

```
1. Admin crea presión (semana/día)
2. User crea/modifica hojas de requisición
3. User adjunta cotizaciones (PDF|JPG|PNG)
4. Admin liga requisición a presión (enlazar_requisiciones)
5. Presión → estado LIGADA
```

#### 2️⃣ Autorizar & Cerrar

```
1. Director revisa y comenta (presiones_detalles)
2. Finanzas autoriza hojas (estado AUTORIZADA)
3. Admin cierra presión → estado CERRADA
4. Button "Cerrar y guardar presión" desaparece
```

#### 3️⃣ Pagar

```
1. Finanzas marca hoja como PAGADA
2. Hoja ya no se puede cambiar
3. Presión sigue CERRADA
```

---

## 🐛 Bugs Corregidos (Fase10)

| Bug                     | Archivo                        | Síntoma                           | Solución                           |
| ----------------------- | ------------------------------ | ---------------------------------- | ----------------------------------- |
| SQL fatal en enlazar    | crud_enlazar_requisiciones.php | "Unknown column p.presiones_total" | Reescribir con requisicionesligadas |
| Missing permisos        | rbac.php                       | 403 al cerrar presión             | Crear migrations 015 & 016          |
| DataTables rompe Alpine | presiones_detalles.php         | "presion is not defined"           | Remover DataTables, usar x-for      |
| Dark mode tablas        | hojas/items/nueva              | Colores hardcoded#fff/#ccc         | Usar --tf-* variables CSS           |
| Race condition cerrar   | presiones_detalles.js          | Button visible tras cerrar         | Await POST antes de reload          |
| No await provider       | item_requisicion.js            | Toast "success" sin envío real    | Await changeProveedor()             |

---

## 🔧 Tech Stack

- **Backend:** PHP 8.4 + PDO (MySQL)
- **Frontend:** Alpine.js 3.x + Bootstrap 5.3 + Axios
- **CSS:** Custom theme system (light/dark via CSS variables)
- **HTTP:** JSON REST API + CSRF + session cookies
- **Files:** Multipart upload (cotizaciones/ folder)

---

## 📍 Puntos de Entrada

- **UI Principal:** `pages/obras.php` → requisiciones → hojas → items
- **Admin:** `pages/admin.php` → catalogs, users
- **Presiones:** `pages/presiones.php` → detalles → ligar → cerrar
- **API:** `api/crud_*.php?accion=N` (cada archivo expone 10-15 acciones)

---

## ✅ Estado Actual (2026-07-14)

- **Test Suite 1:** 25/25 PASS (presiones + requisiciones)
- **Test Suite 2:** 45/45 PASS (hojas + items + cotizaciones)
- **Dark Mode:** Corrected (CSS variables + SweetAlert2 overrides)
- **Modals:** Fixed (dark mode + race conditions)
- **Permissions:** Synced (migrations 015 & 016 applied)
- **Files Synced:** 18 modified + 2 migrations + 4 test files → XAMPP htdocs

---

## 🚀 Para Próximas Mejoras

- [ ] "Reabrir presión" (feature nueva si se requiere)
- [ ] Notifications (real-time WebSocket o polling)
- [ ] Reportes (exportar presiones a PDF)
- [ ] Integración con contabilidad (export a SAT)
