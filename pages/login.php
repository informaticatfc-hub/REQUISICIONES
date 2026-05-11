<!DOCTYPE html>
<html>

<head>
    <meta charset="utf8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="icon" type="image/jpg" href="../images/TheFuenteIcon.png" />
    <!--llamar a la extension de sweet alert-->
    <link rel="stylesheet" href="../assets/lib/sweetalert/sweetalert2.min.css">
    <!-- fuente de Roboto flex-->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Flex:opsz,wght@8..144,100..1000&display=swap" rel="stylesheet">
    <!--Fuentes de Iconos-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <!--llamar a la extension de bootstrap-->
    <!-- esta es la llamada via CDN-
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">-->
    <!-- esta es la llamada local-->
    <link rel="stylesheet" href="../assets/lib/bootstrap/css/bootstrap.min.css">
    <!--llamar a mi documento de CSS-->
    <link rel="stylesheet" href="../assets/css/main.css">
    <title>BIENVENIDO</title>
    <style>

    </style>
</head>

<body class="container-fluid login-page">
    <div id="LoginApp" class="h-100">
        <div class="row g-0 h-100 login-shell">
            <div class="col-lg-5 login-visual">
                <div id="imagenLogin" class="h-100"></div>
            </div>
            <div class="col-lg-7 login-panel-wrap">
                <div class="login-panel">
                <div class=" row g-0">
                    <div class="col">
                        <img src="../images/LogoFuentes.png" class="rounded mx-auto d-block mb-4 login-brand" alt="Logo The Fuentes Corporation">
                    </div>
                </div>
                <div class="row g-0">
                    <div class="col">
                        <h1 class="text-center login-title">Iniciar sesion</h1>
                        <p class="text-center login-subtitle">Accede al espacio de trabajo para administrar obras, requisiciones y catalogos.</p>
                    </div>
                </div>
                <form id="myForm" action="./login.php" method="post" class="login-form">
                    <div class="row g-0 form-group w-75 mx-auto">
                        <div class="col d-block">
                            <label for="User">Usuario</label>
                            <input type="text" class="form-control" id="User" name="User" autocomplete="username" v-model="User">
                        </div>
                    </div>
                    <div class="row g-0 form-group mt-3 w-75 mx-auto">
                        <div class="col d-block">
                            <label for="Password">Contrasena</label>
                            <input type="password" class="form-control" id="Password" name="Password" autocomplete="current-password" v-model="Password">
                        </div>
                    </div>
                    <div class="row g-0 form-group mt-3 w-75 mx-auto">
                        <div class="col d-block">
                            <button type="button" @click="EntarLogin(User,Password)" :disabled="isLoading" class="btn btn-primary w-100">{{ isLoading ? 'Validando...' : 'Entrar' }}</button>
                        </div>
                    </div>
                    <div class="row g-0 mt-4">
                        <div class="col d-block">
                            <p class="text-center my-0">¿Olvidaste tu contraseña?</p>
                            <p class="text-center my-0"><a href="" class="ink-offset-2 link-offset-3-hover link-underline link-underline-opacity-0 link-underline-opacity-75-hover">Haz clic aqui</a></p>
                        </div>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>

    <!--scripts de bootstrap, poppers y jquery-->
    <script src="../assets/lib/jquery/jquery-3.7.1.slim.min.js"></script>
    <script src="https://unpkg.com/@popperjs/core@2/dist/umd/popper.js"></script>
    <script src="../assets/lib/bootstrap/js/bootstrap.min.js"></script>

    <!-- scripts de vue.js-->
    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.16/dist/vue.js"></script>

    <!--Script de axios-->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

    <!--scripts de sweetalert-->
    <script src="../assets/lib/sweetalert/sweetalert2.min.js"></script>

    <!-- scripts constume-->
    <script src="../assets/js/login.js"> </script>
</body>

</html>