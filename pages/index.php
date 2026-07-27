<?php
include_once '../validarSesion.php';

// ----------------------------------------------------------------
// Datos del usuario en sesion + RBAC
// ----------------------------------------------------------------
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

$usuario_sesion = $__user['user_nameUser'] ?? ($_SESSION['Usuario'] ?? '');
$usuario_nombre = $__user['user_name']     ?? $usuario_sesion;
$usuario_rol    = $__user['role']['name']  ?? 'Residente';
$usuario_rolCode= $__user['role']['code']  ?? 'residente';
$usuario_dirAcc = tf_user_has_direction_access($__user) ? 1 : 0;
$usuario_perms  = $__user['permissions']   ?? [];
$usuario_id     = (int)($__user['user_id'] ?? 0);

// ----------------------------------------------------------------
// Variables del layout
// ----------------------------------------------------------------
$tf_page_title     = 'Inicio';
$tf_active_nav     = 'inicio';
$tf_breadcrumb     = [['Inicio', './index.php']];
$tf_user           = [
    'name'        => $usuario_nombre,
    'role'        => $usuario_rol,
    'roleCode'    => $usuario_rolCode,
    'initials'    => '',
    'permissions' => $usuario_perms,
];
$tf_show_direccion = in_array($usuario_rolCode, ['admin', 'director'], true) || $usuario_dirAcc === 1;
$tf_show_admin     = in_array($usuario_rolCode, ['admin', 'desarrollador'], true) || tf_has_permission('admin.users.view', $__user);
$tf_show_subbar    = true;
$tf_user_id_js     = (string)$usuario_sesion;
$tf_is_lector      = ($usuario_rolCode === 'lector');
$canObras          = tf_has_permission('obras.view', $__user);
$canCatalogos      = tf_has_permission('catalogos.view', $__user);
$canDireccion      = tf_has_permission('direccion.view', $__user) || tf_has_permission('presiones.authorize', $__user) || $tf_show_direccion;

$tf_subbar_extra = '';

// ----------------------------------------------------------------
// Dashboard por rol (datos reales, con scope de obras)
// ----------------------------------------------------------------
// El "rol de dashboard" mapea el rol real a una de las 4 vistas.
$dashRole = $usuario_rolCode;
if (in_array($dashRole, ['admin', 'desarrollador'], true)) {
    $dashRole = 'director'; // vista ejecutiva/superset
}

$dash = ['role' => $dashRole, 'lead' => '', 'kpis' => [], 'queueTitle' => '', 'queue' => [], 'bars' => []];

if (!$tf_is_lector) {
    try {
        $__scope   = tf_scope_obras_query($__pdo, $__user);
        $scopeSql  = $__scope['sql'];
        $scopePar  = $__scope['params'];

        // Base de hojas con scope por obra (obras sin alias para que aplique el fragmento de scope)
        $hojaBase  = "FROM `hojasrequisicion` h
            JOIN `requisiciones` r ON r.`requisicion_id` = h.`hojaRequisicion_idReq`
            JOIN `obras` ON `obras`.`obras_id` = r.`requisicion_Obra`";
        $presBase  = "FROM `presiones` JOIN `obras` ON `obras`.`obras_id` = `presiones`.`presiones_obra`";

        // Helpers: cuentan/suman aplicando condicion + scope
        $cntHoja = function ($whereExtra) use ($__pdo, $hojaBase, $scopeSql, $scopePar) {
            $st = $__pdo->prepare("SELECT COUNT(*) $hojaBase WHERE $whereExtra $scopeSql");
            $st->execute($scopePar);
            return (int)$st->fetchColumn();
        };
        $sumHoja = function ($whereExtra) use ($__pdo, $hojaBase, $scopeSql, $scopePar) {
            $st = $__pdo->prepare("SELECT COALESCE(SUM(h.`hojaRequisicion_total`),0) $hojaBase WHERE $whereExtra $scopeSql");
            $st->execute($scopePar);
            return (float)$st->fetchColumn();
        };
        $cntPres = function ($whereExtra) use ($__pdo, $presBase, $scopeSql, $scopePar) {
            $st = $__pdo->prepare("SELECT COUNT(*) $presBase WHERE $whereExtra $scopeSql");
            $st->execute($scopePar);
            return (int)$st->fetchColumn();
        };

        if ($dashRole === 'residente') {
            $mias = " AND h.`hojaRequisicion_userCreado` = " . $usuario_id;
            $dash['lead'] = 'Seguimiento de tus requisiciones. Lo primero: lo que se rechazo y lo que falta enviar.';
            $dash['kpis'] = [
                ['label' => 'Borradores sin enviar', 'value' => number_format($cntHoja("h.`hojaRequisicion_estatus` IN ('NUEVO','NUEVA')" . $mias)), 'tone' => '', 'href' => './requisiciones.php'],
                ['label' => 'En revision',            'value' => number_format($cntHoja("h.`hojaRequisicion_estatus` IN ('REVISION','PENDIENTE')" . $mias)), 'tone' => 'info', 'href' => './requisiciones.php'],
                ['label' => 'Rechazadas', 'flag' => 'Accion', 'value' => number_format($cntHoja("h.`hojaRequisicion_estatus` = 'RECHAZADA'" . $mias)), 'tone' => 'crit', 'href' => './requisiciones.php'],
                ['label' => 'Aprobadas',             'value' => number_format($cntHoja("h.`hojaRequisicion_estatus` IN ('LIGADA','AUTORIZADA','PAGADA')" . $mias)), 'tone' => 'ok', 'href' => './requisiciones.php'],
            ];
            $dash['queueTitle'] = 'Requiere tu atencion';
            $st = $__pdo->prepare("SELECT h.`hojaRequisicion_numero` hnum, h.`hojaRequisicion_total` total, h.`hojaRequisicion_estatus` est,
                    r.`requisicion_Numero` rnum, `obras`.`obras_id` oid, `obras`.`obras_nombre` obra
                $hojaBase WHERE h.`hojaRequisicion_estatus` IN ('RECHAZADA','NUEVO','NUEVA') $mias $scopeSql
                ORDER BY h.`hojaRequisicion_id` DESC LIMIT 6");
            $st->execute($scopePar);
            $dash['queue'] = $st->fetchAll(PDO::FETCH_ASSOC);

        } elseif ($dashRole === 'compras') {
            $dash['lead'] = 'Bandeja de trabajo: valida requisiciones, asigna proveedor y ligalas a una presion.';
            $dash['kpis'] = [
                ['label' => 'Por validar',  'value' => number_format($cntHoja("h.`hojaRequisicion_estatus` = 'REVISION'")), 'tone' => 'info', 'href' => './requisiciones.php'],
                ['label' => 'Pendientes',   'value' => number_format($cntHoja("h.`hojaRequisicion_estatus` = 'PENDIENTE'")), 'tone' => 'warn', 'href' => './requisiciones.php'],
                ['label' => 'Ligadas',      'value' => number_format($cntHoja("h.`hojaRequisicion_estatus` = 'LIGADA'")), 'tone' => 'ok', 'href' => './requisiciones.php'],
                ['label' => 'En captura',   'value' => number_format($cntHoja("h.`hojaRequisicion_estatus` IN ('NUEVO','NUEVA')")), 'tone' => '', 'href' => './requisiciones.php'],
            ];
            $dash['queueTitle'] = 'Requisiciones por procesar';
            $st = $__pdo->prepare("SELECT h.`hojaRequisicion_numero` hnum, h.`hojaRequisicion_total` total, h.`hojaRequisicion_estatus` est,
                    r.`requisicion_Numero` rnum, `obras`.`obras_id` oid, `obras`.`obras_nombre` obra
                $hojaBase WHERE h.`hojaRequisicion_estatus` IN ('REVISION','PENDIENTE') $scopeSql
                ORDER BY h.`hojaRequisicion_FechaSolicitud` ASC, h.`hojaRequisicion_id` ASC LIMIT 6");
            $st->execute($scopePar);
            $dash['queue'] = $st->fetchAll(PDO::FETCH_ASSOC);

        } elseif ($dashRole === 'finanzas') {
            $dash['lead'] = 'Tesoreria: hojas autorizadas listas para pago y el flujo por desembolsar.';
            $porPagar = $sumHoja("h.`hojaRequisicion_estatus` = 'AUTORIZADA'");
            $dash['kpis'] = [
                ['label' => 'Por pagar', 'value' => '$' . number_format($porPagar, 2), 'tone' => 'crit', 'href' => './all_presiones.php'],
                ['label' => 'Hojas listas para pago', 'value' => number_format($cntHoja("h.`hojaRequisicion_estatus` = 'AUTORIZADA'")), 'tone' => 'info', 'href' => './all_presiones.php'],
                ['label' => 'En proceso (ligadas)', 'value' => number_format($cntHoja("h.`hojaRequisicion_estatus` = 'LIGADA'")), 'tone' => 'warn', 'href' => './all_presiones.php'],
                ['label' => 'Pagadas este mes', 'value' => number_format($cntHoja("h.`hojaRequisicion_estatus` = 'PAGADA' AND MONTH(h.`hojaRequisicion_fechaPago`)=MONTH(CURDATE()) AND YEAR(h.`hojaRequisicion_fechaPago`)=YEAR(CURDATE())")), 'tone' => 'ok', 'href' => './reportes_kpi.php'],
            ];
            $dash['queueTitle'] = 'Cola de pagos';
            $st = $__pdo->prepare("SELECT h.`hojaRequisicion_numero` hnum, h.`hojaRequisicion_total` total, h.`hojaRequisicion_estatus` est,
                    r.`requisicion_Numero` rnum, `obras`.`obras_id` oid, `obras`.`obras_nombre` obra,
                    pr.`proveedor_nombre` prov, pr.`proveedor_banco` banco
                $hojaBase LEFT JOIN `provedores` pr ON pr.`proveedor_id` = h.`hojaRequisicion_proveedor`
                WHERE h.`hojaRequisicion_estatus` = 'AUTORIZADA' $scopeSql
                ORDER BY h.`hojaRequisicion_id` DESC LIMIT 6");
            $st->execute($scopePar);
            $dash['queue'] = $st->fetchAll(PDO::FETCH_ASSOC);

        } else { // director / admin / desarrollador
            $dash['lead'] = 'Centro directivo: presiones que esperan tu autorizacion y vista ejecutiva del gasto.';
            $enEspera = $sumHoja("h.`hojaRequisicion_estatus` = 'LIGADA'");
            $dash['kpis'] = [
                ['label' => 'Presiones por autorizar', 'flag' => 'Tu', 'value' => number_format($cntPres("`presiones`.`presiones_estatus` = 'PENDIENTE'")), 'tone' => 'crit', 'href' => './all_presiones.php'],
                ['label' => 'Monto en espera', 'value' => '$' . number_format($enEspera, 2), 'tone' => 'info', 'href' => './all_presiones.php'],
                ['label' => 'Presiones autorizadas', 'value' => number_format($cntPres("`presiones`.`presiones_estatus` = 'AUTORIZADO'")), 'tone' => 'ok', 'href' => './all_presiones.php'],
                ['label' => 'Hojas en revision', 'value' => number_format($cntHoja("h.`hojaRequisicion_estatus` = 'REVISION'")), 'tone' => '', 'href' => './requisiciones.php'],
            ];
            $dash['queueTitle'] = 'Esperando tu autorizacion';
            $st = $__pdo->prepare("SELECT `presiones`.`presiones_id` pid, `presiones`.`presiones_semana` semana,
                    `presiones`.`presiones_fechaCreacion` fecha, `obras`.`obras_nombre` obra
                $presBase WHERE `presiones`.`presiones_estatus` = 'PENDIENTE' $scopeSql
                ORDER BY `presiones`.`presiones_fechaCreacion` ASC, `presiones`.`presiones_id` ASC LIMIT 6");
            $st->execute($scopePar);
            $dash['queue'] = $st->fetchAll(PDO::FETCH_ASSOC);

            // Gasto por obra (top 5, historico pagado) para la vista ejecutiva
            $st = $__pdo->prepare("SELECT `obras`.`obras_nombre` obra, COALESCE(SUM(h.`hojaRequisicion_total`),0) monto
                $hojaBase WHERE h.`hojaRequisicion_estatus` = 'PAGADA' $scopeSql
                GROUP BY `obras`.`obras_id`, `obras`.`obras_nombre`
                ORDER BY monto DESC LIMIT 5");
            $st->execute($scopePar);
            $dash['bars'] = $st->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $__e) {
        error_log('Dashboard index error: ' . $__e->getMessage());
    }
}

// Utilidad de render: clase de pill segun estatus de hoja
function tf_pill_tone($est) {
    switch ($est) {
        case 'RECHAZADA': return 'crit';
        case 'NUEVO': case 'NUEVA': return 'warn';
        case 'REVISION': case 'PENDIENTE': return 'info';
        case 'LIGADA': case 'AUTORIZADA': return 'ok';
        case 'PAGADA': return 'ok';
        default: return 'info';
    }
}

$tf_extra_head = <<<'CSS'
<style>
[x-cloak]{display:none !important;}
.tf-welcome { display:flex; align-items:center; gap:16px; }
.tf-welcome-logo { width:58px; height:58px; border-radius:50%; border:1px solid var(--tf-border); background:var(--tf-surface); padding:6px; box-shadow:var(--tf-shadow-xs); flex:0 0 auto; }
.tf-datechip { display:flex; align-items:center; gap:12px; background:var(--tf-surface); border:1px solid var(--tf-border); border-radius:var(--tf-radius); padding:10px 16px; box-shadow:var(--tf-shadow-xs); }
.tf-datechip .bi { font-size:1.3rem; color:var(--tf-brand-500); }
.tf-datechip .dc-date { font-size:.9rem; font-weight:700; color:var(--tf-text); line-height:1.2; }
.tf-datechip .dc-time { font-size:.78rem; color:var(--tf-text-soft); font-variant-numeric:tabular-nums; }

/* --- Dashboard por rol --- */
.dash-tiles { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:14px; margin-bottom:22px; }
.dash-tile { position:relative; display:block; text-decoration:none; background:var(--tf-surface); border:1px solid var(--tf-border);
    border-radius:var(--tf-radius); padding:16px 16px 16px 20px; overflow:hidden; box-shadow:var(--tf-shadow-xs);
    transition:transform .16s, box-shadow .16s, border-color .16s; }
.dash-tile:hover { transform:translateY(-2px); box-shadow:var(--tf-shadow-md); border-color:var(--tf-brand-400); }
.dash-tile::before { content:""; position:absolute; left:0; top:0; bottom:0; width:4px; background:var(--tf-brand-500); }
.dash-tile.ok::before   { background:var(--tf-success-600); }
.dash-tile.warn::before { background:var(--tf-warning-600); }
.dash-tile.crit::before { background:var(--tf-danger-600); }
.dash-tile .dt-label { font-size:.72rem; font-weight:700; letter-spacing:.03em; text-transform:uppercase; color:var(--tf-text-muted); display:flex; align-items:center; gap:6px; min-height:2.3em; }
.dash-tile .dt-value { font-size:1.6rem; font-weight:800; letter-spacing:-.02em; color:var(--tf-text); margin-top:8px; line-height:1; font-variant-numeric:tabular-nums; }
.dt-flag { font-size:.6rem; font-weight:800; padding:2px 6px; border-radius:5px; letter-spacing:.03em; text-transform:uppercase; }
.dt-flag.crit { background:var(--tf-danger-50); color:var(--tf-danger-600); }
.dt-flag.warn { background:var(--tf-warning-50); color:var(--tf-warning-600); }

.dash-cols { display:grid; grid-template-columns:1.7fr 1fr; gap:18px; }
.q-row { display:flex; align-items:center; gap:12px; padding:13px 0; border-bottom:1px solid var(--tf-border); }
.q-row:last-child { border-bottom:0; }
.q-main { min-width:0; flex:1; }
.q-title { font-size:.84rem; font-weight:700; color:var(--tf-text); }
.q-meta { font-size:.78rem; color:var(--tf-text-soft); margin-top:2px; }
.q-meta .amt { font-variant-numeric:tabular-nums; font-weight:600; color:var(--tf-text); }
.q-pill { font-size:.66rem; font-weight:700; padding:3px 9px; border-radius:999px; white-space:nowrap; letter-spacing:.02em; }
.q-pill.info { background:var(--tf-info-50); color:var(--tf-info-600); }
.q-pill.ok   { background:var(--tf-success-50); color:var(--tf-success-600); }
.q-pill.warn { background:var(--tf-warning-50); color:var(--tf-warning-600); }
.q-pill.crit { background:var(--tf-danger-50); color:var(--tf-danger-600); }
.dash-empty { padding:28px 8px; text-align:center; color:var(--tf-text-muted); font-size:.9rem; }
.dash-empty .bi { display:block; font-size:1.6rem; margin-bottom:8px; }

.bars { display:flex; flex-direction:column; gap:12px; }
.bar-row { display:grid; grid-template-columns:96px 1fr auto; gap:10px; align-items:center; font-size:.78rem; }
.bar-row .b-name { color:var(--tf-text-soft); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.bar-track { height:8px; border-radius:999px; background:var(--tf-border); overflow:hidden; }
.bar-fill { height:100%; border-radius:999px; background:linear-gradient(90deg,var(--tf-brand-600),var(--tf-brand-400)); }
.bar-row .b-amt { font-variant-numeric:tabular-nums; font-weight:700; color:var(--tf-text); }

@media (max-width:820px){ .dash-tiles{grid-template-columns:repeat(2,minmax(0,1fr));} .dash-cols{grid-template-columns:1fr;} }
</style>
CSS;

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="AppIndex" class="tf-page-inner" x-data="indexApp()" x-init="init()" x-cloak>

    <!-- ============================================================
         Page header
         ============================================================ -->
    <header class="tf-page-header">
        <div class="tf-welcome">
            <img src="../images/TheFuenteIcon.png" alt="The Fuentes" class="tf-welcome-logo">
            <div>
                <span class="tf-eyebrow">Panel principal · <?= htmlspecialchars($usuario_rol) ?></span>
                <h1 class="tf-page-title">Bienvenido, <span x-text="NameUser || '<?= htmlspecialchars($usuario_nombre) ?>'"></span></h1>
                <p class="tf-page-lead"><?= htmlspecialchars($tf_is_lector ? 'Dashboard de lectura: accesos a modulos permitidos para consulta.' : $dash['lead']) ?></p>
            </div>
        </div>
        <div class="tf-page-header-actions">
            <div class="tf-datechip">
                <i class="bi bi-calendar3"></i>
                <div>
                    <div class="dc-date" x-text="fechaHoy"></div>
                    <div class="dc-time" x-text="horaHoy"></div>
                </div>
            </div>
        </div>
    </header>

    <?php if ($tf_is_lector): ?>
    <!-- Vista simplificada para rol lector -->
    <section class="tf-card">
        <header class="tf-card-header">
            <div><h2 class="tf-card-title"><i class="bi bi-compass"></i> Accesos permitidos</h2>
            <p class="tf-card-sub">Vista simplificada para rol lector</p></div>
        </header>
        <div class="tf-card-body">
            <div class="tf-module-grid">
                <?php if ($canObras): ?>
                <a href="./obras.php" class="tf-module-card">
                    <span class="tf-module-icon tf-module-icon-primary"><i class="bi bi-buildings"></i></span>
                    <span class="tf-module-label">Obras</span>
                    <span class="tf-module-sub">Consulta requisiciones y presiones de tus obras.</span>
                    <span class="tf-module-cta">Abrir <i class="bi bi-arrow-right"></i></span>
                </a>
                <?php endif; ?>
                <?php if ($canCatalogos): ?>
                <a href="./menu_catalago.php" class="tf-module-card">
                    <span class="tf-module-icon tf-module-icon-success"><i class="bi bi-collection"></i></span>
                    <span class="tf-module-label">Catalogos</span>
                    <span class="tf-module-sub">Consulta proveedores y bancos en modo lectura.</span>
                    <span class="tf-module-cta">Abrir <i class="bi bi-arrow-right"></i></span>
                </a>
                <?php endif; ?>
                <?php if ($canDireccion): ?>
                <a href="./reportes_kpi.php" class="tf-module-card">
                    <span class="tf-module-icon tf-module-icon-warning"><i class="bi bi-graph-up-arrow"></i></span>
                    <span class="tf-module-label">Reportes KPI</span>
                    <span class="tf-module-sub">Consulta indicadores globales y exporta.</span>
                    <span class="tf-module-cta">Abrir <i class="bi bi-arrow-right"></i></span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php else: ?>
    <!-- ============================================================
         KPIs de foco (tiles) segun rol
         ============================================================ -->
    <section class="dash-tiles" aria-label="Indicadores de foco">
        <?php foreach ($dash['kpis'] as $k): ?>
        <a href="<?= htmlspecialchars($k['href']) ?>" class="dash-tile <?= htmlspecialchars($k['tone']) ?>">
            <span class="dt-label">
                <?= htmlspecialchars($k['label']) ?>
                <?php if (!empty($k['flag'])): ?><span class="dt-flag <?= $k['tone'] === 'crit' ? 'crit' : 'warn' ?>"><?= htmlspecialchars($k['flag']) ?></span><?php endif; ?>
            </span>
            <span class="dt-value"><?= $k['value'] ?></span>
        </a>
        <?php endforeach; ?>
    </section>

    <!-- ============================================================
         Cola de trabajo + panel lateral
         ============================================================ -->
    <div class="dash-cols">
        <!-- Cola de trabajo -->
        <section class="tf-card">
            <header class="tf-card-header">
                <div><h2 class="tf-card-title"><i class="bi bi-inbox-fill"></i> <?= htmlspecialchars($dash['queueTitle']) ?></h2>
                <p class="tf-card-sub"><?= count($dash['queue']) ?> en la cola</p></div>
            </header>
            <div class="tf-card-body">
                <?php if (empty($dash['queue'])): ?>
                <div class="dash-empty"><i class="bi bi-check2-circle"></i> No tienes pendientes en esta cola. Todo al dia.</div>
                <?php else: ?>

                    <?php if ($dashRole === 'director'): ?>
                    <?php foreach ($dash['queue'] as $row): ?>
                    <div class="q-row">
                        <div class="q-main">
                            <div class="q-title">Presion · Semana <?= htmlspecialchars($row['semana']) ?> · <?= htmlspecialchars($row['obra']) ?></div>
                            <div class="q-meta">Creada el <?= htmlspecialchars($row['fecha']) ?></div>
                        </div>
                        <span class="q-pill warn">Pendiente</span>
                        <a href="./all_presiones.php" class="tf-btn tf-btn-primary tf-btn-sm">Revisar</a>
                    </div>
                    <?php endforeach; ?>

                    <?php elseif ($dashRole === 'finanzas'): ?>
                    <?php foreach ($dash['queue'] as $row): ?>
                    <div class="q-row">
                        <div class="q-main">
                            <div class="q-title"><?= htmlspecialchars($row['rnum']) ?> · Hoja <?= (int)$row['hnum'] ?></div>
                            <div class="q-meta"><?= htmlspecialchars($row['prov'] ?: '—') ?> · <?= htmlspecialchars($row['banco'] ?: '—') ?> · <span class="amt">$<?= number_format((float)$row['total'], 2) ?></span></div>
                        </div>
                        <span class="q-pill ok">Autorizada</span>
                        <button type="button" class="tf-btn tf-btn-primary tf-btn-sm" x-on:click="irRequis(<?= (int)$row['oid'] ?>)">Pagar</button>
                    </div>
                    <?php endforeach; ?>

                    <?php else: /* residente / compras */ ?>
                    <?php foreach ($dash['queue'] as $row): ?>
                    <div class="q-row">
                        <div class="q-main">
                            <div class="q-title"><?= htmlspecialchars($row['rnum']) ?> · Hoja <?= (int)$row['hnum'] ?></div>
                            <div class="q-meta"><?= htmlspecialchars($row['obra']) ?> · <span class="amt">$<?= number_format((float)$row['total'], 2) ?></span></div>
                        </div>
                        <span class="q-pill <?= tf_pill_tone($row['est']) ?>"><?= htmlspecialchars(ucfirst(strtolower($row['est']))) ?></span>
                        <button type="button" class="tf-btn tf-btn-primary tf-btn-sm" x-on:click="irRequis(<?= (int)$row['oid'] ?>)"><?= $dashRole === 'residente' ? 'Abrir' : 'Procesar' ?></button>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </section>

        <!-- Panel lateral -->
        <aside class="tf-side-col">
            <?php if ($dashRole === 'residente'): ?>
            <section class="tf-card">
                <header class="tf-card-header"><h2 class="tf-card-title"><i class="bi bi-plus-circle-fill"></i> Accion principal</h2></header>
                <div class="tf-card-body">
                    <div class="tf-quick-grid">
                        <a href="./obras.php" class="tf-quick-btn"><i class="bi bi-file-earmark-plus"></i><span>Nueva requisicion</span></a>
                        <a href="./obras.php" class="tf-quick-btn"><i class="bi bi-buildings"></i><span>Mis obras</span></a>
                    </div>
                </div>
            </section>
            <?php elseif ($dashRole === 'director' && !empty($dash['bars'])): ?>
            <section class="tf-card">
                <header class="tf-card-header"><div><h2 class="tf-card-title"><i class="bi bi-bar-chart-fill"></i> Gasto por obra</h2><p class="tf-card-sub">Pagado (historico) · top 5</p></div></header>
                <div class="tf-card-body">
                    <?php
                    $maxBar = 0.0;
                    foreach ($dash['bars'] as $b) { $maxBar = max($maxBar, (float)$b['monto']); }
                    $maxBar = $maxBar ?: 1;
                    ?>
                    <div class="bars">
                        <?php foreach ($dash['bars'] as $b): $pct = max(3, round((float)$b['monto'] / $maxBar * 100)); ?>
                        <div class="bar-row">
                            <span class="b-name"><?= htmlspecialchars($b['obra']) ?></span>
                            <span class="bar-track"><span class="bar-fill" style="width:<?= $pct ?>%"></span></span>
                            <span class="b-amt">$<?= number_format((float)$b['monto'], 0) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <section class="tf-card">
                <header class="tf-card-header"><h2 class="tf-card-title"><i class="bi bi-lightning-charge-fill"></i> Accesos</h2></header>
                <div class="tf-card-body">
                    <div class="tf-quick-grid">
                        <?php if ($canObras): ?><a href="./obras.php" class="tf-quick-btn"><i class="bi bi-buildings"></i><span>Obras</span></a><?php endif; ?>
                        <?php if (tf_has_permission('presiones.view', $__user)): ?><a href="./all_presiones.php" class="tf-quick-btn"><i class="bi bi-briefcase-fill"></i><span>Presiones</span></a><?php endif; ?>
                        <?php if ($canDireccion): ?><a href="./reportes_kpi.php" class="tf-quick-btn"><i class="bi bi-graph-up-arrow"></i><span>Reportes KPI</span></a><?php endif; ?>
                        <?php if ($canCatalogos): ?><a href="./menu_catalago.php" class="tf-quick-btn"><i class="bi bi-collection"></i><span>Catalogos</span></a><?php endif; ?>
                    </div>
                </div>
            </section>
        </aside>
    </div>
    <?php endif; ?>

</div>

<?php
$tf_inline_script = <<<JS
    var url2 = ".";
    function indexApp() {
        return {
            fechaHoy: "",
            horaHoy: "",
            NameUser: (window.TF_CONTEXT && window.TF_CONTEXT.user && window.TF_CONTEXT.user.name) || "",
            actualizarReloj: function () {
                var ahora = new Date();
                this.fechaHoy = ahora.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
                this.horaHoy = ahora.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' });
            },
            irRequis: function (idObra) {
                try { sessionStorage.setItem("obraActiva", String(idObra)); } catch (e) {}
                try { localStorage.setItem("obraActiva", String(idObra)); } catch (e) {}
                window.location.href = url2 + "/requisiciones.php?obra=" + encodeURIComponent(idObra);
            },
            init: function () {
                this.actualizarReloj();
                setInterval(this.actualizarReloj.bind(this), 60000);
            }
        }
    }
JS;

$tf_use_vue = false;
$tf_extra_scripts = '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>';

include __DIR__ . '/../includes/layout_bottom.php';
?>
