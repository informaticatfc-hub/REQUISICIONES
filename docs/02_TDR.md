# TDR — Términos de Referencia
## Proyecto: Actualización mayor del Sistema de Requisiciones — The Fuentes Workspace

| Campo | Valor |
|---|---|
| **Proyecto** | Actualización v4.1 → v5.0 (seguridad, flujos completos y trazabilidad) |
| **Contratante / Sponsor** | Dirección — The Fuentes Corporation |
| **Área ejecutora** | Informática The Fuentes |
| **Fecha** | 13 de julio de 2026 |
| **Documentos base** | [01_PRD.md](01_PRD.md) (requerimientos) · [06_PLAN_TRABAJO.md](06_PLAN_TRABAJO.md) (cronograma) |

---

## 1. Antecedentes

El sistema de requisiciones opera en producción (Hostinger, `thefuentescorp.com/TheFuentesApp`) y cubre aproximadamente el 78 % de la funcionalidad objetivo. Las revisiones de junio de 2026 (`REVISION_2026_06_22.md`, `PENDIENTES_2026-06-23/24/29.md`) documentaron: incidentes críticos ya corregidos (conflictos de merge commiteados, error 500 en historial, tabla de ítems invisible), flujos incompletos (pago sin captura de folio, comentario de director sin UI), brechas de seguridad (validación de alcance por obra, cabeceras HTTP, 2FA) y deuda técnica (librerías legacy, ausencia de pruebas automatizadas).

## 2. Objetivo general

Llevar el sistema a un estado de **producción robusta**: flujo de compra completo y trazable de punta a punta, seguridad reforzada, UI consistente (tema claro/oscuro, responsive) y base de pruebas automatizadas, sin cambiar el stack tecnológico.

### Objetivos específicos

1. Cerrar los flujos de negocio incompletos: modal de pago de Finanzas, comentario de Dirección, estatus inicial correcto de presiones y estados intermedios de hoja.
2. Reforzar seguridad: validación de alcance por obra en todos los endpoints de escritura, cabeceras HTTP, reset de contraseña administrado y (fase final) 2FA TOTP.
3. Completar trazabilidad: creador persistido en presiones y hojas, creador real en PDF, filtros de auditoría, campos fiscales CFDI.
4. Estandarizar UI/UX: dark/light en todas las vistas, rediseño de `nueva_hoja`, selector de obras escalable, tablas responsivas.
5. Establecer calidad: suite E2E de 5 flujos críticos, eliminación de código legacy (Vue 2), migraciones SQL pendientes aplicadas.

## 3. Alcance técnico

### 3.1 Incluye

| Área | Trabajo |
|---|---|
| **Backend PHP** | Modificación de endpoints `api/crud_*.php` existentes; nuevos endpoints solo para notificaciones, reset de contraseña e importación CFDI; helpers en `api/rbac.php` (alcance por obra, cabeceras) |
| **Base de datos** | Migraciones aditivas versionadas en `api/migrations/` (numeración continua a partir de la 015): campos CFDI, fechas de transición de estado en presiones, presupuesto/fechas en obras, tabla `notificaciones`; corrección de anomalías BD-1 a BD-5 documentadas en `docs/README.md` |
| **Frontend** | Vistas PHP en `pages/` + apps Alpine en `assets/js/`; sin build step; componentes sobre el sistema de diseño v4 (`assets/css/v4.css`) |
| **Seguridad** | Según [05_BACKEND.md §6](05_BACKEND.md); pruebas de control de acceso por rol y por obra |
| **Pruebas** | Suite E2E Playwright (5 flujos), `php -l` + `node --check` como gate mínimo por entrega, smoke test de BD |
| **Documentación** | Actualización de `docs/README.md`, changelog por sprint, guía de aplicación de migraciones |

### 3.2 No incluye

- Reescritura a framework backend o SPA (propuesta A1, fase futura).
- Cambios de hosting o infraestructura.
- Corrección de nomenclatura histórica de BD (`provedores`, etc.).
- Emisión/timbrado CFDI (solo importación y conciliación).

## 4. Entregables

| # | Entregable | Criterio de aceptación | Fase |
|---|---|---|:---:|
| E-01 | Sistema estabilizado (fixes P1: estatus inicial de presiones, watermark PDF, CSS legacy, reactivar rol) | Verificación visual + `php -l`/`node --check` sin errores; 0 marcadores de conflicto en repo | F1 |
| E-02 | Endurecimiento de seguridad (alcance por obra, cabeceras HTTP) | Prueba negativa: residente recibe 403 al escribir en obra ajena; cabeceras presentes en respuestas de páginas y API | F1 |
| E-03 | Flujo de pago completo (modal Finanzas + comentario Dirección + estados de hoja) | Presión pagada registra folio/banco/fecha; comentario visible en detalle; máquina de estados documentada y aplicada | F2 |
| E-04 | Trazabilidad de creador (presiones, hojas, PDF) | Columnas de creador pobladas en nuevas altas; PDF muestra creador real e "impreso por" | F2 |
| E-05 | Panel admin operativo (filtros auditoría, paginación, reset contraseña) | Búsqueda por fecha/usuario/acción; contraseña temporal de un solo uso con cambio obligatorio | F2 |
| E-06 | Dashboard KPI con período y obra única | Filtros semana/mes/trimestre/año; KPIs de ciclo de autorización y presupuesto (tras migraciones) | F3 |
| E-07 | Cotizaciones ampliadas (imágenes, integración en `nueva_hoja`, rediseño dark/light) | JPG/PNG/PDF hasta límite definido con validación de MIME real; visualización desde la hoja | F3 |
| E-08 | Importación CFDI XML + migraciones fiscales | XML del SAT parseado; UUID/RFC/total almacenados y conciliables contra hoja | F3 |
| E-09 | Suite E2E Playwright (5 flujos críticos) | Pruebas ejecutables localmente y en pre-despliegue; 5/5 en verde | F4 |
| E-10 | Limpieza legacy + UI responsive + notificaciones + Command Palette | Vue 2 eliminado del repo; tablas card-stack en móvil; badge de campana funcional | F4 |
| E-11 | 2FA TOTP (admin/director) + plantillas + importación Excel de ítems | Enrolamiento QR + códigos de recuperación; opt-in por usuario | F5 |
| E-12 | Documentación final actualizada | `docs/README.md` refleja el estado real; changelog completo; los 6 documentos de gestión actualizados | Todas |

## 5. Responsabilidades

### 5.1 Equipo ejecutor (Informática The Fuentes)

- Desarrollo, pruebas y despliegue de todos los entregables.
- Respaldo de BD **antes de cada migración** (`mysqldump`) y ventana de despliegue acordada.
- Trabajo en ramas por fase (convención actual `FaseNN_*`), merge a `main` vía Pull Request.
- Registro de cada entrega en changelog y actualización de documentación.
- No commitear conflictos de merge: gate `grep` de marcadores + `php -l`/`node --check` antes de cada commit (lección del incidente del 29-jun).

### 5.2 Usuarios clave / Negocio

- **Compras, Finanzas, Dirección**: pruebas de aceptación por entregable en ambiente de producción controlado (usuario Desarrollador), en un plazo máximo de 5 días hábiles por entrega.
- **Dirección (sponsor)**: priorización de cambios de alcance, decisión sobre límites (p. ej. tamaño máximo de cotizaciones) y aprobación de fases.
- Reporte de observaciones vía documentos en `docs/` (formato actual `PENDIENTES_AAAA-MM-DD.md`).

### 5.3 Matriz RACI resumida

| Actividad | Informática | Compras/Finanzas | Dirección |
|---|:---:|:---:|:---:|
| Desarrollo y migraciones | R/A | I | I |
| Pruebas de aceptación | C | R | A |
| Priorización de backlog | C | C | R/A |
| Despliegue a producción | R/A | I | I |
| Definición de reglas de negocio (estados, límites) | C | R | A |

## 6. Condiciones de ejecución

| Condición | Detalle |
|---|---|
| **Ambiente de desarrollo** | Local con Docker (`deploy/docker-compose.yml`) o XAMPP equivalente; BD clonada de los dumps `DataBase/u701868959_TFC_0*.sql` |
| **Ambiente de producción** | Hostinger VPS, MariaDB 11.8.6, PHP; despliegue por copia de archivos + ejecución manual de migraciones |
| **Control de versiones** | GitHub (`informaticatfc-hub`); rama por fase; PR a `main` con revisión |
| **Definition of Done** | Código lint-ok (`php -l`, `node --check`), sin marcadores de conflicto, probado visualmente en tema claro y oscuro, permisos verificados con al menos 2 roles, documentado |
| **Gestión de cambios** | Toda observación nueva entra al backlog (`PENDIENTES_*.md`) y se prioriza contra el plan; no se interrumpe el sprint activo salvo severidad crítica |
| **Criterio de severidad crítica** | Vista rota en producción, fuga de datos entre obras, pago sin autorización — se atiende de inmediato con hotfix |
| **Duración estimada** | 5 fases / ~10 semanas calendario (ver [06_PLAN_TRABAJO.md](06_PLAN_TRABAJO.md)) |

## 7. Riesgos y mitigación

| Riesgo | Prob. | Impacto | Mitigación |
|---|:---:|:---:|---|
| Migración SQL sobre datos reales corrompe información | Media | Alto | Respaldo previo obligatorio; migraciones aditivas; verificación post-migración con queries de control |
| Conflictos de merge commiteados rompen producción (ya ocurrió) | Media | Alto | Gate automático pre-commit (grep de marcadores + linters); PRs revisados |
| Regresiones al tocar vistas compartidas (layout, navbar) | Media | Medio | Suite E2E (E-09) lo antes posible; verificación en tema claro/oscuro y en vistas legacy |
| Datos anómalos existentes (IDs fuera de rango en `requisicionesligadas`, columnas duplicadas de banco) | Alta | Medio | Fase de saneamiento con queries de verificación antes de construir KPIs sobre esos datos |
| Disponibilidad limitada de usuarios clave para aceptación | Media | Medio | Ventana fija de 5 días hábiles; aceptación tácita documentada si no hay observaciones |
| Dependencia de CDN (jsDelivr) para Alpine | Baja | Medio | Vendorizar Alpine en `assets/lib/` como el resto de librerías |
