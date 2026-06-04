
### 2026-05-26 - Limpieza deuda tecnica frontend (Vue legacy)

- Se movieron scripts legacy basados en `new Vue(...)` de `assets/js/` a `assets/js/legacy/` para evitar uso accidental en vistas activas ya migradas a Alpine.
- Archivos movidos:
   - `bancos.js`
   - `catalago.js`
   - `index.js`
   - `menu_catalagos.js`
   - `nueva_requisicion.js`
   - `obras.js`
   - `proveedor.js`
   - `requisiciones.js`
- Validacion posterior:
   - No hay referencias activas en `pages/*.php` a esos scripts en su ruta anterior.
   - `assets/js/*.js` ya no contiene `new Vue(` en la ruta activa.
# STATUS DE MEJORAS — Sistema de Requisiciones TFC
**Fecha de revisión:** 26 de Mayo 2026  
**Versión actual:** v4.1 (rama `fase08-vistas-std`)  
**Revisado por:** GitHub Copilot

---

## Índice

1. [Resumen ejecutivo](#1-resumen-ejecutivo)
2. [Mapa de roles y permisos actuales](#2-mapa-de-roles-y-permisos-actuales)
3. [Estado por tipo de usuario](#3-estado-por-tipo-de-usuario)
   - [Administrador](#31-administrador)
   - [Director](#32-director)
   - [Compras](#33-compras)
   - [Finanzas](#34-finanzas)
   - [Residente](#35-residente)
   - [Lector](#36-lector)
4. [Mejoras globales de UX/UI](#4-mejoras-globales-de-uxui)
5. [Mejoras de seguridad](#5-mejoras-de-seguridad)
6. [Mejoras de rendimiento](#6-mejoras-de-rendimiento)
7. [Deuda técnica](#7-deuda-técnica)
8. [Prioridad de implementación](#8-prioridad-de-implementación)

---

## 1. Resumen ejecutivo

El sistema se encuentra en un estado **funcional estable** (v4.1). La migración de layout v3→v4 (topbar + offcanvas móvil) está completada. El modelo RBAC granular (`modulo.accion`) está implementado en backend pero **aún no totalmente expuesto en la UI**. Existen brechas importantes de UX según el rol del usuario, funcionalidades faltantes de flujo de trabajo, y riesgos de seguridad moderados que deben atenderse antes de una versión de producción.

### Semáforo global

| Categoría | Estado | Prioridad |
|-----------|--------|-----------|
| Flujo de autenticación y sesión | 🟡 Parcial | Alta |
| RBAC — visibilidad de UI | 🟡 Parcial | Alta |
| Módulo Requisiciones | 🟢 Funcional | Media |
| Módulo Presiones | 🟢 Funcional | Media |
| Módulo Catálogos | 🟡 Inconsistente | Media |
| Módulo Dirección / KPI | 🟢 Funcional | Baja |
| Panel Admin | 🟡 Parcial | Alta |
| Exportaciones (XLSX/PDF) | 🟡 Parcial | Baja |
| Auditoría y trazabilidad | 🔴 Mínimo | Alta |
| Seguridad | 🟡 Parcial | Alta |
| Rendimiento | 🟡 Parcial | Media |

---

## 2. Mapa de roles y permisos actuales

| Rol | Nivel | Obras | Requisiciones | Presiones | Catálogos | KPI/Dirección | Admin |
|-----|-------|-------|---------------|-----------|-----------|---------------|-------|
| **admin** | 100 | ✅ Todas | ✅ CRUD | ✅ CRUD | ✅ CRUD | ✅ | ✅ Panel completo |
| **director** | 80 | ✅ Todas | 👁️ Solo ver | ✅ Autorizar/pagar | 👁️ Solo ver | ✅ Dashboard global | ❌ |
| **compras** | 60 | Asignadas | ✅ Crear/Editar | 👁️ Ver y Ligar | ✅ Ver/Gestionar prov. | ❌ | ❌ |
| **finanzas** | 60 | Asignadas | 👁️ Solo ver | ✅ Marcar pagado | 👁️ Solo ver | ❌ | ❌ |
| **residente** | 40 | Asignadas | ✅ Crear/Ver | 👁️ Solo ver | 👁️ Solo ver | ❌ | ❌ |
| **lector** | 20 | Asignadas | 👁️ Solo ver | 👁️ Solo ver | 👁️ Solo ver | ❌ | ❌ |

---

## 3. Estado por tipo de usuario

---

### 3.1 Administrador

#### Acciones disponibles actualmente
- Gestión completa de usuarios (crear, editar, asignar roles, desactivar)
- Gestión de roles y permisos (matriz N:M)
- Visualización del log de auditoría en el panel admin
- Acceso irrestricto a todos los módulos

#### Limitaciones detectadas

| # | Limitación | Impacto |
|---|-----------|---------|
| A-1 | El panel admin no permite **reactivar roles desactivados** desde la UI | Bloquea recuperación de roles sin DB access |
| A-2 | No existe **restablecimiento de contraseña** para usuarios desde el panel | Admins deben acceder directo a BD |
| A-3 | El log de auditoría no tiene **filtros por fecha, usuario o acción** | Difícil auditar en producción con muchos registros |
| A-4 | No existe **exportación del log** (CSV/XLSX) | Auditorías externas imposibles desde la app |
| A-5 | La **asignación masiva de obras** a un usuario requiere operación por operación | Ineficiente para onboarding de nuevos residentes |
| A-6 | No hay **confirmación por email** al crear/editar usuarios | Riesgo de creación silenciosa de cuentas |
| A-7 | El panel no muestra **último acceso** ni **sesiones activas** de usuarios | Sin visibilidad de actividad real |

#### Mejoras recomendadas

```
✅ [A-M1] Agregar botón "Reactivar" en la lista de roles inactivos del panel admin
✅ [A-M2] Formulario "Cambiar contraseña" en el perfil de usuario admin
✅ [A-M3] Filtros de fecha/usuario/acción en la tabla de auditoría + paginación server-side
✅ [A-M4] Botón "Exportar log" (CSV) en la sección de auditoría
✅ [A-M5] Checkbox múltiple para asignación de obras en el formulario de usuario
✅ [A-M6] Campo `ultimo_acceso` en tabla `users` + visualización en panel
```

---

### 3.2 Director

#### Acciones disponibles actualmente
- Dashboard KPI global (`reportes_kpi.php`): obras, presiones, montos pagados/adeudados
- Vista global de todas las presiones (`all_presiones.php`): grid tipo Excel, gráficas, exportar XLSX
- Autorización de presiones
- Navegación a cualquier obra/módulo (solo lectura en la mayoría)

#### Limitaciones detectadas

| # | Limitación | Impacto |
|---|-----------|---------|
| D-1 | El dashboard KPI **no tiene filtro de período** (semana, mes, año) | Solo muestra totales históricos, sin tendencias |
| D-2 | **`all_presiones`** carga todos los registros de una vez (no paginado) | Lento con muchas obras/presiones |
| D-3 | No existe vista de **presiones pendientes de autorización** agrupadas por urgencia | Director debe buscar manualmente |
| D-4 | Las **gráficas de `all_presiones`** (stacked bar) no son interactivas; no permiten drill-down | Pérdida de contexto al comparar obras |
| D-5 | No hay **notificaciones en tiempo real** cuando una requisición necesita autorización | Flujo reactivo (hay que entrar y buscar) |
| D-6 | La exportación XLSX de `all_presiones` no incluye **encabezados de obra ni totales de fila** con formato condicional | Requiere trabajo manual post-exportación |
| D-7 | El director **no puede crear notas/comentarios** en una presión para comunicar rechazo | Comunicación off-system (WhatsApp/email) |

#### Mejoras recomendadas

```
✅ [D-M1] Selector de período (mes/trimestre/año) en reportes_kpi.php con recarga dinámica
✅ [D-M2] Paginación server-side en all_presiones (DataTables server-side rendering)
✅ [D-M3] Tab "Pendientes de autorización" en all_presiones ordenado por fecha más antiguo primero
✅ [D-M4] Migrar gráficas de Chart.js a versión con click-to-drill-down (abrir presión al hacer clic)
✅ [D-M5] Campo `comentario_director` en presiones + visualización en presiones_detalles
⏳ [D-M6] Notificaciones push/badge (badge en topbar actualizado via polling cada 60s)
```

---

### 3.3 Compras

#### Acciones disponibles actualmente
- Crear/editar/eliminar requisiciones en sus obras asignadas
- Gestionar hojas dentro de una requisición
- Agregar ítems a las hojas
- Ver y ligar requisiciones a presiones
- Gestionar proveedores (crear, editar)
- Consultar catálogos

#### Limitaciones detectadas

| # | Limitación | Impacto |
|---|-----------|---------|
| C-1 | Al crear una requisición, el **selector de proveedor no tiene búsqueda por RFC o CLABE** | Confusión en catálogos grandes |
| C-2 | No hay **validación en tiempo real del RFC** del proveedor contra el SAT | Datos incorrectos se guardan sin aviso |
| C-3 | No existe **flujo de "enviar a revisión"**: el cambio de estado NUEVO→PENDIENTE requiere acción manual y no notifica | Proceso manual sin trazabilidad |
| C-4 | Las hojas de requisición no muestran **subtotal calculado en la tabla principal** | Usuario debe entrar a cada hoja para ver monto |
| C-5 | No existe vista de **requisiciones recientes** o "mis requisiciones" separada de todas las de la obra | Difícil rastrear trabajo propio |
| C-6 | Al ligar requisiciones a presiones (`enlazar_requisiciones.php`) no hay **confirmación visual** del total enlazado vs. el tope de la presión | Riesgo de sobrepasar presupuesto |
| C-7 | No hay opción de **duplicar una requisición** existente | Trabajo repetitivo en obras similares |

#### Mejoras recomendadas

```
✅ [C-M1] Agregar campo de búsqueda combinada (nombre/RFC/CLABE) en el select de proveedor (Select2 o Choices.js)
✅ [C-M2] Indicador de monto total por hoja en la tabla de hojas_requisicion.php (columna calculada)
✅ [C-M3] Botón "Duplicar requisición" que copie cabecera e ítems (excepto presión asignada)
✅ [C-M4] Badge "Mis requisiciones" en la lista: filtrar por usuario creador actual
✅ [C-M5] Alert visual en enlazar_requisiciones cuando el total enlazado supere el monto de la presión
✅ [C-M6] Workflow de estado: botón explícito "Enviar a revisión" con confirmación SweetAlert + registro en bitácora
```

---

### 3.4 Finanzas

#### Acciones disponibles actualmente
- Ver lista de presiones por obra
- Marcar presiones como pagadas
- Gestionar bancos (CRUD)
- Ver detalles de presiones

#### Limitaciones detectadas

| # | Limitación | Impacto |
|---|-----------|---------|
| F-1 | No existe vista de **presiones aprobadas pendientes de pago** filtrada para finanzas | Debe navegar por todas las presiones |
| F-2 | Al marcar una presión como pagada, **no se solicita número de folio/referencia de pago** | Sin trazabilidad bancaria |
| F-3 | No hay campo para **subir comprobante de pago** (PDF/imagen) | Evidencia off-system |
| F-4 | No existe **conciliación automática** de presiones pagadas vs. saldo disponible por obra | Manual y propenso a errores |
| F-5 | Los **bancos no están vinculados a obras** específicas | Se muestran todos los bancos a todos |
| F-6 | No existe exportación de **reporte de pagos del período** filtrado por banco u obra | Reporting off-system |

#### Mejoras recomendadas

```
✅ [F-M1] Tab "Pendientes de pago" en presiones.php (filtro status = AUTORIZADA)
✅ [F-M2] Modal de pago con campo obligatorio: folio_pago, banco_id, fecha_pago
✅ [F-M3] Campo file upload para comprobante (almacenamiento local o Cloud Storage)
✅ [F-M4] Relacionar bancos con obras (tabla bancos_obras) para filtrar catálogo
✅ [F-M5] Reporte mensual de pagos: exportable en CSV desde presiones con status PAGADA + filtro fechas
```

---

### 3.5 Residente

#### Acciones disponibles actualmente
- Crear requisiciones en obras asignadas
- Agregar hojas e ítems a sus requisiciones
- Ver presiones de sus obras
- Consultar catálogos (solo lectura)

#### Limitaciones detectadas

| # | Limitación | Impacto |
|---|-----------|---------|
| R-1 | El residente **no ve el status de autorización** de sus requisiciones de forma prominente | No sabe si fue aprobada sin navegar |
| R-2 | No hay **vista de progreso** de la requisición (timeline: creada → revisión → ligada → autorizada → pagada) | Sin contexto de en qué paso está |
| R-3 | Si el residente crea una requisición **en la obra equivocada**, no puede cambiarle la obra | Debe eliminar y recrear |
| R-4 | No existe **vista de items agrupados por categoría** o por proveedor en una requisición | Difícil revisar antes de enviar |
| R-5 | El residente **no puede ver el monto total** de su requisición desde la lista de requisiciones.php | Solo visible al entrar a cada hojas |
| R-6 | No hay **límite de monto configurable** por requisición para el rol residente | Sin control presupuestal por usuario |

#### Mejoras recomendadas

```
✅ [R-M1] Columna "Monto total" calculado en la tabla de requisiciones (suma de ítems de todas las hojas)
✅ [R-M2] Badge de estado prominente (pill color-coded) en cada requisición + hover para ver historial
✅ [R-M3] Timeline visual del flujo de aprobación en la vista de detalle de la requisición
✅ [R-M4] Restricción: mostrar alerta si se intenta crear más de N requisiciones abiertas simultáneamente (configurable)
```

---

### 3.6 Lector

#### Acciones disponibles actualmente
- Ver requisiciones de obras asignadas (solo lectura)
- Ver presiones de obras asignadas (solo lectura)
- Ver catálogos (solo lectura)

#### Limitaciones detectadas

| # | Limitación | Impacto |
|---|-----------|---------|
| L-1 | Los **botones de acción** (Editar, Eliminar, Agregar) aparecen en la UI aunque estén deshabilitados por RBAC | Confusión: usuario no sabe qué puede hacer |
| L-2 | No hay **página de inicio personalizada** para el lector (ven el mismo dashboard que el residente) | UI sobrecargada con módulos inaccesibles |
| L-3 | El lector **no puede exportar datos** (sin permiso explícito para exportar) | Limitación excesiva; exportar es operación de solo lectura |

#### Mejoras recomendadas

```
✅ [L-M1] Ocultar (no solo deshabilitar) botones de CRUD para roles sin permiso de escritura
✅ [L-M2] Permitir exports (XLSX/PDF) para cualquier rol con permiso `.view` del módulo
✅ [L-M3] Dashboard simplificado para rol lector: solo accesos directos a módulos permitidos
```

---

## 4. Mejoras globales de UX/UI

### 4.1 Navegación y orientación

| ID | Mejora | Estado |
|----|--------|--------|
| UX-1 | **Selector de obra activa** visible permanentemente en topbar (ya existe, mejora: mostrar nombre completo en tablet) | 🟡 Parcial |
| UX-2 | **Breadcrumb** en todas las páginas (algunas no lo tienen configurado) | 🟡 Parcial |
| UX-3 | **Ctrl+K Command Palette** — está en HTML pero no implementado | 🔴 Sin implementar |
| UX-4 | **Modo oscuro** — toggle en topbar existe, pero no todas las páginas respetan las custom properties CSS | 🟡 Parcial |
| UX-5 | **Notificación badge** en topbar — elemento HTML presente pero sin lógica de datos | 🔴 Sin implementar |
| UX-6 | **Feedback de carga** — páginas sin skeleton loaders; solo spinner genérico | 🟡 Mejorable |

### 4.2 Formularios y validaciones

| ID | Mejora | Estado |
|----|--------|--------|
| UX-7 | **Validación en tiempo real** de campos (RFC, CLABE, email) en lugar de solo al enviar | 🔴 Falta en varios forms |
| UX-8 | **Autoguardado / borrador** en formularios largos (nueva_requisicion, agregar_proveedor) | 🔴 Sin implementar |
| UX-9 | **Mensajes de error** específicos por campo (actualmente mensajes genéricos de SweetAlert) | 🟡 Mejorable |
| UX-10 | **Campos requeridos** marcados visualmente con asterisco + tooltip | 🟡 Inconsistente |

### 4.3 Tablas y listados

| ID | Mejora | Estado |
|----|--------|--------|
| UX-11 | **Paginación server-side** en tablas grandes (requisiciones, presiones, all_presiones) | 🔴 Sin implementar |
| UX-12 | **Filtros persistentes** (obra, estado, fecha) que sobrevivan recarga de página | 🔴 Sin implementar |
| UX-13 | **Orden en columnas** con click en encabezado (DataTables lo soporta, algunas tablas no lo tienen activo) | 🟡 Parcial |
| UX-14 | **Búsqueda global** en la topbar (Ctrl+K) con resultados de requisiciones/presiones/proveedores | 🔴 Sin implementar |
| UX-15 | **Vista compacta vs. detallada** toggle en listados largos | 🔴 Sin implementar |

### 4.4 Mobile

| ID | Mejora | Estado |
|----|--------|--------|
| UX-16 | El offcanvas nav móvil está implementado pero **tablas DataTables no son responsive** en pantallas <768px | 🔴 Falta responsive config |
| UX-17 | El **selector de obra activa** en mobile puede quedar oculto detrás del offcanvas backdrop | 🟡 Bug conocido |
| UX-18 | **Formularios complejos** (nueva_requisicion) no están optimizados para teclado virtual móvil | 🔴 Sin optimizar |

---

## 5. Mejoras de seguridad

| ID | Riesgo | Descripción | Prioridad |
|----|--------|-------------|-----------|
| SEC-1 | 🔴 Alto | **Vue 2.5.16 EOL** — sin parches de seguridad desde 2023; XSS posible en templates sin sanitizar | Alta |
| SEC-2 | 🟡 Medio | **`localStorage` contiene `obraActiva` e `idRequisicion`** — usuario puede manipular para intentar acceder a recursos de otra obra (mitigado por validación server-side, pero inconsistente) | Media |
| SEC-3 | 🟢 Bajo | **Cabeceras HTTP de seguridad** activas vía `tf_security_headers()` (`Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`) | Baja |
| SEC-4 | 🟡 Medio | **Tokens CSRF implementados** en RBAC pero algunos endpoints legacy (`crud_catalago.php`, `crud_proveedor.php`) no los validan | Alta |
| SEC-5 | 🟡 Medio | **Subida de archivos** (si se implementa comprobantes F-M3) debe validar MIME type server-side, no solo extensión | Media |
| SEC-6 | 🟡 Medio | **Rate limiting** no implementado en `LoginAcces.php` — vulnerable a fuerza bruta | Alta |
| SEC-7 | 🟢 Bajo | **Audit log** existe pero no registra todos los eventos críticos (cambios de contraseña, asignación de roles) | Media |

### Acciones inmediatas de seguridad recomendadas

```php
// 1. Agregar en layout_top.php o en cada PHP antes del output:
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{TOKEN}'; style-src 'self' 'unsafe-inline'");

// 2. Rate limiting en LoginAcces.php:
// Bloquear IP después de 5 intentos fallidos en 15 minutos (con tabla `login_attempts`)

// 3. Reemplazar Vue 2 por Alpine.js (ligero, seguro, sin build step):
// <script src="assets/lib/alpinejs/alpine.min.js" defer></script>
```

---

## 6. Mejoras de rendimiento

| ID | Problema | Solución recomendada | Impacto |
|----|----------|---------------------|---------|
| PERF-1 | `all_presiones` carga todos los registros en una request | Implementar paginación server-side (LIMIT/OFFSET) o lazy-load por obra | Alto |
| PERF-2 | DataTables carga sin configuración de `deferRender` | Activar `deferRender: true` y `scrollY` virtual scrolling | Medio |
| PERF-3 | Múltiples peticiones axios en cascada al cargar páginas (waterfall) | Agrupar en endpoints multi-data o usar `Promise.all()` | Medio |
| PERF-4 | Sin caché de respuestas API para catálogos estáticos (bancos, obras) | `Cache-Control: max-age=300` + ETag en endpoints de solo lectura | Bajo |
| PERF-5 | `main.css` legacy (v3) sigue siendo cargado en algunas páginas junto con `v4.css` | Eliminar `main.css` de todas las páginas migradas a v4 | Bajo |
| PERF-6 | jQuery y Bootstrap Bundle duplicados en algunas páginas (carga desde CDN Y local) | Auditar scripts incluidos; usar solo locales | Medio |

---

## 7. Deuda técnica

| ID | Ítem | Estado | Prioridad |
|----|------|--------|-----------|
| DT-1 | `catalago.php` aún usa **layout v3 legacy** (sidebar) + `legacy_navbar.php` | 🔴 Pendiente migrar | Media |
| DT-2 | **`main.css`** contiene reglas v3 conflictivas con `v4.css` | 🔴 Pendiente eliminar | Baja |
| DT-3 | **Vue 2.5.16** usado en ~8 páginas — EOL, reemplazar por Alpine.js o Vanilla JS | 🔴 Crítico | Alta |
| DT-4 | **jQuery slim** incluido en páginas que ya usan axios+Vanilla JS | 🟡 Redundante | Baja |
| DT-5 | `crud_catalago.php` vs `crud_menu_catalagos.php` — confusión de nomenclatura | 🟡 Nomenclatura | Baja |
| DT-6 | `user_directionAcess` (typo en columna) — legacy flag coexiste con RBAC | 🟡 Deuda RBAC | Media |
| DT-7 | **Sin tests automatizados** (ni unitarios ni de integración) | 🔴 Riesgo | Alta |
| DT-8 | **`consultas.sql`** contiene consultas hardcoded para referencia — debe estar en docs/ o eliminarse | 🟡 Orden | Baja |

---

## 8. Prioridad de implementación

### 🔴 Sprint inmediato (crítico / bloquea producción)

| ID | Mejora | Módulo |
|----|--------|--------|
| SEC-3 | Cabeceras HTTP de seguridad ya activas vía `tf_security_headers()` | Backend global |
| SEC-4 | Validar CSRF en endpoints legacy sin validar | API (crud_catalago, crud_proveedor) |
| SEC-6 | Rate limiting en login (5 intentos / 15 min) | LoginAcces.php |
| DT-3 | Plan de migración Vue 2 → Alpine.js (iniciar por 1-2 páginas) | Frontend |
| A-M1 | Reactivar roles desde panel admin | admin.php |

### 🟡 Sprint corto plazo (mejoras funcionales de alto impacto)

| ID | Mejora | Módulo | Beneficiario |
|----|--------|--------|-------------|
| A-M3 | Filtros en log de auditoría | admin.php | Admin |
| D-M1 | Filtro de período en KPI dashboard | reportes_kpi.php | Director |
| D-M3 | Tab "Pendientes de autorización" | all_presiones.php | Director |
| D-M5 | Campo comentario director en presiones | presiones + API | Director, Compras |
| C-M1 | Búsqueda combinada en selector de proveedor | nueva_requisicion.php | Compras |
| C-M2 | Columna monto total por hoja | hojas_requisicion.php | Compras, Residente |
| C-M5 | Alert de tope en enlazar_requisiciones | enlazar_requisiciones.php | Compras |
| F-M1 | Tab "Pendientes de pago" en presiones | presiones.php | Finanzas |
| F-M2 | Modal de pago con folio/banco | presiones + API | Finanzas |
| R-M1 | Columna monto total en lista de requisiciones | requisiciones.php | Residente |
| R-M2 | Badge estado prominente + historial hover | requisiciones.php | Residente |
| L-M1 | Ocultar botones CRUD sin permiso | Layout global | Lector |
| UX-11 | Paginación server-side en tablas | API + DataTables | Todos |

### 🟢 Mediano plazo (mejoras de calidad)

| ID | Mejora | Módulo | Beneficiario |
|----|--------|--------|-------------|
| UX-3 | Implementar Command Palette (Ctrl+K) | v4-layout.js | Todos |
| UX-4 | Completar dark mode en todas las páginas | v4.css | Todos |
| UX-7 | Validación en tiempo real de RFC/CLABE | agregar_proveedor.js | Compras |
| C-M3 | Duplicar requisición | requisiciones.php | Compras |
| F-M3 | Upload de comprobante de pago | presiones + storage | Finanzas |
| D-M6 | Notificaciones badge en topbar | v4-layout.js | Director, Admin |
| A-M6 | Campo `ultimo_acceso` en tabla users | admin.php | Admin |
| DT-1 | Migrar `catalago.php` a layout v4 | catalago.php | Todos |
| DT-7 | Implementar pruebas básicas (Playwright E2E) | Testing | Devs |

---

## Notas de arquitectura

### Patrón de estado actual
```
localStorage  → obraActiva (ID numérico), idRequisicion, idHoja, NameUser
sessionStorage → (sin uso consistente)
PHP $_SESSION  → usuario autenticado, CSRF token
```
**Recomendación:** Migrar `idRequisicion` e `idHoja` de localStorage a parámetros de URL (`?req=ID&hoja=ID`) para mejorar navegación con historial del navegador y evitar estado stale entre pestañas.

### Estructura de API recomendada
Actualmente los endpoints usan `$accion` como entero (1, 2, 3...). Considerar migrar a REST semántico en endpoints nuevos:
```
GET  /api/requisiciones.php?obra=X   → list
POST /api/requisiciones.php          → create (accion=6)
PUT  /api/requisiciones.php          → update (accion=8)
DELETE /api/requisiciones.php        → delete (accion=9)
```

---

*Documento generado automáticamente con análisis de código estático. Última actualización: 2026-05-26.*
