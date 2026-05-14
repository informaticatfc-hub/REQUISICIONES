<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b1424">
    <link rel="icon" type="image/png" href="../images/TheFuenteIcon.png">
    <title>Iniciar sesion :: The Fuentes Workspace</title>

    <!-- Bootstrap 5.3.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
          rel="stylesheet">

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="../assets/lib/sweetalert/sweetalert2.min.css">

    <!-- Diseno v4 -->
    <link rel="stylesheet" href="../assets/css/v4.css">
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

                <form id="myForm" class="tf-login-form" autocomplete="on" @submit.prevent="EntarLogin(User, Password)">
                    <div class="form-group">
                        <label for="User">Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-person-fill"></i>
                            </span>
                            <input type="text"
                                   class="form-control"
                                   id="User"
                                   name="User"
                                   autocomplete="username"
                                   v-model="User"
                                   placeholder="Tu nombre de usuario"
                                   required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="Password">Contrasena</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                            <input :type="showPwd ? 'text' : 'password'"
                                   class="form-control"
                                   id="Password"
                                   name="Password"
                                   autocomplete="current-password"
                                   v-model="Password"
                                   placeholder="Tu contrasena"
                                   required>
                            <button type="button"
                                    class="btn btn-outline-secondary"
                                    @click="showPwd = !showPwd"
                                    :aria-label="showPwd ? 'Ocultar contrasena' : 'Mostrar contrasena'">
                                <i :class="showPwd ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit"
                            :disabled="isLoading"
                            class="tf-btn tf-btn-primary">
                        <span v-if="!isLoading">
                            <i class="bi bi-box-arrow-in-right"></i> Entrar
                        </span>
                        <span v-else>
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

    <!-- Bootstrap 5.3.3 bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>

    <!-- Vue 2.7 (compat con appLogin existente) -->
    <script src="https://cdn.jsdelivr.net/npm/vue@2.7.16/dist/vue.min.js"></script>

    <!-- Axios -->
    <script src="https://cdn.jsdelivr.net/npm/axios@1.7.2/dist/axios.min.js"></script>

    <!-- SweetAlert2 -->
    <script src="../assets/lib/sweetalert/sweetalert2.min.js"></script>

    <!-- Login JS (instancia Vue para #LoginApp) -->
    <script src="../assets/js/login.js"></script>

    <!-- Patch: agregar campo showPwd al data() del appLogin -->
    <script>
        // Pequeno parche: si la instancia Vue no expone showPwd, lo creamos reactivo via $set
        document.addEventListener('DOMContentLoaded', function () {
            try {
                if (window.appLogin && typeof window.appLogin.$set === 'function') {
                    window.appLogin.$set(window.appLogin, 'showPwd', false);
                }
            } catch (e) { /* silencioso */ }
        });
    </script>
</body>
</html>
