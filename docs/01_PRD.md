# PRD — Product Requirements Document
## The Fuentes Workspace — Sistema de Requisiciones

| Campo | Valor |
|---|---|
| **Producto** | The Fuentes Workspace — Sistema de Requisiciones de Compra |
| **Versión actual** | v4.1 (rama `Fase09_Seguridad_UXUI`) |
| **Fecha del documento** | 13 de julio de 2026 |
| **Estado** | Sistema funcional en producción con actualizaciones mayores planificadas |
| **Documentos relacionados** | [02_TDR.md](02_TDR.md) · [03_UIUX.md](03_UIUX.md) · [04_FLUJOS.md](04_FLUJOS.md) · [05_BACKEND.md](05_BACKEND.md) · [06_PLAN_TRABAJO.md](06_PLAN_TRABAJO.md) |

---

## 1. Propósito del producto

### 1.1 Problema que resuelve

The Fuentes Corporation (empresa constructora) gestionaba sus solicitudes de compra de forma manual: captura en hojas de cálculo dispersas, aprobaciones por correo o verbales, y sin trazabilidad de quién solicitó, quién autorizó y quién pagó cada egreso. Esto producía:

- **Pérdida de trazabilidad de egresos** por obra, proveedor y período.
- **Cuellos de botella en aprobaciones**: sin visibilidad del estado de cada solicitud.
- **Riesgo de pagos duplicados o no autorizados** al no existir un flujo formal de validación.
- **Retrabajo administrativo** al capturar la misma información varias veces (solicitud → cotización → pago → reporte).

### 1.2 Propuesta de valor

Plataforma web centralizada que digitaliza el ciclo completo de compra: **captura de requisición → cotización → pre-aprobación (validación de Compras) → programación de pago ("presión") → autorización de Dirección → registro de pago por Finanzas**, con control de acceso por rol (RBAC), auditoría de cada acción y reportes KPI de egresos por obra.

### 1.3 Usuarios objetivo

| Rol | Nivel | Necesidad principal |
|---|:---:|---|
| Residente de obra | 40 | Capturar requisiciones de materiales/servicios de sus obras asignadas y dar seguimiento a su estado |
| Compras | 60 | Validar requisiciones, gestionar proveedores, integrar hojas a presiones de pago semanales |
| Dirección | 80 | Autorizar o rechazar presiones de pago con visibilidad global y comentarios |
| Finanzas | 60 | Ejecutar el registro del pago (folio, banco, fecha) y gestionar catálogo de bancos |
| Administrador | 100 | Gestión de usuarios, roles, permisos y auditoría |
| Lector | 20 | Consulta de solo lectura para supervisión/auditoría externa |

---

## 2. Características clave

### 2.1 Funcionales (existentes)

| # | Característica | Descripción | Estado |
|---|---|---|:---:|
| F-01 | Autenticación con sesión segura | Login con rate limiting (5 intentos/15 min), cookies httpOnly + SameSite, rotación de ID de sesión | 🟢 |
| F-02 | RBAC granular | 6 roles + rol desarrollador, permisos por módulo/acción, matriz en BD (`roles`, `permissions`, `role_permissions`) | 🟢 |
| F-03 | Gestión de requisiciones | CRUD de requisiciones por obra con clave, número, creador y estatus | 🟢 |
| F-04 | Hojas de requisición e ítems | Cada requisición se desglosa en hojas (por proveedor) con ítems línea a línea y totales | 🟢 |
| F-05 | Cotizaciones adjuntas | Carga de cotización PDF (límite 8 MB) vinculada a hoja, con trazabilidad de quién subió el archivo | 🟢 |
| F-06 | Presiones de pago | Programación semanal de pagos por obra; enlace de hojas de requisición a presiones | 🟢 |
| F-07 | Autorización de Dirección | Autorizar/rechazar presiones con campo `comentario_director` (BD lista; UI parcial) | 🟡 |
| F-08 | Registro de pago | Marcar presión como pagada; campos `folio_pago`, `banco_id`, `fecha_pago` en BD (UI de modal pendiente) | 🟡 |
| F-09 | Catálogos | Proveedores (RFC, CLABE), bancos, obras, estados de obra | 🟢 |
| F-10 | Auditoría | `audit_log` de acciones de negocio + `hoja_estatus_log` de transiciones de estado + bitácora consultable | 🟢 |
| F-11 | Exportación | XLSX (SheetJS/ExcelJS) y PDF cliente (jsPDF + autoTable) | 🟢 |
| F-12 | Dashboard KPI | Reportes de egresos por obra con Chart.js | 🟡 |
| F-13 | Panel de administración | Alta/edición/desactivación de usuarios y roles, consulta de auditoría | 🟡 |
| F-14 | Multi-obra por usuario | Asignación N:M usuario↔obra (`user_obras`); alcance de datos por obra asignada | 🟢 |
| F-15 | Modo oscuro y Command Palette | Tema claro/oscuro persistente; paleta de comandos Ctrl+K (lógica de búsqueda pendiente) | 🟡 |

### 2.2 Funcionales (nuevas — alcance de la actualización)

| # | Característica | Justificación | Prioridad |
|---|---|---|:---:|
| N-01 | Modal de pago completo para Finanzas (folio + banco + fecha + notas) | Los campos ya existen en BD (migración 011); sin UI el pago no queda documentado | P1 |
| N-02 | UI de comentario del director en autorización/rechazo | Campo en BD (migración 010) sin captura ni visualización | P1 |
| N-03 | Validación de alcance de obra en endpoints de escritura | Un residente no debe poder escribir en obras no asignadas conociendo IDs | P1 (seguridad) |
| N-04 | Estatus inicial "EN REVISIÓN" y ordenamiento de presiones | Hoy nacen como AUTORIZADA (OBS-1, 29-jun) — riesgo de pago no revisado | P1 |
| N-05 | Cotizaciones con imágenes (JPG/PNG) integradas en `nueva_hoja` | Los proveedores envían cotizaciones fotografiadas (OBS-3) | P2 |
| N-06 | Trazabilidad de creador en presiones y hojas + creador real en PDF | Auditoría exige saber quién creó cada documento, no quién lo imprime (OBS-5/6) | P2 |
| N-07 | Estados intermedios de hoja (máquina de estados completa) | El badge LIGADA/RECHAZADA/PAGADA no refleja el proceso real (OBS-4c) | P2 |
| N-08 | Filtros y paginación en bitácora de auditoría + reset de contraseña desde admin | Operación en producción sin acceso directo a BD | P2 |
| N-09 | Filtro de período y selector de obra única en dashboard KPI | Dirección necesita cortes semana/mes/trimestre/año | P2 |
| N-10 | Selector de obras del navbar escalable (>5 obras → catálogo con búsqueda) | Desborde visual con usuarios multi-obra (OBS-8B) | P3 |
| N-11 | Notificaciones in-app (badge campana, polling 60 s) | Avisar cambios de estado a los roles involucrados | P3 |
| N-12 | Importación CFDI XML (SAT) y campos fiscales (UUID, RFC) | Conciliación fiscal de pagos contra facturas | P3 |
| N-13 | Timeline visual de estados en requisición | El residente no sabe qué paso falta | P3 |
| N-14 | 2FA TOTP para roles admin/director | Sistema con datos financieros; tabla `two_factor_tokens` ya existe | P4 |
| N-15 | Plantillas de requisición e importación de ítems desde Excel | Reducción de captura repetitiva | P4 |

### 2.3 No funcionales

| Categoría | Requerimiento |
|---|---|
| **Seguridad** | Sesión httpOnly/SameSite, CSRF en toda mutación, RBAC server-side como línea de defensa real (UI solo oculta), cabeceras HTTP de seguridad (X-Frame-Options, nosniff, Referrer-Policy), rate limiting en login, contraseñas con hash seguro, validación de alcance por obra |
| **Auditoría** | Toda acción de negocio (crear/validar/autorizar/pagar/eliminar) registrada en `audit_log` con usuario, IP, módulo, entidad y detalle |
| **Rendimiento** | Listados con paginación server-side (implementado en presiones); respuesta de endpoints < 2 s con volumen de producción; exportaciones sin paginar |
| **Disponibilidad** | Hosting Hostinger (VPS) con MariaDB 11.8; respaldo previo a toda migración SQL |
| **Compatibilidad** | Navegadores evergreen (Chrome/Edge/Firefox); uso operativo principal en escritorio, consulta en móvil (responsive) |
| **Usabilidad** | Modo claro/oscuro consistente en todas las vistas, feedback inmediato (SweetAlert2/toasts), textos en español |
| **Mantenibilidad** | Migraciones SQL versionadas en `api/migrations/`, un archivo JS Alpine por página, documentación en `docs/` |
| **Sin build step** | Frontend sin compilación: librerías locales en `assets/lib/` + CDN (jsDelivr) para Alpine; despliegue por copia de archivos |

---

## 3. Casos de uso principales

| ID | Caso de uso | Actor | Resumen |
|---|---|---|---|
| CU-01 | Crear requisición | Residente / Compras | Captura requisición para una obra asignada; genera hojas por proveedor con ítems y adjunta cotización |
| CU-02 | Validar requisición | Compras | Revisa datos, proveedor y cotización; valida la requisición para que sus hojas puedan ligarse a pago |
| CU-03 | Programar presión de pago | Compras / Finanzas | Crea presión (obra + semana + día) y liga hojas de requisición validadas; la presión nace EN REVISIÓN |
| CU-04 | Autorizar / rechazar presión | Dirección | Revisa el consolidado (hojas, montos, adeudo), autoriza o rechaza con comentario |
| CU-05 | Registrar pago | Finanzas | Sobre presión autorizada captura folio de pago, banco y fecha; las hojas pasan a PAGADA |
| CU-06 | Consultar KPI de egresos | Dirección | Dashboard por obra/período: totales, adeudos, presiones pendientes de autorizar |
| CU-07 | Administrar usuarios y roles | Administrador | Alta de usuarios, asignación de rol y obras, desactivación/reactivación, reset de contraseña |
| CU-08 | Auditar actividad | Administrador / Lector | Consulta filtrada de `audit_log` e historial de estatus de hojas |
| CU-09 | Exportar información | Todos (según permiso) | PDF de requisición/hoja (con creador real) y XLSX de listados |
| CU-10 | Gestionar catálogos | Compras / Finanzas | Alta y mantenimiento de proveedores (RFC/CLABE) y bancos |

El detalle paso a paso de cada flujo está en [04_FLUJOS.md](04_FLUJOS.md).

---

## 4. Restricciones

| Tipo | Restricción |
|---|---|
| **Tecnológica** | Mantener el stack actual: PHP sin framework (PDO), MariaDB, Alpine.js 3 + Bootstrap 5.3. No se introduce SPA ni framework backend en esta etapa (evaluado como fase futura A1) |
| **Infraestructura** | Producción en Hostinger; sin acceso a colas/servicios gestionados — las soluciones deben funcionar en LAMP estándar (p. ej. notificaciones por polling, no WebSockets) |
| **Datos** | La BD de producción (`u701868959_TFC`) contiene datos reales; toda migración requiere respaldo previo y debe ser aditiva (no destructiva) |
| **Compatibilidad legacy** | Conviven vistas v4 (layout `includes/layout_top.php`) y vistas legacy (`legacy_navbar.php`); la estandarización es gradual |
| **Equipo** | Desarrollo a cargo del área de Informática interna (equipo reducido); los sprints se dimensionan en horas-esfuerzo, no en equipos paralelos |
| **Nomenclatura BD** | Se conservan nombres existentes aunque tengan erratas históricas (`provedores`, "presiones" = presiones de pago); corregirlos rompería integraciones |

---

## 5. Métricas de éxito

| Métrica | Línea base | Objetivo | Medición |
|---|---|---|---|
| Cobertura del flujo digital (captura→pago sin pasos manuales fuera del sistema) | ~78 % | 100 % | Checklist funcional por módulo |
| Pagos con folio/banco/fecha documentados | 0 % (sin UI) | 100 % de presiones pagadas | Query sobre `presiones` (campos migración 011) |
| Ciclo de autorización (creación → autorización de presión) | Sin medir | ≤ 5 días promedio | KPI `AVG(DATEDIFF(fechaAutorizado, fechaCreacion))` (requiere migración de fechas de estado) |
| Presiones creadas con estatus incorrecto (AUTORIZADA de origen) | Presente | 0 | Auditoría de `INSERT` en presiones |
| Incidentes de acceso indebido entre obras | Sin control | 0 (bloqueo 403 + registro en auditoría) | `audit_log` |
| Documentos con creador trazable (requisición, hoja, presión, cotización) | Parcial (2 de 4) | 4 de 4 | Columnas de creador pobladas |
| Errores JS críticos en producción (vistas rotas) | 2 detectados (29-jun) | 0 | Revisión de consola + pruebas E2E |
| Cobertura E2E de flujos críticos | 0 pruebas | 5 flujos (login, crear, validar, autorizar, pagar) | Suite Playwright |

---

## 6. Fuera de alcance (esta actualización)

- Migración a API REST desacoplada / SPA (propuesta A1 — fase futura).
- App móvil nativa.
- Integración bancaria automática (dispersión de pagos).
- Timbrado o emisión de CFDI (solo **importación/conciliación** de XML está en alcance N-12).
- Sistema de colas asíncronas y observabilidad estructurada (A2/A3 — fase futura).
