var url = "../api/crud_presionDetail.php";
var url2 = ".";

function presionDetailApp() {
    return {
        users: [],
        presiones: [],
        obras: [],
        obraActiva: [],
        NameUser: "",
        semana: "",
        dia: "",
        PagoParcial: "",
        FechaPago: "",
        BancoPago: "",
        timeNow: "",
        estatus: "",
        canClosePresion: false,
        comentarioDirector: "",
        savingComentario: false,
        initDataTable: function () {
            if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.DataTable) return;
            var table = window.jQuery('#example');
            if (!table.length) return;
            if (window.jQuery.fn.dataTable.isDataTable('#example')) {
                table.DataTable().destroy();
            }
            table.DataTable({
                "order": [],
                "responsive": true,
                "autoWidth": false,
                "language": {
                    "sProcessing": "Procesando...",
                    "sLengthMenu": "Mostrar _MENU_ registros",
                    "sZeroRecords": "No se encontraron resultados",
                    "sEmptyTable": "Ningún dato disponible en esta tabla",
                    "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                    "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                    "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
                    "sInfoPostFix": "",
                    "sSearch": "Buscar:",
                    "sUrl": "",
                    "sInfoThousands": ",",
                    "sLoadingRecords": "Cargando...",
                    "oPaginate": {
                        "sFirst": "Primero",
                        "sLast": "Último",
                        "sNext": "Siguiente",
                        "sPrevious": "Anterior"
                    },
                    "oAria": {
                        "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                        "sSortDescending": ": Activar para ordenar la columna de manera descendente"
                    }
                }
            });
            window.jQuery('[data-toggle="tooltip"]').tooltip();
        },
        consultarUsuario: async function () {
            // Fase 5: identidad por sesion server-side (sin localStorage.NameUser)
            try {
                const response = await axios.post(url, { accion: 1 });
                this.users = response.data || [];
                if (this.users.length > 0 && this.users[0].user_name) {
                    this.NameUser = this.users[0].user_name;
                }
                var u = this.users[0] || {};
                var perms = Array.isArray(u.permissions) ? u.permissions : [];
                this.canClosePresion = perms.indexOf('*') !== -1 || perms.indexOf('presiones.authorize') !== -1;
            } catch (error) {
                console.error("Error al consultar usuario", error);
            }
        },
        listarObras: async function () {
            try {
                const response = await axios.post(url, { accion: 2 });
                this.obras = response.data;
                console.log(this.obras);
            } catch (error) {
                console.error("Error al listar las Obras:",error);
            }
        },
        cargarDatosPresion: async function (IdPresion) {
            try {
                const response = await axios.post(url, { accion: 3, idPresion: IdPresion, dia: this.dia, semana: this.semana });
                this.presiones = response.data;
                console.log(this.presiones);
                setTimeout(this.initDataTable.bind(this), 0);
            } catch (error) {
                console.error("Error al cargar las Presiones:",error);
            }
        },
        infoObraActiva: async function (obrasId) {
            try {
                const response = await axios.post(url, { accion: 4, obra: obrasId });
                this.obraActiva = response.data;
                console.log(this.obraActiva);
            } catch (error) {
                console.error("Error al cargar la Obra Activa:", error);
            }
        },
        irObra(idObra) {
            localStorage.setItem("obraActiva", idObra);
            window.location.href = url2 + "/obras.php";
        },
        asignarDiaySamana: async function () {
            this.semana = localStorage.getItem("Semana");
            this.dia = localStorage.getItem("Dia");
        },
        ordenarDatosPresion(dataArray) {
            var auxRow = {
                'clave': "",
                'requisicion': "",
                'proveedor': "",
                'concepto': [],
                'adeudo': "",
                'neto': "",
                'observaciones': [],
                'formaPago': ""
            };
            var AuxArray = [];

            for (var i = 0; i < dataArray.length; i++) {

            }
        },
        getWeekNumber: function (date) {
            const onejan = new Date(date.getFullYear(), 0, 1);
            const week = Math.ceil((((date - onejan) / 86400000) + onejan.getDay() + 1) / 7);
            return week;
        },
        getDayOfWeek: function (date) {
            const days = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
            return days[date.getDay() + 1];
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
        pagarItem: async function (idHoja, fecha, banco) {
            const swalWithBootstrapButtons = await Swal.mixin({
                customClass: {
                    confirmButton: "btn btn-success",
                    cancelButton: "btn btn-danger"
                },
                buttonsStyling: true
            });
            swalWithBootstrapButtons.fire({
                title: "¿Aprobaras este este Concepto para pago?",
                text: "Esta operacion no se puede revertir",
                icon: "info",
                showCancelButton: true,
                confirmButtonText: "SI",
                cancelButtonText: "NO",
                reverseButtons: false
            }).then((result) => {
                if (result.isConfirmed) {
                    this.marcarpagado(idHoja, fecha, banco);
                    swalWithBootstrapButtons.fire({
                        title: "Pagado",
                        text: "El articulo se marco como pagado",
                        icon: "success"
                    });
                } 
            });
        },
        marcarpagado: function(idHoja, fecha, banco){
              //alert("Agregado"+id+parcial+" "+fecha+" "+banco);
              var estatus = "PAGADO";
              axios.post(url, { accion: 5, idHoja:idHoja , fechaPago: fecha, bancoPago: banco, status: estatus}).then(response => {
                                if (window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable('#example')) {
                                        window.jQuery('#example').DataTable().destroy();
                                }
                this.cargarDatosPresion(localStorage.getItem("IdPresion"));  
                console.log(response.data);
              });
        },
        imprimirReq: function(NumReq,clave,id_hoja){
            console.log("Obras: ");
            console.log(this.obraActiva[0]);
             axios.post(url, { accion: 8, idHoja: id_hoja}).then(response => {
                 console.log(response.data);
                  generarPDFRequisicion(
                    NumReq, // Número de la requisición
                    clave, // Clave de la requisición
                    response.data[0]['infoHoja'], // Información de la Hoja
                    this.NameUser, // Nombre del usuario
                    response.data[0]['items'], // Items de la Hoja
                    this.obraActiva[0] // Información de la obra
                ); 
             });
        },
        excelJsDisponible: function () {
            return !!(window.ExcelJS && window.ExcelJS.Workbook);
        },
        descargarBufferExcel: function (buffer, filename) {
            var blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = filename;
            link.click();
            URL.revokeObjectURL(link.href);
        },
        leerImagenComoDataUrl: async function (path) {
            try {
                var resp = await fetch(path, { cache: 'no-store' });
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
            var safe = String(name || fallback || 'Presion').replace(/[\\\/?*\[\]:]/g, ' ').trim();
            if (!safe) safe = fallback || 'Presion';
            if (safe.length > 31) safe = safe.slice(0, 31);
            return safe;
        },
        // Fallback de exportacion cuando ExcelJS no esta disponible.
        exportarCsv: function () {
            var headers = ['CLAVE', 'NUM_REQUISICION', 'PROVEEDOR', 'CONCEPTO', 'ADEUDO', 'NETO_A_PAGAR', 'FORMA_PAGO', 'FECHA_PAGO', 'BANCO_PAGO'];
            var rows = this.presiones
                .filter(function (p) {
                    return p.HojaEstatus === 'LIGADA' || p.HojaEstatus === 'AUTORIZADA' || p.HojaEstatus === 'PAGADA';
                })
                .map((p) => [
                    p.clave || '',
                    p.NumReq || '',
                    p.proveedor || '',
                    p.concepto || '',
                    this.toNumber(p.total),
                    this.toNumber(p.adeudo),
                    p.formaPago || '',
                    p.Fecha || '',
                    p.Banco || ''
                ]);
            if (!rows.length) {
                Swal.fire('Sin datos', 'No hay filas para exportar en esta presion.', 'info');
                return;
            }
            var escape = function (v) {
                var s = String(v == null ? '' : v);
                return /[",\n;]/.test(s) ? '"' + s.replace(/"/g, '""') + '"' : s;
            };
            var lines = [headers.join(',')].concat(rows.map(function (r) { return r.map(escape).join(','); }));
            var obraNombre = (this.obraActiva.length && this.obraActiva[0].obras_nombre) ? this.obraActiva[0].obras_nombre : 'obra';
            var safeObra = String(obraNombre).replace(/\s+/g, '_').replace(/[^A-Za-z0-9_\-.]/g, '_');
            var blob = new Blob(['﻿' + lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
            var link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'PRESION_DETALLE_SEMANA_' + (this.semana || '-') + '_OBRA_' + safeObra + '.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        },

        exportarExcel: async function () {
            if (!this.excelJsDisponible()) {
                this.exportarCsv();
                Swal.fire('Excel no disponible', 'No fue posible cargar ExcelJS para exportar .xlsx. Se genero un CSV como alternativa.', 'info');
                return;
            }

            var obraNombre = (this.obraActiva.length && this.obraActiva[0].obras_nombre)
                ? this.obraActiva[0].obras_nombre
                : 'obra';

            var rows = this.presiones
                .filter(function (p) {
                    return p.HojaEstatus === 'LIGADA' || p.HojaEstatus === 'AUTORIZADA' || p.HojaEstatus === 'PAGADA';
                })
                .map((p) => {
                    return {
                        CLAVE: p.clave || '',
                        NUM_REQUISICION: p.NumReq || '',
                        PROVEEDOR: p.proveedor || '',
                        CONCEPTO: p.concepto || '',
                        ADEUDO: this.toNumber(p.total),
                        NETO_A_PAGAR: this.toNumber(p.adeudo),
                        FORMA_PAGO: p.formaPago || '',
                        FECHA_PAGO: p.Fecha || '',
                        BANCO_PAGO: p.Banco || ''
                    };
                });

            if (!rows.length) {
                Swal.fire('Sin datos', 'No hay filas para exportar en esta presion.', 'info');
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

            var ws = workbook.addWorksheet(this.nombreHojaExcelSeguro(obraNombre, 'Presion'), {
                views: [{ state: 'frozen', ySplit: 5 }],
                pageSetup: { orientation: 'landscape', fitToPage: true, fitToWidth: 1, fitToHeight: 0 }
            });
            ws.columns = [
                { header: 'CLAVE', width: 14 },
                { header: 'NUM_REQUISICION', width: 18 },
                { header: 'PROVEEDOR', width: 26 },
                { header: 'CONCEPTO', width: 42 },
                { header: 'ADEUDO', width: 16 },
                { header: 'NETO_A_PAGAR', width: 16 },
                { header: 'FORMA_PAGO', width: 16 },
                { header: 'FECHA_PAGO', width: 14 },
                { header: 'BANCO_PAGO', width: 20 }
            ];

            ws.mergeCells('A1:I1');
            ws.getCell('A1').value = 'THE FUENTES CORPORATION';
            ws.getCell('A1').font = { size: 18, bold: true, color: { argb: 'FF0F172A' } };
            ws.getCell('A1').alignment = { horizontal: 'left', vertical: 'middle' };

            ws.mergeCells('A2:I2');
            ws.getCell('A2').value = 'PRESION DE GASTOS - DETALLE DE PAGO';
            ws.getCell('A2').font = { size: 13, bold: true, color: { argb: 'FF1E293B' } };

            ws.getCell('A3').value = 'Fecha de generacion: ' + fechaISO;
            ws.getCell('C3').value = 'Usuario: ' + (this.NameUser || 'N/D');
            ws.mergeCells('E3:I3');
            ws.getCell('E3').value = 'EDITABLE: solo FECHA_PAGO y BANCO_PAGO';
            ws.getCell('E3').font = { bold: true, color: { argb: 'FF1D4ED8' } };

            ws.mergeCells('A4:I4');
            ws.getCell('A4').value = 'THE FUENTES CORPORATION';
            ws.getCell('A4').font = { size: 28, bold: true, color: { argb: '1A0F172A' } };
            ws.getCell('A4').alignment = { horizontal: 'center', vertical: 'middle' };

            var logoData = await this.leerImagenComoDataUrl('../images/LogoFuentes.png');
            if (logoData) {
                var logoId = workbook.addImage({
                    base64: logoData,
                    extension: this.extensionDesdeDataUrl(logoData, 'png')
                });
                ws.addImage(logoId, { tl: { col: 7.1, row: 0.15 }, ext: { width: 130, height: 46 } });
            }

            var watermarkData = await this.leerImagenComoDataUrl('../images/watermark.jpg');
            if (watermarkData) {
                var wmId = workbook.addImage({
                    base64: watermarkData,
                    extension: this.extensionDesdeDataUrl(watermarkData, 'jpeg')
                });
                ws.addImage(wmId, { tl: { col: 1.3, row: 4.5 }, ext: { width: 560, height: 300 } });
            }

            ws.getRow(5).values = ['CLAVE', 'NUM_REQUISICION', 'PROVEEDOR', 'CONCEPTO', 'ADEUDO', 'NETO_A_PAGAR', 'FORMA_PAGO', 'FECHA_PAGO', 'BANCO_PAGO'];
            ws.getRow(5).font = { bold: true, color: { argb: 'FF1E293B' } };
            ws.getRow(5).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFE2E8F0' } };
            ws.getRow(5).alignment = { vertical: 'middle', horizontal: 'center', wrapText: true };
            ws.getRow(5).border = {
                bottom: { style: 'thin', color: { argb: 'FFCBD5E1' } }
            };

            var cursor = 6;
            rows.forEach((r) => {
                var row = ws.getRow(cursor);
                row.values = [
                    r.CLAVE,
                    r.NUM_REQUISICION,
                    r.PROVEEDOR,
                    r.CONCEPTO,
                    this.toNumber(r.ADEUDO),
                    this.toNumber(r.NETO_A_PAGAR),
                    r.FORMA_PAGO,
                    r.FECHA_PAGO,
                    r.BANCO_PAGO
                ];
                row.getCell(5).numFmt = '$ #,##0.00';
                row.getCell(6).numFmt = '$ #,##0.00';
                row.eachCell(function (cell) {
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

            ws.mergeCells('A' + cursor + ':D' + cursor);
            ws.getCell('A' + cursor).value = 'TOTAL';
            ws.getCell('A' + cursor).font = { bold: true, color: { argb: 'FF0F172A' } };
            ws.getCell('A' + cursor).fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFFFF7ED' } };
            ws.getCell('E' + cursor).value = rows.reduce((acc, r) => acc + this.toNumber(r.ADEUDO), 0);
            ws.getCell('F' + cursor).value = rows.reduce((acc, r) => acc + this.toNumber(r.NETO_A_PAGAR), 0);
            ws.getCell('E' + cursor).numFmt = '$ #,##0.00';
            ws.getCell('F' + cursor).numFmt = '$ #,##0.00';
            ws.getCell('E' + cursor).font = { bold: true };
            ws.getCell('F' + cursor).font = { bold: true };

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
            wsInfo.getCell('A3').value = '1) Este archivo puede usarse como respaldo o evidencia de pago.';
            wsInfo.getCell('A4').value = '2) En esta exportacion solo debes editar FECHA_PAGO y BANCO_PAGO.';
            wsInfo.getCell('A5').value = '3) No modifiques CLAVE ni NUM_REQUISICION.';
            wsInfo.getCell('A6').value = '4) El archivo incluye logo y marca de agua corporativa.';
            wsInfo.getCell('A7').value = '5) Obra: ' + obraNombre + ' | Semana: ' + (this.semana || '-') + ' | Dia: ' + (this.dia || '-');

            var safeObra = String(obraNombre).replace(/\s+/g, '_').replace(/[^A-Za-z0-9_\-.]/g, '_');
            var filename = 'PRESION_DETALLE_SEMANA_' + (this.semana || '-') + '_DEL_' + fechaCompacta + '_OBRA_' + safeObra + '.xlsx';
            var buffer = await workbook.xlsx.writeBuffer();
            this.descargarBufferExcel(buffer, filename);
        },
        cerrarPresion: async function () {
            const swalWithBootstrapButtons = await Swal.mixin({
                customClass: {
                    confirmButton: "btn btn-success",
                    cancelButton: "btn btn-danger"
                },
                buttonsStyling: true
            });
            swalWithBootstrapButtons.fire({
                title: "¿Quieres cerrar esta presion?",
                text: "Esta operacion no se puede revertir",
                icon: "info",
                showCancelButton: true,
                confirmButtonText: "SI",
                cancelButtonText: "NO",
                reverseButtons: false
            }).then((result) => {
                if (result.isConfirmed) {
                    this.closePresion(localStorage.getItem("IdPresion"));
                    swalWithBootstrapButtons.fire({
                        title: "CARRADA",
                        text: "La presion a sido cerrada con exito.",
                        icon: "success"
                    }).then(() => {
                        window.location.reload();
                    });
                } 
            });
        },
        closePresion: function (idPresion) {
            axios.post(url, { accion: 7, idPresion: idPresion, Presiones: JSON.stringify(this.presiones)}).then(response => {
                console.log(response.data);
            });
        },
        irDireecion: function(){
            window.location.href = url2 + "/direccion.php";
        },
        consultarEstatus: async function(idPresion){
            try {
                const response = await   axios.post(url, { accion: 9, idPresion: idPresion});
                this.estatus = response.data[0]['presiones_estatus'];
                console.log(response.data[0]['presiones_estatus']);
            } catch (error) {
                console.error("Error al consultar el estatus de la presion:", error);
            }
        },
        cambiarBooleano: function (valor, indice) {
            this.presiones[indice].showDetail = !valor; // Devuelve el valor opuesto
            if (!valor) {
                this.presiones[indice].atrClass = "text-left align-middle inline-block fs-6";
                this.presiones[indice].strStyle = "max-width: auto;";
            }
            else {
                this.presiones[indice].atrClass = "text-left align-middle inline-block text-truncate fs-6";
                this.presiones[indice].strStyle = "max-width: 100px;";
            } 
        },
        formatearMoneda: function (cadena, incluirSimbolo) {
            // Convertir la cadena a un número
            let numero = parseFloat(cadena);
            // Verificar si la conversión fue exitosa
            if (isNaN(numero)) {
                return null; // O puedes lanzar un error si prefieres
            }
            // Formatear el número como moneda en pesos mexicanos
            let formato = numero.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            
            // Retornar el formato con o sin el símbolo de pesos
            return incluirSimbolo ? "$" + formato : formato;
        },
        toNumber: function (value) {
            var num = parseFloat(value);
            return isNaN(num) ? 0 : num;
        },
        sumatoria: function(arreglo, indice){
            var totalRetunr = 0;
            arreglo.forEach(function(elemento) {
                totalRetunr += parseFloat(elemento[indice]);
            });
            return totalRetunr;
        },
        irMenuCatalago: function(){
            window.location.href = url2 + "/menu_catalago.php";
        },
        cargarComentarioDirector: async function (presionId) {
            try {
                const resp = await axios.post(url, { accion: 11, idPresion: presionId });
                this.comentarioDirector = (resp.data && resp.data.comentario) || '';
            } catch (e) {
                console.warn('No se pudo cargar comentario director:', e);
            }
        },
        guardarComentarioDirector: async function () {
            var presionId = localStorage.getItem("IdPresion");
            if (!presionId) { Swal.fire('Error', 'ID de presión no disponible.', 'error'); return; }
            this.savingComentario = true;
            try {
                var csrf = (this.users[0] && this.users[0].csrf) || '';
                await axios.post(url, {
                    accion: 12,
                    idPresion: presionId,
                    comentario: this.comentarioDirector,
                    _csrf: csrf
                });
                Swal.fire({ icon: 'success', title: 'Nota guardada', timer: 1500, showConfirmButton: false });
            } catch (e) {
                Swal.fire('Error', 'No se pudo guardar la nota.', 'error');
            } finally {
                this.savingComentario = false;
            }
        },
        init: async function () {
            var obraId = localStorage.getItem("obraActiva");
            var presionId = localStorage.getItem("IdPresion");
            if (!obraId || !presionId) { window.location.href = './index.php'; return; }
            await this.listarObras();
            await this.asignarDiaySamana();
            await this.infoObraActiva(obraId);
            await this.consultarUsuario();
            await this.consultarEstatus(presionId);
            await this.cargarDatosPresion(presionId);
            await this.cargarComentarioDirector(presionId);
        }
    };
}