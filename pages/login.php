<?php
$tf_bootstrap_css_v = @filemtime(__DIR__ . '/../assets/lib/bootstrap/css/bootstrap.min.css') ?: time();
$tf_swal_css_v = @filemtime(__DIR__ . '/../assets/lib/sweetalert/sweetalert2.min.css') ?: time();
$tf_v4_css_v = @filemtime(__DIR__ . '/../assets/css/v4.css') ?: time();
$tf_swal_js_v = @filemtime(__DIR__ . '/../assets/lib/sweetalert/sweetalert2.min.js') ?: time();
$tf_login_js_v = @filemtime(__DIR__ . '/../assets/js/login.js') ?: time();
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b1424">
    <link rel="icon" type="image/png" href="../images/TheFuenteIcon.png">
    <title>Iniciar sesion :: The Fuentes Workspace</title>

        <!-- Bootstrap 5.3.3 local -->
        <link rel="stylesheet" href="../assets/lib/bootstrap/css/bootstrap.min.css?v=<?= $tf_bootstrap_css_v ?>">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="../assets/lib/sweetalert/sweetalert2.min.css?v=<?= $tf_swal_css_v ?>">

    <!-- Diseno v4 -->
    <link rel="stylesheet" href="../assets/css/v4.css?v=<?= $tf_v4_css_v ?>">
</head>

<body class="tf-login">
    <div id="LoginApp" class="tf-login-shell">

        <!-- Panel visual izquierdo -->
        <aside class="tf-login-visual" aria-hidden="true">
            <div class="tf-login-visual-text">
                <h2>The Fuentes Workspace</h2>
                <p>Gestion integral de obras, requisiciones de compra y catalogos en un solo lugar.</p>
            </div>
        </aside>

        <!-- Panel del formulario -->
        <section class="tf-login-panel">
            <div class="tf-login-card">

                <div class="tf-login-brand-row">
                    <span class="tf-brand-mark">TF</span>
                    <span class="tf-brand-text" style="font-weight:700;color:var(--tf-text)">
                        The Fuentes
                        <small style="display:block;font-size:.7rem;color:var(--tf-text-soft);font-weight:500;letter-spacing:.04em">
                            Corporation
                        </small>
                    </span>
                </div>

                <h1>Iniciar sesion</h1>
                <p class="lead">
                    Accede al espacio de trabajo para administrar obras, requisiciones y catalogos.
                </p>

                <form id="myForm" class="tf-login-form" autocomplete="on">
                    <div class="form-group">
                        <label for="User">Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true">U</span>
                            <input type="text"
                                   class="form-control"
                                   id="User"
                                   name="User"
                                   autocomplete="username"
                                   placeholder="Tu nombre de usuario"
                                   required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="Password">Contrasena</label>
                        <div class="input-group">
                            <span class="input-group-text" aria-hidden="true">P</span>
                            <input type="password"
                                   class="form-control"
                                   id="Password"
                                   name="Password"
                                   autocomplete="current-password"
                                   placeholder="Tu contrasena"
                                   required>
                            <button type="button"
                                    id="togglePassword"
                                    class="btn btn-outline-secondary"
                                    aria-label="Mostrar contrasena"
                                    aria-pressed="false">
                                Ver
                            </button>
                        </div>
                    </div>

                    <button type="submit"
                            id="loginSubmit"
                            class="tf-btn tf-btn-primary">
                        <span class="login-label">Entrar</span>
                        <span class="login-loading d-none">
                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            Validando...
                        </span>
                    </button>

                    <div class="text-center mt-4" style="font-size:.85rem;color:var(--tf-text-soft)">
                        <span>Olvidaste tu contrasena?</span>
                        <a href="#" style="color:var(--tf-primary);font-weight:600;text-decoration:none">
                            Recuperar acceso
                        </a>
                    </div>
                </form>

                <footer class="text-center mt-5" style="font-size:.72rem;color:var(--tf-text-soft);opacity:.7">
                    &copy; <?= date('Y') ?> The Fuentes Corporation &middot; Workspace v4.1
                </footer>

            </div>
        </section>
    </div>

    <!-- SweetAlert2 -->
    <script src="../assets/lib/sweetalert/sweetalert2.min.js?v=<?= $tf_swal_js_v ?>"></script>

    <!-- Login JS -->
    <script src="../assets/js/login.js?v=<?= $tf_login_js_v ?>"></script>
</body>
</html>
