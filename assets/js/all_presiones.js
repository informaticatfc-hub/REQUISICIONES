var url = "../api/crud_all_presiones.php";
var url2 = ".";
var DIRECTOR_DRAFT_KEY = "tf_director_presiones_draft_v1";

const appRequesition = new Vue({
    el: "#AppIndex",
    data: {
        users: [],
        obras: [],
        NameUser: "",
        presiones: [],
        adeudo: "",
        comentarios: "",
        selectedCell: null,
        formulaBar: "",
        draftTimer: null
    },
    methods: {
        handleGlobalHotkeys: function (event) {
            if (!(event.ctrlKey || event.metaKey) || event.key.toLowerCase() !== 's') return;
            event.preventDefault();
            if (!this.selectedCell) return;
            var obra = this.presiones[this.selectedCell.presion];
            if (!obra) return;
            this.guardarCambios(obra.Presion_Obra || []);
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

                $(document).ready(function () {
                    $('[data-toggle="tooltip"]').tooltip(); // Inicializa los tooltips
                });
            });
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
                                    <div class="input-group mb-3"">
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
                row.Observaciones = row.Observaciones || 'Autorizado al 100% (masivo).';
                this.marcarFilaEditada(row);
            });
            this.programarGuardadoBorrador();
        },
        aplicarDescuentoObra: function (indicePresion) {
            var obra = this.presiones[indicePresion];
            (obra.Presion_Obra || []).forEach((row) => {
                row.adeudo = Math.round(this.toNumber(row.total) * 0.9 * 100) / 100;
                row.Observaciones = row.Observaciones || 'Descuento masivo del 10% aplicado.';
                this.marcarFilaEditada(row);
            });
            this.programarGuardadoBorrador();
        },
        rechazarTodoObra: function (indicePresion) {
            var obra = this.presiones[indicePresion];
            (obra.Presion_Obra || []).forEach((row) => {
                row.adeudo = 0;
                row.Observaciones = row.Observaciones || 'Rechazado de forma masiva.';
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
            var headers = ['Clave', 'NumeroReq', 'Proveedor', 'Concepto', 'AdeudoPropuesto', 'PagoAutorizado', 'Formula', 'Observaciones', 'FormaPago'];
            var csv = [headers.join(',')];

            rows.forEach((row) => {
                var line = [
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
        aplicarFormulaBarra: function (indicePresion) {
            if (!this.selectedCell) {
                Swal.fire('Sin celda', 'Selecciona primero una celda para aplicar la formula.', 'info');
                return;
            }

            if (this.selectedCell.presion !== indicePresion) {
                Swal.fire('Obra distinta', 'La celda seleccionada pertenece a otra obra. Selecciona una celda de esta tabla.', 'warning');
                return;
            }

            var row = this.presiones[this.selectedCell.presion].Presion_Obra[this.selectedCell.hoja];
            var value = String(this.formulaBar || '').trim();

            if (this.selectedCell.col === 'AUT') {
                if (value.startsWith('=')) {
                    try {
                        row.adeudo = this.evaluarFormulaExcel(value, row);
                        row.formula = value;
                        this.marcarFilaEditada(row);
                        this.programarGuardadoBorrador();
                    } catch (error) {
                        Swal.fire('Formula invalida', error.message, 'warning');
                        return;
                    }
                } else {
                    row.adeudo = this.toNumber(value);
                    this.marcarFilaEditada(row);
                    this.programarGuardadoBorrador();
                }
            }

            if (this.selectedCell.col === 'FX') {
                row.formula = value;
                if (value) {
                    try {
                        row.adeudo = this.evaluarFormulaExcel(value, row);
                        this.marcarFilaEditada(row);
                        this.programarGuardadoBorrador();
                    } catch (error) {
                        Swal.fire('Formula invalida', error.message, 'warning');
                        return;
                    }
                }
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
            const porcentaje = parseInt(formValues["porcentaje"]);
            const accion = formValues["accion"];
            const cantidadDecimal = this.convertirAdecimalEntero(porcentaje);

            let adeudoActual = this.toNumber(this.presiones[indicePresion]["Presion_Obra"][indiceHoja]["adeudo"]);
            let cantidadAccion = adeudoActual * cantidadDecimal;

            // Aplicar operación
            this.presiones[indicePresion]["Presion_Obra"][indiceHoja]["adeudo"] =
                accion === "Inc"
                    ? adeudoActual + cantidadAccion
                    : adeudoActual - cantidadAccion;
            this.marcarFilaEditada(this.presiones[indicePresion]["Presion_Obra"][indiceHoja]);
            this.programarGuardadoBorrador();
        }
    },
    created: function () {
        window.addEventListener('keydown', this.handleGlobalHotkeys);
        this.listarObras();
        this.consultarUsuario();
    },
    beforeDestroy: function () {
        window.removeEventListener('keydown', this.handleGlobalHotkeys);
    },
    computed: {
        selectedCellLabel: function () {
            if (!this.selectedCell) return '';
            var rowNumber = this.selectedCell.hoja + 1;
            return this.selectedCell.col + rowNumber;
        }
    }
});