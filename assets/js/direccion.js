var url = "../api/crud_direccion.php";
var url2 = ".";

const appRequesition = new Vue({
    el: "#AppDireccion",
    data: {
        users: [],
        obras: [],
        obrasLista: [],
        NameUser: ""
    },
    methods: {
        consultarUsuario: function (user_id) {
            axios.post(url, { accion: 1, id_user: user_id }).then(response => {
                this.users = response.data;
                this.NameUser = this.users[0].user_name;
                console.log(this.users);
            });
        },
        infoObraActiva: function (obrasId) {
            axios.post(url, { accion: 3, obra: obrasId }).then(response => {
                this.obras = response.data;
                console.log(this.obras);
            });
        },
        listarObras: function () {
            axios.post(url, { accion: 2 }).then(response => {
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
        enterAllPresiones: function()
        {
            window.location.href = url2 + "/all_presiones.php";
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
        this.consultarUsuario(localStorage.getItem("NameUser"));
    },
    computed: {

    }
});