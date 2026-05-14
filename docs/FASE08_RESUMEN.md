# FASE 08 - Resumen de cambios

Fecha: 2026-05-14
Rama de trabajo: fase08-vistas-std

## Objetivo
Estandarizar vistas legacy, corregir flujos de obra activa, unificar navegacion visual y eliminar exportaciones Excel obsoletas.

## Cambios realizados

### 1) Estandarizacion de navegacion en vistas legacy
- Se unifico topbar con menu de ACCIONES por rol.
- Se retiro la dependencia de inyeccion dinamica de acciones basada en sidebar.
- Se eliminaron includes de layout_sidebar.js en vistas legacy.
- Se retiro el archivo assets/js/layout_sidebar.js del repositorio.

### 2) Correccion de layout visual
- Se corrigio conflicto CSS de sidebar-collapsed para evitar desplazamientos laterales no deseados.
- La vista principal queda alineada a la izquierda cuando la sidebar esta oculta globalmente.

### 3) Excel moderno (.xlsx)
- Se migro exportacion en detalle de presiones a .xlsx con SheetJS.
- Se agrego carga de SheetJS en la vista de presiones_detalles.
- Se mantuvo all_presiones con import/export en .xlsx y soporte de CSV.

### 4) Limpieza de API de detalle de presion
- Se removieron headers legacy de descarga .xls en crud_presionDetail.php.
- El endpoint quedo con respuesta API JSON consistente.
- El caso de exportacion server-side se marco como deprecado en favor de generacion frontend .xlsx.

### 5) Flujo de obra activa
- Se ajustaron vistas y scripts para evitar arrastre no intencional de obra activa.
- Se reforzo seleccion manual de obra donde aplica.
- Se deshabilito dependencia de obra activa en flujo directivo donde no corresponde.

### 6) Documentacion actualizada
- Se actualizaron docs de fases para reflejar estado real:
  - layout_sidebar.js ya fue retirado.
  - main.css permanece temporalmente como legacy mientras termina migracion total a layout v4.

## Estado final
- Sin errores nuevos reportados en los archivos modificados.
- Vistas legacy con navegacion mas uniforme.
- Exportaciones Excel en flujo principal ya no usan .xls legacy.
- Menor deuda tecnica por eliminacion de script legacy no utilizado.

## Mejora lograda
- Mayor consistencia visual entre vistas.
- Menor riesgo de errores por estado persistente de obra.
- Mejor compatibilidad con Excel moderno.
- Base mas limpia para completar migracion total a layout v4.

## Pendientes sugeridos
- Terminar migracion completa de vistas legacy a layout v4.
- Retiro progresivo de main.css cuando todas las vistas queden en v4.
- Consolidar librerias frontend para reducir mezcla de versiones/CDN.
