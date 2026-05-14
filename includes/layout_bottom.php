<?php
/**
 * Layout Bottom — The Fuentes Workspace v4.1
 * ------------------------------------------------------------
 * Cierra el <main> abierto por layout_top.php e incluye el
 * bundle de scripts (Bootstrap 5.3.3, SweetAlert2, axios, Vue 2
 * y v4-layout.js).
 *
 * USO:
 *   <?php include __DIR__ . '/../includes/layout_bottom.php'; ?>
 *
 * Variables opcionales que el caller puede definir antes:
 *   $tf_use_vue         (bool)  default true   — incluir Vue 2.5.18
 *   $tf_use_axios       (bool)  default true   — incluir axios
 *   $tf_use_datatables  (bool)  default false  — incluir DataTables 2.0.8
 *   $tf_use_jquery      (bool)  default false  — incluir jQuery (solo si se necesita)
 *   $tf_extra_scripts   (string)default ''     — HTML adicional con <script> tags
 *   $tf_inline_script   (string)default ''     — JS inline a ejecutar al final
 *   $tf_user_id_js      (string)default ''     — id del usuario (para inyectar window.TF_USER_ID)
 * ------------------------------------------------------------
 */

$tf_use_vue        = $tf_use_vue        ?? true;
$tf_use_axios      = $tf_use_axios      ?? true;
$tf_use_datatables = $tf_use_datatables ?? false;
$tf_use_jquery     = $tf_use_jquery     ?? false;
$tf_extra_scripts  = $tf_extra_scripts  ?? '';
$tf_inline_script  = $tf_inline_script  ?? '';
$tf_user_id_js     = $tf_user_id_js     ?? '';
?>
    </main><!-- /.tf-page (abierto en layout_top.php) -->

    <!-- ====== FOOTER MINIMAL ====== -->
    <footer class="tf-footer">
        <div class="tf-footer-inner">
            <span>&copy; <?= date('Y') ?> The Fuentes Corporation</span>
            <span class="tf-footer-sep">·</span>
            <span>Workspace v4.1</span>
        </div>
    </footer>

    <!-- ====== Contexto global JS ====== -->
    <?php
        // CSRF token (creado por validarSesion.php). Si no esta, lo creamos.
        $__tf_csrf = function_exists('tf_csrf_token') ? tf_csrf_token() : '';
    ?>
    <script>
        window.TF_CONTEXT = {
            user: {
                id:        <?= json_encode($tf_user_id_js !== '' ? $tf_user_id_js : null) ?>,
                name:      <?= json_encode($tf_user['name']      ?? '') ?>,
                role:      <?= json_encode($tf_user['role']      ?? '') ?>,
                roleCode:  <?= json_encode($tf_user['roleCode']  ?? '') ?>,
                initials:  <?= json_encode($tf_user['initials']  ?? '') ?>,
                permissions: <?= json_encode($tf_user['permissions'] ?? []) ?>
            },
            page: {
                title:      <?= json_encode($tf_page_title ?? '') ?>,
                active_nav: <?= json_encode($tf_active_nav ?? '') ?>
            },
            csrf: <?= json_encode($__tf_csrf) ?>
        };
        // Helper rapido: TF.can('requisiciones.create')
        window.TF = {
            can: function(code) {
                var perms = (window.TF_CONTEXT.user && window.TF_CONTEXT.user.permissions) || [];
                return perms.indexOf('*') !== -1 || perms.indexOf(code) !== -1;
            }
        };
    </script>

    <?php if ($tf_use_jquery): ?>
    <!-- jQuery (solo para paginas legacy que aun lo usen) -->
    <script src="../assets/lib/jquery/jquery-3.7.1.slim.min.js"></script>
    <?php endif; ?>

    <!-- Bootstrap 5.3.3 local bundle (incluye Popper) -->
    <script src="../assets/lib/bootstrap/js/bootstrap.bundle.min.js"></script>

    <?php if ($tf_use_vue): ?>
    <!-- Vue 2.x local (modo compat para paginas existentes durante la migracion) -->
    <script src="../assets/lib/vue/vue.min.js"></script>
    <?php endif; ?>

    <?php if ($tf_use_axios): ?>
    <!-- Axios local -->
    <script src="../assets/lib/axios/axios.min.js"></script>
    <?php endif; ?>

    <!-- SweetAlert2 (local) -->
    <script src="../assets/lib/sweetalert/sweetalert2.min.js"></script>

    <?php if ($tf_use_datatables): ?>
    <!-- DataTables 2.0.8 -->
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.js"></script>
    <?php endif; ?>

    <!-- Configurar axios para enviar CSRF en cada peticion (Fase 2) -->
    <?php if ($tf_use_axios): ?>
    <script>
        (function() {
            if (window.axios && window.TF_CONTEXT && window.TF_CONTEXT.csrf) {
                window.axios.defaults.headers.common['X-CSRF-Token'] = window.TF_CONTEXT.csrf;
                window.axios.defaults.withCredentials = true;
            }
        })();
    </script>
    <?php endif; ?>

    <!-- Controlador de layout v4 (theme, mobile nav, command palette) -->
    <script src="../assets/js/v4-layout.js"></script>

    <?php if (!empty($tf_extra_scripts)) echo $tf_extra_scripts; ?>

    <?php if (!empty($tf_inline_script)): ?>
    <script>
    <?= $tf_inline_script ?>
    </script>
    <?php endif; ?>
</body>
</html>
