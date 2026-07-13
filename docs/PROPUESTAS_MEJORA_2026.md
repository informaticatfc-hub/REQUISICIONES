# Propuestas de Mejora y Actualización — The Fuentes Workspace v4.1

**Fecha de revisión:** 12 de junio 2026
**Estado del sistema:** Funcional con deuda técnica pendiente
**Rama activa:** `fase08-vistas-std`

---

## Contexto

Este documento consolida las propuestas de mejora derivadas de una revisión integral del código actual, el historial de fases documentadas en `docs/`, y los pendientes detectados en `REVISION_2026_05_27.md` y `STATUS_MEJORAS.md`. Las propuestas están ordenadas por prioridad y agrupadas por categoría.

---

## Resumen ejecutivo

El sistema cubre ~78% de la funcionalidad objetivo. Los módulos críticos (autenticación, RBAC, CRUD de requisiciones/presiones, exportación, auditoría) están operativos. Lo que resta son mejoras de seguridad, flujos incompletos, UX y modernización técnica.

**Clasificación de prioridades:**

| Prioridad      | Criterio                                          |
| -------------- | ------------------------------------------------- |
| P1 — Crítico | Bloquea producción o implica riesgo de seguridad |
| P2 — Alto     | Afecta flujo de trabajo principal de un rol       |
| P3 — Medio    | Mejora notable de UX o calidad                    |
| P4 — Bajo     | Niceness, deuda técnica menor                    |

---

## P1 — Crítico (Antes de ir a producción)w

### P1-1: Cabeceras HTTP de seguridad faltantes

**Problema:** Las cabeceras `X-Frame-Options`, `X-Content-Type-Options` y `Referrer-Policy` no se envían desde todas las páginas PHP. Solo el `.htaccess` las cubre parcialmente, pero cuando PHP responde directamente (en algunos endpoints) las cabeceras no se aplican.

**Impacto:** Vulnerabilidad a clickjacking y MIME sniffing.

**Solución:** Centralizar en `includes/layout_top.php` y en cada `crud_*.php` que no incluya el layout:

```php
// Al inicio de layout_top.php y de cada crud_*.php
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
```

**Esfuerzo estimado:** 1 hora
**Archivos afectados:** `includes/layout_top.php`, todos los `api/crud_*.php`

---

### P1-2: Botón "Reactivar rol" en panel Admin

**Problema:** La UI de `pages/admin.php` muestra un botón para desactivar roles, pero no tiene su contraparte de reactivación. Un rol inactivo solo puede reactivarse accediendo directamente a la base de datos.

**Impacto:** Bloquea recuperación de accesos sin acceso a DB en producción.

**Solución:**

1. Agregar en la tabla de roles (sección Roles del panel) un botón condicional:
   - Si `role_estatus = 'ACTIVO'` → mostrar botón "Desactivar"
   - Si `role_estatus = 'INACTIVO'` → mostrar botón "Reactivar" (con badge visual diferente)
2. Agregar endpoint `accion:X` en `api/crud_admin.php` para cambiar estatus

**Esfuerzo estimado:** 2 horas

---

### P1-3: Eliminar referencia a `main.css` en `all_presiones.php`

**Problema:** `pages/all_presiones.php` incluye `assets/css/main.css` (legacy v3) junto con `v4.css`. Esto genera conflictos visuales en modo oscuro y sobreescribe estilos del layout actual.

**Solución:** Eliminar la línea de inclusión de `main.css` en `all_presiones.php` y verificar visualmente que el layout no se rompa.

**Esfuerzo estimado:** 15 minutos + verificación

---

### P1-4: Validación de alcance de obra en endpoints críticos

**Problema:** Algunos endpoints de escritura en `api/crud_Presiones.php` y `api/crud_hojas_requisicion.php` validan el permiso RBAC pero no siempre validan que la obra del recurso pertenezca a las obras asignadas al usuario actual. Un residente podría (en teoría) modificar ítems de obras no asignadas si conoce los IDs.

**Solución:** En cada endpoint de modificación, después de verificar permisos:

```php
// Verificar que la obra del recurso está en las obras del usuario
$user_obras = tf_get_user_obras($pdo, $user['id']);
if (!in_array($obra_del_recurso, $user_obras) && $user['nivel'] < 60) {
    http_response_code(403);
    die(json_encode(['error' => 'Acceso denegado a esta obra']));
}
```

**Esfuerzo estimado:** 4 horas
**Archivos afectados:** `crud_Requisiciones.php`, `crud_hojas_requisicion.php`, `crud_items_requisiciones.php`, `crud_nueva_hoja.php`

---

## P2 — Alto (Primer sprint post-lanzamiento)

### P2-1: Modal de pago completo para Finanzas

**Problema:** El botón "Marcar como pagada" en `pages/presiones.php` cambia el estado directamente, sin capturar datos del pago. La migración `2026_05_28_011_pago_hoja_requisicion.sql` ya agregó los campos `folio_pago`, `banco_id`, `fecha_pago` a la tabla `presiones`, pero la UI no los utiliza.

**Solución:**

1. Reemplazar la acción directa por un modal con:
   - Campo `Folio de pago` (texto, requerido)
   - Selector `Banco` (dropdown cargado desde `crud_bancos.php`)
   - Campo `Fecha de pago` (date picker, default hoy)
   - Campo `Notas` (textarea, opcional)
2. Endpoint existente en `crud_Presiones.php` debe recibir y guardar estos campos
3. Mostrar estos datos en `presiones_detalles.php`

**Esfuerzo estimado:** 4–5 horas

---

### P2-2: UI para comentario del director en presiones

**Problema:** La columna `comentario_director` existe en la tabla `presiones` (migración `2026_05_26_010`), y el endpoint de autorizar/rechazar la recibe, pero la interfaz de `pages/presiones_detalles.php` no muestra ni permite editar este campo.

**Solución:**

1. En `presiones_detalles.php`, mostrar el comentario si existe (bloque de cita visual)
2. En el modal de "Autorizar/Rechazar" del director: agregar `<textarea>` para capturarlo
3. En la lista de `all_presiones.php`, mostrar icono de "tiene comentario" con tooltip

**Esfuerzo estimado:** 3 horas

---

### P2-3: Filtros en bitácora de auditoría

**Problema:** La sección de auditoría en `pages/admin.php` carga los últimos 50 registros sin filtros. Con el volumen esperado en producción (tabla `audit_log`), esto es inoperante para investigar incidentes.

**Solución:**

1. Agregar barra de filtros:
   - Rango de fechas (date picker doble)
   - Selector de usuario
   - Selector de tipo de acción (`LOGIN`, `CREATE`, `UPDATE`, `DELETE`, etc.)
   - Campo de búsqueda libre (entidad o descripción)
2. Implementar paginación server-side (20 registros por página)
3. Botón "Exportar CSV" de los resultados filtrados

**Esfuerzo estimado:** 6–8 horas
**Archivos afectados:** `pages/admin.php`, `api/crud_admin.php`

---

### P2-4: Filtro de período en dashboard KPI (Director)

**Problema:** El dashboard de dirección (`pages/index.php` / `pages/reportes_kpi.php`) muestra totales acumulados históricos. No hay forma de filtrar por semana, mes o año.

**Solución:**

1. Agregar selector de período en topbar del dashboard: `Semana / Mes / Trimestre / Año / Personalizado`
2. Pasar `fecha_inicio` y `fecha_fin` como parámetros a `api/crud_direccion.php`
3. Agregar filtro `WHERE fecha BETWEEN :inicio AND :fin` en las consultas SQL relevantes
4. Las gráficas de barras deben refrescarse al cambiar el período (llamada Alpine reactiva)

**Esfuerzo estimado:** 5–6 horas

---

### P2-5: Restablecimiento de contraseña desde panel Admin

**Problema:** El panel de admin permite crear y desactivar usuarios, pero no hay forma de restablecer la contraseña de un usuario desde la UI. Actualmente requiere acceso directo a la DB.

**Solución:**

1. Agregar botón "Resetear contraseña" en el modal de edición de usuario
2. Al ejecutarse: generar contraseña temporal (12 caracteres, alta entropía)
3. Mostrarla UNA VEZ en pantalla (modal con copia al portapapeles)
4. Guardar el hash en DB y activar flag `requiere_cambio_password = 1`
5. En `login.php`, si el flag está activo, redirigir a pantalla de cambio obligatorio

**Esfuerzo estimado:** 5 horas

---

## P3 — Medio (Segundo sprint)

### P3-1: Command Palette funcional (`Ctrl+K`)

**Problema:** El HTML de la Command Palette existe en `includes/layout_top.php` y el atajo de teclado está registrado en `assets/js/v4-layout.js`, pero la búsqueda no tiene lógica implementada. Solo abre y cierra el panel.

**Solución:**

1. Indexar en `window.TF_CONTEXT` las entidades navegables: obras, páginas del menú, acciones frecuentes
2. Al escribir en el input, filtrar localmente con `fuzzy match` (sin petición al servidor)
3. Para búsqueda en requisiciones/presiones: petición `debounced` de 300ms al endpoint correspondiente
4. Resultado: lista navegable con teclado (↑↓ Enter) que redirige o ejecuta acción
5. Categorías: Páginas, Obras, Requisiciones recientes, Acciones

**Esfuerzo estimado:** 8–10 horas
**Archivos afectados:** `includes/layout_top.php`, `assets/js/v4-layout.js`

---

### P3-2: Notificaciones badge en topbar

**Problema:** El topbar tiene un ícono de campana sin funcionalidad. No hay sistema de notificaciones para alertar sobre cambios de estado (requisición autorizada, presión rechazada, etc.).

**Solución (liviana, sin WebSockets):**

1. Crear tabla `notificaciones` (id, user_id, tipo, mensaje, leida, created_at)
2. Al cambiar estado de presión o requisición: insertar notificación para usuarios relevantes
3. `layout_top.php` hace polling cada 60s a un endpoint ligero (`api/notificaciones.php`) que retorna el conteo de no leídas
4. Click en campana: panel lateral con lista de notificaciones, marcar como leídas

**Esfuerzo estimado:** 8 horas
**Costo de polling:** mínimo (query SELECT COUNT + índice por user_id y leida)

---

### P3-3: Búsqueda combinada de proveedor (RFC + CLABE)

**Problema:** El selector de proveedor en `pages/nueva_requisicion.php` y `pages/hojas_requisicion.php` solo busca por nombre. Para validar al proveedor correcto, compras necesita ver RFC y CLABE.

**Solución:**

1. Reemplazar el `<select>` simple por un componente Alpine con búsqueda asíncrona
2. El dropdown muestra: `Nombre — RFC — CLABE (últimos 4 dígitos)`
3. Búsqueda funciona sobre los tres campos
4. Al seleccionar, mostrar tarjeta de resumen del proveedor debajo del campo

**Esfuerzo estimado:** 4 horas

---

### P3-4: Timeline visual de estatus en requisiciones

**Problema:** El residente no tiene una representación visual clara del flujo de su requisición. Solo ve el badge de estado, pero no sabe qué pasos faltan.

**Solución:**

1. Agregar componente de stepper/timeline en `pages/requisiciones.php` (vista de detalle):
   ```
   ● BORRADOR → ● PENDIENTE → ○ VALIDADA → ○ AUTORIZADA → ○ PAGADA
   ```
2. Mostrar fecha y usuario en cada paso completado
3. Mostrar próximo paso requerido con instrucción (ej. "Compras debe validar")

**Esfuerzo estimado:** 3–4 horas

---

### P3-5: Paginación server-side en `all_presiones.php`

**Problema:** La vista global de presiones carga todas las filas en una sola petición. Con el volumen esperado en producción, esta vista se volverá lenta y consumirá memoria innecesaria.

**Solución:**

1. Modificar `api/crud_all_presiones.php` para aceptar `page` y `per_page` como parámetros
2. Retornar `{ data: [...], total: N, pages: M }` en el payload
3. En el frontend, reemplazar la tabla estática por paginación Alpine reactiva
4. Mantener la exportación XLSX con todos los registros (sin paginación en ese caso)

**Esfuerzo estimado:** 6 horas

---

### P3-6: Ocultar botones CRUD para rol Lector

**Problema:** El rol Lector tiene sus peticiones bloqueadas en el backend, pero los botones de acción (Editar, Eliminar, Crear) siguen apareciendo en la UI. Esto genera confusión y errores 403 al intentar usarlos.

**Solución:**

1. Usar `TF.can(permission)` en el frontend (ya disponible en `window.TF_CONTEXT`) para condicionar la visibilidad con Alpine:
   ```html
   <button x-show="$store.tf.can('requisiciones.editar')">Editar</button>
   ```
2. Revisar todas las páginas y ocultar acciones no autorizadas según el rol
3. Mantener la validación backend como línea de defensa real

**Esfuerzo estimado:** 4 horas (revisión de todas las páginas)

---

### P3-7: Tab "Pendientes de autorización" para Director

**Problema:** La vista `all_presiones.php` del director mezcla todas las presiones sin destacar las que requieren su acción inmediata. El director debe filtrar manualmente para encontrar las pendientes.

**Solución:**

1. Agregar tab "Requieren autorización" que filtre por `estatus = 'PENDIENTE'`
2. Mostrar badge con conteo en el tab
3. Ordenar por antigüedad (las más viejas primero, con indicador de días sin revisión)
4. Opcionalmente: botón de "Autorizar todo lo seleccionado" con confirmación

**Esfuerzo estimado:** 3 horas

---

## P4 — Bajo (Mejoras de calidad y deuda técnica)

### P4-1: Eliminar Vue 2.7 del proyecto

**Problema:** `assets/lib/vue/` contiene Vue 2.7.16 (EOL desde diciembre 2023, sin parches de seguridad). Está movido a `legacy/` pero el bundle sigue presente en el repo y en la imagen Docker.

**Solución:**

1. Verificar que ninguna página activa referencia Vue (solo `assets/js/legacy/`)
2. Eliminar `assets/lib/vue/` del repositorio
3. Eliminar `assets/js/legacy/` si ningún archivo está en uso activo
4. Actualizar `.dockerignore` para excluir carpetas legacy

**Esfuerzo estimado:** 2 horas + verificación completa

---

### P4-2: Pruebas E2E básicas con Playwright

**Problema:** El único test existente es `tests/smoke.php` (prueba básica de conexión). No hay pruebas funcionales que validen los flujos críticos del sistema.

**Propuesta (mínimo viable):**
Implementar 5 pruebas E2E con Playwright que cubran los flujos de mayor riesgo:

1. Login exitoso y fallido (rate limiting)
2. Crear requisición (residente) → verificar aparece en lista
3. Validar requisición (compras) → cambio de estado
4. Autorizar presión (director) → estado actualizado
5. Marcar presión como pagada (finanzas)

**Esfuerzo estimado:** 10–12 horas
**Beneficio:** Detecta regresiones automáticamente en cada push

---

### P4-3: Autenticación de dos factores (2FA)

**Problema:** La tabla `two_factor_tokens` ya existe en el esquema, pero la funcionalidad está completamente desactivada. En un sistema con datos financieros, 2FA es una capa crítica especialmente para roles Admin y Director.

**Solución sugerida (TOTP con Google Authenticator compatible):**

1. Usar una librería PHP ligera (ej. `robthree/twofactorauth`)
2. En panel admin: opción "Activar 2FA" por usuario
3. Mostrar QR code para configurar app autenticadora
4. En login: si usuario tiene 2FA activo, pedir el código TOTP como segundo paso
5. Agregar opción de "códigos de recuperación" (10 códigos de un solo uso)

**Esfuerzo estimado:** 8 horas

---

### P4-4: Generación de PDF server-side

**Problema:** La generación de PDF de requisiciones se hace en cliente con jsPDF, lo que limita el control de formato, el tamaño del archivo y no funciona bien en móviles.

**Solución:**

1. Agregar `dompdf/dompdf` vía Composer
2. Crear template HTML de la requisición (`templates/pdf_requisicion.php`)
3. Endpoint `api/generar_pdf.php?id=X` que valide permisos y genere el PDF server-side
4. Mantener la generación cliente como fallback
5. Aplicar el logo de The Fuentes Corporation y formato corporativo

**Esfuerzo estimado:** 6 horas

---

### P4-5: Importar ítems desde Excel

**Problema:** Agregar ítems uno por uno en `pages/items_requisicion.php` es lento cuando una requisición tiene decenas de líneas. Muchos usuarios ya tienen sus listas de materiales en Excel.

**Solución:**

1. Botón "Importar desde Excel" en la página de ítems
2. Input `<type=file>` que acepta `.xlsx` y `.xls`
3. Parsear con SheetJS (ya disponible en el proyecto) en el cliente
4. Preview de las filas antes de confirmar (con validación visual de columnas)
5. Enviar el array de ítems al endpoint existente en `crud_items_requisiciones.php`
6. Template Excel descargable con las columnas correctas

**Esfuerzo estimado:** 6 horas

---

### P4-6: Plantillas de requisición

**Problema:** Compras y residentes frecuentemente crean requisiciones similares (ej. "materiales de limpieza mensual", "herramientas de seguridad"). No hay forma de guardar una requisición como plantilla reutilizable.

**Solución:**

1. Agregar tabla `plantillas_requisicion` (id, nombre, user_id, obra_id, items_json)
2. En `pages/nueva_requisicion.php`: botón "Guardar como plantilla" y "Cargar desde plantilla"
3. Las plantillas son privadas por defecto, con opción de compartir con la obra

**Esfuerzo estimado:** 8 horas

---

### P4-7: Modo responsive mejorado en tablas largas

**Problema:** Las tablas de `presiones.php`, `requisiciones.php` y `all_presiones.php` no tienen un comportamiento móvil adecuado. En pantallas pequeñas, las columnas se comprimen hasta ser ilegibles.

**Solución:**

1. Aplicar patrón "card stack" en móvil: cada fila se convierte en una tarjeta
2. Usar `@media (max-width: 768px)` para ocultar columnas secundarias
3. Agregar `data-label` en `<td>` para el patrón de tabla CSS responsiva
4. No requiere JavaScript adicional — solo CSS en `v4.css`

**Esfuerzo estimado:** 4 horas

---

## Mejoras de arquitectura a largo plazo

Estas propuestas son de mayor alcance y deben evaluarse en función del crecimiento del proyecto.

### A1: Migrar a una arquitectura de API REST separada

**Contexto:** El patrón actual `crud_*.php?accion=N` funciona pero mezcla routing, validación y lógica en un mismo archivo. Con el crecimiento del sistema, esto complica el mantenimiento.

**Propuesta:** Migrar gradualmente a un enrutador PHP ligero (ej. Slim Framework o un router custom) con rutas declarativas:

```
GET  /api/requisiciones        → listar
POST /api/requisiciones        → crear
PUT  /api/requisiciones/{id}   → actualizar
DELETE /api/requisiciones/{id} → eliminar
```

**Beneficio:** Permite un frontend completamente desacoplado (SPA o app móvil en el futuro), testing unitario más fácil, documentación automática (OpenAPI).

**Esfuerzo estimado:** 40+ horas (migración gradual, módulo por módulo)
**Recomendación:** Hacer en una fase dedicada, no mezclado con features nuevas

---

### A2: Sistema de colas para tareas asíncronas

**Contexto:** Algunas operaciones futuras (enviar notificaciones por email, generar PDFs pesados, calcular reportes grandes) no deben ejecutarse en el request HTTP.

**Propuesta:** Implementar una cola simple usando la misma DB MySQL:

1. Tabla `job_queue` (tipo, payload, estatus, intentos, created_at)
2. Worker PHP ejecutado por cron cada minuto
3. Jobs iniciales: envío de emails de notificación, generación de reportes

**Esfuerzo estimado:** 12 horas (cola + primer job funcional)

---

### A3: Observabilidad y logging estructurado

**Contexto:** Actualmente el sistema tiene `audit_log` para acciones de negocio, pero no hay logging de errores PHP centralizado ni métricas de rendimiento.

**Propuesta:**

1. Configurar Monolog (PHP) para logs estructurados (JSON) a archivo o stdout
2. En Docker: redirigir logs al sistema de contenedores
3. Agregar tiempo de respuesta de los endpoints más lentos a los logs
4. Alertas: si un endpoint supera los 2 segundos, registrar el query SQL involucrado

**Esfuerzo estimado:** 6 horas

---

## Plan de implementación sugerido

### Sprint 1 — Producción lista (2 semanas)

| ID              | Tarea                                       | Esfuerzo       |
| --------------- | ------------------------------------------- | -------------- |
| P1-1            | Cabeceras HTTP en todos los archivos        | 1h             |
| P1-2            | Botón reactivar rol en admin               | 2h             |
| P1-3            | Eliminar main.css de all_presiones          | 15min          |
| P1-4            | Validación de alcance de obra en endpoints | 4h             |
| P2-1            | Modal de pago (Finanzas)                    | 5h             |
| P2-2            | UI comentario director                      | 3h             |
| P2-5            | Resetear contraseña desde admin            | 5h             |
| P3-6            | Ocultar botones CRUD para Lector            | 4h             |
| **Total** |                                             | **~24h** |

### Sprint 2 — Flujos completos (2 semanas)

| ID              | Tarea                                | Esfuerzo       |
| --------------- | ------------------------------------ | -------------- |
| P2-3            | Filtros en bitácora de auditoría   | 8h             |
| P2-4            | Filtro de período en KPI            | 6h             |
| P3-1            | Command Palette funcional            | 10h            |
| P3-2            | Notificaciones badge                 | 8h             |
| P3-4            | Timeline de estatus en requisiciones | 4h             |
| P3-7            | Tab "Pendientes" para Director       | 3h             |
| **Total** |                                      | **~39h** |

### Sprint 3 — Calidad y deuda técnica (2 semanas)

| ID              | Tarea                                 | Esfuerzo       |
| --------------- | ------------------------------------- | -------------- |
| P3-3            | Búsqueda combinada de proveedor      | 4h             |
| P3-5            | Paginación server-side all_presiones | 6h             |
| P4-1            | Eliminar Vue 2 del proyecto           | 2h             |
| P4-2            | Pruebas E2E básicas (Playwright)     | 12h            |
| P4-5            | Importar ítems desde Excel           | 6h             |
| P4-7            | Tablas responsivas en móvil          | 4h             |
| **Total** |                                       | **~34h** |

### Fases futuras (planificar con stakeholders)

- Sprint 4: P4-3 (2FA), P4-4 (PDF server-side), P4-6 (Plantillas)
- Sprint 5+: A1 (REST API), A2 (Colas), A3 (Observabilidad)

---

## Estado por módulo

| Módulo        | Estado   | Pendiente principal                            |
| -------------- | -------- | ---------------------------------------------- |
| Autenticación | Completo | 2FA desactivado (P4-3)                         |
| RBAC           | Completo | Reactivar rol (P1-2)                           |
| Requisiciones  | Completo | Timeline (P3-4), plantillas (P4-6)             |
| Hojas e ítems | Completo | Importar Excel (P4-5)                          |
| Presiones      | Parcial  | Modal pago (P2-1), comentario director (P2-2)  |
| Catálogos     | Completo | Búsqueda RFC+CLABE (P3-3)                     |
| Obras          | Completo | —                                             |
| Admin          | Parcial  | Resetear pwd (P2-5), filtros auditoría (P2-3) |
| Dashboard KPI  | Parcial  | Filtro período (P2-4), drill-down             |
| Seguridad      | Parcial  | Cabeceras (P1-1), 2FA (P4-3)                   |
| Testing        | Mínimo  | E2E Playwright (P4-2)                          |

---

*Documento generado en base a revisión de código del 12 de junio 2026. Complementa `REVISION_2026_05_27.md` y `STATUS_MEJORAS.md`.*
