var url = "../api/crud_obras.php";
var url2 = ".";

const appRequesition = new Vue({
    el: "#AppObras",
    data: {
        users: [],
        obras: [],
        obrasLista: [],
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
        infoObraActiva: function (obrasId) {
            axios.post(url, { accion: 3, obra: obrasId }).then(response => {
                this.obras = response.data;
                console.log(this.obras);
            });
        },
        listarObras: function () {
            axios.post(url, { accion: 2, modo: "recientes", limite: 12 }).then(response => {
                this.obrasLista = response.data;
                console.log(this.obrasLista);
            });
        },
        irObra(idObra) {
            localStorage.setItem("obraActiva", idObra);
            window.location.href = url2 + "/obras.php";
        },
        enterRequisiciones: function()
        {
            window.location.href = url2 + "/requisiciones.php";
        },
        enterPresiones: function()
        {
            window.location.href = url2 + "/presiones.php";
        },
        irDireecion: function(){
            window.location.href = url2 + "/direccion.php";
        },
        irMenuCatalago: function(){
            window.location.href = url2 + "/menu_catalago.php";
        }
    },
    created: function () {
        this.listarObras();
        this.infoObraActiva(localStorage.getItem("obraActiva"));
        this.consultarUsuario();
    },
    computed: {

    }
});