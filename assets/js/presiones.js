var url = "../api/crud_Presiones.php";
var url2 = ".";

function presionesApp() {
    return {
        presiones: [],
        users: [],
        obras: [],
        obrasLista: [],
        presion: "",
        semana: "",
        dia: "",
        clave: "",
        NameUser: "",
        activeTab: "todas",
        totalPresiones: 0,
        pendientesCount: 0,
        autorizadasCount: 0,
        totalRows: 0,
        currentPage: 1,
        totalPages: 1,
        limite: 20,
        searchText: "",
        searchTimer: null,
        loadingTable: false,
        alias: "",
        timeNow: ""
    ,
        ConsultarPresion: async function (idPresion, Accion, week, day) {
            console.log(idPresion + " " + Accion);
            switch (Accion) {
                case 1:
                    localStorage.setItem("IdPresion", idPresion);
                    window.location.href = url2 + "/enlazar_requisiciones.php";
                    break;
                case 2:
                    localStorage.setItem("Semana", week);
                    localStorage.setItem("Dia", day);
                    localStorage.setItem("IdPresion", idPresion);
                    window.location.href = url2 + "/presiones_detalles.php";
                    break;
            }

        },
        NewPression: async function () {
            const date = new Date(this.getCurrentDate()); // 1 de enero de 2023
            const numweek = this.getWeekNumber(date);
            const dayName = this.getDayOfWeek(date);
            const { value: formValues } = await Swal.fire({
                title: "Nueva Presion",
                html: `
                    <div class="col">
                    <hr/>
                        <div class="row form-group mx-0 my-3">
                            <div class="col d-flex flex-column">
                                <label class="text-start py-2" for="Sem_Press">Semana</label>
                                 <input type="number" value="`+ numweek + `" class="form-control" min="1" max="52" id="Sem_Press" name="Sem_Press" disabled>
                            </div>
                        </div>
                        <div class="row form-group mx-0 my-3">
                            <div class="col d-flex flex-column">
                                <label class="text-start py-2" for="Day_Press">Dia</label>
                                    <input type="text" value="`+ dayName + `" class="form-control" id="Day_Press" name="Day_Press" disabled>
                            </div>
                        </div>
                        <div class="row form-group mx-0 my-3">
                            <div class="col d-flex flex-column">
                                <label class="text-start py-2" for="Alias">Alias</label>
                                <select class="form-select" aria-label="Default select example" id="Alias">
                                    <option value="Selecciona Alias">Selecciona Alias</option>
                                    <option value="Acarreo">Acarreo</option>
                                    <option value="Indirectos">Indirectos</option>
                                    <option value="Maquinaria">Maquinaria</option>
                                </select>
                            </div>
                        </div>
                    <hr/>
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Agregar',
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#dc3545',
                preConfirm: () => {
                    this.semana = document.getElementById("Sem_Press").value;
                    this.dia = document.getElementById("Day_Press").value;
                    this.alias = document.getElementById("Alias").value;
                    if (this.alias === "Selecciona Alias") {
                        Swal.showValidationMessage('Por favor selecciona un alias');
                        return false;
                    }
                    return true;
                }
            });
            if(formValues){
                this.agregarPresion();
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
                Toast.fire({
                    icon: 'success',
                    title: 'Presion Agregada'
                });
            }
        },
        listarPresiones: async function (obrasId) {
            try {
                this.loadingTable = true;
                var estatus = this.activeTab === 'pendientes' ? 'AUTORIZADO' : '';
                const response = await axios.post(url, {
                    accion: 1,
                    obra: obrasId,
                    page: this.currentPage,
                    limite: this.limite,
                    search: this.searchText,
                    estatus: estatus,
                    serverSide: 1
                });
                var payload = response.data || {};
                var rows = Array.isArray(payload) ? payload : (payload.rows || []);
                this.presiones = rows;
                if (!Array.isArray(payload)) {
                    this.totalRows = Number(payload.total || 0);
                    this.currentPage = Number(payload.page || this.currentPage || 1);
                    this.totalPages = Number(payload.pages || 1);
                    var stats = payload.stats || {};
                    this.totalPresiones = Number(stats.total || 0);
                    this.pendientesCount = Number(stats.pendientes || 0);
                    this.autorizadasCount = Number(stats.autorizadas || 0);
                } else {
                    this.totalRows = rows.length;
                    this.currentPage = 1;
                    this.totalPages = 1;
                    this.totalPresiones = rows.length;
                    this.pendientesCount = rows.filter(function (p) { return p.presiones_estatus === 'PENDIENTE'; }).length;
                    this.autorizadasCount = rows.filter(function (p) { return p.presiones_estatus === 'AUTORIZADO'; }).length;
                }
                console.log(this.presiones);
            } catch (error) {
                console.error("Error al Listar las Presiones:", error);
            } finally {
                this.loadingTable = false;
            }
        },
        consultarUsuario: async function () {
            // Fase 5: identidad por sesion server-side (sin localStorage.NameUser)
            try {
                const response = await axios.post(url, { accion: 2 });
                this.users = response.data || [];
                if (this.users.length > 0 && this.users[0].user_name) {
                    this.NameUser = this.users[0].user_name;
                }
            } catch (error) {
                console.error("Error al consultar usuario:", error);
            }
        },
        agregarPresion: function () {
            const fecha = new Date();
            var mes = fecha.getMonth() + 1;
            var dia = fecha.getDate();
            this.timeNow = this.getTime();
            mes = mes < 10 ? '0' + mes : mes;
            dia = dia < 10 ? '0' + dia : dia;
            var fechaActual = fecha.getFullYear() + "-" + mes + "-" + dia;
            axios.post(url, { accion: 3, alias: this.alias, semana: this.semana, dia: this.dia, time: this.timeNow, fecha: fechaActual, user_creado: 0, obra: localStorage.getItem("obraActiva") }).then(response => {
                console.log(response.data);
                this.currentPage = 1;
                this.listarPresiones(localStorage.getItem("obraActiva"));
            });
        },
        infoObraActiva: async function (obrasId) {
            try {
                const response = await axios.post(url, { accion: 4, obra: obrasId });
                this.obras = response.data;
                console.log(this.obras);
            } catch (error) {
                console.error("Error al consultar la informacion de la Obra", error);
            }
        },
        listarObras: async function () {
            try {
                const response = await axios.post(url, { accion: 5 });
                this.obrasLista = response.data;
                console.log(this.obrasLista);
            } catch (error) {
                console.error("Error al Listar las Obras:", error);
            }
        },
        irObra(idObra) {
            localStorage.setItem("obraActiva", idObra);
            window.location.href = url2 + "/obras.php";
        },
        getWeekNumber: function (date) {
            const onejan = new Date(date.getFullYear(), 0, 1);
            const week = Math.ceil((((date - onejan) / 86400000) + onejan.getDay() + 1) / 7);
            return week;
        },
        getDayOfWeek: function (date) {
            const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
            console.log(days);
            return days[date.getDay()+1]; 
        },
        getCurrentDate: function () {
            const date = new Date();
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },
        getTime: function () {
            const currentTime = new Date();
            const hours = currentTime.getHours();
            const minutes = currentTime.getMinutes();
            const seconds = currentTime.getSeconds();

            const formattedTime = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

            return formattedTime;
        },
        onSearchInput: function () {
            if (this.searchTimer) clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => {
                this.currentPage = 1;
                this.listarPresiones(localStorage.getItem("obraActiva"));
            }, 350);
        },
        goToPage: function (page) {
            if (this.loadingTable) return;
            var target = Math.max(1, Math.min(this.totalPages, parseInt(page, 10) || 1));
            if (target === this.currentPage) return;
            this.currentPage = target;
            this.listarPresiones(localStorage.getItem("obraActiva"));
        },
        irDireecion: function(){
            window.location.href = url2 + "/direccion.php";
        },
        irMenuCatalago: function(){
            window.location.href = url2 + "/menu_catalago.php";
        },
        setActiveTab: function (tab) {
            if (this.activeTab === tab) return;
            this.activeTab = tab;
            this.currentPage = 1;
            this.listarPresiones(localStorage.getItem("obraActiva"));
        },
        init: async function () {
            await this.consultarUsuario();
            var obraId = localStorage.getItem("obraActiva");
            if (!obraId) { window.location.href = './obras.php'; return; }
            await this.listarObras();
            await this.infoObraActiva(obraId);
            await this.listarPresiones(obraId);
        },
        get pendientesDePago() {
            return this.presiones;
        },
        get pageRange() {
            var start = Math.max(1, this.currentPage - 2);
            var end = Math.min(this.totalPages, start + 4);
            start = Math.max(1, end - 4);
            var out = [];
            for (var i = start; i <= end; i++) out.push(i);
            return out;
        }
    };
}
