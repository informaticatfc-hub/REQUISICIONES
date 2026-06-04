var url = "../api/crud_direccion.php";
var url2 = ".";

function direccionApp() {
    return {
        users: [],
        obras: [],
        obrasLista: [],
        NameUser: "",
        init: function () {
            this.listarObras();
            this.consultarUsuario();
        },
        consultarUsuario: async function () {
            try {
                // Fase 5: identidad por sesion server-side (sin localStorage.NameUser)
                var response = await axios.post(url, { accion: 1 });
                this.users = response.data || [];
                if (this.users[0] && this.users[0].user_name) {
                    this.NameUser = this.users[0].user_name;
                }
            } catch (err) {
                console.error("consultarUsuario:", err);
            }
        },
        infoObraActiva: async function (obrasId) {
            var response = await axios.post(url, { accion: 3, obra: obrasId });
            this.obras = response.data;
            console.log(this.obras);
        },
        listarObras: async function () {
            var response = await axios.post(url, { accion: 2 });
            this.obrasLista = response.data;
            console.log(this.obrasLista);
        },
        irObra: function (idObra) {
            localStorage.setItem("obraActiva", idObra);
            window.location.href = url2 + "/obras.php";
        },
        enterRequisiciones: function () {
            window.location.href = url2 + "/requisiciones.php";
        },
        enterAllPresiones: function () {
            window.location.href = url2 + "/all_presiones.php";
        },
        enterReportesKpi: function () {
            window.location.href = url2 + "/reportes_kpi.php";
        },
        irDireecion: function () {
            window.location.href = url2 + "/direccion.php";
        },
        irMenuCatalago: function () {
            window.location.href = url2 + "/menu_catalago.php";
        }
    };
}