var loginUrl = "../api/LoginAcces.php";
var homeUrl = "./index.php";

function createToast() {
    return Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timerProgressBar: true,
        didOpen: function (toast) {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });
}

function setLoadingState(isLoading, submitButton) {
    var loginLabel = submitButton.querySelector(".login-label");
    var loginLoading = submitButton.querySelector(".login-loading");

    submitButton.disabled = isLoading;
    loginLabel.classList.toggle("d-none", isLoading);
    loginLoading.classList.toggle("d-none", !isLoading);
}

function persistLoginData(credentials) {
    try {
        sessionStorage.setItem("tf_user_id", String(credentials.user_id));
        if (credentials.rol) sessionStorage.setItem("tf_user_role", credentials.rol);
        if (credentials.rolName) sessionStorage.setItem("tf_user_role_name", credentials.rolName);
        if (credentials.csrf) sessionStorage.setItem("tf_csrf", credentials.csrf);
    } catch (error) {
        // Silencioso: el login no debe fallar por storage bloqueado.
    }

    // Fase 5: la identidad ahora vive en la sesion PHP (tf_current_user).
    // Ya no escribimos localStorage.NameUser para evitar identity client-side.
}

document.addEventListener("DOMContentLoaded", function () {
    var form = document.getElementById("myForm");
    var userInput = document.getElementById("User");
    var passwordInput = document.getElementById("Password");
    var submitButton = document.getElementById("loginSubmit");
    var togglePasswordButton = document.getElementById("togglePassword");

    if (!form || !userInput || !passwordInput || !submitButton) {
        return;
    }

    var toast = createToast();
    var isLoading = false;

    if (togglePasswordButton) {
        togglePasswordButton.addEventListener("click", function () {
            var revealPassword = passwordInput.type === "password";
            passwordInput.type = revealPassword ? "text" : "password";
            togglePasswordButton.textContent = revealPassword ? "Ocultar" : "Ver";
            togglePasswordButton.setAttribute("aria-label", revealPassword ? "Ocultar contrasena" : "Mostrar contrasena");
            togglePasswordButton.setAttribute("aria-pressed", revealPassword ? "true" : "false");
        });
    }

    form.addEventListener("submit", function (event) {
        event.preventDefault();

        var user = userInput.value.trim();
        var password = passwordInput.value.trim();

        userInput.value = user;
        passwordInput.value = password;

        if (!user || !password) {
            toast.fire({
                icon: "warning",
                title: "Datos incompletos",
                timer: 3000
            });
            return;
        }

        if (isLoading) {
            return;
        }

        isLoading = true;
        setLoadingState(true, submitButton);

        fetch(loginUrl, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                "X-Requested-With": "XMLHttpRequest"
            },
            body: JSON.stringify({ user: user, password: password })
        }).then(function (response) {
            return response.json();
        }).then(function (credentials) {
            if (credentials && credentials.bandera == "true") {
                persistLoginData(credentials);
                return toast.fire({
                    icon: "success",
                    title: "Autenticacion Correcta",
                    timer: 1000
                }).then(function () {
                    window.location.href = homeUrl;
                });
            }

            return toast.fire({
                icon: "error",
                title: "Verifica la informacion",
                timer: 1000
            });
        }).catch(function () {
            toast.fire({
                icon: "error",
                title: "No se pudo conectar con el servidor",
                timer: 3000
            });
        }).finally(function () {
            isLoading = false;
            setLoadingState(false, submitButton);
        });
    });
});