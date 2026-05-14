var url = "../api/crud_menu_catalagos.php";
var url2 = ".";

const appRequesition = new Vue({
    el: "#AppIndex",
    data: {
        users: [],
        obras: [],
        NameUser: ""
    },
    methods: {
        consultarUsuario: function (user_id) {
            axios.post(url, { accion: 1, id_user: user_id }).then(response => {
                this.users = response.data;
                if (this.users.length > 0) {
                    this.NameUser = this.users[0].user_name;
                } else {
                    this.NameUser = "Usuario desconocido";
                }
                console.log(this.users);
            }).catch(error => {
                console.error("Error al consultar usuario:", error);
            });
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
        },
        irMenuCatalago: function(){
            window.location.href = url2 + "/menu_catalago.php";
        },
        IrCatalagoProveedor: function(){
            window.location.href = url2 + "/proveedores.php";
        },
        irCatalagoBanco: function(){
            window.location.href = url2 + "/bancos.php";
        }
    },
    created: function () {
        this.listarObras();
        this.consultarUsuario(localStorage.getItem("NameUser"));
    },
    computed: {

    }
});

console.log("¿Vue tiene funcionDePrueba?", typeof appRequesition.funcionDePrueba);
