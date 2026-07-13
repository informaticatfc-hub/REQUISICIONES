# PLAN DE TRABAJO — Actualización v4.1 → v5.0
## The Fuentes Workspace — Sistema de Requisiciones

| Campo | Valor |
|---|---|
| **Fecha de elaboración** | 13 de julio de 2026 |
| **Inicio propuesto** | Lunes 13 de julio de 2026 (semana S1) |
| **Duración total** | 5 fases · 10 semanas calendario · ~140 h de esfuerzo |
| **Documentos base** | [01_PRD.md](01_PRD.md) (features N-xx) · [02_TDR.md](02_TDR.md) (entregables E-xx) |

> Esfuerzos tomados de las estimaciones ya validadas en `PROPUESTAS_MEJORA_2026.md` y los pendientes de junio. Las fases asumen un desarrollador principal; cada fase cierra con PR a `main`, pruebas de aceptación de usuarios clave (máx. 5 días hábiles) y despliegue.

---

## Fase 1 — Análisis, saneamiento y seguridad base (S1–S2 · 13–24 jul · ~26 h)

**Objetivo:** partir de una base estable y segura antes de construir features. Corresponde a E-01 y E-02.

| # | Tarea | Ref. | Esfuerzo | Dependencia |
|---|---|---|:---:|---|
| 1.1 | Verificación post-fixes del 29-jun en navegador (ítems visibles, historial, navbar, presiones pagadas), ambos temas | OBS-2/4d/7/8A | 3 h | — |
| 1.2 | Gate anti-regresión: script pre-commit (grep marcadores de conflicto + `php -l` + `node --check`) | TDR §5.1 | 2 h | — |
| 1.3 | Fix estatus inicial de presiones → EN REVISIÓN + orden de lista + duplicado como advertencia | OBS-1 / N-04 | 3 h | — |
| 1.4 | Fix PDF watermark + eliminar jsPDF 1.5.3 duplicado + eliminar `main.css` de all_presiones | P1/PDF/CDN/P1-3 | 1 h | — |
| 1.5 | Cabeceras HTTP de seguridad centralizadas (layout + todos los `crud_*.php`) | P1-1 | 1 h | — |
| 1.6 | **Validación de alcance de obra en endpoints de escritura** (requisiciones, hojas, ítems, presiones) + pruebas negativas por rol | N-03 / P1-4 | 4 h | — |
| 1.7 | Botón "Reactivar rol" en admin | P1-2 | 2 h | — |
| 1.8 | Saneamiento BD: resolver BD-1 (columna banco duplicada) y BD-4 (IDs huérfanos en `requisicionesligadas`) con queries de verificación | BD-1/BD-4 | 4 h | Respaldo BD |
| 1.9 | Migraciones `016` (fechas de estado en presiones) y `017` (presupuesto en obras) — escritura, prueba local, aplicación con respaldo | §4.3 Backend | 3 h | 1.8 |
| 1.10 | Trazabilidad de creador en presiones y hojas (patrón de cotizaciones) | OBS-5 / N-06 | 3 h | — |

**🏁 Hito H1 (24-jul):** sistema estabilizado y endurecido; migraciones 016–017 aplicadas; residente recibe 403 fuera de sus obras.

---

## Fase 2 — Diseño y cierre de flujos de negocio (S3–S4 · 27 jul–7 ago · ~30 h)

**Objetivo:** el ciclo captura→pago queda completo y documentado. Corresponde a E-03, E-04 y E-05.

| # | Tarea | Ref. | Esfuerzo | Dependencia |
|---|---|---|:---:|---|
| 2.1 | Definir máquina de estados completa de hoja (estados intermedios) con Compras/Dirección y aplicarla en backend + badges | OBS-4c / N-07 | 6 h | H1 |
| 2.2 | Modal de pago Finanzas (folio + banco + fecha + notas) y visualización en detalle | N-01 / P2-1 | 5 h | H1 |
| 2.3 | UI comentario del director (captura en autorizar/rechazar, visible en detalle, icono en lista) | N-02 / P2-2 | 3 h | H1 |
| 2.4 | PDF con creador real de la hoja + "impreso por" | OBS-6 | 2 h | 1.10 |
| 2.5 | Filtros + paginación server-side en auditoría admin + export CSV | N-08 / P2-3 | 8 h | — |
| 2.6 | Reset de contraseña desde admin (temporal un solo uso + cambio obligatorio; migración `019`) | N-08 / P2-5 | 5 h | — |
| 2.7 | Pruebas de aceptación de Fase 2 con Compras/Finanzas/Dirección | TDR §5.2 | 1 h | 2.1–2.6 |

**🏁 Hito H2 (7-ago):** flujo completo trazable: toda presión pagada tiene folio/banco/fecha; todo rechazo tiene comentario; admin opera sin acceso a BD.

---

## Fase 3 — Desarrollo de mejoras funcionales (S5–S7 · 10–28 ago · ~40 h)

**Objetivo:** KPIs con cortes reales, cotizaciones completas y conciliación fiscal. Corresponde a E-06, E-07 y E-08.

| # | Tarea | Ref. | Esfuerzo | Dependencia |
|---|---|---|:---:|---|
| 3.1 | Filtro de período en dashboard KPI (semana/mes/trimestre/año/personalizado) | N-09 / P2-4 | 6 h | H1 (migración 016) |
| 3.2 | Selector de obra única en KPI + KPIs nuevos (ciclo de autorización, % presupuesto, adeudo por proveedor) | N-09 / P2-6 | 6 h | 3.1, migración 017 |
| 3.3 | Cotizaciones con imágenes (MIME real JPG/PNG), integración del alta en `nueva_hoja` | OBS-3 / N-05 | 5 h | — |
| 3.4 | Rediseño de `nueva_hoja.php` (meta-strip, distribución, dark/light) + P-DARK en `presiones_detalles` | OBS-3 / P-DARK | 5 h | 3.3 |
| 3.5 | Búsqueda combinada de proveedor (nombre/RFC/CLABE) con tarjeta resumen | P3-3 | 4 h | — |
| 3.6 | Migración `015` (campos CFDI) + importación de XML SAT con conciliación contra hoja | N-12 / P3-9 | 7 h | Respaldo BD |
| 3.7 | Limpieza Excel export (valores planos, sin marca de agua, quitar CSV por obra) | P-EXCEL | 3 h | — |
| 3.8 | Tab "Pendientes de autorización" con conteo y orden por antigüedad (Director) | P3-7 | 3 h | — |
| 3.9 | Pruebas de aceptación de Fase 3 | TDR §5.2 | 1 h | 3.1–3.8 |

**🏁 Hito H3 (28-ago):** Dirección opera con KPIs por período/obra; cotizaciones (PDF/imagen) integradas; facturas SAT conciliables.

---

## Fase 4 — UX transversal, notificaciones y pruebas (S8–S9 · 31 ago–11 sep · ~30 h)

**Objetivo:** experiencia consistente y red de seguridad automatizada. Corresponde a E-09 y E-10.

| # | Tarea | Ref. | Esfuerzo | Dependencia |
|---|---|---|:---:|---|
| 4.1 | **Suite E2E Playwright** — 5 flujos: login/rate-limit, crear requisición, validar, autorizar presión, pagar | P4-2 / E-09 | 12 h | H2 (flujos cerrados) |
| 4.2 | Ocultar controles CRUD por permiso con `TF.can()` en todas las páginas (rol Lector) | P3-6 | 4 h | — |
| 4.3 | Notificaciones in-app: migración `018`, inserción en cambios de estado, badge con polling 60 s, panel lateral | N-11 / P3-2 | 8 h | H2 |
| 4.4 | Selector de obras escalable (>5 → catálogo con búsqueda) | OBS-8B / N-10 | 4 h | — |
| 4.5 | Timeline de estados en detalle de requisición | N-13 / P3-4 | 4 h | 2.1 |
| 4.6 | Tablas responsivas móvil (card-stack CSS) en presiones/requisiciones/all_presiones | P4-7 | 4 h | — |
| 4.7 | Limpieza legacy: eliminar `assets/lib/vue/` y `assets/js/legacy/`; vendorizar Alpine (quitar CDN) | P4-1 | 2 h | 4.1 (E2E en verde) |

**🏁 Hito H4 (11-sep):** 5/5 pruebas E2E en verde; UI consistente por rol y en móvil; repo sin legacy.

---

## Fase 5 — Endurecimiento final, despliegue y cierre (S10 · 14–18 sep · ~14 h)

**Objetivo:** capa final de seguridad y productividad; release v5.0. Corresponde a E-11 y E-12.

| # | Tarea | Ref. | Esfuerzo | Dependencia |
|---|---|---|:---:|---|
| 5.1 | 2FA TOTP opt-in para admin/director (QR + códigos de recuperación) | N-14 / P4-3 | 8 h | H4 |
| 5.2 | Command Palette funcional (índice local + búsqueda debounced) — *si el tiempo lo permite; si no, pasa a backlog* | P3-1 | (10 h) | — |
| 5.3 | Regresión completa: suite E2E + prueba manual por rol en ambos temas | E-09 | 2 h | 5.1 |
| 5.4 | Actualizar `docs/README.md`, changelog y estos 6 documentos al estado final | E-12 | 2 h | 5.3 |
| 5.5 | Despliegue v5.0 (respaldo → migraciones → archivos → humo) y cierre con sponsor | TDR §6 | 2 h | 5.4 |

**🏁 Hito H5 (18-sep):** **Release v5.0 en producción** — flujo completo, seguro, trazable y probado.

---

## Resumen de cronograma

| Fase | Semanas | Fechas 2026 | Esfuerzo | Hito |
|---|:---:|---|:---:|---|
| F1 Análisis y seguridad base | S1–S2 | 13 jul – 24 jul | ~26 h | H1 Sistema estabilizado |
| F2 Cierre de flujos | S3–S4 | 27 jul – 7 ago | ~30 h | H2 Ciclo captura→pago completo |
| F3 Mejoras funcionales | S5–S7 | 10 ago – 28 ago | ~40 h | H3 KPI + cotizaciones + CFDI |
| F4 UX y pruebas | S8–S9 | 31 ago – 11 sep | ~30 h | H4 E2E verde + UI consistente |
| F5 Endurecimiento y release | S10 | 14 sep – 18 sep | ~14 h | H5 **v5.0 en producción** |

### Cadena de dependencias críticas

```
1.8 Saneamiento BD ──► 1.9 Migraciones 016/017 ──► 3.1/3.2 KPIs por período y presupuesto
1.10 Creador ──► 2.4 PDF creador real
2.1 Estados de hoja ──► 4.5 Timeline
H2 Flujos cerrados ──► 4.1 Suite E2E ──► 4.7 Limpieza legacy y 5.x Release
```

### Backlog post-v5.0 (no comprometido)

PDF server-side con dompdf (P4-4) · Plantillas de requisición (P4-6) · Importar ítems desde Excel (P4-5) · Command Palette (si se difiere 5.2) · A1 API REST · A2 Colas · A3 Observabilidad.

### Reglas de gestión

- **Buffer:** cada fase incluye ~15 % de holgura implícita (horas estimadas vs. semana laboral); si una fase se desborda > 3 días, se recorta alcance de la siguiente empezando por tareas P3/P4.
- **Hotfix:** severidad crítica (vista rota, fuga entre obras, pago sin autorización) interrumpe el sprint; todo lo demás entra al backlog `PENDIENTES_*.md`.
- **Cierre de fase:** PR revisado + aceptación de usuarios (≤ 5 días hábiles) + despliegue + changelog.
