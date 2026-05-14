var url = "../api/crud_direccion.php";
var url2 = ".";

const appRequesition = new Vue({
    el: "#AppDireccion",
    data: {
        users: [],
        obras: [],
        obrasLista: [],
        NameUser: "",
        selectedObraId: "",
        selectedObraNombre: ""
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
            axios.post(url, { accion: 2 }).then(response => {
                this.obrasLista = response.data;
                console.log(this.obrasLista);

                if (this.selectedObraId) {
                    var encontrada = (this.obrasLista || []).find((o) => String(o.obras_id) === String(this.selectedObraId));
                    this.selectedObraNombre = encontrada ? encontrada.obras_nombre : "";
                }
            });
        },
        seleccionarObraActiva: function () {
            var id = String(this.selectedObraId || "").trim();
            if (!id) {
                Swal.fire("Selecciona una obra", "Primero debes elegir una obra para continuar.", "info");
                return;
            }

            localStorage.setItem("obraActiva", id);
            var encontrada = (this.obrasLista || []).find((o) => String(o.obras_id) === id);
            this.selectedObraNombre = encontrada ? encontrada.obras_nombre : "";

            Swal.fire({
                icon: "success",
                title: "Obra activa actualizada",
                text: this.selectedObraNombre ? ("Obra seleccionada: " + this.selectedObraNombre) : "La obra activa fue actualizada.",
                timer: 1200,
                showConfirmButton: false
            });
        },
        validarObraSeleccionada: function () {
            var id = String(localStorage.getItem("obraActiva") || "").trim();
            if (!id) {
                Swal.fire("Selecciona una obra", "Antes de continuar, selecciona una obra activa en esta pantalla.", "info");
                return false;
            }
            return true;
        },
        irObra(idObra) {
            localStorage.setItem("obraActiva", idObra);
            window.location.href = url2 + "/obras.php";
        },
        enterRequisiciones: function()
        {
            if (!this.validarObraSeleccionada()) return;
            window.location.href = url2 + "/requisiciones.php";
        },
        enterAllPresiones: function()
        {
            if (!this.validarObraSeleccionada()) return;
            window.location.href = url2 + "/all_presiones.php";
        },
        enterReportesKpi: function()
        {
            if (!this.validarObraSeleccionada()) return;
            window.location.href = url2 + "/reportes_kpi.php";
        },
        irDireecion: function(){
            window.location.href = url2 + "/direccion.php";
        },
        irMenuCatalago: function(){
            window.location.href = url2 + "/menu_catalago.php";
        }
    },
    created: function () {
        var obraId = localStorage.getItem("obraActiva");
        if (obraId) {
            this.selectedObraId = String(obraId);
        }
        this.listarObras();
        if (obraId) {
            this.infoObraActiva(obraId);
        }
        this.consultarUsuario();
    },
    computed: {

    }
});