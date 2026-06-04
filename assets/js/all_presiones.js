var url = "../api/crud_all_presiones.php";
var url2 = ".";
var DIRECTOR_DRAFT_KEY = "tf_director_presiones_draft_v1";
var DIRECTOR_IMPORT_ALL_ID = "excelImportAllGlobal";

function allPresionesApp() {
    return {
        users: [],
        obras: [],
        NameUser: "",
        presiones: [],
        adeudo: "",
        comentarios: "",
        selectedCell: null,
        formulaBar: "",
        draftTimer: null,
        activeTab: "excel",
        pendientesRows: [],
        pendientesSearch: "",
        pendientesPage: 1,
        pendientesPages: 1,
        pendientesTotal: 0,
        pendientesLimite: 20,
        pendientesLoading: false,
        pendientesSearchTimer: null,
        canPresionesWrite: !!(window.TF_ALL_PRESIONES_PERMS && window.TF_ALL_PRESIONES_PERMS.canPresionesWrite),
        // F-M2: Pendientes de pago
        canFinanzasPagar: false,
        pendientesPagoRows: [],
        pendientesPagoSearch: '',
        pendientesPagoPage: 1,
        pendientesPagoPages: 1,
        pendientesPagoTotal: 0,
        pendientesPagoLimite: 20,
        pendientesPagoLoading: false,
        pendientesPagoSearchTimer: null,
        bancos: []
    ,
        handleGlobalHotkeys: function (event) {
            if (!(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 's') return;
            event.preventDefault();
            this.guardarCambiosMasivos();
        },
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
                this.listarPresiones();
                //console.log(this.obras);
            });
        },
        irObra(idObra) {
            localStorage.setItem("obraActiva", idObra);
            window.location.href = url2 + "/obras.php";
        },
        irDireecion: function () {
            window.location.href = url2 + "/direccion.php";
        },
        listarPresiones: function () {
            console.log(this.obras);
            axios.post(url, { accion: 3, obras: JSON.stringify(this.obras) }).then(response => {
                this.presiones = (response.data || []).map(function (obra) {
                    obra.formulaGlobal = obra.formulaGlobal || "";
                    obra.filtro = obra.filtro || "";
                    obra.soloEditados = obra.soloEditados || false;
                    obra.Presion_Obra = (obra.Presion_Obra || []).map(function (item) {
                        item.formula = item.formula || "";
                        item.adeudo = item.adeudo != null ? item.adeudo : item.total;
                        item._dirty = false;
                        item._baseAdeudo = item.adeudo;
                        item._baseObservaciones = item.Observaciones || "";
                        item._baseFormula = item.formula || "";
                        return item;
                    });
                    return obra;
                });
                this.aplicarBorradorLocal();
                console.log(this.presiones);
            });
        },
        cargarPendientesAutorizacion: function () {
            this.pendientesLoading = true;
            axios.post(url, {
                accion: 10,
                page: this.pendientesPage,
                limite: this.pendientesLimite,
                search: this.pendientesSearch
            }).then(function (response) {
                var payload = response.data || {};
                this.pendientesRows = payload.rows || [];
                this.pendientesTotal = Number(payload.total || 0);
                this.pendientesPage = Number(payload.page || 1);
                this.pendientesPages = Number(payload.pages || 1);
            }.bind(this)).catch(function (err) {
                console.error('cargarPendientesAutorizacion:', err);
                this.pendientesRows = [];
                this.pendientesTotal = 0;
                this.pendientesPage = 1;
                this.pendientesPages = 1;
            }.bind(this)).finally(function () {
                this.pendientesLoading = false;
            }.bind(this));
        },
        onPendientesSearchInput: function () {
            if (this.pendientesSearchTimer) clearTimeout(this.pendientesSearchTimer);
            this.pendientesSearchTimer = setTimeout(function () {
                this.pendientesPage = 1;
                this.cargarPendientesAutorizacion();
            }.bind(this), 350);
        },
        goPendientesPage: function (page) {
            if (this.pendientesLoading) return;
            var target = Math.max(1, Math.min(this.pendientesPages, parseInt(page, 10) || 1));
            if (target === this.pendientesPage) return;
            this.pendientesPage = target;
            this.cargarPendientesAutorizacion();
        },
        quitarEspacios: function (cadena) {
            if (!cadena) return ''; // Si la cadena es undefined o null, retorna una cadena vacía
            return cadena.replace(/\s+/g, ''); // Elimina todos los espacios
        },
        convertirADecimal: function (cadena) {
            // Verifica si cadena es una cadena de texto
            if (typeof cadena !== 'string') {
                // Si no es una cadena, conviértela a cadena
                cadena = String(cadena);
            }
            // Elimina el símbolo de dólar y convierte la cadena a número
            return parseFloat(cadena.replace('$', '').replace(',', ''));
        },
        autoriar: async function (idHoja, adeudo) {
            const { value: formValues, isConfirmed, isDenied } = await Swal.fire({
                title: "¿Desea Autorizar este Concepto?",
                html: `
                    <div class="col">
                        <div class="row form-group mx-0 my-3">
                            <div class="col">
                                <label for="adeudo" class="form-label">Adeudo a Pagar</label>
                                <input type="number" min="0" class="form-control" id="adeudo" value=`+ adeudo + `>
                                
                            </div>
                        </div>
                        <div class="row form-group mx-0 my-3">
                            <div class="col">
                               <label for="comentarios" class="form-label">Comentarios </label>
                               <textarea class="form-control" id="comentarios" rows="3"></textarea>
                            </div>
                        </div>
                        <hr />
                    </div>
                `,
                focusConfirm: false,
                showDenyButton: true,
                confirmButtonText: 'Autorizar',
                confirmButtonColor: '#0d6efd',
                denyButtonText: 'Rechazar', // Cambia el texto del botón de cancelar
                preConfirm: () => {
                    const productoValue = document.getElementById("comentarios").value;
                    console.log(productoValue);
                    this.comentarios = document.getElementById("comentarios").value;;
                    console.log(this.comentarios);
                    this.adeudo = document.getElementById("adeudo").value;
                    if (productoValue.length > 200) {
                        Swal.showValidationMessage('El campo Producto no puede exceder los 200 caracteres.');
                        return false;
                    }
                    return true;
                }
            });
            if (isConfirmed) {
                // Acción para el botón "Autorizar"
                this.autorizado(idHoja, this.adeudo);
                Swal.fire({
                    title: "Autorizado",
                    text: "El articulo fue Aprovado.",
                    icon: "success"
                }).then(() => {
                    this.listarObras();
                });
                // Aquí puedes agregar la lógica para manejar la autorización
            } else if (isDenied) {
                // Acción para el botón "Rechazar"
                this.comentarios = document.getElementById("comentarios").value;
                this.rechazado(idHoja, this.comentarios);
                console.log(this.comentarios);
                Swal.fire({
                    title: "No autorizado",
                    text: "El articulo no se aprobo para pago.",
                    icon: "error"
                }).then(() => {
                    this.listarObras();
                });
                // Aquí puedes agregar la lógica para manejar el rechazo
            }
        },
        autorizado: function (idHoja, adeudo) {
            //alert("Agregado"+id+parcial+" "+fecha+" "+banco);
            var estatus = "AUTORIZADO";
            //this.timeNow = this.getTime();
            axios.post(url, { accion: 4, idHoja: idHoja, parcial: adeudo, status: estatus, autorizado: true }).then(response => {
                console.log(response.data);
            });
        },
        rechazado: function (idHoja, comentarios) {
            //alert("Agregado"+id+parcial+" "+fecha+" "+banco);
            var estatus = "RECHAZADO";
            //this.timeNow = this.getTime();
            axios.post(url, { accion: 4, idHoja: idHoja, coments: comentarios, status: estatus, autorizado: false }).then(response => {
                console.log(response.data);
            });
        },
        formatearMoneda: function (cadena) {
            // Convertir la cadena a un número
            let numero = parseFloat(cadena);
            // Verificar si la conversión fue exitosa
            if (isNaN(numero)) {
                return null; // O puedes lanzar un error si prefieres
            }
            // Formatear el número como moneda en pesos mexicanos
            return "$ " + numero.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        cambiarBooleano: function (valor, indice, index) {
            this.presiones[index].Presion_Obra[indice].showDetail = !valor; // Devuelve el valor opuesto
            if (!valor) {
                this.presiones[index].Presion_Obra[indice].atrClass = "inline-block fs-6";
                this.presiones[index].Presion_Obra[indice].strStyle = ""; //max-width: 150px;
            }
            else {
                this.presiones[index].Presion_Obra[indice].atrClass = "inline-block text-truncate fs-6";
                this.presiones[index].Presion_Obra[indice].strStyle = "max-width: 100px;";//max-width: 100px;
            }

        },
        consultarTotales: async function (totalGlobalProp, TotalGlobalAut, totalEfectivoProp, totalEfectivoAut, totalTransProp, totalTransAut, totalGlobalRechazado, totalEfectivoRechazado, totalTransRechazado, nombreObra) {
            const { value: formValues } = await Swal.fire({
                title: "Totales — " + nombreObra,
                width: Math.min(window.innerWidth * 0.92, 700),
                html: `
                <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
                 <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
                    <thead>
                        <tr>
                            <th style="background:#1e293b;color:#fff;padding:10px 14px;white-space:nowrap;min-width:110px;"></th>
                            <th style="background:#1e293b;color:#fff;padding:10px 14px;white-space:nowrap;">Total Global</th>
                            <th style="background:#1e293b;color:#fff;padding:10px 14px;white-space:nowrap;">Total Efectivo</th>
                            <th style="background:#1e293b;color:#fff;padding:10px 14px;white-space:nowrap;">Total Transferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="background:#f1f5f9;font-weight:700;padding:10px 14px;white-space:nowrap;">Propuesto</td>
                            <td style="padding:10px 14px;white-space:nowrap;">`+ this.formatearMoneda(totalGlobalProp) + `</td>
                            <td style="padding:10px 14px;white-space:nowrap;">`+ this.formatearMoneda(totalEfectivoProp) + `</td>
                            <td style="padding:10px 14px;white-space:nowrap;">`+ this.formatearMoneda(totalTransProp) + `</td>
                        </tr>
                        <tr style="background:#eff6ff;">
                            <td style="background:#f1f5f9;font-weight:700;padding:10px 14px;white-space:nowrap;">Autorizado</td>
                            <td style="padding:10px 14px;white-space:nowrap;color:#1d4ed8;font-weight:600;">`+ this.formatearMoneda(TotalGlobalAut) + `</td>
                            <td style="padding:10px 14px;white-space:nowrap;color:#1d4ed8;font-weight:600;">`+ this.formatearMoneda(totalEfectivoAut) + `</td>
                            <td style="padding:10px 14px;white-space:nowrap;color:#1d4ed8;font-weight:600;">`+ this.formatearMoneda(totalTransAut) + `</td>
                        </tr>
                        <tr style="background:#fef2f2;">
                            <td style="background:#f1f5f9;font-weight:700;padding:10px 14px;white-space:nowrap;">Rechazado</td>
                            <td style="padding:10px 14px;white-space:nowrap;color:#dc2626;font-weight:600;">`+ this.formatearMoneda(totalGlobalRechazado) + `</td>
                            <td style="padding:10px 14px;white-space:nowrap;color:#dc2626;font-weight:600;">`+ this.formatearMoneda(totalEfectivoRechazado) + `</td>
                            <td style="padding:10px 14px;white-space:nowrap;color:#dc2626;font-weight:600;">`+ this.formatearMoneda(totalTransRechazado) + `</td>
                        </tr>
                    </tbody>
                </table>
                </div>
                `,
                focusConfirm: false,
            });
        },
        restartAlert: async function (id_Hoja) {
            const swalWithBootstrapButtons = await Swal.mixin({
                customClass: {
                    confirmButton: "btn btn-success",
                    cancelButton: "btn btn-danger"
                },
                buttonsStyling: true
            });
            swalWithBootstrapButtons.fire({
                title: "¿Quieres restablecer este concepto?",
                text: 'Al restablecer el concepto se marcara como "Ligada". Esta operacion no se puede revertir',
                icon: "info",
                showCancelButton: true,
                confirmButtonText: "SI",
                cancelButtonText: "NO",
                reverseButtons: false
            }).then((result) => {
                if (result.isConfirmed) {
                    this.restart(id_Hoja);
                    swalWithBootstrapButtons.fire({
                        title: "Restablecida",
                        text: "La presion a sido Restablecida con exito.",
                        icon: "success"
                    }).then(() => {
                        this.listarObras();
                    });
                }
            });
        },
        restart: function (id_Hoja) {
            axios.post(url, { accion: 5, idHoja: id_Hoja }).then(response => {
                this.listarObras();
            });
        },
        showEdit: function (index, index2) {
            this.presiones[index2].Presion_Obra[index].atrClass = "inline-block fs-6";
            this.presiones[index2].Presion_Obra[index].strStyle = "max-width: 150px;";
            this.presiones[index2].Presion_Obra[index].edit_Auto = true;
        },
        saveEdit: function (adeudo, observaciones, idHoja) {
            axios.post(url, { accion: 6, idHoja: idHoja, parcial: adeudo, coments: observaciones }).then(response => {
                this.listarObras();
            });
        },
        irMenuCatalago: function () {
            window.location.href = url2 + "/menu_catalago.php";
        },
        changedCheck: function (estatus, adeudo, observaciones, idHoja) {
            if (estatus == "LIGADA") {
                axios.post(url, { accion: 4, idHoja: idHoja, parcial: adeudo, coments: observaciones, autorizado: true }).then(response => {
                    console.log(response.data);
                });
            }
            else {
                axios.post(url, { accion: 4, idHoja: idHoja, parcial: adeudo, coments: observaciones, autorizado: false }).then(response => {
                    console.log(response.data);
                });
            }
        },
        guardarCambios: function (presion) {
            const Toast = Swal.mixin({
                toast: true,
                position: "bottom-start",
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.onmouseenter = Swal.stopTimer;
                    toast.onmouseleave = Swal.resumeTimer;
                }
            });

            if (typeof presion !== 'object' || presion === null) {
                console.error('El parámetro "presion" no es un objeto válido:', presion);
                return;
            }

            console.log(presion);
            axios.post(url, { accion: 7, presion: presion }).then(response => {
                if (response.data["status"] == "success") {
                    (presion || []).forEach((row) => {
                        row._baseAdeudo = row.adeudo;
                        row._baseObservaciones = row.Observaciones || "";
                        row._baseFormula = row.formula || "";
                        row._dirty = false;
                    });
                    var pending = this.presiones.some((obra) => (obra.Presion_Obra || []).some((row) => row._dirty));
                    if (!pending) this.limpiarBorradorLocal();
                    Toast.fire({
                        icon: "success",
                        title: response.data["mensaje"]
                    });
                }
            }
            ).catch(error => {
                console.error(error);
                Toast.fire({
                    icon: "error",
                    title: "Fallo al Eliminar elemento"
                });
            });
        },
        openWinPorcentaje: async function (indicePresion, indiceHoja) {
            console.log(indicePresion + " " + indiceHoja);
            const { value: formValues } = await Swal.fire({
                title: "AGREGAR/DESCONTAR PORCENTAJE",
                html: `
                        <div style="overflow: hidden; text-align: left;">
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="accion" value="Desc" id="descrementBox" checked>
                                        <label class="form-check-label" for="incrementoBox">
                                           Aplicar Descuento
                                        </label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="accion" value="Inc" id="incrementoBox">
                                        <label class="form-check-label" for="descrementoBox">
                                            Aplicar Incremento
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label">Porcentaje a Aplicar:</label>
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" id="porcentaje">
                                        <span class="input-group-text fw-bold" id="basic-addon2"> % </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        `, // Tu contenido aquí
                focusConfirm: false,
                width: '30%',
                showCancelButton: true,
                confirmButtonText: 'Aplicar',
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#dc3545',
                preConfirm: () => {
                    return {
                        porcentaje: document.getElementById('porcentaje')?.value || '',
                        accion: document.querySelector('input[name="accion"]:checked').value
                    };
                }
            });
            if (formValues) {
               this.aplicarPorcentaje(indicePresion, indiceHoja, formValues);
            }
        },
        convertirAdecimalEntero: function (numeroEntero) {
            // Verificamos que sea un número
            if (isNaN(numeroEntero)) return 0;

            // Convertimos el entero a decimal dividiendo entre 100
            return parseFloat(numeroEntero) / 100;
        },
        toNumber: function (value) {
            if (value === null || value === undefined) return 0;
            if (typeof value === 'number') return isNaN(value) ? 0 : value;
            var sanitized = String(value).replace(/\$/g, '').replace(/,/g, '').trim();
            var parsed = parseFloat(sanitized);
            return isNaN(parsed) ? 0 : parsed;
        },
        parseImportNumber: function (value, fallback) {
            if (value === null || value === undefined || value === '') {
                return this.toNumber(fallback);
            }
            if (typeof value === 'number') {
                return isNaN(value) ? this.toNumber(fallback) : value;
            }

            var text = String(value).trim();
            if (!text) return this.toNumber(fallback);

            // Si la celda trae formula sin valor calculado por Excel, conserva el valor actual.
            if (text[0] === '=') return this.toNumber(fallback);

            var normalized = text
                .replace(/\$/g, '')
                .replace(/\s+/g, '')
                .replace(/\(([^)]+)\)/g, '-$1')
                .replace(/'/g, '');

            if (/^-?\d{1,3}(\.\d{3})+,\d+$/.test(normalized)) {
                normalized = normalized.replace(/\./g, '').replace(',', '.');
            } else {
                normalized = normalized.replace(/,/g, '');
            }

            var parsed = parseFloat(normalized);
            return isNaN(parsed) ? this.toNumber(fallback) : parsed;
        },
        normalizeImportToken: function (value) {
            return String(value == null ? '' : value)
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/\s+/g, ' ')
                .trim()
                .toUpperCase();
        },
        normalizeImportId: function (value) {
            var text = this.normalizeImportToken(value).replace(/\s+/g, '');
            if (!text) return '';
            if (text[0] === '=') return '';

            var numericCandidate = text.replace(/,/g, '');
            var asNumber = Number(numericCandidate);
            if (isFinite(asNumber) && !isNaN(asNumber)) {
                if (Math.abs(asNumber) >= 1) return String(Math.round(asNumber));
                return String(asNumber);
            }

            if (/^\d+\.0+$/.test(text)) {
                return text.split('.')[0];
            }
            return text;
        },
        firstImportField: function (item, keys) {
            for (var i = 0; i < keys.length; i++) {
                var key = keys[i];
                if (Object.prototype.hasOwnProperty.call(item, key) && item[key] != null && String(item[key]).trim() !== '') {
                    return item[key];
                }
            }
            return '';
        },
        parseImportSheetRows: function (sheet) {
            var matrix = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });
            if (!Array.isArray(matrix) || !matrix.length) return [];

            var headerRowIndex = -1;
            var headers = [];
            for (var r = 0; r < matrix.length; r++) {
                var row = matrix[r] || [];
                var normalized = row.map((cell) => this.normalizeImportToken(cell));
                var hasId = normalized.indexOf('ID_HOJA') !== -1 || normalized.indexOf('ID HOJA') !== -1;
                var hasPago = normalized.indexOf('PAGO_AUTORIZADO') !== -1 || normalized.indexOf('PAGO AUTORIZADO') !== -1;
                var hasObs = normalized.indexOf('OBSERVACIONES') !== -1;
                if (hasId && hasPago && hasObs) {
                    headerRowIndex = r;
                    headers = row.map((cell) => String(cell == null ? '' : cell).trim());
                    break;
                }
            }

            if (headerRowIndex < 0) {
                headers = (matrix[0] || []).map((cell) => String(cell == null ? '' : cell).trim());
                headerRowIndex = 0;
            }

            var rows = [];
            for (var i = headerRowIndex + 1; i < matrix.length; i++) {
                var values = matrix[i] || [];
                var item = {};
                var hasValue = false;
                for (var c = 0; c < headers.length; c++) {
                    var key = headers[c];
                    if (!key) continue;
                    var val = values[c] == null ? '' : values[c];
                    item[key] = val;
                    if (String(val).trim() !== '') hasValue = true;
                }
                if (hasValue) rows.push(item);
            }

            return rows;
        },
        filaImportable: function (item) {
            if (!item || typeof item !== 'object') return false;

            var id = this.normalizeImportId(this.firstImportField(item, ['ID_HOJA', 'IdHoja', 'id_hoja', 'ID HOJA', 'HojaId']));
            var clave = this.normalizeImportToken(this.firstImportField(item, ['CLAVE', 'Clave']));
            var req = this.normalizeImportToken(this.firstImportField(item, ['NUMERO_REQ', 'NUMERO_REQUISICION', 'NumeroReq', 'NUM_REQ', 'NoReq', 'N_REQ']));

            if (id || clave || req) return true;

            var first = this.normalizeImportToken(this.firstImportField(item, ['ID_HOJA', 'ID HOJA', 'CLAVE', 'NUMERO_REQ', 'NUM_REQ']) || '');
            if (first.indexOf('TOTAL') !== -1) return false;
            return false;
        },
        excelJsDisponible: function () {
            return !!(window.ExcelJS && window.ExcelJS.Workbook);
        },
        asegurarExcelJs: function () {
            if (this.excelJsDisponible()) return true;
            Swal.fire('Excel no disponible', 'No fue posible cargar ExcelJS para exportar .xlsx con formato avanzado.', 'warning');
            return false;
        },
        descargarBufferExcel: function (buffer, filename) {
            var blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
            URL.revokeObjectURL(link.href);
        },
        leerImagenComoDataUrl: async function (url) {
            try {
                var resp = await fetch(url, { cache: 'no-store' });
                if (!resp.ok) return null;
                var blob = await resp.blob();
                return await new Promise(function (resolve) {
                    var fr = new FileReader();
                    fr.onload = function () { resolve(fr.result); };
                    fr.onerror = function () { resolve(null); };
                    fr.readAsDataURL(blob);
                });
            } catch (e) {
                return null;
            }
        },
        extensionDesdeDataUrl: function (dataUrl, fallback) {
            var match = /^data:image\/(png|jpeg|jpg);/i.exec(String(dataUrl || ''));
            if (!match) return fallback || 'png';
            return match[1].toLowerCase() === 'jpg' ? 'jpeg' : match[1].toLowerCase();
        },
        nombreHojaExcelSeguro: function (name, fallback) {
            var safe = String(name || fallback || 'Hoja')
                .replace(/[\\\/?*\[\]:]/g, ' ')
                .trim();
            if (!safe) safe = fallback || 'Hoja';
            if (safe.length > 31) safe = safe.slice(0, 31);
            return safe;
        },
        totalPropuestoObra: function (obra) {
            return (obra.Presion_Obra || []).reduce((sum, item) => sum + this.toNumber(item.total), 0);
        },
        totalAutorizadoObra: function (obra) {
            return (obra.Presion_Obra || []).reduce((sum, item) => sum + this.toNumber(item.adeudo), 0);
        },
        filaVisible: function (obra, row) {
            if (obra.soloEditados && !row._dirty) return false;
            var filtro = String(obra.filtro || '').trim().toLowerCase();
            if (!filtro) return true;

            var texto = [
                row.clave,
                row.NumReq,
                row.proveedor,
                row.concepto,
                row.formaPago,
                row.Observaciones
            ].join(' ').toLowerCase();
            return texto.indexOf(filtro) !== -1;
        },
        conteoFilasEditadas: function (obra) {
            return (obra.Presion_Obra || []).filter(function (row) { return !!row._dirty; }).length;
        },
        marcarFilaEditada: function (row) {
            row._dirty = (
                this.toNumber(row.adeudo) !== this.toNumber(row._baseAdeudo)
                || String(row.Observaciones || '') !== String(row._baseObservaciones || '')
                || String(row.formula || '') !== String(row._baseFormula || '')
            );
        },
        onRowInput: function (indicePresion, indiceHoja, row) {
            this.marcarFilaEditada(row);
            this.programarGuardadoBorrador();
        },
        programarGuardadoBorrador: function () {
            if (this.draftTimer) clearTimeout(this.draftTimer);
            this.draftTimer = setTimeout(() => {
                this.guardarBorradorLocal();
            }, 600);
        },
        getRowKey: function (row) {
            return String(row.idHoja || row.id_Hoja || row.hojaRequisicion_id || row.NumReq || row.clave || '');
        },
        getRowHojaId: function (row) {
            return String(row.id_hoja || row.idHoja || row.id_Hoja || row.hojaRequisicion_id || '').trim();
        },
        getRowsFlat: function () {
            var all = [];
            (this.presiones || []).forEach((obra) => {
                (obra.Presion_Obra || []).forEach((row) => {
                    all.push({
                        obra: obra.Nombre_Obra || '',
                        row: row
                    });
                });
            });
            return all;
        },
        guardarBorradorLocal: function () {
            try {
                var payload = this.presiones.map((obra) => {
                    return {
                        nombre: obra.Nombre_Obra,
                        rows: (obra.Presion_Obra || []).map((row) => ({
                            key: this.getRowKey(row),
                            adeudo: row.adeudo,
                            Observaciones: row.Observaciones || '',
                            formula: row.formula || ''
                        }))
                    };
                });
                localStorage.setItem(DIRECTOR_DRAFT_KEY, JSON.stringify(payload));
            } catch (e) {
                console.warn('No fue posible guardar borrador local:', e);
            }
        },
        aplicarBorradorLocal: function () {
            try {
                var raw = localStorage.getItem(DIRECTOR_DRAFT_KEY);
                if (!raw) return;
                var draft = JSON.parse(raw);
                if (!Array.isArray(draft)) return;

                var byObra = {};
                draft.forEach(function (o) { byObra[o.nombre] = o.rows || []; });

                this.presiones.forEach((obra) => {
                    var rowsDraft = byObra[obra.Nombre_Obra] || [];
                    var mapDraft = {};
                    rowsDraft.forEach((d) => { mapDraft[String(d.key)] = d; });

                    (obra.Presion_Obra || []).forEach((row) => {
                        var key = this.getRowKey(row);
                        var d = mapDraft[key];
                        if (!d) return;
                        row.adeudo = d.adeudo;
                        row.Observaciones = d.Observaciones;
                        row.formula = d.formula;
                        this.marcarFilaEditada(row);
                    });
                });
            } catch (e) {
                console.warn('No fue posible cargar borrador local:', e);
            }
        },
        limpiarBorradorLocal: function () {
            try {
                localStorage.removeItem(DIRECTOR_DRAFT_KEY);
            } catch (e) {
                console.warn('No fue posible limpiar borrador local:', e);
            }
        },
        autorizarTodoObra: function (indicePresion) {
            var obra = this.presiones[indicePresion];
            (obra.Presion_Obra || []).forEach((row) => {
                row.adeudo = this.toNumber(row.total);
                row.Observaciones = 'Autorizado por accion masiva.';
                this.marcarFilaEditada(row);
            });
            this.programarGuardadoBorrador();
        },
        aplicarDescuentoObra: function (indicePresion) {
            var obra = this.presiones[indicePresion];
            (obra.Presion_Obra || []).forEach((row) => {
                row.adeudo = Math.round(this.toNumber(row.total) * 0.9 * 100) / 100;
                row.Observaciones = 'Ajuste masivo -10% aplicado.';
                this.marcarFilaEditada(row);
            });
            this.programarGuardadoBorrador();
        },
        rechazarTodoObra: function (indicePresion) {
            var obra = this.presiones[indicePresion];
            (obra.Presion_Obra || []).forEach((row) => {
                row.adeudo = 0;
                row.Observaciones = 'Rechazado por accion masiva.';
                this.marcarFilaEditada(row);
            });
            this.programarGuardadoBorrador();
        },
        restaurarCambiosObra: function (indicePresion) {
            var obra = this.presiones[indicePresion];
            (obra.Presion_Obra || []).forEach((row) => {
                row.adeudo = row._baseAdeudo;
                row.Observaciones = row._baseObservaciones;
                row.formula = row._baseFormula;
                row._dirty = false;
            });
            this.programarGuardadoBorrador();
        },
        exportarCsvObra: function (indicePresion) {
            var obra = this.presiones[indicePresion];
            var rows = obra.Presion_Obra || [];
            var headers = ['IdHoja', 'Clave', 'NumeroReq', 'Proveedor', 'Concepto', 'AdeudoPropuesto', 'PagoAutorizado', 'Formula', 'Observaciones', 'FormaPago'];
            var csv = [headers.join(',')];

            rows.forEach((row) => {
                var line = [
                    this.getRowHojaId(row),
                    row.clave,
                    row.NumReq,
                    row.proveedor,
                    row.concepto,
                    this.toNumber(row.total),
                    this.toNumber(row.adeudo),
                    row.formula || '',
                    row.Observaciones || '',
                    row.formaPago || ''
                ].map(function (v) {
                    var safe = String(v).replace(/"/g, '""');
                    return '"' + safe + '"';
                }).join(',');
                csv.push(line);
            });

            var blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'presiones_' + (obra.Nombre_Obra || 'obra').replace(/\s+/g, '_') + '.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        },
        exportarExcelMasivo: async function () {
            if (!this.asegurarExcelJs()) return;

            var rowsFlat = this.getRowsFlat();
            if (!rowsFlat.length) {
                Swal.fire('Sin datos', 'No hay registros para exportar.', 'info');
                return;
            }

            var porObra = {};
            rowsFlat.forEach((entry) => {
                var obraNombre = String(entry.obra || 'SIN OBRA').trim() || 'SIN OBRA';
                if (!porObra[obraNombre]) porObra[obraNombre] = [];
                porObra[obraNombre].push(entry.row);
            });
            var obrasOrdenadas = Object.keys(porObra).sort(function (a, b) { return a.localeCompare(b); });

            var now = new Date();
            var yyyy = now.getFullYear();
            var mm = String(now.getMonth() + 1).padStart(2, '0');
            var dd = String(now.getDate()).padStart(2, '0');
            var fechaISO = yyyy + '-' + mm + '-' + dd;
            var fechaCompacta = dd + mm + String(yyyy).slice(-2);

            var semana = 'GLOBAL';
            var firstRow = rowsFlat[0] && rowsFlat[0].row ? rowsFlat[0].row : {};
            var weekRaw = firstRow.presiones_semana || firstRow.semana || firstRow.Semana || firstRow.week || '';
            if (String(weekRaw).trim()) semana = 'SEMANA_' + String(weekRaw).trim().replace(/\s+/g, '_');

            var workbook = new ExcelJS.Workbook();
            workbook.creator = 'The Fuentes Corporation';
            workbook.created = new Date();
            workbook.modified = new Date();

            var logoData = await this.leerImagenComoDataUrl('../images/LogoFuentes.png');
            var logoId = null;
            if (logoData) {
                logoId = workbook.addImage({
                    base64: logoData,
                    extension: this.extensionDesdeDataUrl(logoData, 'png')
                });
            }

            var headers = ['ID_HOJA', 'ID_PRESION', 'CLAVE', 'NUMERO_REQ', 'PROVEEDOR', 'CONCEPTO', 'ADEUDO_PROPUESTO', 'PAGO_AUTORIZADO', 'OBSERVACIONES', 'FORMA_PAGO'];

            obrasOrdenadas.forEach((obraNombre) => {
                var rows = porObra[obraNombre] || [];
                var ws = workbook.addWorksheet(this.nombreHojaExcelSeguro(obraNombre, 'OBRA'), {
                    views: [{ state: 'frozen', ySplit: 5 }],
                    pageSetup: { orientation: 'landscape', fitToPage: true, fitToWidth: 1, fitToHeight: 0 }
                });

                ws.columns = [
                    { header: 'ID_HOJA', width: 12 },
                    { header: 'ID_PRESION', width: 12 },
                    { header: 'CLAVE', width: 14 },
                    { header: 'NUMERO_REQ', width: 16 },
                    { header: 'PROVEEDOR', width: 24 },
                    { header: 'CONCEPTO', width: 42 },
                    { header: 'ADEUDO_PROPUESTO', width: 18 },
                    { header: 'PAGO_AUTORIZADO', width: 18 },
                    { header: 'OBSERVACIONES', width: 38 },
                    { header: 'FORMA_PAGO', width: 16 }
                ];

                ws.mergeCells('A1:J1');
                ws.getCell('A1').value = 'THE FUENTES CORPORATION';
                ws.getCell('A1').font = { size: 16, bold: true, color: { argb: 'FF0F172A' } };

                ws.mergeCells('A2:J2');
                ws.getCell('A2').value = 'AUTORIZACION MASIVA - ' + obraNombre;
                ws.getCell('A2').font = { size: 12, bold: true, color: { argb: 'FF1E293B' } };

                ws.getCell('A3').value = 'Fecha de generacion: ' + fechaISO;
                ws.getCell('D3').value = 'Usuario: ' + (this.NameUser || 'N/D');
                ws.mergeCells('G3:J3');
                ws.getCell('G3').value = 'EDITABLE: PAGO_AUTORIZADO y OBSERVACIONES';
                ws.getCell('G3').font = { bold: true, color: { argb: 'FF1D4ED8' } };

                if (logoId) {
                    ws.addImage(logoId, {
                        tl: { col: 8.2, row: 0.1 },
                        ext: { width: 120, height: 42 }
                    });
                }

                var headerRow = 5;
                ws.getRow(headerRow).values = headers;
                ws.getRow(headerRow).font = { bold: true, color: { argb: 'FF1E293B' } };
                ws.getRow(headerRow).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE2E8F0' } };
                ws.getRow(headerRow).alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };

                var rowCursor = 6;
                var subtotalPropuesto = 0;
                var subtotalAutorizado = 0;

                rows.forEach((row) => {
                    var adeudoPropuesto = this.toNumber(row.total);
                    var pagoAutorizado = this.toNumber(row.adeudo);
                    subtotalPropuesto += adeudoPropuesto;
                    subtotalAutorizado += pagoAutorizado;

                    var rr = ws.getRow(rowCursor);
                    rr.values = [
                        this.getRowHojaId(row),
                        row.id_presion || '',
                        row.clave || '',
                        row.NumReq || '',
                        row.proveedor || '',
                        row.concepto || '',
                        adeudoPropuesto,
                        pagoAutorizado,
                        row.Observaciones || '',
                        row.formaPago || ''
                    ];
                    rr.getCell(7).numFmt = '$ #,##0.00';
                    rr.getCell(8).numFmt = '$ #,##0.00';
                    rr.eachCell(function (cell) {
                        cell.border = {
                            top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                            left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                            bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                            right: { style: 'thin', color: { argb: 'FFE2E8F0' } }
                        };
                        cell.alignment = { vertical: 'middle', wrapText: true };
                    });
                    rowCursor += 1;
                });

                ws.mergeCells('A' + rowCursor + ':F' + rowCursor);
                ws.getCell('A' + rowCursor).value = 'TOTAL OBRA';
                ws.getCell('A' + rowCursor).font = { bold: true, color: { argb: 'FF0F172A' } };
                ws.getCell('A' + rowCursor).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFFF7ED' } };
                ws.getCell('G' + rowCursor).value = subtotalPropuesto;
                ws.getCell('H' + rowCursor).value = subtotalAutorizado;
                ws.getCell('G' + rowCursor).numFmt = '$ #,##0.00';
                ws.getCell('H' + rowCursor).numFmt = '$ #,##0.00';
                ws.getCell('G' + rowCursor).font = { bold: true };
                ws.getCell('H' + rowCursor).font = { bold: true };
            });

            var fileName = ('PRESION_DE_GASTOS_' + semana + '_DEL_' + fechaCompacta + '_AUTORIZADO.xlsx')
                .replace(/[^A-Za-z0-9_\-.]/g, '_');
            var buffer = await workbook.xlsx.writeBuffer();
            this.descargarBufferExcel(buffer, fileName);
        },
        triggerImportExcelGlobal: function () {
            var input = document.getElementById(DIRECTOR_IMPORT_ALL_ID);
            if (input) input.click();
        },
        importarExcelMasivo: function (event) {
            if (!window.XLSX || !window.XLSX.utils) {
                Swal.fire('Excel no disponible', 'No fue posible cargar la libreria para importar .xlsx.', 'warning');
                return;
            }

            var file = event.target.files && event.target.files[0];
            if (!file) return;

            var reader = new FileReader();
            reader.onload = (e) => {
                try {
                    var imported = [];
                    var fileName = (file.name || '').toLowerCase();

                    if (fileName.endsWith('.csv')) {
                        var text = new TextDecoder('utf-8').decode(e.target.result);
                        var lines = text.split(/\r?\n/).filter(function (line) { return line.trim() !== ''; });
                        if (lines.length < 2) throw new Error('Archivo CSV sin datos.');
                        var headers = lines[0].split(',').map(function (h) { return h.trim().replace(/^"|"$/g, ''); });
                        for (var i = 1; i < lines.length; i++) {
                            var values = lines[i].split(',');
                            var rowObj = {};
                            headers.forEach(function (h, idx) {
                                rowObj[h] = (values[idx] || '').replace(/^"|"$/g, '');
                            });
                            imported.push(rowObj);
                        }
                    } else {
                        var wb = XLSX.read(e.target.result, { type: 'array' });
                        if (!wb.SheetNames || !wb.SheetNames.length) {
                            throw new Error('El archivo no contiene hojas de calculo.');
                        }
                        imported = [];
                        wb.SheetNames.forEach((sheetName) => {
                            var rowsSheet = this.parseImportSheetRows(wb.Sheets[sheetName]);
                            rowsSheet.forEach((rowObj) => {
                                rowObj.__sheet_name = sheetName;
                            });
                            imported = imported.concat(rowsSheet);
                        });
                    }

                    var byHoja = {};
                    var byObraClave = {};
                    var byObraReq = {};

                    this.getRowsFlat().forEach((entry) => {
                        var row = entry.row;
                        var hojaId = this.normalizeImportId(this.getRowHojaId(row));
                        var obra = this.normalizeImportToken(entry.obra || '');
                        var clave = this.normalizeImportToken(row.clave || '');
                        var req = this.normalizeImportToken(row.NumReq || '');
                        if (hojaId) byHoja[hojaId] = row;
                        if (obra && clave) byObraClave[obra + '|' + clave] = row;
                        if (obra && req) byObraReq[obra + '|' + req] = row;
                    });

                    var applied = 0;
                    var skipped = 0;
                    var unchanged = 0;
                    imported.forEach((item) => {
                        if (!this.filaImportable(item)) {
                            return;
                        }

                        var hojaId = this.normalizeImportId(this.firstImportField(item, ['ID_HOJA', 'IdHoja', 'id_hoja', 'ID HOJA', 'HojaId']));
                        var obra = this.normalizeImportToken(
                            this.firstImportField(item, ['OBRA', 'Obra', 'NOMBRE_OBRA', 'NombreObra'])
                            || item.__sheet_name
                        );
                        var clave = this.normalizeImportToken(this.firstImportField(item, ['CLAVE', 'Clave']));
                        var req = this.normalizeImportToken(this.firstImportField(item, ['NUMERO_REQ', 'NUMERO_REQUISICION', 'NumeroReq', 'NUM_REQ', 'NoReq', 'N_REQ']));

                        var target = null;
                        if (hojaId && byHoja[hojaId]) target = byHoja[hojaId];
                        if (!target && obra && clave && byObraClave[obra + '|' + clave]) target = byObraClave[obra + '|' + clave];
                        if (!target && obra && req && byObraReq[obra + '|' + req]) target = byObraReq[obra + '|' + req];

                        if (!target) {
                            skipped++;
                            return;
                        }

                        var nextAdeudo = this.parseImportNumber(
                            this.firstImportField(item, ['PAGO_AUTORIZADO', 'ADEUDO_AUTORIZADO', 'NETO_A_PAGAR', 'PAGO AUTORIZADO']),
                            target.adeudo
                        );
                        var nextObservaciones = String(this.firstImportField(item, ['OBSERVACIONES', 'Observaciones']) || target.Observaciones || '');
                        var changed = (this.toNumber(target.adeudo) !== this.toNumber(nextAdeudo))
                            || (String(target.Observaciones || '') !== nextObservaciones);
                        if (!changed) {
                            unchanged++;
                            return;
                        }

                        // Solo se permite actualizar monto autorizado y observaciones.
                        target.adeudo = nextAdeudo;
                        target.Observaciones = nextObservaciones;
                        this.marcarFilaEditada(target);
                        applied++;
                    });

                    this.programarGuardadoBorrador();
                    var detail = [
                        'Filas actualizadas: ' + applied,
                        'No encontradas: ' + skipped,
                        'Sin cambios: ' + unchanged
                    ].join('\n');
                    if (applied > 0) {
                        Swal.fire('Importacion completada', detail, (skipped || unchanged) ? 'warning' : 'success');
                    } else {
                        Swal.fire('Sin cambios aplicados', detail + '\n\nValida columnas ID_HOJA, OBRA, CLAVE o NUMERO_REQ del archivo.', 'warning');
                    }
                } catch (error) {
                    Swal.fire('Error de importacion', error.message || 'No fue posible leer el archivo.', 'error');
                } finally {
                    event.target.value = '';
                }
            };

            reader.readAsArrayBuffer(file);
        },
        guardarCambiosMasivos: function () {
            var dirty = [];
            (this.presiones || []).forEach((obra) => {
                (obra.Presion_Obra || []).forEach((row) => {
                    if (row._dirty) dirty.push(row);
                });
            });
            if (!dirty.length) {
                Swal.fire('Sin cambios', 'No hay filas editadas para guardar.', 'info');
                return;
            }
            this.guardarCambios(dirty);
        },
        exportarExcelObra: async function (indicePresion) {
            if (!this.asegurarExcelJs()) return;

            var obra = this.presiones[indicePresion];
            var rows = obra.Presion_Obra || [];
            if (!rows.length) {
                Swal.fire('Sin datos', 'No hay registros de la obra para exportar.', 'info');
                return;
            }

            var now = new Date();
            var yyyy = now.getFullYear();
            var mm = String(now.getMonth() + 1).padStart(2, '0');
            var dd = String(now.getDate()).padStart(2, '0');
            var fechaISO = yyyy + '-' + mm + '-' + dd;
            var fechaCompacta = dd + mm + String(yyyy).slice(-2);

            var workbook = new ExcelJS.Workbook();
            workbook.creator = 'The Fuentes Corporation';
            workbook.created = new Date();
            workbook.modified = new Date();
            var ws = workbook.addWorksheet(this.nombreHojaExcelSeguro(obra.Nombre_Obra, 'Autorizacion'), {
                views: [{ state: 'frozen', ySplit: 6 }],
                pageSetup: { orientation: 'landscape', fitToPage: true, fitToWidth: 1, fitToHeight: 0 }
            });
            ws.columns = [
                { header: 'ID_HOJA', width: 12 },
                { header: 'CLAVE', width: 14 },
                { header: 'NUM_REQ', width: 16 },
                { header: 'PROVEEDOR', width: 24 },
                { header: 'CONCEPTO', width: 42 },
                { header: 'ADEUDO_PROPUESTO', width: 18 },
                { header: 'PAGO_AUTORIZADO', width: 18 },
                { header: 'OBSERVACIONES', width: 38 },
                { header: 'FORMA_PAGO', width: 16 }
            ];

            ws.mergeCells('A1:I1');
            ws.getCell('A1').value = 'THE FUENTES CORPORATION';
            ws.getCell('A1').font = { size: 18, bold: true, color: { argb: 'FF0F172A' } };
            ws.getCell('A1').alignment = { horizontal: 'left', vertical: 'middle' };

            ws.mergeCells('A2:I2');
            ws.getCell('A2').value = 'AUTORIZACION DE PAGO - ' + String(obra.Nombre_Obra || 'OBRA');
            ws.getCell('A2').font = { size: 13, bold: true, color: { argb: 'FF1E293B' } };

            ws.getCell('A3').value = 'Fecha de generacion: ' + fechaISO;
            ws.getCell('C3').value = 'Usuario: ' + (this.NameUser || 'N/D');
            ws.mergeCells('E3:I3');
            ws.getCell('E3').value = 'EDITABLE: solo PAGO_AUTORIZADO y OBSERVACIONES';
            ws.getCell('E3').font = { bold: true, color: { argb: 'FF1D4ED8' } };

            ws.mergeCells('A4:I4');
            ws.getCell('A4').value = 'THE FUENTES CORPORATION';
            ws.getCell('A4').font = { size: 28, bold: true, color: { argb: '1A0F172A' } };
            ws.getCell('A4').alignment = { horizontal: 'center', vertical: 'middle' };

            var logoData = await this.leerImagenComoDataUrl('../images/LogoFuentes.png');
            if (logoData) {
                var logoId = workbook.addImage({ base64: logoData, extension: this.extensionDesdeDataUrl(logoData, 'png') });
                ws.addImage(logoId, { tl: { col: 7.1, row: 0.15 }, ext: { width: 130, height: 46 } });
            }
            var watermarkData = await this.leerImagenComoDataUrl('../images/watermark.jpg');
            if (watermarkData) {
                var wmId = workbook.addImage({ base64: watermarkData, extension: this.extensionDesdeDataUrl(watermarkData, 'jpeg') });
                ws.addImage(wmId, { tl: { col: 1.5, row: 4.5 }, ext: { width: 560, height: 300 } });
            }

            ws.getRow(1).height = 24;
            ws.getRow(2).height = 20;
            ws.getRow(4).height = 36;

            var headerRowIndex = 6;
            ws.getRow(headerRowIndex).values = ['ID_HOJA', 'CLAVE', 'NUM_REQ', 'PROVEEDOR', 'CONCEPTO', 'ADEUDO_PROPUESTO', 'PAGO_AUTORIZADO', 'OBSERVACIONES', 'FORMA_PAGO'];
            ws.getRow(headerRowIndex).font = { bold: true, color: { argb: 'FF1E293B' } };
            ws.getRow(headerRowIndex).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE2E8F0' } };
            ws.getRow(headerRowIndex).alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
            ws.getRow(headerRowIndex).border = {
                bottom: { style: 'thin', color: { argb: 'FFCBD5E1' } }
            };

            var cursor = 7;
            var subtotalPropuesto = 0;
            var subtotalAutorizado = 0;
            rows.forEach((row) => {
                var adeudoPropuesto = this.toNumber(row.total);
                var pagoAutorizado = this.toNumber(row.adeudo);
                subtotalPropuesto += adeudoPropuesto;
                subtotalAutorizado += pagoAutorizado;

                var rr = ws.getRow(cursor);
                rr.values = [
                    this.getRowHojaId(row),
                    row.clave || '',
                    row.NumReq || '',
                    row.proveedor || '',
                    row.concepto || '',
                    adeudoPropuesto,
                    pagoAutorizado,
                    row.Observaciones || '',
                    row.formaPago || ''
                ];
                rr.getCell(6).numFmt = '$ #,##0.00';
                rr.getCell(7).numFmt = '$ #,##0.00';
                rr.eachCell(function (cell) {
                    cell.border = {
                        top: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                        left: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                        bottom: { style: 'thin', color: { argb: 'FFE2E8F0' } },
                        right: { style: 'thin', color: { argb: 'FFE2E8F0' } }
                    };
                    cell.alignment = { vertical: 'middle', wrapText: true };
                });
                cursor += 1;
            });

            ws.mergeCells('A' + cursor + ':E' + cursor);
            ws.getCell('A' + cursor).value = 'TOTAL OBRA';
            ws.getCell('A' + cursor).font = { bold: true, color: { argb: 'FF0F172A' } };
            ws.getCell('A' + cursor).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFFF7ED' } };
            ws.getCell('F' + cursor).value = subtotalPropuesto;
            ws.getCell('G' + cursor).value = subtotalAutorizado;
            ws.getCell('F' + cursor).numFmt = '$ #,##0.00';
            ws.getCell('G' + cursor).numFmt = '$ #,##0.00';
            ws.getCell('F' + cursor).font = { bold: true };
            ws.getCell('G' + cursor).font = { bold: true };

            for (var c = 1; c <= 9; c++) {
                var cell = ws.getRow(cursor).getCell(c);
                cell.border = {
                    top: { style: 'thin', color: { argb: 'FFF59E0B' } },
                    left: { style: 'thin', color: { argb: 'FFF59E0B' } },
                    bottom: { style: 'thin', color: { argb: 'FFF59E0B' } },
                    right: { style: 'thin', color: { argb: 'FFF59E0B' } }
                };
            }

            var wsInfo = workbook.addWorksheet('Instrucciones');
            wsInfo.columns = [{ width: 100 }];
            wsInfo.getCell('A1').value = 'PLANTILLA DE EDICION';
            wsInfo.getCell('A1').font = { bold: true, size: 14 };
            wsInfo.getCell('A3').value = '1) Este archivo puede importarse de regreso al sistema.';
            wsInfo.getCell('A4').value = '2) Solo se editaran PAGO_AUTORIZADO y OBSERVACIONES al importar.';
            wsInfo.getCell('A5').value = '3) No cambies ID_HOJA, CLAVE ni NUM_REQ.';
            wsInfo.getCell('A6').value = '4) El archivo incluye logo y marca de agua corporativa.';
            wsInfo.getCell('A7').value = '5) Documento generado por: ' + (this.NameUser || 'N/D') + ' el ' + fechaISO;

            var safeName = String(obra.Nombre_Obra || 'obra').replace(/\s+/g, '_').replace(/[^A-Za-z0-9_\-.]/g, '_');
            var buffer = await workbook.xlsx.writeBuffer();
            this.descargarBufferExcel(buffer, 'AUTORIZACION_OBRA_' + safeName + '_DEL_' + fechaCompacta + '.xlsx');
        },
        triggerImportExcel: function (indicePresion) {
            var input = document.getElementById('excelImport' + indicePresion);
            if (input) input.click();
        },
        importarExcelObra: function (event, indicePresion) {
            if (!window.XLSX || !window.XLSX.utils) {
                Swal.fire('Excel no disponible', 'No fue posible cargar la libreria para importar .xlsx.', 'warning');
                return;
            }

            var file = event.target.files && event.target.files[0];
            if (!file) return;

            var obra = this.presiones[indicePresion];
            var rows = obra.Presion_Obra || [];

            var reader = new FileReader();
            reader.onload = (e) => {
                try {
                    var imported = [];
                    var fileName = (file.name || '').toLowerCase();

                    if (fileName.endsWith('.csv')) {
                        var text = new TextDecoder('utf-8').decode(e.target.result);
                        var lines = text.split(/\r?\n/).filter(function (line) { return line.trim() !== ''; });
                        if (lines.length < 2) throw new Error('Archivo CSV sin datos.');
                        var headers = lines[0].split(',').map(function (h) { return h.trim().replace(/^"|"$/g, ''); });
                        for (var i = 1; i < lines.length; i++) {
                            var values = lines[i].split(',');
                            var rowObj = {};
                            headers.forEach(function (h, idx) {
                                rowObj[h] = (values[idx] || '').replace(/^"|"$/g, '');
                            });
                            imported.push(rowObj);
                        }
                    } else {
                        var wb = XLSX.read(e.target.result, { type: 'array' });
                        var sheetName = wb.SheetNames[0];
                        if (!sheetName) {
                            throw new Error('El archivo no contiene hojas de calculo.');
                        }
                        var ws = wb.Sheets[sheetName];
                        imported = this.parseImportSheetRows(ws);
                    }

                    var byKey = {};
                    rows.forEach((row) => {
                        var keyHoja = this.normalizeImportId(this.getRowHojaId(row));
                        var keyReq = this.normalizeImportToken(row.NumReq || '');
                        if (keyHoja) byKey['H:' + keyHoja] = row;
                        byKey['C:' + this.normalizeImportToken(row.clave || '')] = row;
                        if (keyReq) byKey['R:' + keyReq] = row;
                    });

                    var applied = 0;
                    var skipped = 0;
                    var unchanged = 0;
                    imported.forEach((item) => {
                        if (!this.filaImportable(item)) {
                            return;
                        }

                        var keyHoja = this.normalizeImportId(this.firstImportField(item, ['ID_HOJA', 'IdHoja', 'id_hoja', 'ID HOJA', 'HojaId']));
                        var key = this.normalizeImportToken(this.firstImportField(item, ['CLAVE', 'Clave']));
                        var keyReq = this.normalizeImportToken(this.firstImportField(item, ['NUMERO_REQ', 'NUMERO_REQUISICION', 'NumeroReq', 'NUM_REQ', 'NoReq', 'N_REQ']));
                        var target = null;
                        if (keyHoja && byKey['H:' + keyHoja]) target = byKey['H:' + keyHoja];
                        if (!target && key && byKey['C:' + key]) target = byKey['C:' + key];
                        if (!target && keyReq && byKey['R:' + keyReq]) target = byKey['R:' + keyReq];
                        if (!target) {
                            skipped++;
                            return;
                        }

                        var pago = this.parseImportNumber(
                            this.firstImportField(item, ['PAGO_AUTORIZADO', 'ADEUDO_AUTORIZADO', 'NETO_A_PAGAR', 'PAGO AUTORIZADO']),
                            target.adeudo
                        );
                        var observ = String(this.firstImportField(item, ['OBSERVACIONES', 'Observaciones']) || target.Observaciones || '');
                        var changed = (this.toNumber(target.adeudo) !== this.toNumber(pago))
                            || (String(target.Observaciones || '') !== observ);
                        if (!changed) {
                            unchanged++;
                            return;
                        }

                        // Solo se permite actualizar monto autorizado y observaciones.
                        target.adeudo = pago;
                        target.Observaciones = observ;
                        this.marcarFilaEditada(target);
                        applied += 1;
                    });

                    this.programarGuardadoBorrador();
                    var detail = [
                        'Filas actualizadas: ' + applied,
                        'No encontradas: ' + skipped,
                        'Sin cambios: ' + unchanged
                    ].join('\n');
                    if (applied > 0) {
                        Swal.fire('Importacion completada', detail, (skipped || unchanged) ? 'warning' : 'success');
                    } else {
                        Swal.fire('Sin cambios aplicados', detail + '\n\nValida columnas ID_HOJA, CLAVE o NUMERO_REQ del archivo.', 'warning');
                    }
                } catch (error) {
                    Swal.fire('Error de importacion', error.message || 'No fue posible leer el archivo.', 'error');
                } finally {
                    event.target.value = '';
                }
            };

            reader.readAsArrayBuffer(file);
        },
        mostrarAyudaRapida: function () {
            Swal.fire({
                title: 'Atajos y tips para Direccion',
                html: `
                    <div style="text-align:left;font-size:14px;line-height:1.6">
                        <strong>Flujo rapido:</strong><br>
                        1) Usa formula global o acciones masivas.<br>
                        2) Ajusta excepciones por fila.<br>
                        3) Guarda cambios por obra.<br><br>
                        <strong>Atajos:</strong><br>
                        - Enter/Flechas en Pago Autorizado: navega filas.<br>
                        - Pegar varias filas desde Excel: se distribuye hacia abajo.<br><br>
                        <strong>Funciones:</strong><br>
                        MIN(), MAX(), AVG(), SUM(), ROUND(), IF().
                    </div>
                `,
                icon: 'info'
            });
        },
        selectCell: function (indicePresion, indiceHoja, columnCode) {
            this.selectedCell = {
                presion: indicePresion,
                hoja: indiceHoja,
                col: columnCode
            };

            var row = (((this.presiones[indicePresion] || {}).Presion_Obra || [])[indiceHoja] || {});
            if (columnCode === 'AUT') {
                this.formulaBar = String(row.adeudo != null ? row.adeudo : '');
            } else if (columnCode === 'FX') {
                this.formulaBar = String(row.formula || '');
            } else {
                this.formulaBar = '';
            }
        },
        onCellKeydown: function (event, indicePresion, indiceHoja) {
            var key = event.key;
            if (key !== 'Enter' && key !== 'ArrowDown' && key !== 'ArrowUp') return;

            event.preventDefault();
            var delta = key === 'ArrowUp' ? -1 : 1;
            var nextRow = indiceHoja + delta;
            var currentObra = this.presiones[indicePresion] || { Presion_Obra: [] };
            if (nextRow < 0 || nextRow >= (currentObra.Presion_Obra || []).length) return;

            var inputs = event.currentTarget.closest('tbody').querySelectorAll('input[aria-describedby="adeudo"]');
            if (!inputs || !inputs[nextRow]) return;
            inputs[nextRow].focus();
            inputs[nextRow].select();
        },
        pegarRangoAdeudo: function (event, indicePresion, indiceHoja) {
            var text = (event.clipboardData || window.clipboardData).getData('text');
            if (!text) return;

            var lines = text.split(/\r?\n/).filter(function (line) { return line.trim() !== ''; });
            if (lines.length <= 1 && text.indexOf('\t') === -1) return;

            event.preventDefault();
            var rows = this.presiones[indicePresion].Presion_Obra || [];

            for (var i = 0; i < lines.length; i++) {
                var target = indiceHoja + i;
                if (target >= rows.length) break;

                var firstCell = lines[i].split('\t')[0];
                rows[target].adeudo = this.toNumber(firstCell);
                this.marcarFilaEditada(rows[target]);
            }
            this.programarGuardadoBorrador();
        },
        safeEvalExpression: function (expr) {
            if (!/^[A-Z0-9_+\-*/().,\s<>=!]+$/.test(expr)) {
                throw new Error('Expresion invalida. Solo se permiten numeros, operadores y funciones soportadas.');
            }

            var identifiers = expr.match(/[A-Z_][A-Z0-9_]*/g) || [];
            var allowedIds = { MIN: true, MAX: true, AVG: true, ROUND: true, SUM: true, IF: true };
            for (var i = 0; i < identifiers.length; i++) {
                if (!allowedIds[identifiers[i]]) {
                    throw new Error('Funcion no permitida: ' + identifiers[i]);
                }
            }

            var fnMin = function () {
                return Math.min.apply(null, Array.from(arguments).map(function (n) { return Number(n); }));
            };
            var fnMax = function () {
                return Math.max.apply(null, Array.from(arguments).map(function (n) { return Number(n); }));
            };
            var fnAvg = function () {
                var args = Array.from(arguments).map(function (n) { return Number(n); });
                if (!args.length) return 0;
                var sum = args.reduce(function (acc, n) { return acc + n; }, 0);
                return sum / args.length;
            };
            var fnSum = function () {
                var args = Array.from(arguments).map(function (n) { return Number(n); });
                if (!args.length) return 0;
                return args.reduce(function (acc, n) { return acc + n; }, 0);
            };
            var fnRound = function (value, decimals) {
                var num = Number(value);
                var dec = parseInt(decimals, 10);
                if (isNaN(dec)) dec = 0;
                var factor = Math.pow(10, dec);
                return Math.round(num * factor) / factor;
            };
            var fnIf = function (condition, whenTrue, whenFalse) {
                return condition ? Number(whenTrue) : Number(whenFalse);
            };

            var result = Function('MIN', 'MAX', 'AVG', 'SUM', 'ROUND', 'IF', '"use strict"; return (' + expr + ');')(
                fnMin,
                fnMax,
                fnAvg,
                fnSum,
                fnRound,
                fnIf
            );
            if (typeof result !== 'number' || !isFinite(result)) {
                throw new Error('La formula no produjo un numero valido.');
            }
            return result;
        },
        evaluarFormulaExcel: function (formula, row) {
            if (!formula) return this.toNumber(row.adeudo);

            var raw = String(formula).trim();
            if (!raw) return this.toNumber(row.adeudo);
            if (raw[0] === '=') raw = raw.slice(1);

            var expr = raw.toUpperCase();
            var adeudoBase = this.toNumber(row.total);
            var autorizadoActual = this.toNumber(row.adeudo);

            expr = expr
                .replace(/\$/g, '')
                .replace(/,/g, '')
                .replace(/\bADEUDO\b/g, String(adeudoBase))
                .replace(/\bTOTAL\b/g, String(adeudoBase))
                .replace(/\bAUT\b/g, String(autorizadoActual));

            expr = expr.replace(/(\d+(?:\.\d+)?)%/g, '($1/100)');

            var result = this.safeEvalExpression(expr);
            if (result < 0) result = 0;
            return Math.round(result * 100) / 100;
        },
        aplicarFormulaFila: function (indicePresion, indiceHoja) {
            var row = this.presiones[indicePresion].Presion_Obra[indiceHoja];
            try {
                row.adeudo = this.evaluarFormulaExcel(row.formula, row);
                this.marcarFilaEditada(row);
                this.programarGuardadoBorrador();
            } catch (error) {
                Swal.fire('Formula invalida', error.message, 'warning');
            }
        },
        aplicarFormulaGlobal: function (indicePresion) {
            var obra = this.presiones[indicePresion];
            if (!obra.formulaGlobal || !obra.formulaGlobal.trim()) {
                Swal.fire('Sin formula', 'Ingresa una formula global antes de aplicar.', 'info');
                return;
            }
            try {
                (obra.Presion_Obra || []).forEach((row) => {
                    row.adeudo = this.evaluarFormulaExcel(obra.formulaGlobal, row);
                    this.marcarFilaEditada(row);
                });
                this.programarGuardadoBorrador();
                Swal.fire('Formula aplicada', 'Se actualizaron los montos autorizados de la obra.', 'success');
            } catch (error) {
                Swal.fire('Formula invalida', error.message, 'warning');
            }
        },
        normalizarAdeudo: function (indicePresion, indiceHoja) {
            var row = this.presiones[indicePresion].Presion_Obra[indiceHoja];
            row.adeudo = Math.round(this.toNumber(row.adeudo) * 100) / 100;
            this.marcarFilaEditada(row);
            this.programarGuardadoBorrador();
        },
        aplicarPorcentaje: function (indicePresion, indiceHoja, formValues) {
            const porcentaje = parseFloat(String(formValues["porcentaje"] || '').replace(',', '.'));
            const accion = formValues["accion"];
            if (isNaN(porcentaje) || porcentaje < 0) {
                Swal.fire('Porcentaje invalido', 'Ingresa un porcentaje numerico mayor o igual a 0.', 'warning');
                return;
            }
            const cantidadDecimal = this.convertirAdecimalEntero(porcentaje);

            let adeudoActual = this.toNumber(this.presiones[indicePresion]["Presion_Obra"][indiceHoja]["adeudo"]);
            let cantidadAccion = adeudoActual * cantidadDecimal;

            // Aplicar operación
            this.presiones[indicePresion]["Presion_Obra"][indiceHoja]["adeudo"] =
                accion === "Inc"
                    ? adeudoActual + cantidadAccion
                    : adeudoActual - cantidadAccion;
            if (this.presiones[indicePresion]["Presion_Obra"][indiceHoja]["adeudo"] < 0) {
                this.presiones[indicePresion]["Presion_Obra"][indiceHoja]["adeudo"] = 0;
            }
            this.presiones[indicePresion]["Presion_Obra"][indiceHoja]["Observaciones"] = accion === "Inc"
                ? 'Ajuste manual masivo por incremento porcentual.'
                : 'Ajuste manual masivo por descuento porcentual.';
            this.marcarFilaEditada(this.presiones[indicePresion]["Presion_Obra"][indiceHoja]);
            this.programarGuardadoBorrador();
        }
    ,
        get selectedCellLabel() {
            if (!this.selectedCell) return '';
            var rowNumber = this.selectedCell.hoja + 1;
            return this.selectedCell.col + rowNumber;
        },
        get pendientesPageRange() {
            var start = Math.max(1, this.pendientesPage - 2);
            var end = Math.min(this.pendientesPages, start + 4);
            start = Math.max(1, end - 4);
            var out = [];
            for (var i = start; i <= end; i++) out.push(i);
            return out;
        }
    ,
        setActiveTab: function (val) {
            if (this.activeTab === val) return;
            this.activeTab = val;
            if (val === 'pendientes') this.cargarPendientesAutorizacion();
            if (val === 'pendientes_pago') this.cargarPendientesPago();
        },
        init: function () {
            this.canPresionesWrite = !!(window.TF_ALL_PRESIONES_PERMS && window.TF_ALL_PRESIONES_PERMS.canPresionesWrite);
            window.addEventListener('keydown', this.handleGlobalHotkeys);
            this.listarObras();
            this.consultarUsuario();
            this.cargarPendientesAutorizacion();
            this.cargarBancos();
            // Detectar si el usuario puede pagar (rol finanzas o permiso finanzas.pagar)
            var self = this;
            axios.post(url, { accion: 1 }).then(function (r) {
                var u = (r.data || [])[0] || {};
                var perms = u.permissions || [];
                self.canFinanzasPagar = perms.indexOf('finanzas.pagar') !== -1;
                if (self.canFinanzasPagar) self.cargarPendientesPago();
            }).catch(function () {});

            // Limpia listeners al salir de la página.
            window.addEventListener('beforeunload', () => {
                window.removeEventListener('keydown', this.handleGlobalHotkeys);
            });
        },
        // F-M2: Carga lista de bancos
        cargarBancos: function () {
            var urlBancos = '../api/crud_bancos.php';
            axios.post(urlBancos, { accion: 3 }).then(function (r) {
                this.bancos = r.data || [];
            }.bind(this)).catch(function () { this.bancos = []; }.bind(this));
        },
        // F-M2: Lista paginada de hojas AUTORIZADAS pendientes de pago
        cargarPendientesPago: function () {
            this.pendientesPagoLoading = true;
            axios.post(url, {
                accion: 12,
                page: this.pendientesPagoPage,
                limite: this.pendientesPagoLimite,
                search: this.pendientesPagoSearch
            }).then(function (r) {
                var p = r.data || {};
                this.pendientesPagoRows  = p.rows  || [];
                this.pendientesPagoTotal = Number(p.total || 0);
                this.pendientesPagoPage  = Number(p.page  || 1);
                this.pendientesPagoPages = Number(p.pages || 1);
            }.bind(this)).catch(function (err) {
                console.error('cargarPendientesPago:', err);
                this.pendientesPagoRows  = [];
                this.pendientesPagoTotal = 0;
            }.bind(this)).finally(function () {
                this.pendientesPagoLoading = false;
            }.bind(this));
        },
        onPendientesPagoSearchInput: function () {
            if (this.pendientesPagoSearchTimer) clearTimeout(this.pendientesPagoSearchTimer);
            this.pendientesPagoSearchTimer = setTimeout(function () {
                this.pendientesPagoPage = 1;
                this.cargarPendientesPago();
            }.bind(this), 350);
        },
        goPendientesPagoPage: function (page) {
            if (this.pendientesPagoLoading) return;
            var t = Math.max(1, Math.min(this.pendientesPagoPages, parseInt(page, 10) || 1));
            if (t === this.pendientesPagoPage) return;
            this.pendientesPagoPage = t;
            this.cargarPendientesPago();
        },
        // F-M2: Abre modal de pago para una hoja
        abrirModalPago: async function (row) {
            var bancosOpts = this.bancos.map(function (b) {
                return '<option value="' + b.banco_id + '">' + (b.banco_nombreComercial || b.banco_razonSocial || b.banco_id) + '</option>';
            }).join('');
            var hoy = new Date().toISOString().substring(0, 10);
            var result = await Swal.fire({
                title: 'Registrar pago',
                html:
                    '<div class="text-start">' +
                    '<p class="text-muted mb-3 small">Proveedor: <strong>' + (row.proveedor || '—') + '</strong>' +
                    (row.proveedor_clabe ? ' — CLABE: <code>' + row.proveedor_clabe + '</code>' : '') + '</p>' +
                    '<p class="text-muted mb-3 small">Adeudo: <strong class="text-success">$' +
                    Number(row.hojarequisicion_adeudo || 0).toLocaleString('es-MX', {minimumFractionDigits:2}) + '</strong></p>' +
                    '<div class="mb-3">' +
                    '<label class="form-label fw-semibold">Folio / Referencia de pago <span class="text-danger">*</span></label>' +
                    '<input id="swal_folio" class="form-control" placeholder="Ej. TRF-2026-001" maxlength="100">' +
                    '</div>' +
                    (bancosOpts ? '<div class="mb-3"><label class="form-label fw-semibold">Banco</label><select id="swal_banco" class="form-select"><option value="">— Sin especificar —</option>' + bancosOpts + '</select></div>' : '') +
                    '<div class="mb-3">' +
                    '<label class="form-label fw-semibold">Fecha de pago <span class="text-danger">*</span></label>' +
                    '<input id="swal_fecha" class="form-control" type="date" value="' + hoy + '">' +
                    '</div>' +
                    '</div>',
                showCancelButton: true,
                confirmButtonText: 'Registrar pago',
                confirmButtonColor: '#198754',
                cancelButtonText: 'Cancelar',
                preConfirm: function () {
                    var folio = document.getElementById('swal_folio').value.trim();
                    var fecha = document.getElementById('swal_fecha').value.trim();
                    if (!folio) { Swal.showValidationMessage('El folio de pago es obligatorio'); return false; }
                    if (!fecha) { Swal.showValidationMessage('La fecha de pago es obligatoria'); return false; }
                    var bancoEl = document.getElementById('swal_banco');
                    return { folio: folio, banco: bancoEl ? bancoEl.value : '', fecha: fecha };
                }
            });
            if (!result.isConfirmed || !result.value) return;
            var form = result.value;
            try {
                var resp = await axios.post(url, {
                    accion: 11,
                    idHoja: row.hojaRequisicion_id,
                    folio_pago: form.folio,
                    banco_id: form.banco || 0,
                    fecha_pago: form.fecha
                });
                if (resp.data && resp.data.status === 'ok') {
                    Swal.fire({ icon: 'success', title: '¡Pago registrado!', text: 'Folio: ' + form.folio, timer: 2500, showConfirmButton: false });
                    this.cargarPendientesPago();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo registrar el pago' });
                }
            } catch (e) {
                console.error(e);
                var msg = (e.response && e.response.data && e.response.data.error) ? e.response.data.error : 'Error al registrar el pago';
                Swal.fire({ icon: 'error', title: 'Error', text: msg });
            }
        }
    };
}