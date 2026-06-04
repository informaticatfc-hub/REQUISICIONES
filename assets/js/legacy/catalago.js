var url = "../api/crud_catalogos.php";
var url2 = ".";

const appRequesition = new Vue({
    el: "#AppCatalague",
    data: {
        users: [],
        obras: [],
        NameUser: ""
    },
    methods: {
        consultarUsuario: function () {
            // Fase 5: identidad por sesion server-side (sin localStorage.NameUser)
            axios.post(url, { accion: 1 }).then(response => {
                this.users = response.data || [];
                if (this.users[0] && this.users[0].user_name) {
                    this.NameUser = this.users[0].user_name;
                }
            }).catch(err => console.error("consultarUsuario:", err));
        },
        listarObras: function () {
            axios.post(url, { accion: 2 }).then(response => {
                this.obras = response.data;
                console.log(this.obras);
            });
        },
        irObra(idObra) {
            localStorage.setItem("obraActiva", idObra);
            window.location.href = url2 + "/obras.php";
        },
        irDireecion: function(){
            window.location.href = url2 + "/direccion.php";
        }
    },
    created: function () {
        this.listarObras();
        this.consultarUsuario();
    },
    computed: {

    }
});