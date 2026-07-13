# UI/UX — Lineamientos de Diseño e Interfaz
## The Fuentes Workspace — Sistema de Requisiciones v4.1

| Campo | Valor |
|---|---|
| **Sistema de diseño** | v4 (`assets/css/v4.css` + Bootstrap 5.3) |
| **Fecha** | 13 de julio de 2026 |
| **Documentos relacionados** | [01_PRD.md](01_PRD.md) · [04_FLUJOS.md](04_FLUJOS.md) |

---

## 1. Principios de diseño

1. **La operación primero.** Los usuarios (residentes en obra, compras, finanzas) capturan y consultan datos a diario: menos clics, tablas legibles, acciones frecuentes a un clic, atajos (Ctrl+K).
2. **El estado siempre visible.** Cada requisición, hoja y presión muestra su estatus con badge de color consistente; el usuario nunca debe preguntarse "¿en qué paso va?" (refuerzo: timeline N-13).
3. **La UI oculta, el backend bloquea.** Los controles se muestran u ocultan con `TF.can(permiso)` según el rol, pero la seguridad real es server-side. Un botón oculto no es un control de acceso.
4. **Consistencia sobre novedad.** Toda vista nueva o rediseñada usa los componentes v4 existentes (`tf-page-header`, `tf-admin-table`, meta-strip). Prohibido reintroducir `main.css` (legacy v3) o DataTables sobre tablas Alpine.
5. **Feedback inmediato y honesto.** Toda mutación responde con toast/SweetAlert2; los errores del servidor se muestran como error, nunca como éxito (lección OBS-1).
6. **Tema dual obligatorio.** Ninguna vista se considera terminada si no se verifica en modo claro **y** oscuro.

---

## 2. Estructura de navegación

### 2.1 Arquitectura de la aplicación

```
Login (login.php)
└── Shell v4 (includes/layout_top.php + layout_bottom.php)
    ├── Topbar: logo · selector de obra · Command Palette (Ctrl+K) · campana · tema · usuario
    ├── Inicio / Dashboard ........... index.php (KPI según rol)
    ├── Requisiciones ................ requisiciones.php → nueva_requisicion.php
    │   └── Hojas de requisición ..... hojas_requisicion.php → nueva_hoja.php
    │       └── Ítems de la hoja ..... items_requisicion.php (+ cotizaciones)
    ├── Presiones de pago ............ presiones.php → presiones_detalles.php
    │   ├── Enlazar requisiciones .... enlazar_requisiciones.php
    │   └── Vista global (Director) .. all_presiones.php
    ├── Reportes KPI ................. reportes_kpi.php
    ├── Catálogos .................... menu_catalago.php
    │   ├── Proveedores .............. proveedores.php / agregar_proveedor.php
    │   ├── Bancos ................... bancos.php
    │   └── Obras .................... obras.php
    └── Administración ............... admin.php (usuarios, roles, auditoría)
```

### 2.2 Navegación por rol

El menú se construye según permisos del usuario (`window.TF_CONTEXT`):

| Rol | Vistas principales en su navegación |
|---|---|
| Residente | Requisiciones (sus obras), Hojas, Ítems, consulta de presiones de sus obras |
| Compras | Todo lo del residente + Proveedores, Enlazar requisiciones, Presiones |
| Finanzas | Presiones (pagar), Bancos, consulta general |
| Dirección | Dashboard KPI, all_presiones (autorizar), consulta global |
| Admin | Todo + Panel de administración |
| Lector | Solo vistas de consulta, sin botones de acción (pendiente P3-6 de generalizar) |

### 2.3 Selector de obra (navbar)

- Con ≤ 5 obras asignadas: dropdown directo en topbar (comportamiento actual).
- Con > 5 obras (pendiente N-10): el dropdown se reemplaza por acceso a un **catálogo de obras con búsqueda** por nombre; la obra activa queda fijada en sesión y visible en el topbar.

---

## 3. Lineamientos visuales

### 3.1 Identidad

| Elemento | Definición |
|---|---|
| Marca | The Fuentes Corporation — logo en `images/LogoFuentes.png` / `logoFuentes.svg` |
| Tipografía | **Inter** (Google Fonts), pesos 400/500/600/700 |
| Iconografía | Bootstrap Icons 1.11 + set propio SVG en `images/icons/` |
| Base CSS | Bootstrap 5.3 + tokens propios en `assets/css/v4.css` |

### 3.2 Semántica de color de estados

Los badges de estatus usan color consistente en **todas** las vistas:

| Estado | Uso | Color |
|---|---|---|
| NUEVO / BORRADOR / EN REVISIÓN | Documento capturado, sin validar | Gris / azul neutro |
| PENDIENTE | Espera acción de otro rol | Amarillo/ámbar |
| VALIDADA / LIGADA / AUTORIZADA | Aprobado en su etapa | Verde |
| PAGADA | Ciclo cerrado | Verde oscuro / con icono de pago |
| RECHAZADA | Devuelto con comentario | Rojo |
| CERRADA / INACTIVO | Archivado / deshabilitado | Gris oscuro |

### 3.3 Componentes estándar v4

| Componente | Uso |
|---|---|
| `tf-page-header` | Título de página + acciones primarias (un solo botón primario por vista) |
| Meta-strip compacto | Datos de contexto del documento (obra, número, fechas, creador) en franja horizontal, no apilados |
| `tf-admin-table` | Tabla estándar theme-aware; **Alpine `x-for` es el único dueño del DOM de la tabla** (nunca DataTables encima) |
| Modales Bootstrap | Captura de acciones con datos (pago, comentario director, reset contraseña) |
| SweetAlert2 / toasts | Confirmaciones destructivas y resultado de operaciones |
| Command Palette | Overlay Ctrl+K: páginas, obras, requisiciones recientes, acciones |

### 3.4 Modo claro / oscuro

- Toggle en topbar, persistencia de preferencia del usuario.
- Todos los componentes usan variables del tema; prohibido color hardcodeado en vistas.
- **Deuda activa (P-DARK)**: `presiones_detalles.php`, `nueva_hoja.php` — deben migrarse al patrón corregido en `hojas_requisicion.php` (29-jun).
- Checklist de entrega: captura de pantalla en ambos temas.

---

## 4. Accesibilidad

| Requerimiento | Lineamiento |
|---|---|
| Contraste | Mínimo WCAG AA (4.5:1 texto normal) en ambos temas; los badges de estado no dependen solo del color (incluyen texto) |
| Teclado | Command Palette navegable con ↑ ↓ Enter Esc; modales con focus-trap y cierre con Esc; formularios navegables con Tab en orden lógico |
| Formularios | Todo `input` con `label` asociado; errores de validación anunciados junto al campo, no solo en toast |
| Semántica | Tablas con `<th scope>`; botones reales (`<button>`) y no divs clicables; iconos con `aria-label` cuando van solos |
| Idioma | `lang="es"`; textos de interfaz y mensajes de error en español |
| Estados de carga | Indicador visible durante peticiones (spinner/skeleton); botones deshabilitados durante submit para evitar dobles envíos |

---

## 5. Responsividad

| Breakpoint | Comportamiento |
|---|---|
| ≥ 1200 px (escritorio) | Experiencia completa: tablas anchas, dashboard con gráficas lado a lado |
| 768–1199 px (tablet) | Grid Bootstrap colapsa a 2 columnas; topbar compacta |
| < 768 px (móvil) | **Patrón card-stack** (pendiente P4-7): cada fila de tabla se vuelve tarjeta usando `data-label` en `<td>` + CSS; columnas secundarias ocultas; acciones en menú contextual |

Reglas:

- Uso operativo principal es escritorio; el móvil se optimiza para **consulta y aprobación** (Dirección autorizando desde el teléfono), no para captura masiva.
- Sin scroll horizontal en el body en ningún breakpoint; tablas anchas dentro de contenedor con scroll propio.
- La generación de PDF en móvil es limitada (jsPDF cliente); el PDF server-side (P4-4) resolverá este caso.

---

## 6. Patrones de interacción por flujo

| Flujo | Patrón UI |
|---|---|
| Captura de requisición/hoja | Formulario por pasos en página (no wizard modal); autoguardado no requerido, pero confirmación al abandonar con cambios |
| Adjuntar cotización | Zona de carga con arrastrar/soltar + botón; acepta PDF/JPG/PNG; preview y enlace "Ver/Imprimir" tras subir; error claro si excede el límite |
| Selección de proveedor | Combo con búsqueda asíncrona que muestra `Nombre — RFC — CLABE (últimos 4)` y tarjeta resumen al seleccionar (N/P3-3) |
| Autorización (Director) | Vista global con tab "Requieren autorización" + badge de conteo, orden por antigüedad; modal con textarea de comentario obligatorio al rechazar |
| Registro de pago (Finanzas) | Modal con folio (requerido), banco (dropdown de catálogo), fecha (default hoy), notas; los datos quedan visibles en el detalle |
| Seguimiento (Residente) | Timeline horizontal de estados con fecha y usuario por paso completado y siguiente acción esperada |
| Notificaciones | Badge numérico en campana (polling 60 s); panel lateral con lista y "marcar como leídas" |

---

## 7. Deuda de UI activa (entrada del plan de trabajo)

| ID | Vista | Problema | Referencia |
|---|---|---|---|
| P-DARK | `presiones_detalles`, `nueva_hoja` | Tablas/fondos no respetan tema oscuro | PENDIENTES_2026-06-24 |
| OBS-3 | `nueva_hoja.php` | Distribución de datos mal estructurada; integrar carga de cotización | PENDIENTES_2026-06-29 |
| OBS-8B | Navbar | Selector de obras se desborda con >5 obras | PENDIENTES_2026-06-29 |
| P-PROV-UI | `proveedores.php` | Tabs inactivos y tarjetas KPI sin función | PENDIENTES_2026-06-24 |
| P3-6 | Todas | Botones CRUD visibles para rol Lector (403 al usar) | PROPUESTAS_MEJORA_2026 |
| P4-7 | Tablas largas | Sin patrón móvil card-stack | PROPUESTAS_MEJORA_2026 |
