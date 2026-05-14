var url = "../api/crud_addProveedor.php";
var url2 = ".";

const appRequesition = new Vue({
    el: "#AppNewProv",
    data: {
        users: [],
        bancos: [],
        obras: [],
        NameUser: "",
        selected_Banco: "",
        nombre_prov: "",
        direccion_prov: "",
        rfc_prov: "",
        clabe_prov: "",
        cuenta_prov: "",
        tarjeta_prov: "",
        referencia_prov: "",
        tipo_prov: "",
        suc_prov: "",
        tel_prov: "",
        email_prov: ""
    },
    methods: {
        consultarUsuario: function () {
            // Fase 5: identidad por sesion server-side (sin localStorage.NameUser)
            axios.post(url, { accion: 2 }).then(response => {
                this.users = response.data || [];
                if (this.users[0] && this.users[0].user_name) {
                    this.NameUser = this.users[0].user_name;
                }
            }).catch(err => console.error("consultarUsuario:", err));
        },
        irObra(idObra) {
            localStorage.setItem("obraActiva", idObra);
            window.location.href = url2 + "/obras.php";
        },
        listarObras: function () {
            axios.post(url, { accion: 3 }).then(response => {
                this.obras = response.data;
                console.log(this.obras);
            });
        },
        listarBancos: function () {
            axios.post(url, { accion: 1 }).then(response => {
                this.bancos = response.data;
                console.log(this.bancos);
            });
        },
        agregarProveedor: async function () {        
            const { value: formValues } = await Swal.fire({
                title: "¿Quieres guardar el proveedor?",
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: "Guardar",
                denyButtonText: `No Guardar`
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    axios.post(url, { accion: 4, nombre: this.nombre_prov, direccion: this.direccion_prov, rfc: this.rfc_prov, clabe: this.clabe_prov, cuenta: this.cuenta_prov, tarjeta: this.tarjeta_prov, referencia: this.referencia_prov, banco: this.selected_Banco, tipoProv: this.tipo_prov, sucursal: this.suc_prov, telefono: this.tel_prov, correo: this.email_prov }).then(response => {
                        console.log(response.data);
                    });
                    Swal.fire("El proveedor fue guardada con Exito", "", "success");
                } else if (result.isDenied) {
                    Swal.fire("No se guardo el proveedor", "", "info");
                }
            });
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
        this.consultarUsuario();
        this.listarBancos();
    },
    computed: {

    }
});