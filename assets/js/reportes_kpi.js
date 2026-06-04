var kpiUrl = "../api/crud_all_presiones.php";
var itemsReqUrl = "../api/crud_items_requisiciones.php";

function reportesKpiApp() {
    return {
        obras: [],
        presionesRaw: [],
        hojasRaw: [],
        hojasFiltradas: [],
        obraStats: [],
        reporteAgrupado: [],
        presionesDetalle: [],
        selectedPresionId: null,
        filtroObraTexto: "",
        consultaAplicada: false,
        cargando: false,
        cargandoHojas: false,
        exportandoZip: false,
        mostrarTabla: false,
        mostrarHojas: true,
        filtros: {
            obraIds: [],
            desde: "",
            hasta: ""
        },
        kpi: {
            presiones: 0,
            total: 0,
            adeudo: 0,
            pagado: 0,
            pct: 0,
            hojas: 0,
            ticket: 0
        }
    ,

        get obraActualNombre() {
            var ids = Array.isArray(this.filtros.obraIds) ? this.filtros.obraIds : [];
            if (!ids.length) return "";
            var selected = this.obras
                .filter(function (o) { return ids.indexOf(String(o.obras_id)) !== -1; })
                .map(function (o) { return o.obras_nombre; });
            if (!selected.length) return "";
            if (selected.length <= 2) return selected.join(", ");
            return selected.length + " obras seleccionadas";
        },
        get obraUnicaSeleccionada() {
            return Array.isArray(this.filtros.obraIds) && this.filtros.obraIds.length === 1;
        },
        get obrasFiltradasSelector() {
            var texto = String(this.filtroObraTexto || "").trim().toLowerCase();
            if (!texto) return this.obras;
            return this.obras.filter(function (obra) {
                return String(obra.obras_nombre || "").toLowerCase().indexOf(texto) !== -1;
            });
        },
        get obrasSeleccionadasResumen() {
            var ids = Array.isArray(this.filtros.obraIds) ? this.filtros.obraIds : [];
            if (!ids.length) return [];
            return this.obras
                .filter(function (o) { return ids.indexOf(String(o.obras_id)) !== -1; })
                .map(function (o) { return { id: String(o.obras_id), nombre: o.obras_nombre }; });
        },
        get obraSeleccionUnicaDetalle() {
            if (!this.obraUnicaSeleccionada) return null;
            var id = String(this.filtros.obraIds[0] || "");
            for (var i = 0; i < this.obras.length; i++) {
                if (String(this.obras[i].obras_id) === id) return this.obras[i];
            }
            return null;
        },
        get presionSeleccionada() {
            var id = Number(this.selectedPresionId || 0);
            if (!id) return null;
            for (var i = 0; i < this.presionesDetalle.length; i++) {
                if (Number(this.presionesDetalle[i].presiones_id) === id) return this.presionesDetalle[i];
            }
            return null;
        },
        get hojasPresionSeleccionada() {
            var id = Number(this.selectedPresionId || 0);
            if (!id) return [];
            return this.hojasFiltradas.filter(function (h) {
                return Number(h.presiones_id) === id;
            });
        }
    ,
        money: function (value) {
            var n = Number(value || 0);
            return "$ " + n.toLocaleString("es-MX", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        toNum: function (value) {
            var n = Number(value);
            return isNaN(n) ? 0 : n;
        },
        isoDate: function (v) {
            if (!v) return "";
            var d = new Date(v);
            return isNaN(d.getTime()) ? "" : d.toISOString().slice(0, 10);
        },
        calcPct: function (valor, total) {
            if (!total) return 0;
            return Math.min(100, Math.max(0, (valor / total) * 100));
        },
        pctClass: function (pctVal) {
            if (pctVal >= 80) return "good";
            if (pctVal >= 40) return "warn";
            return "bad";
        },
        estadoBadgeClass: function (estatus) {
            var s = String(estatus || "").toUpperCase();
            if (s === "PAGADA" || s === "AUTORIZADA") return "ok";
            if (s === "REVISION" || s === "LIGADA" || s === "PENDIENTE") return "mid";
            return "off";
        },
        nombreSeguro: function (value) {
            return String(value || "")
                .normalize("NFD")
                .replace(/[\u0300-\u036f]/g, "")
                .replace(/[^A-Za-z0-9_-]+/g, "_")
                .replace(/_+/g, "_")
                .replace(/^_|_$/g, "")
                .slice(0, 80) || "SIN_NOMBRE";
        },
        fechaCompacta: function (value) {
            var raw = this.isoDate(value) || this.isoDate(new Date());
            return raw.replace(/-/g, "");
        },

        normalizeImageSource: function (raw, mime) {
            if (!raw) return null;
            var str = String(raw).trim();
            if (!str) return null;
            if (/^data:image\//i.test(str)) return str;
            if (/^[A-Za-z0-9+/=]+$/.test(str.slice(0, 120))) {
                return "data:image/" + (mime || "png") + ";base64," + str;
            }
            return str;
        },
        loadImageDataUrl: function (src, mimeOut) {
            return new Promise(function (resolve) {
                if (!src) {
                    resolve(null);
                    return;
                }
                var img = new Image();
                img.crossOrigin = "anonymous";
                img.onload = function () {
                    try {
                        var canvas = document.createElement("canvas");
                        canvas.width = img.naturalWidth || img.width;
                        canvas.height = img.naturalHeight || img.height;
                        var ctx = canvas.getContext("2d");
                        ctx.drawImage(img, 0, 0);
                        resolve(canvas.toDataURL(mimeOut || "image/png"));
                    } catch (e) {
                        resolve(null);
                    }
                };
                img.onerror = function () { resolve(null); };
                img.src = src;
            });
        },
        cargarBrandingPdf: async function () {
            var logoBase = this.normalizeImageSource((typeof IMAGE_LOGO_BASE64 !== "undefined" ? IMAGE_LOGO_BASE64 : null), "png");
            var wmBase = this.normalizeImageSource((typeof IMAGE_WATERMARK_BASE64 !== "undefined" ? IMAGE_WATERMARK_BASE64 : null), "jpeg");
            var logoSrc = logoBase || "../images/LogoFuentes.png";
            var wmSrc = wmBase || "../images/watermark.jpg";
            var assets = await Promise.all([
                this.loadImageDataUrl(logoSrc, "image/png"),
                this.loadImageDataUrl(wmSrc, "image/jpeg")
            ]);
            return { logo: assets[0], watermark: assets[1] };
        },
        drawKpiPdfHeader: function (doc, branding, titulo, subtitulo, resumen) {
            var pageW = doc.internal.pageSize.getWidth();
            var pageH = doc.internal.pageSize.getHeight();

            if (branding && branding.watermark) {
                try {
                    doc.addImage(branding.watermark, "JPEG", 20, 30, pageW - 40, pageH - 60);
                } catch (e) {}
            }

            doc.setTextColor(230, 233, 239);
            doc.setFontSize(42);
            doc.text("THE FUENTES", pageW / 2, pageH / 2, { align: "center", angle: 24 });

            doc.setTextColor(20, 31, 52);
            doc.setFontSize(14);
            doc.text(titulo, 14, 12);

            if (branding && branding.logo) {
                try {
                    doc.addImage(branding.logo, "PNG", pageW - 34, 6, 20, 12);
                } catch (e) {}
            }

            doc.setFontSize(9);
            doc.setTextColor(71, 85, 105);
            doc.text(subtitulo, 14, 18);
            doc.text(resumen, 14, 23);
        },

        cargarCatalogos: async function () {
            var resp = await axios.post(kpiUrl, { accion: 2 });
            this.obras = resp.data || [];
        },
        cargarResumen: async function () {
            var resp = await axios.post(kpiUrl, { accion: 8 });
            this.presionesRaw = resp.data || [];
        },
        cargarHojasDetalle: async function () {
            if (!this.obraUnicaSeleccionada) {
                this.hojasRaw = [];
                this.hojasFiltradas = [];
                this.selectedPresionId = null;
                return;
            }
            this.cargandoHojas = true;
            try {
                var obraId = Number(this.filtros.obraIds[0] || 0);
                var payload = {
                    accion: 9,
                    obra: obraId,
                    desde: this.filtros.desde || "",
                    hasta: this.filtros.hasta || ""
                };
                var resp = await axios.post(kpiUrl, payload);
                this.hojasRaw = resp.data || [];
            } finally {
                this.cargandoHojas = false;
            }
        },

        drillDown: function (obraId) {
            this.filtros.obraIds = [String(obraId)];
            this.filtroObraTexto = "";
            this.mostrarTabla = true;
            this.aplicarFiltros();
        },
        verTodas: function () {
            this.filtros.obraIds = [];
            this.filtroObraTexto = "";
            this.selectedPresionId = null;
            this.mostrarTabla = false;
            this.aplicarFiltros();
        },
        limpiarObras: function () {
            this.filtros.obraIds = [];
            this.selectedPresionId = null;
        },
        seleccionarTodasObras: function () {
            this.filtros.obraIds = this.obras.map(function (obra) { return String(obra.obras_id); });
            this.selectedPresionId = null;
        },
        toggleObraSeleccion: function (obraId) {
            var id = String(obraId);
            var lista = Array.isArray(this.filtros.obraIds) ? this.filtros.obraIds.slice() : [];
            var idx = lista.indexOf(id);
            if (idx === -1) {
                lista.push(id);
            } else {
                lista.splice(idx, 1);
            }
            this.filtros.obraIds = lista;
            this.selectedPresionId = null;
        },
        seleccionarPresion: function (presionId) {
            this.selectedPresionId = Number(presionId || 0) || null;
            this.mostrarHojas = true;
        },

        /**
         * Establece rangos de fecha predefinidos.
         * @param {'semana'|'mes'|'trimestre'|'anio'|'todo'} periodo
         */
        setPeriodo: function (periodo) {
            var hoy   = new Date();
            var desde = new Date(hoy);
            var hasta = new Date(hoy);
            var fmt   = function (d) {
                return d.toISOString().slice(0, 10);
            };
            hasta = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate());

            if (periodo === 'semana') {
                desde = new Date(hoy);
                desde.setDate(hoy.getDate() - 6);
            } else if (periodo === 'mes') {
                desde = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
            } else if (periodo === 'trimestre') {
                desde = new Date(hoy);
                desde.setMonth(hoy.getMonth() - 2);
                desde.setDate(1);
            } else if (periodo === 'anio') {
                desde = new Date(hoy.getFullYear(), 0, 1);
            } else {
                // todo — sin filtro de fecha
                this.filtros.desde = '';
                this.filtros.hasta = '';
                return;
            }
            this.filtros.desde = fmt(desde);
            this.filtros.hasta = fmt(hasta);
        },

        aplicarFiltros: async function () {
            this.cargando = true;
            try {
                if (!this.presionesRaw.length) await this.cargarResumen();

                var obraIds = Array.isArray(this.filtros.obraIds)
                    ? this.filtros.obraIds.map(function (v) { return String(v); })
                    : [];
                var desde = this.filtros.desde;
                var hasta = this.filtros.hasta;
                var self = this;

                if (desde && hasta && desde > hasta) {
                    Swal.fire("Rango invalido", "La fecha Desde no puede ser mayor a Hasta.", "warning");
                    return;
                }

                var filtradas = this.presionesRaw.filter(function (row) {
                    if (obraIds.length && obraIds.indexOf(String(row.presiones_obra)) === -1) return false;
                    var f = self.isoDate(row.presiones_fechaCreacion);
                    if (desde && (!f || f < desde)) return false;
                    if (hasta && (!f || f > hasta)) return false;
                    return true;
                });

                var obraMap = {};
                filtradas.forEach(function (row) {
                    var id = row.presiones_obra;
                    var nombre = row.obras_nombre || "SIN OBRA";
                    if (!obraMap[id]) {
                        obraMap[id] = { obraId: id, obra: nombre, presiones: 0, hojas: 0, total: 0, adeudo: 0 };
                    }
                    obraMap[id].presiones += 1;
                    obraMap[id].hojas += self.toNum(row.hojas_ligadas);
                    obraMap[id].total += self.toNum(row.total_calculado);
                    obraMap[id].adeudo += self.toNum(row.adeudo_calculado);
                });
                this.obraStats = Object.keys(obraMap).map(function (k) {
                    var o = obraMap[k];
                    o.pagado = o.total - o.adeudo;
                    o.pct = o.total > 0 ? (o.pagado / o.total) * 100 : 0;
                    o.ticket = o.presiones > 0 ? o.total / o.presiones : 0;
                    return o;
                }).sort(function (a, b) { return b.total - a.total; });

                var groups = {};
                var presionMap = {};
                filtradas.forEach(function (row) {
                    var obraNombre = row.obras_nombre || "SIN_OBRA";
                    var fecha = self.isoDate(row.presiones_fechaCreacion) || "SIN_FECHA";
                    var key = row.presiones_obra + "|" + fecha;
                    if (!groups[key]) {
                        groups[key] = {
                            key: key,
                            obraId: row.presiones_obra,
                            obra: obraNombre,
                            fecha: fecha,
                            presiones: 0,
                            hojas: 0,
                            total: 0,
                            adeudo: 0
                        };
                    }
                    groups[key].presiones += 1;
                    groups[key].hojas += self.toNum(row.hojas_ligadas);
                    groups[key].total += self.toNum(row.total_calculado);
                    groups[key].adeudo += self.toNum(row.adeudo_calculado);

                    var pid = Number(row.presiones_id || 0);
                    if (pid) {
                        if (!presionMap[pid]) {
                            presionMap[pid] = {
                                presiones_id: pid,
                                presiones_nombre: row.presiones_nombre || ("Presion #" + pid),
                                presiones_fechaCreacion: row.presiones_fechaCreacion,
                                obra: obraNombre,
                                total: 0,
                                adeudo: 0,
                                pagado: 0,
                                hojas: 0
                            };
                        }
                        presionMap[pid].total += self.toNum(row.total_calculado);
                        presionMap[pid].adeudo += self.toNum(row.adeudo_calculado);
                        presionMap[pid].pagado = presionMap[pid].total - presionMap[pid].adeudo;
                        presionMap[pid].hojas += self.toNum(row.hojas_ligadas);
                    }
                });

                this.reporteAgrupado = Object.keys(groups).map(function (k) {
                    var g = groups[k];
                    g.pagado = g.total - g.adeudo;
                    g.pct = g.total > 0 ? (g.pagado / g.total) * 100 : 0;
                    g.ticket = g.presiones > 0 ? g.total / g.presiones : 0;
                    return g;
                }).sort(function (a, b) {
                    var c = a.obra.localeCompare(b.obra);
                    return c !== 0 ? c : a.fecha.localeCompare(b.fecha);
                });

                this.presionesDetalle = Object.keys(presionMap).map(function (k) {
                    var p = presionMap[k];
                    p.pct = p.total > 0 ? (p.pagado / p.total) * 100 : 0;
                    return p;
                }).sort(function (a, b) {
                    return new Date(b.presiones_fechaCreacion).getTime() - new Date(a.presiones_fechaCreacion).getTime();
                });

                if (this.obraUnicaSeleccionada) {
                    await this.cargarHojasDetalle();
                    this.hojasFiltradas = this.hojasRaw.slice();
                    if (this.presionesDetalle.length) {
                        var selected = Number(this.selectedPresionId || 0);
                        var found = this.presionesDetalle.some(function (p) { return Number(p.presiones_id) === selected; });
                        if (!found) this.selectedPresionId = Number(this.presionesDetalle[0].presiones_id);
                    } else {
                        this.selectedPresionId = null;
                    }
                } else {
                    this.hojasRaw = [];
                    this.hojasFiltradas = [];
                    this.selectedPresionId = null;
                }

                var totalG = filtradas.reduce(function (s, r) { return s + self.toNum(r.total_calculado); }, 0);
                var totalA = filtradas.reduce(function (s, r) { return s + self.toNum(r.adeudo_calculado); }, 0);
                var totalH = this.obraUnicaSeleccionada ? this.hojasFiltradas.length : 0;
                var totalP = filtradas.length;
                this.kpi = {
                    presiones: totalP,
                    total: totalG,
                    adeudo: totalA,
                    pagado: totalG - totalA,
                    pct: totalG > 0 ? ((totalG - totalA) / totalG) * 100 : 0,
                    hojas: totalH,
                    ticket: totalP > 0 ? totalG / totalP : 0
                };

                this.consultaAplicada = true;
            } catch (err) {
                console.error("Error al aplicar filtros KPI:", err);
                Swal.fire("Error", "No se pudieron cargar los datos. Revisa la conexion.", "error");
            } finally {
                this.cargando = false;
            }
        },

        exportarCsv: function () {
            if (!this.consultaAplicada || !this.reporteAgrupado.length) {
                Swal.fire("Sin datos", "Primero aplica filtros para generar el desglose.", "info");
                return;
            }
            var headers = ["OBRA", "FECHA", "PRESIONES", "HOJAS_LIGADAS", "TOTAL_GASTOS", "PAGADO", "ADEUDO", "PCT_PAGADO", "TICKET_PROM"];
            var lines = [headers.join(",")];
            this.reporteAgrupado.forEach(function (r) {
                var line = [
                    r.obra, r.fecha, r.presiones, r.hojas,
                    r.total.toFixed(2), r.pagado.toFixed(2),
                    r.adeudo.toFixed(2), r.pct.toFixed(1) + "%",
                    r.ticket.toFixed(2)
                ].map(function (v) {
                    return '"' + String(v).replace(/"/g, '""') + '"';
                }).join(",");
                lines.push(line);
            });
            var blob = new Blob([lines.join("\n")], { type: "text/csv;charset=utf-8;" });
            var a = document.createElement("a");
            a.href = URL.createObjectURL(blob);
            a.download = "kpi_desglose_" + new Date().toISOString().slice(0, 10) + ".csv";
            a.click();
            URL.revokeObjectURL(a.href);
        },

        exportarPdf: async function () {
            if (!this.consultaAplicada || !this.reporteAgrupado.length) {
                Swal.fire("Sin datos", "Primero aplica filtros para generar el desglose.", "info");
                return;
            }
            if (!window.jspdf || !window.jspdf.jsPDF) {
                Swal.fire("PDF no disponible", "No se encontro la libreria jsPDF.", "warning");
                return;
            }
            var jsPDF = window.jspdf.jsPDF;
            var self = this;
            var doc = new jsPDF({ orientation: "landscape", unit: "mm", format: "a4" });
            var fechaGen = new Date().toISOString().slice(0, 10);
            var obraTxt = (this.filtros.obraIds && this.filtros.obraIds.length) ? this.obraActualNombre : "Todas las obras";

            var branding = await this.cargarBrandingPdf();
            var titulo = "Dashboard KPI - Direccion";
            var subtitulo = "Generado: " + fechaGen
                + "   Obra: " + obraTxt
                + "   Desde: " + (this.filtros.desde || "-")
                + "   Hasta: " + (this.filtros.hasta || "-");
            var resumen = "Presiones: " + this.kpi.presiones
                + "   Total: " + this.money(this.kpi.total)
                + "   Pagado: " + this.money(this.kpi.pagado)
                + "   Adeudo: " + this.money(this.kpi.adeudo)
                + "   % Pagado: " + this.kpi.pct.toFixed(1) + "%";

            this.drawKpiPdfHeader(doc, branding, titulo, subtitulo, resumen);

            doc.autoTable({
                startY: 30,
                head: [["Obra", "Fecha", "Pres.", "Hojas", "Total Gastos", "Pagado", "Adeudo", "% Pagado", "Ticket Prom."]],
                body: this.reporteAgrupado.map(function (r) {
                    return [r.obra, r.fecha, r.presiones, r.hojas,
                        self.money(r.total), self.money(r.pagado),
                        self.money(r.adeudo), r.pct.toFixed(1) + "%",
                        self.money(r.ticket)];
                }),
                foot: [["TOTALES", "", this.kpi.presiones, this.kpi.hojas,
                    this.money(this.kpi.total), this.money(this.kpi.pagado),
                    this.money(this.kpi.adeudo), this.kpi.pct.toFixed(1) + "%",
                    this.money(this.kpi.ticket)]],
                theme: "grid",
                styles: { fontSize: 7.5 },
                footStyles: { fillColor: [241, 245, 249], textColor: [0, 0, 0], fontStyle: "bold" },
                didDrawPage: function () {
                    self.drawKpiPdfHeader(doc, branding, titulo, subtitulo, resumen);
                }
            });
            doc.save("kpi_desglose_" + fechaGen + ".pdf");
        },

        generarExcelPresionBuffer: async function (presion, hojas) {
            if (!(window.ExcelJS && window.ExcelJS.Workbook)) {
                throw new Error("ExcelJS no disponible");
            }
            var workbook = new ExcelJS.Workbook();
            workbook.creator = "The Fuentes Corporation";
            workbook.created = new Date();

            var sheetName = this.nombreSeguro("PRESION_" + (presion.presiones_id || ""));
            var ws = workbook.addWorksheet(sheetName || "PRESION", {
                views: [{ state: "frozen", ySplit: 4 }]
            });

            ws.columns = [
                { header: "PRESION", width: 18 },
                { header: "OBRA", width: 26 },
                { header: "HOJA", width: 10 },
                { header: "REQUISICION", width: 20 },
                { header: "PROVEEDOR", width: 26 },
                { header: "ESTATUS", width: 14 },
                { header: "TOTAL", width: 14 },
                { header: "ADEUDO", width: 14 }
            ];

            ws.mergeCells("A1:H1");
            ws.getCell("A1").value = "THE FUENTES CORPORATION";
            ws.getCell("A1").font = { bold: true, size: 16, color: { argb: "FF0F172A" } };

            ws.mergeCells("A2:H2");
            ws.getCell("A2").value = "RESUMEN PRESION " + (presion.presiones_nombre || ("#" + presion.presiones_id));
            ws.getCell("A2").font = { bold: true, size: 12, color: { argb: "FF1E293B" } };

            var rHeader = ws.getRow(4);
            rHeader.values = ["PRESION", "OBRA", "HOJA", "REQUISICION", "PROVEEDOR", "ESTATUS", "TOTAL", "ADEUDO"];
            rHeader.font = { bold: true, color: { argb: "FF1E293B" } };
            rHeader.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFE2E8F0" } };
            rHeader.alignment = { horizontal: "center", vertical: "middle" };

            var cursor = 5;
            for (var i = 0; i < hojas.length; i++) {
                var h = hojas[i];
                var rr = ws.getRow(cursor);
                rr.values = [
                    presion.presiones_nombre || ("Presion #" + presion.presiones_id),
                    h.obras_nombre || "SIN OBRA",
                    h.hojaRequisicion_numero || h.hojaRequisicion_id,
                    h.requisicion_Numero || "",
                    h.proveedor_nombre || "",
                    h.hojaRequisicion_estatus || "",
                    this.toNum(h.hojaRequisicion_total),
                    this.toNum(h.hojarequisicion_adeudo)
                ];
                rr.getCell(7).numFmt = "$ #,##0.00";
                rr.getCell(8).numFmt = "$ #,##0.00";
                rr.eachCell(function (cell) {
                    cell.border = {
                        top: { style: "thin", color: { argb: "FFE2E8F0" } },
                        left: { style: "thin", color: { argb: "FFE2E8F0" } },
                        bottom: { style: "thin", color: { argb: "FFE2E8F0" } },
                        right: { style: "thin", color: { argb: "FFE2E8F0" } }
                    };
                });
                cursor += 1;
            }

            ws.mergeCells("A" + cursor + ":F" + cursor);
            ws.getCell("A" + cursor).value = "TOTALES";
            ws.getCell("A" + cursor).font = { bold: true };
            ws.getCell("G" + cursor).value = this.toNum(presion.total);
            ws.getCell("H" + cursor).value = this.toNum(presion.adeudo);
            ws.getCell("G" + cursor).numFmt = "$ #,##0.00";
            ws.getCell("H" + cursor).numFmt = "$ #,##0.00";
            ws.getCell("G" + cursor).font = { bold: true };
            ws.getCell("H" + cursor).font = { bold: true };

            return await workbook.xlsx.writeBuffer();
        },

        generarPdfHojaBlob: async function (presion, hoja) {
            var canUseTemplate = typeof window.generarPDFRequisicionBlob === "function" && typeof window.jsPDF === "function";
            if (!canUseTemplate) {
                throw new Error("Plantilla PDF operativa no disponible");
            }

            var idHoja = Number(hoja.hojaRequisicion_id || 0);
            if (!idHoja) throw new Error("ID de hoja invalido para PDF");

            var cabResp = await axios.post(itemsReqUrl, { accion: 5, id_Hoja: idHoja });
            var itemsResp = await axios.post(itemsReqUrl, { accion: 1, id_Hoja: idHoja });

            var requisicion = (cabResp.data && cabResp.data[0]) ? cabResp.data[0] : null;
            var items = Array.isArray(itemsResp.data) ? itemsResp.data : [];
            if (!requisicion) throw new Error("No se encontro la cabecera de la hoja");

            var obraId = Number(hoja.presiones_obra || presion.obraId || 0);
            var obraObj = {
                obras_nombre: hoja.obras_nombre || presion.obra || "SIN OBRA",
                ciudadesObras_nombre: ""
            };
            if (obraId > 0) {
                try {
                    var obraResp = await axios.post(itemsReqUrl, { accion: 8, obra: obraId });
                    if (obraResp.data && obraResp.data[0]) {
                        obraObj = obraResp.data[0];
                    }
                } catch (e) {
                    // Mantener fallback con nombre de obra ya disponible.
                }
            }

            var numeroReq = requisicion.requisicion_Numero || hoja.requisicion_Numero || ("REQ-" + (hoja.requisicion_id || ""));
            var claveReq = requisicion.requisicion_Clave || hoja.requisicion_Clave || "";
            var userName = (window.TF_CONTEXT && window.TF_CONTEXT.user && window.TF_CONTEXT.user.name)
                ? window.TF_CONTEXT.user.name
                : "Sistema";

            return window.generarPDFRequisicionBlob(numeroReq, claveReq, requisicion, userName, items, obraObj);
        },

        exportarExcelPresion: async function () {
            var presion = this.presionSeleccionada;
            var hojas = this.hojasPresionSeleccionada;
            if (!presion || !hojas.length) {
                Swal.fire("Sin datos", "Selecciona una presion con hojas ligadas.", "info");
                return;
            }
            try {
                var buffer = await this.generarExcelPresionBuffer(presion, hojas);
                var blob = new Blob([buffer], { type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" });
                var link = document.createElement("a");
                link.href = URL.createObjectURL(blob);
                link.download = this.nombreSeguro((presion.obra || "OBRA") + "_PRESION_" + (presion.presiones_id || "")) + "_" + this.fechaCompacta(presion.presiones_fechaCreacion) + ".xlsx";
                link.click();
                URL.revokeObjectURL(link.href);
            } catch (err) {
                console.error("Error exportando Excel de presion:", err);
                Swal.fire("Error", "No fue posible generar el Excel de la presion.", "error");
            }
        },

        exportarZipPresion: async function () {
            var presion = this.presionSeleccionada;
            var hojas = this.hojasPresionSeleccionada;
            if (!presion || !hojas.length) {
                Swal.fire("Sin datos", "Selecciona una presion con hojas ligadas.", "info");
                return;
            }
            if (!window.JSZip) {
                Swal.fire("ZIP no disponible", "No fue posible cargar JSZip para comprimir los archivos.", "warning");
                return;
            }
            this.exportandoZip = true;
            try {
                var zip = new window.JSZip();
                var excelBuffer = await this.generarExcelPresionBuffer(presion, hojas);

                var obraSafe = this.nombreSeguro(presion.obra || this.obraActualNombre || "OBRA");
                var fechaDesde = this.filtros.desde || this.isoDate(presion.presiones_fechaCreacion) || "SIN_FECHA";
                var fechaHasta = this.filtros.hasta || this.isoDate(presion.presiones_fechaCreacion) || "SIN_FECHA";
                var presionNum = this.nombreSeguro(presion.presiones_nombre || ("PRESION_" + presion.presiones_id));

                var baseName = obraSafe
                    + "__"
                    + fechaDesde.replace(/-/g, "")
                    + "_"
                    + fechaHasta.replace(/-/g, "")
                    + "__"
                    + presionNum;

                zip.file(baseName + "/" + baseName + ".xlsx", excelBuffer);

                for (var i = 0; i < hojas.length; i++) {
                    var h = hojas[i];
                    var pdfBlob = await this.generarPdfHojaBlob(presion, h);
                    var hojaNum = this.nombreSeguro(String(h.hojaRequisicion_numero || h.hojaRequisicion_id || (i + 1)));
                    zip.file(baseName + "/HOJA_" + hojaNum + ".pdf", pdfBlob);
                }

                var zipBlob = await zip.generateAsync({ type: "blob" });
                var link = document.createElement("a");
                link.href = URL.createObjectURL(zipBlob);
                link.download = baseName + ".zip";
                link.click();
                URL.revokeObjectURL(link.href);
            } catch (err) {
                console.error("Error exportando ZIP de presion:", err);
                Swal.fire("Error", "No fue posible generar el ZIP con Excel y PDFs.", "error");
            } finally {
                this.exportandoZip = false;
            }
        }
    ,
        init: async function () {
            this.cargando = true;
            try {
                await this.cargarCatalogos();
                await this.aplicarFiltros();
            } catch (err) {
                console.error("Error cargando KPI:", err);
                this.cargando = false;
            }
        }
    };
}
