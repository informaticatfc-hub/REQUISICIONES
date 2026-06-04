# Propuesta integral de mejoras — The Fuentes Workspace v4

> Documento que acompaña la nueva propuesta visual ubicada en
> [`/preview/index.html`](../preview/index.html). Aquí se detallan las mejoras
> **reales al flujo de trabajo**, no sólo cambios visuales.

---

## 1. Diagnóstico del estado actual (v3)

### 1.1. UX / UI

| Problema detectado | Impacto en el usuario |
|---|---|
| **Sidebar fijo** de 260 px ocupando siempre el lado izquierdo. | Resta ancho útil; las tablas y formularios se sienten "comprimidos" en pantallas 1366×768. |
| Sidebar repite **todas las obras** como sub-lista. | Si hay 30+ obras la barra se llena y obliga a scroll vertical interno. |
| **Encabezado triple** (topbar negro + breadcrumb + page-header) consume ~150 px verticales antes del contenido real. | Menos contenido visible "above the fold". |
| `container` con `max-width: 1400px` y `padding: 24px 28px`. | En monitores 1920+ queda un cinturón gris enorme a los lados. |
| Tipografía Roboto Flex con muchos pesos cargados pero usados de forma inconsistente. | Carga extra y jerarquía visual poco clara. |
| **Iconos como imágenes SVG sueltas** (`<img src="../images/icons/obras.svg">`). | Imposible cambiarles color con CSS, no escalan con la tipografía, requieren HTTP por cada uno. |
| Sin **modo oscuro**. | Fatiga visual en jornadas largas en obra/oficina. |
| Mensaje "Bienvenido, {NameUser}" pero **ninguna métrica accionable** en el inicio. | El usuario no sabe qué hacer al entrar. |
| **Sin buscador global**. Para llegar a una obra hay que pasar por menú → catálogo → … | 4-5 clics por tarea típica. |

### 1.2. Código / Arquitectura

| Hallazgo | Riesgo |
|---|---|
| `pages/*.php` repiten **~70 líneas idénticas de `<head>`** y todo el sidebar. | Mantener cambios visuales requiere editar 20+ archivos. |
| **Vue 2.5.16** (2018). Sin soporte oficial desde 2023. | Sin parches de seguridad. |
| **jQuery 3.7.1 slim** + jQuery completo cargado en otros archivos (duplicado). | ~90 KB innecesarios. |
| Bootstrap 5.3 local + DataTables 2.0 desde CDN sin SRI. | Inconsistente. |
| `crud_*.php` reciben `accion` como entero suelto. Mezcla `switch` enormes (hasta 400 líneas en `crud_all_presiones.php`). | Difícil de auditar; cualquier cambio en SQL es de alto riesgo. |
| `localStorage.setItem("NameUser", user_id)` — guarda el **ID numérico** bajo un nombre engañoso, y el ID se usa para autenticación de cliente. | Si el usuario edita el localStorage, ¡se hace pasar por otro! La validación real depende de `$_SESSION["Usuario"]`, pero el front confía en localStorage. |
| `validarSesion.php` sólo verifica `$_SESSION["Usuario"]`, sin chequeo de rol por página. | Cualquier usuario logeado puede tocar `/pages/direccion.php` directamente si conoce la URL. |
| Sin **CSRF tokens** en los `axios.post`. | Vulnerable a Cross-Site Request Forgery. |
| `LoginAcces.php` migra a hash al primer login válido (bueno) pero el **`hash_equals` se hace contra password plano** mientras existan usuarios sin migrar. | Mientras dure la transición, las contraseñas viajan y se comparan en claro. |
| **Falta `Content-Security-Policy`, `X-Frame-Options`, `Referrer-Policy`**. | Permite clickjacking y leakage. |
| `error_log` y `die("Error interno de conexion")` exponen estructura. | OK como genérico, pero conviene unificar mensajes JSON. |
| **No hay paginación** real en endpoints de lectura (`SELECT * FROM obras ORDER BY id DESC LIMIT 12` solamente). | A 200+ obras / 5000+ requisiciones el dashboard se ralentiza. |
| Las requisiciones con cálculos financieros usan `DECIMAL(16,6)` pero en JS se suman con `parseFloat`. | Errores de redondeo en montos grandes. |

### 1.3. Flujo de trabajo (UX procesual)

Hoy, para crear una requisición típica el usuario:

1. Login.
2. Click en "Obras" (sidebar).
3. Click en el nombre de la obra (sublista).
4. Click en "Requisiciones".
5. Click en "Nueva hoja".
6. Click en "Agregar ítem" (varias veces).
7. Guardar.
8. Volver a Inicio. Buscar otra obra. Repetir.

**Total: 6-8 clics + 2-3 navegaciones de página** sólo para empezar.

---

## 2. Filosofía de la nueva propuesta v4

> *"Optimizar para la tarea más frecuente: crear y revisar requisiciones de compra."*

Principios:

1. **Densidad útil**: aprovechar la pantalla completa sin sentirse atiborrado.
2. **Navegación predecible**: una sola barra superior persistente.
3. **Tareas en 1 clic** o vía teclado (`Ctrl+K`).
4. **Contexto siempre visible** (obra activa, rol, pendientes).
5. **Sin perder lo existente**: la BD (`obras`, `requisiciones`, `presiones`,
   `itemrequisicion`, `users.user_directionAcess`) se mantiene; sólo cambia
   capa de presentación y se añaden tablas auxiliares.

---

## 3. Cambios visuales clave (v3 → v4)

### 3.1. Eliminación del sidebar

- Reemplazado por **topbar única** (`tf-topbar`) con:
  - Logo / marca compacta a la izquierda.
  - Navegación principal con dropdowns (Obras, Catálogos, etc.).
  - **Buscador global con atajo `Ctrl+K`** (command palette).
  - Acciones: tema, notificaciones, usuario.
- La lista de obras pasa de "barra lateral" a:
  - **Dropdown rápido** en el menú "Obras" (5–8 más recientes).
  - **Página completa "Obras"** con grid + búsqueda + filtros (la lista
    completa con metadatos: residente, ciudad, # requisiciones, etc.).

**Ganancia**: +260 px de ancho útil siempre. Las tablas dejan de sentirse
apretadas, los formularios respiran.

### 3.2. Layout fluido con `max-width: 1600px`

- Centrado, pero hasta 1600 px (antes 1400). En monitores 1920+ ya no
  queda un cinturón gris enorme.
- Padding lateral consistente `24px`.

### 3.3. **Modo claro / oscuro** automático

- Variable `data-bs-theme="dark"` (Bootstrap 5.3 nativo).
- Detecta `prefers-color-scheme` y permite override manual con persistencia
  en `localStorage`.

### 3.4. KPIs accionables al entrar

El dashboard nuevo muestra al instante:

- Obras activas.
- Requisiciones pendientes (con tendencia ▲▼).
- Aprobadas este mes.
- Presiones a autorizar (con monto total).
- "Mis pendientes" (tareas asignadas a mí).
- **Actividad reciente** con timeline.

### 3.5. **Command palette** (`Ctrl+K`)

- Buscar obras, requisiciones, proveedores, **y disparar acciones**
  ("Crear nueva requisición") desde cualquier pantalla.
- Reduce tareas de 6 clics a 1 atajo de teclado.

### 3.6. Tipografía + iconos

- Migración a **Inter** (más legible en UI densas que Roboto Flex; igual de
  libre).
- Migración a **Bootstrap Icons 1.11** como *icon-font* (CSS-coloreable,
  un solo archivo, escala con `font-size`).

---

## 4. Mejoras reales al flujo de trabajo

| # | Mejora | Ahorro estimado |
|---|---|---|
| 1 | **Command palette `Ctrl+K`** para crear requisición / saltar a obra. | 6 → 1 clic |
| 2 | **Plantillas de requisición** (guardar conjunto de ítems frecuentes). Útil cuando una obra repite los mismos materiales semana a semana. | 5–10 min por requisición repetida |
| 3 | **Importar ítems desde Excel/CSV** (`xlsx` JS). El residente pega su cotización y se llena la tabla. | El "agregar ítem uno por uno" desaparece |
| 4 | **Estados claros del documento**: `Borrador → En validación → Autorizada → Pagada → Cerrada`, con badges visibles y filtros. Hoy el flujo está implícito en `hojarequisicion_comentariosValidacion/Autorizacion`. | Visibilidad total del status |
| 5 | **Autoguardado de borradores** en el cliente (`localStorage` + sync al servidor cada 30 s). | Cero pérdidas por timeout |
| 6 | **Notificaciones in-app** y por correo cuando hay algo que necesita tu atención (validar, autorizar). | Reemplaza el "avísame por WhatsApp" |
| 7 | **Adjuntar archivos** (cotizaciones, fotos) directamente a la requisición. | Adiós a los correos paralelos |
| 8 | **Búsqueda global con índice**: una sola caja encuentra requisiciones, proveedores y obras (no tablas dispersas). | Reduce navegación zigzag |
| 9 | **Filtros persistentes por usuario** (recordar "ver sólo Plaza Norte, sólo esta semana"). | Cada login arranca con el contexto del residente |
| 10 | **Generación de PDF en servidor** (hoy es jsPDF en cliente — frágil con tablas largas). Migrar a `dompdf` (PHP) o `Browsershot`. | PDFs consistentes y reproducibles |
| 11 | **Acciones masivas** (validar múltiples requisiciones desde la lista, exportar batch). | De N×3 clics a 2 clics |
| 12 | **Modo offline mínimo**: caching de catálogos (proveedores, productos) con Service Worker para residentes en obra con mala señal. | El residente puede capturar y sincroniza después |
| 13 | **Comentarios y menciones** (`@maria`) dentro de la requisición. | Conversación trazable por documento |
| 14 | **Historial de versiones** de cada requisición. | Auditoría sin SQL manual |
| 15 | **Atajos de teclado documentados** (`/` para buscar, `N` nueva requisición, `G O` ir a obras). | Productividad de power-users |

---

## 5. Sistema de roles propuesto

Ver [`ROLES.md`](./ROLES.md) para el detalle completo. Resumen:

| Rol | Puede ver | Puede crear | Puede validar | Puede autorizar | Puede pagar |
|---|---|---|---|---|---|
| **Administrador** | Todo | Todo | Sí | Sí | Sí |
| **Director / CEO** | Todo | — | — | Sí (Presiones) | Sí |
| **Residente de obra** | Sólo sus obras | Requisiciones | — | — | — |
| **Compras** | Todas | Requisiciones, Proveedores | Sí | — | — |
| **Finanzas / Tesorería** | Todas | — | — | — | Sí |
| **Lector / Auditor** | Todo (sólo lectura) | — | — | — | — |

Hoy el sistema sólo tiene una bandera `user_directionAcess` (1/0). Se
extiende a un sistema RBAC limpio.

---

## 6. Mejoras técnicas

### 6.1. Librerías actualizadas

| Librería | Versión actual (v3) | Propuesta (v4) | Motivo |
|---|---|---|---|
| Bootstrap | 5.3.3 (local) | 5.3.3 + Icons 1.11 | Mantener, añadir tema oscuro |
| Vue | 2.5.16 (2018) | **Vue 3.4** (Composition API) o **Alpine.js 3** | Vue 2 EOL diciembre 2023 |
| jQuery | 3.7.1 slim + full | **Eliminar** | Bootstrap 5 ya no lo requiere |
| Axios | latest CDN | **Fetch nativo** o Axios pinned | Reducir CDN |
| DataTables | 2.0.8 | **TanStack Table** + Vue 3 o quedarse con DT 2.x | TanStack es headless, más flexible |
| SweetAlert2 | 11.x local | Mantener | Excelente UX |
| jsPDF | (PDF cliente) | **dompdf** server-side | PDFs consistentes |
| — | — | **xlsx (SheetJS)** | Importar Excel |
| — | — | **Chart.js 4** | Gráficas KPI |
| Fuentes | Roboto Flex | **Inter** | Mejor legibilidad UI |

### 6.2. Backend

- **Plantilla de página** (`layout.php`) con `<head>` y header únicos.
  Las páginas pasan a tener 20-30 líneas en lugar de 200.
- **Router PHP** simple (`index.php?r=requisiciones/nueva`) o mantener
  `/pages/*` pero con un *bootstrap.php* común.
- **Middleware de autorización**: `requireRole(['admin','compras'])`.
- **CSRF token** por sesión, verificado en cada `crud_*.php`.
- **Logger estructurado** (`api/log.php`) en lugar de `error_log` suelto.
- **Migraciones SQL versionadas** en `/db/migrations/` (hoy hay
  `consultas.sql` con SQL acumulado).
- Headers de seguridad vía `.htaccess`:
  ```apache
  Header set X-Frame-Options "SAMEORIGIN"
  Header set X-Content-Type-Options "nosniff"
  Header set Referrer-Policy "strict-origin-when-cross-origin"
  Header set Content-Security-Policy "default-src 'self'; ..."
  Header set Strict-Transport-Security "max-age=31536000; includeSubDomains"
  ```

### 6.3. Bugs detectados

| Bug | Archivo / línea | Severidad |
|---|---|---|
| `localStorage.setItem("NameUser", user_id)` — el front confía en este valor para `consultarUsuario(user_id)`. Editable por el usuario. | `login.js` ~52, `index.js` ~36 | **Alta** — debería usar la sesión del servidor (`/api/me`) |
| `JOIN` en `consultas_legacy.sql` referencia tabla `provedores` (con typo) — confirma que la tabla está en BD con ese typo. Si se renombra, romper. | `docs/consultas_legacy.sql` | Media |
| En `LoginAcces.php`, si `$user["user_password"]` es null no se evalúa con `isset`, lo cual sí lo cubre, pero el flujo de migración a hash mantiene comparaciones en plano. | `api/LoginAcces.php` | Media |
| `crud_index.php` interpola `$limite` directo al SQL (`LIMIT $limite`). Lo castea a `int` y lo recorta, pero idealmente debería ir vía `bindValue(PDO::PARAM_INT)`. | `api/crud_index.php` | Baja |
| `assets/lib/jquery/jquery-3.7.1.slim.min.js` cargado en login pero el formulario nunca usa jQuery. | `pages/login.php` | Baja |
| El popper.js viene de `unpkg.com/@popperjs/core@2/dist/umd/popper.js` (sin minify ni SRI). | múltiples páginas | Baja |
| `users[0].user_directionAcess` se accede sin verificar `users.length` en algunos `v-if`. | `pages/index.php` | Baja |
| `meta http-equiv="refresh"` en `index.html` se ejecuta junto a `window.location.replace`. Redundante. | `index.html` | Cosmético |
| BOM (`﻿`) al inicio de muchos archivos PHP — puede causar `headers already sent`. | varios `.php` | Media |
| `validarSesion.php` calcula `$loginPath` con regex de la URL — frágil. Mejor: ruta absoluta resuelta con `BASE_URL`. | `validarSesion.php` | Baja |
| `pdfGenerate.js` (356 líneas) genera el PDF cliente; cualquier cambio de plantilla obliga a redeploy del JS. | `assets/js/pdfGenerate.js` | Media |

### 6.4. Performance

- **Bundle dedicado** (`vite` o esbuild) → un único `app.min.js` por
  página, no 4 CDNs + 3 locales.
- **HTTP caching** con `Cache-Control: max-age=31536000, immutable` para
  `/assets/lib/*`.
- **Lazy load** de DataTables sólo en páginas que lo usan.
- **Preconnect + preload** para Google Fonts.
- Eliminar todos los `.css.map` en producción (no se sirven, pero ocupan
  repo).

---

## 7. Plan de implementación sugerido (fases)

### Fase 1 — Visual (1 sprint)
- Aprobar diseño v4 (`preview/index.html`).
- Crear `layout.php` con nueva topbar.
- Migrar `index.php` y `login.php` a la nueva plantilla.
- Sustituir SVG sueltos por Bootstrap Icons.
- **Sin tocar BD**.

### Fase 2 — Roles y seguridad (1 sprint)
- Tabla `roles`, `user_roles`, `role_permissions`.
- Middleware `requireRole()` en API.
- CSRF token.
- Headers de seguridad.
- Migrar `user_directionAcess` → rol `director`.

### Fase 3 — Productividad (2 sprints)
- Command palette.
- Plantillas de requisición.
- Importar Excel.
- Autoguardado.
- Filtros persistentes.

### Fase 4 — Notificaciones y archivos (1 sprint)
- Adjuntos por requisición.
- Notificaciones in-app (tabla `notifications`).
- Email cuando hay validación pendiente.

### Fase 5 — Reportes y auditoría (1 sprint)
- PDF server-side con `dompdf`.
- Historial de cambios (`audit_log`).
- Dashboard de KPIs con Chart.js.

### Fase 6 — Modernización JS (1 sprint, opcional pero recomendado)
- Migrar de Vue 2 → **Vue 3** o **Alpine.js**.
- Eliminar jQuery.
- Bundler.

---

## 8. Cómo abrir la vista previa

```bash
# Servir en local:
cd /home/user/webapp
php -S 0.0.0.0:8080
# Abrir http://localhost:8080/preview/index.html
```

O simplemente abrir `preview/index.html` en el navegador.

Atajos en la demo:
- `Ctrl + K` → buscador global.
- Icono ☾ en topbar → cambia tema claro/oscuro.

---

## 9. Estimación de impacto

| Métrica | Hoy (v3) | Con v4 |
|---|---|---|
| Clics para crear requisición | 6–8 | 2 (1 con Ctrl+K) |
| Tiempo "aterrizar y saber qué hacer" | ~15 s | ~3 s (KPIs al frente) |
| Ancho útil en 1366×768 | ~1106 px | ~1366 px |
| Archivos PHP por modificar para un cambio de header | 20+ | 1 (`layout.php`) |
| Bundle inicial (sin DataTables) | ~520 KB | ~280 KB |
| Acceso teclado | No | Sí (paleta + atajos) |
| Modo oscuro | No | Sí |
| Compatibilidad móvil | Aceptable | Completa con menú hamburguesa |

---

*Documento elaborado como parte de la revisión integral del proyecto
**REQUISICIONES** — The Fuentes Corporation. Mayo 2026.*
