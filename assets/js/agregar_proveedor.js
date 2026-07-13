var url = "../api/crud_addProveedor.php";
var url2 = ".";

function addProveedorApp() {
    return {
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
        email_prov: "",
        get rfcValido() {
            var rfc = String(this.rfc_prov || '').trim().toUpperCase();
            return /^[A-Z&Ñ]{3,4}\d{6}[A-Z0-9]{3}$/.test(rfc);
        },
        get clabeValida() {
            var clabe = String(this.clabe_prov || '').trim();
            if (!/^\d{18}$/.test(clabe)) return false;
            var pesos = [3, 7, 1];
            var suma = 0;
            for (var i = 0; i < 17; i++) {
                var n = parseInt(clabe.charAt(i), 10);
                suma += ((n * pesos[i % 3]) % 10);
            }
            var digito = (10 - (suma % 10)) % 10;
            return digito === parseInt(clabe.charAt(17), 10);
        },
        get puedeGuardar() {
            return !!String(this.nombre_prov || '').trim()
                && this.rfcValido
                && this.clabeValida
                && !!String(this.cuenta_prov || '').trim()
                && !!String(this.selected_Banco || '').trim();
        },
        init: function () {
            this.listarObras();
            this.consultarUsuario();
            this.listarBancos();
        },
        normalizarRFC: function () {
            this.rfc_prov = String(this.rfc_prov || '')
                .toUpperCase()
                .replace(/\s+/g, '')
                .replace(/[^A-Z0-9&Ñ]/g, '');
        },
        normalizarClabe: function () {
            this.clabe_prov = String(this.clabe_prov || '')
                .replace(/\D/g, '')
                .slice(0, 18);
        },
        consultarUsuario: async function () {
            try {
                // Fase 5: identidad por sesion server-side (sin localStorage.NameUser)
                var response = await axios.post(url, { accion: 2 });
                this.users = response.data || [];
                if (this.users[0] && this.users[0].user_name) {
                    this.NameUser = this.users[0].user_name;
                }
            } catch (err) {
                console.error("consultarUsuario:", err);
            }
        },
        irObra: function (idObra) {
            localStorage.setItem("obraActiva", idObra);
            window.location.href = url2 + "/obras.php";
        },
        listarObras: async function () {
            var response = await axios.post(url, { accion: 3 });
            this.obras = response.data;
            console.log(this.obras);
        },
        listarBancos: async function () {
            var response = await axios.post(url, { accion: 1 });
            this.bancos = response.data;
            console.log(this.bancos);
        },
        agregarProveedor: async function () {
            this.normalizarRFC();
            this.normalizarClabe();
            if (!this.puedeGuardar) {
                Swal.fire({
                    icon: "warning",
                    title: "Datos inválidos",
                    text: "Verifica RFC, CLABE y campos requeridos antes de guardar."
                });
                return;
            }
            var result = await Swal.fire({
                title: "¿Quieres guardar el proveedor?",
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: "Guardar",
                denyButtonText: "No Guardar"
            });

            if (result.isConfirmed) {
                var resp = await axios.post(url, {
                    accion: 4,
                    nombre: this.nombre_prov,
                    direccion: this.direccion_prov,
                    rfc: this.rfc_prov,
                    clabe: this.clabe_prov,
                    cuenta: this.cuenta_prov,
                    tarjeta: this.tarjeta_prov,
                    referencia: this.referencia_prov,
                    banco: this.selected_Banco,
                    tipoProv: this.tipo_prov,
                    sucursal: this.suc_prov,
                    telefono: this.tel_prov,
                    correo: this.email_prov
                });
                if (resp.data && resp.data.duplicate) {
                    Swal.fire({
                        icon: "warning",
                        title: "Proveedor duplicado",
                        text: "Ya existe un proveedor con la misma CLABE o cuenta bancaria: \"" + resp.data.proveedor_nombre + "\". Verifica los datos antes de continuar."
                    });
                    return;
                }
                Swal.fire("El proveedor fue guardado con éxito", "", "success");
                return;
            }

            if (result.isDenied) {
                Swal.fire("No se guardo el proveedor", "", "info");
            }
        },
        irDireecion: function () {
            window.location.href = url2 + "/direccion.php";
        },
        irMenuCatalago: function () {
            window.location.href = url2 + "/menu_catalago.php";
        }
    };
}