<?php
/**
 * Layout Top — The Fuentes Workspace v4.1
 * ------------------------------------------------------------
 * Include base que renderiza:
 *   - <head> con todos los assets (Bootstrap 5.3.3, Bootstrap Icons,
 *     Inter, SweetAlert2, CSS v4)
 *   - Topbar con navegacion principal
 *   - Sub-bar con breadcrumb dinamico
 *   - Mobile offcanvas nav
 *
 * USO:
 *   <?php
 *     $tf_page_title   = 'Inicio';
 *     $tf_active_nav   = 'inicio';   // inicio|obras|requisiciones|presiones|catalogos|direccion|admin
 *     $tf_breadcrumb   = [['Inicio', '#']];   // [[label, url], ...]
 *     $tf_user         = ['name' => 'Alan Fuentes', 'role' => 'Residente', 'initials' => 'AF'];
 *     $tf_show_direccion = false;  // segun rol
 *     $tf_show_admin     = false;  // segun rol
 *     include __DIR__ . '/../includes/layout_top.php';
 *   ?>
 *   ... contenido de la pagina ...
 *   <?php include __DIR__ . '/../includes/layout_bottom.php'; ?>
 *
 * Requiere que el caller ya haya ejecutado validarSesion.php.
 * ------------------------------------------------------------
 */

// Defaults defensivos
$tf_page_title     = $tf_page_title     ?? 'The Fuentes Workspace';
$tf_active_nav     = $tf_active_nav     ?? '';
$tf_breadcrumb     = $tf_breadcrumb     ?? [];
$tf_user           = $tf_user           ?? ['name' => '', 'role' => '', 'initials' => '?'];
$tf_show_direccion = $tf_show_direccion ?? false;
$tf_show_admin     = $tf_show_admin     ?? false;
$tf_show_subbar    = $tf_show_subbar    ?? true;

// Sanitiza iniciales
if (empty($tf_user['initials']) && !empty($tf_user['name'])) {
    $parts = preg_split('/\s+/', trim($tf_user['name']));
    $tf_user['initials'] = strtoupper(
        ($parts[0][0] ?? '') . ((isset($parts[1]) ? $parts[1][0] : ''))
    );
    if ($tf_user['initials'] === '') $tf_user['initials'] = '?';
}

/**
 * Helper local: marca un item como activo si coincide con $tf_active_nav.
 */
function tf_active($current, $key) {
    return $current === $key ? 'active' : '';
}
?>
<!doctype html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b1424">
    <link rel="icon" type="image/png" href="../images/TheFuenteIcon.png">
    <title><?= htmlspecialchars($tf_page_title) ?> :: The Fuentes Workspace</title>

    <!-- Bootstrap 5.3.3 local -->
    <link rel="stylesheet" href="../assets/lib/bootstrap/css/bootstrap.min.css">

    <!-- Bootstrap Icons 1.11 local -->
    <link rel="stylesheet" href="../assets/lib/bootstrap-icons/font/bootstrap-icons.min.css">

    <!-- Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="../assets/lib/sweetalert/sweetalert2.min.css">

    <!-- Diseno v4 -->
    <link rel="stylesheet" href="../assets/css/v4.css">

    <?php if (!empty($tf_extra_head)) echo $tf_extra_head; ?>
</head>
<body class="tf-app">

    <!-- ====== TOPBAR ====== -->
    <header class="tf-topbar" role="banner">
        <div class="tf-topbar-inner">
            <button class="tf-burger" id="tfBurger" type="button" aria-label="Abrir menu">
                <i class="bi bi-list"></i>
            </button>

            <a href="./index.php" class="tf-brand">
                <span class="tf-brand-mark">TF</span>
                <span class="tf-brand-text">
                    The Fuentes
                    <small>Workspace</small>
                </span>
            </a>

            <nav class="tf-nav" aria-label="Navegacion principal">
                <a href="./index.php" class="<?= tf_active($tf_active_nav, 'inicio') ?>">
                    <i class="bi bi-grid-1x2-fill"></i> Inicio
                </a>

                <div class="dropdown">
                    <button type="button"
                            class="tf-nav-trigger <?= tf_active($tf_active_nav, 'obras') ?>"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-building"></i> Obras
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu p-2 shadow-lg"
                         style="min-width:300px;border-radius:12px;"
                         id="tfObrasMenu">
                        <div class="px-2 py-1 d-flex align-items-center justify-content-between">
                            <small class="text-uppercase fw-bold text-muted"
                                   style="font-size:.66rem;letter-spacing:.08em">
                                Obras activas
                            </small>
                        </div>
                        <div id="tfObrasMenuList">
                            <div class="px-3 py-2 small text-muted">Cargando...</div>
                        </div>
                    </div>
                </div>

                <a href="./requisiciones.php"
                   class="<?= tf_active($tf_active_nav, 'requisiciones') ?>">
                    <i class="bi bi-receipt"></i> Requisiciones
                </a>
                <a href="./presiones.php"
                   class="<?= tf_active($tf_active_nav, 'presiones') ?>">
                    <i class="bi bi-cash-coin"></i> Presiones
                </a>

                <div class="dropdown">
                    <button type="button"
                            class="tf-nav-trigger <?= tf_active($tf_active_nav, 'catalogos') ?>"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-collection"></i> Catalogos
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu shadow-lg p-2"
                         style="min-width:220px;border-radius:12px;">
                        <a class="dropdown-item rounded py-2" href="./proveedores.php">
                            <i class="bi bi-truck text-primary me-2"></i>Proveedores
                        </a>
                        <a class="dropdown-item rounded py-2" href="./bancos.php">
                            <i class="bi bi-bank text-primary me-2"></i>Bancos
                        </a>
                    </div>
                </div>

                <?php if ($tf_show_direccion): ?>
                <a href="./direccion.php"
                   class="<?= tf_active($tf_active_nav, 'direccion') ?>">
                    <i class="bi bi-briefcase-fill"></i> Direccion
                </a>
                <?php endif; ?>

                <?php if ($tf_show_admin): ?>
                <a href="./admin.php"
                   class="<?= tf_active($tf_active_nav, 'admin') ?>">
                    <i class="bi bi-shield-lock-fill"></i> Admin
                </a>
                <?php endif; ?>
            </nav>

            <button class="tf-search" id="tfOpenCmd" type="button"
                    aria-label="Abrir buscador global">
                <i class="bi bi-search"></i>
                <span class="tf-search-label">Buscar obras, requisiciones...</span>
                <span class="tf-search-kbd">Ctrl K</span>
            </button>

            <div class="tf-topbar-actions">
                <button class="tf-icon-btn" id="tfThemeToggle" type="button"
                        title="Cambiar tema" aria-label="Cambiar tema">
                    <i class="bi bi-moon-stars-fill"></i>
                </button>
                <button class="tf-icon-btn" type="button"
                        title="Notificaciones" aria-label="Notificaciones">
                    <i class="bi bi-bell-fill"></i>
                </button>
                <div class="dropdown">
                    <button class="tf-user" data-bs-toggle="dropdown"
                            aria-expanded="false" type="button">
                        <span class="tf-user-avatar"><?= htmlspecialchars($tf_user['initials']) ?></span>
                        <span class="tf-user-meta">
                            <strong><?= htmlspecialchars($tf_user['name'] ?: '—') ?></strong>
                            <small><?= htmlspecialchars($tf_user['role'] ?: 'Usuario') ?></small>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end shadow-lg"
                         style="min-width:240px;border-radius:12px;">
                        <div class="px-3 py-2">
                            <div class="fw-semibold"><?= htmlspecialchars($tf_user['name'] ?: '—') ?></div>
                            <?php if (!empty($tf_user['role'])): ?>
                            <span class="tf-role tf-role-<?= strtolower(preg_replace('/[^a-z]/i', '', $tf_user['role'])) ?: 'lector' ?> mt-2 d-inline-block">
                                <?= htmlspecialchars($tf_user['role']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <hr class="dropdown-divider">
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-person-circle me-2"></i>Mi perfil
                        </a>
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-sliders me-2"></i>Preferencias
                        </a>
                        <hr class="dropdown-divider">
                        <a class="dropdown-item text-danger" href="./closeSesion.php">
                            <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesion
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <?php if ($tf_show_subbar && !empty($tf_breadcrumb)): ?>
    <!-- ====== SUB-BAR ====== -->
    <div class="tf-subbar">
        <div class="tf-subbar-inner">
            <nav class="tf-breadcrumb" aria-label="breadcrumb">
                <a href="./index.php" aria-label="Inicio">
                    <i class="bi bi-house-fill"></i>
                </a>
                <?php foreach ($tf_breadcrumb as $i => $crumb): ?>
                    <span class="sep">/</span>
                    <?php if (!empty($crumb[1]) && $i < count($tf_breadcrumb) - 1): ?>
                        <a href="<?= htmlspecialchars($crumb[1]) ?>">
                            <?= htmlspecialchars($crumb[0]) ?>
                        </a>
                    <?php else: ?>
                        <span><?= htmlspecialchars($crumb[0]) ?></span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <?php if (!empty($tf_subbar_extra)) echo $tf_subbar_extra; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ====== MOBILE OFFCANVAS NAV ====== -->
    <div class="tf-mobile-backdrop" id="tfMobileBackdrop"></div>
    <aside class="tf-mobile-nav" id="tfMobileNav" aria-label="Menu de navegacion">
        <div class="tf-mobile-nav-header">
            <a href="./index.php" class="tf-mobile-nav-brand">
                <span class="tf-brand-mark">TF</span>
                <span>The Fuentes</span>
            </a>
            <button class="tf-mobile-nav-close" id="tfMobileClose" type="button"
                    aria-label="Cerrar menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="tf-mobile-nav-list">
            <a href="./index.php" class="tf-mobile-nav-item <?= tf_active($tf_active_nav, 'inicio') ?>">
                <i class="bi bi-grid-1x2-fill"></i> Inicio
            </a>
            <a href="#" class="tf-mobile-nav-item <?= tf_active($tf_active_nav, 'obras') ?>">
                <i class="bi bi-building"></i> Obras
            </a>
            <a href="./requisiciones.php" class="tf-mobile-nav-item <?= tf_active($tf_active_nav, 'requisiciones') ?>">
                <i class="bi bi-receipt"></i> Requisiciones
            </a>
            <a href="./presiones.php" class="tf-mobile-nav-item <?= tf_active($tf_active_nav, 'presiones') ?>">
                <i class="bi bi-cash-coin"></i> Presiones
            </a>

            <div class="tf-mobile-nav-section">Catalogos</div>
            <a href="./proveedores.php" class="tf-mobile-nav-item">
                <i class="bi bi-truck"></i> Proveedores
            </a>
            <a href="./bancos.php" class="tf-mobile-nav-item">
                <i class="bi bi-bank"></i> Bancos
            </a>

            <?php if ($tf_show_direccion || $tf_show_admin): ?>
            <div class="tf-mobile-nav-section">Administracion</div>
            <?php endif; ?>
            <?php if ($tf_show_direccion): ?>
            <a href="./direccion.php" class="tf-mobile-nav-item <?= tf_active($tf_active_nav, 'direccion') ?>">
                <i class="bi bi-briefcase-fill"></i> Direccion
            </a>
            <?php endif; ?>
            <?php if ($tf_show_admin): ?>
            <a href="./admin.php" class="tf-mobile-nav-item <?= tf_active($tf_active_nav, 'admin') ?>">
                <i class="bi bi-shield-lock-fill"></i> Admin
            </a>
            <?php endif; ?>
        </div>
        <div class="tf-mobile-nav-footer">
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="tf-user-avatar"><?= htmlspecialchars($tf_user['initials']) ?></span>
                <div>
                    <div class="fw-semibold small"><?= htmlspecialchars($tf_user['name'] ?: '—') ?></div>
                    <?php if (!empty($tf_user['role'])): ?>
                    <span class="tf-role tf-role-<?= strtolower(preg_replace('/[^a-z]/i', '', $tf_user['role'])) ?: 'lector' ?>">
                        <?= htmlspecialchars($tf_user['role']) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <a href="./closeSesion.php" class="tf-btn tf-btn-secondary w-100 mt-1">
                <i class="bi bi-box-arrow-right"></i> Cerrar sesion
            </a>
        </div>
    </aside>

    <!-- ====== COMMAND PALETTE ====== -->
    <div class="tf-cmd-overlay" id="tfCmd" role="dialog" aria-label="Buscador global">
        <div class="tf-cmd">
            <div class="tf-cmd-input">
                <i class="bi bi-search text-muted"></i>
                <input type="text"
                       placeholder="Buscar obras, requisiciones, proveedores, acciones..."
                       id="tfCmdInput">
                <span class="tf-search-kbd"
                      style="color:var(--tf-text-soft);background:var(--tf-surface-2);border:1px solid var(--tf-border)">
                    Esc
                </span>
            </div>
            <div class="tf-cmd-list" id="tfCmdList">
                <div class="tf-cmd-section">Acciones rapidas</div>
                <a class="tf-cmd-item active" href="./nueva_requisicion.php">
                    <i class="bi bi-file-earmark-plus"></i>
                    <div class="tf-cmd-item-text">
                        <strong>Crear nueva requisicion</strong>
                        <small>Solicitud de compra</small>
                    </div>
                </a>
                <a class="tf-cmd-item" href="./agregar_proveedor.php">
                    <i class="bi bi-truck"></i>
                    <div class="tf-cmd-item-text">
                        <strong>Agregar proveedor</strong>
                        <small>Catalogo</small>
                    </div>
                </a>

                <div class="tf-cmd-section">Navegacion</div>
                <a class="tf-cmd-item" href="./requisiciones.php">
                    <i class="bi bi-receipt"></i>
                    <div class="tf-cmd-item-text">
                        <strong>Requisiciones</strong>
                        <small>Modulo</small>
                    </div>
                </a>
                <a class="tf-cmd-item" href="./presiones.php">
                    <i class="bi bi-cash-coin"></i>
                    <div class="tf-cmd-item-text">
                        <strong>Presiones de pago</strong>
                        <small>Modulo</small>
                    </div>
                </a>
                <a class="tf-cmd-item" href="./menu_catalago.php">
                    <i class="bi bi-collection"></i>
                    <div class="tf-cmd-item-text">
                        <strong>Catalogos</strong>
                        <small>Proveedores, Bancos</small>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <main class="tf-page" role="main">
