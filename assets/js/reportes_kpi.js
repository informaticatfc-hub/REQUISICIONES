var kpiUrl = "../api/crud_all_presiones.php";
var kpiBase = ".";

const appKpiDireccion = new Vue({
    el: "#AppKpiDireccion",
    data: {
        obras: [],
        presionesRaw: [],
        reporteAgrupado: [],
        topObras: [],
        semaforoObras: [],
        alertasEjecutivas: [],
        filtros: {
            obraId: "0",
            periodo: "total",
            desde: "",
            hasta: ""
        },
        umbral: {
            medio: 5,
            alto: 15
        },
        kpi: {
            presiones: 0,
            total: 0,
            adeudo: 0
        },
        ejecutivo: {
            semanaActualLabel: "SEMANA ACTUAL",
            semanaAnteriorLabel: "SEMANA ANTERIOR",
            totalSemanaActual: 0,
            totalSemanaAnterior: 0,
            variacionSemana: 0,
            variacionTexto: "+0.00%",
            riesgoAltoCount: 0
        }
    },
    methods: {
        goDireccion: function () { window.location.href = kpiBase + "/direccion.php"; },
        goAutorizacion: function () { window.location.href = kpiBase + "/all_presiones.php"; },
        goIndex: function () { window.location.href = kpiBase + "/index.php"; },
        goLogout: function () { window.location.href = kpiBase + "/closeSesion.php"; },

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
            if (isNaN(d.getTime())) return "";
            return d.toISOString().slice(0, 10);
        },
        weekOfYear: function (dateObj) {
            var date = new Date(Date.UTC(dateObj.getFullYear(), dateObj.getMonth(), dateObj.getDate()));
            var dayNum = date.getUTCDay() || 7;
            date.setUTCDate(date.getUTCDate() + 4 - dayNum);
            var yearStart = new Date(Date.UTC(date.getUTCFullYear(), 0, 1));
            return Math.ceil((((date - yearStart) / 86400000) + 1) / 7);
        },
        weekLabel: function (dateObj) {
            return dateObj.getFullYear() + "-S" + this.weekOfYear(dateObj);
        },
        variacionClass: function (value) {
            if (value > 0) return "var-positive";
            if (value < 0) return "var-negative";
            return "var-neutral";
        },
        semaforoDotClass: function (estado) {
            if (estado === "Riesgo Alto") return "semaforo-risk";
            if (estado === "Riesgo Medio") return "semaforo-warn";
            return "semaforo-ok";
        },
        calcularCapaEjecutiva: function (filtradas) {
            var umbralMedio = Number(this.umbral.medio || 0);
            var umbralAlto = Number(this.umbral.alto || 0);
            if (umbralAlto < umbralMedio) {
                umbralAlto = umbralMedio;
                this.umbral.alto = umbralAlto;
            }

            if (!filtradas.length) {
                this.ejecutivo = {
                    semanaActualLabel: "SEMANA ACTUAL",
                    semanaAnteriorLabel: "SEMANA ANTERIOR",
                    totalSemanaActual: 0,
                    totalSemanaAnterior: 0,
                    variacionSemana: 0,
                    variacionTexto: "0.00%",
                    riesgoAltoCount: 0
                };
                this.topObras = [];
                this.semaforoObras = [];
                this.alertasEjecutivas = [];
                return;
            }

            var rowsWithDate = filtradas
                .map((r) => ({ row: r, d: new Date(r.presiones_fechaCreacion) }))
                .filter((x) => !isNaN(x.d.getTime()));

            var latestDate = rowsWithDate.length
                ? rowsWithDate.reduce((a, b) => (a.d > b.d ? a : b)).d
                : new Date();

            var prevDate = new Date(latestDate);
            prevDate.setDate(prevDate.getDate() - 7);

            var wkCurrent = this.weekLabel(latestDate);
            var wkPrev = this.weekLabel(prevDate);

            var totalCurrent = 0;
            var totalPrev = 0;

            rowsWithDate.forEach((x) => {
                var wk = this.weekLabel(x.d);
                var total = this.toNum(x.row.total_calculado);
                if (wk === wkCurrent) totalCurrent += total;
                if (wk === wkPrev) totalPrev += total;
            });

            var variacion = 0;
            if (totalPrev === 0) {
                variacion = totalCurrent > 0 ? 100 : 0;
            } else {
                variacion = ((totalCurrent - totalPrev) / totalPrev) * 100;
            }

            this.ejecutivo = {
                semanaActualLabel: wkCurrent,
                semanaAnteriorLabel: wkPrev,
                totalSemanaActual: totalCurrent,
                totalSemanaAnterior: totalPrev,
                variacionSemana: variacion,
                variacionTexto: (variacion >= 0 ? "+" : "") + variacion.toFixed(2) + "%",
                riesgoAltoCount: 0
            };

            var byObra = {};
            filtradas.forEach((r) => {
                var obra = r.obras_nombre || "SIN_OBRA";
                if (!byObra[obra]) {
                    byObra[obra] = {
                        obra: obra,
                        presiones: 0,
                        total: 0,
                        adeudo: 0,
                        desviacionPct: 0,
                        montoDesviado: 0,
                        estado: "Controlado"
                    };
                }
                byObra[obra].presiones += 1;
                byObra[obra].total += this.toNum(r.total_calculado);
                byObra[obra].adeudo += this.toNum(r.adeudo_calculado);
            });

            var obrasArr = Object.keys(byObra).map((k) => {
                var o = byObra[k];
                o.montoDesviado = Math.max(0, o.total - o.adeudo);
                o.desviacionPct = o.total > 0 ? (o.montoDesviado / o.total) * 100 : 0;
                if (o.desviacionPct > umbralAlto) o.estado = "Riesgo Alto";
                else if (o.desviacionPct > umbralMedio) o.estado = "Riesgo Medio";
                else o.estado = "Controlado";
                return o;
            });

            this.topObras = obrasArr
                .slice()
                .sort((a, b) => b.total - a.total)
                .slice(0, 10);

            this.semaforoObras = obrasArr
                .slice()
                .sort((a, b) => b.desviacionPct - a.desviacionPct)
                .slice(0, 10);

            this.alertasEjecutivas = obrasArr
                .filter((o) => o.estado !== "Controlado")
                .sort((a, b) => b.desviacionPct - a.desviacionPct)
                .slice(0, 5);

            this.ejecutivo.riesgoAltoCount = obrasArr.filter((o) => o.estado === "Riesgo Alto").length;
        },
        bucketLabel: function (row) {
            if (this.filtros.periodo === "mes") {
                return (row.presiones_fechaCreacion || "").slice(0, 7) || "SIN_FECHA";
            }
            if (this.filtros.periodo === "semana") {
                if (!row.presiones_fechaCreacion) return "SEM_SIN_FECHA";
                var d = new Date(row.presiones_fechaCreacion);
                if (isNaN(d.getTime())) return "SEM_SIN_FECHA";
                return d.getFullYear() + "-S" + this.weekOfYear(d);
            }
            return "TOTAL";
        },
        cargarCatalogos: async function () {
            var resp = await axios.post(kpiUrl, { accion: 2 });
            this.obras = resp.data || [];
        },
        cargarResumen: async function () {
            var resp = await axios.post(kpiUrl, { accion: 8 });
            this.presionesRaw = resp.data || [];
            this.aplicarFiltros();
        },
        aplicarFiltros: function () {
            var desde = this.filtros.desde;
            var hasta = this.filtros.hasta;
            var obraId = Number(this.filtros.obraId || 0);

            var filtradas = this.presionesRaw.filter((row) => {
                if (obraId > 0 && Number(row.presiones_obra) !== obraId) return false;

                var f = this.isoDate(row.presiones_fechaCreacion);
                if (desde && (!f || f < desde)) return false;
                if (hasta && (!f || f > hasta)) return false;

                return true;
            });

            var groups = {};
            filtradas.forEach((row) => {
                var obraNombre = row.obras_nombre || "SIN_OBRA";
                var periodo = this.bucketLabel(row);
                var key = obraNombre + "|" + periodo;
                if (!groups[key]) {
                    groups[key] = {
                        key: key,
                        obra: obraNombre,
                        periodo: periodo,
                        presiones: 0,
                        hojas: 0,
                        total: 0,
                        adeudo: 0,
                        ticket: 0
                    };
                }
                groups[key].presiones += 1;
                groups[key].hojas += this.toNum(row.hojas_ligadas);
                groups[key].total += this.toNum(row.total_calculado);
                groups[key].adeudo += this.toNum(row.adeudo_calculado);
            });

            this.reporteAgrupado = Object.keys(groups)
                .map((k) => groups[k])
                .map((g) => {
                    g.ticket = g.presiones > 0 ? (g.total / g.presiones) : 0;
                    return g;
                })
                .sort((a, b) => a.obra.localeCompare(b.obra) || a.periodo.localeCompare(b.periodo));

            this.kpi.presiones = filtradas.length;
            this.kpi.total = filtradas.reduce((s, r) => s + this.toNum(r.total_calculado), 0);
            this.kpi.adeudo = filtradas.reduce((s, r) => s + this.toNum(r.adeudo_calculado), 0);
            this.calcularCapaEjecutiva(filtradas);
        },
        exportarCsv: function () {
            var headers = ["OBRA", "PERIODO", "PRESIONES", "HOJAS_LIGADAS", "TOTAL_GASTOS", "TOTAL_ADEUDO", "TICKET_PROM"];
            var lines = [headers.join(",")];
            this.reporteAgrupado.forEach((r) => {
                var line = [
                    r.obra,
                    r.periodo,
                    r.presiones,
                    r.hojas,
                    this.toNum(r.total).toFixed(2),
                    this.toNum(r.adeudo).toFixed(2),
                    this.toNum(r.ticket).toFixed(2)
                ].map(function (v) {
                    return '"' + String(v).replace(/"/g, '""') + '"';
                }).join(",");
                lines.push(line);
            });

            var blob = new Blob([lines.join("\n")], { type: "text/csv;charset=utf-8;" });
            var a = document.createElement("a");
            a.href = URL.createObjectURL(blob);
            a.download = "kpi_direccion.csv";
            a.click();
            URL.revokeObjectURL(a.href);
        },
        exportarPdf: function () {
            if (!window.jspdf || !window.jspdf.jsPDF) {
                Swal.fire("PDF no disponible", "No se encontro la libreria jsPDF para generar el archivo.", "warning");
                return;
            }

            var jsPDF = window.jspdf.jsPDF;
            var doc = new jsPDF({ orientation: "landscape", unit: "mm", format: "a4" });
            var hoy = new Date();
            var fecha = hoy.toISOString().slice(0, 10);

            doc.setFontSize(14);
            doc.text("Reporte Ejecutivo KPI - Direccion", 14, 12);
            doc.setFontSize(9);
            doc.text("Fecha: " + fecha, 14, 18);

            var filtroTxt = "Obra: " + (this.filtros.obraId === "0" ? "Todas" : (this.obras.find((o) => String(o.obras_id) === String(this.filtros.obraId)) || {}).obras_nombre || "N/A")
                + " | Periodo: " + this.filtros.periodo
                + " | Desde: " + (this.filtros.desde || "-")
                + " | Hasta: " + (this.filtros.hasta || "-")
                + " | Umbral medio: " + this.umbral.medio + "%"
                + " | Umbral alto: " + this.umbral.alto + "%";
            doc.text(filtroTxt, 14, 23);

            doc.text("Total gastos: " + this.money(this.kpi.total) + "   Total adeudo: " + this.money(this.kpi.adeudo) + "   Variacion semanal: " + this.ejecutivo.variacionTexto, 14, 28);

            doc.autoTable({
                startY: 32,
                head: [["Top 10 Obras", "Presiones", "Total Gasto", "Total Adeudo"]],
                body: (this.topObras || []).map((r) => [r.obra, r.presiones, this.money(r.total), this.money(r.adeudo)]),
                theme: "grid",
                styles: { fontSize: 8 }
            });

            var y2 = doc.lastAutoTable ? doc.lastAutoTable.finalY + 6 : 60;
            doc.autoTable({
                startY: y2,
                head: [["Semaforo", "Obra", "Desviacion %", "Monto Desviado"]],
                body: (this.semaforoObras || []).map((r) => [r.estado, r.obra, r.desviacionPct.toFixed(2) + "%", this.money(r.montoDesviado)]),
                theme: "grid",
                styles: { fontSize: 8 }
            });

            var y3 = doc.lastAutoTable ? doc.lastAutoTable.finalY + 6 : 120;
            doc.autoTable({
                startY: y3,
                head: [["Obra", "Periodo", "Presiones", "Hojas", "Total Gasto", "Total Adeudo", "Ticket"]],
                body: (this.reporteAgrupado || []).map((r) => [r.obra, r.periodo, r.presiones, r.hojas, this.money(r.total), this.money(r.adeudo), this.money(r.ticket)]),
                theme: "grid",
                styles: { fontSize: 7 }
            });

            doc.save("reporte_ejecutivo_kpi_" + fecha + ".pdf");
        }
    },
    created: async function () {
        try {
            await this.cargarCatalogos();
            await this.cargarResumen();
        } catch (error) {
            console.error("Error cargando reportes KPI:", error);
            Swal.fire("Error", "No se pudo cargar la informacion KPI", "error");
        }
    }
});
