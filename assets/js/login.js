var url = "../api/LoginAcces.php";
var url2 = ".";

const appLogin = new Vue({
    el: "#LoginApp",
    data: {
        User: "",
        Password: "",
        Credenciales: [],
        isLoading: false,
        showPwd: false
    },
    methods: {
        EntarLogin: async function (User, Password) {
            const user = (User || "").trim();
            const password = (Password || "").trim();
            this.User = user;
            this.Password = password;

            if (user === "" || password === "") {
                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });
                Toast.fire({
                    icon: "warning",
                    title: "Datos incompletos"
                });
            }
            else {
                this.login();
            }
        },
        login: function () {
            if (this.isLoading) {
                return;
            }

            this.isLoading = true;
            axios.post(url, { user: this.User, password: this.Password }).then(response => {
                console.log("La respuesta es: "+response.data);
                this.Credenciales = response.data;
                if (this.Credenciales.bandera == "true") {
                    localStorage.setItem("NameUser",this.Credenciales.user_id);
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 1000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.onmouseenter = Swal.stopTimer;
                            toast.onmouseleave = Swal.resumeTimer;
                        }
                    });
                    Toast.fire({
                        icon: "success",
                        title: "Autenticacion Correcta"
                    }).then(()=>{
                        window.location.href = url2+"/index.php";
                    });
                }
                else {
                    const Toast = Swal.mixin({
                        toast: true,
                        position: "top-end",
                        showConfirmButton: false,
                        timer: 1000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.onmouseenter = Swal.stopTimer;
                            toast.onmouseleave = Swal.resumeTimer;
                        }
                    });
                    Toast.fire({
                        icon: "error",
                        title: "Verifica la informacion"
                    });

                }
            }).catch(() => {
                const Toast = Swal.mixin({
                    toast: true,
                    position: "top-end",
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.onmouseenter = Swal.stopTimer;
                        toast.onmouseleave = Swal.resumeTimer;
                    }
                });
                Toast.fire({
                    icon: "error",
                    title: "No se pudo conectar con el servidor"
                });
            }).finally(() => {
                this.isLoading = false;
            });
        }
    },
    created: function () { },
    computed: {}
});

// Exponer la instancia globalmente para el patch en pages/login.php
try { window.appLogin = appLogin; } catch (e) { /* silencioso */ }