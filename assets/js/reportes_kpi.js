var kpiUrl = "../api/crud_all_presiones.php";
var kpiBase = ".";

const appKpiDireccion = new Vue({
    el: "#AppKpiDireccion",
    data: {
        obras: [],
        presionesRaw: [],
        reporteAgrupado: [],
        consultaAplicada: false,
        filtros: {
            obraId: "",
            desde: "",
            hasta: ""
        },
        kpi: {
            presiones: 0,
            total: 0,
            adeudo: 0
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

        cargarCatalogos: async function () {
            var resp = await axios.post(kpiUrl, { accion: 2 });
            this.obras = resp.data || [];
        },
        cargarResumen: async function () {
            var resp = await axios.post(kpiUrl, { accion: 8 });
            this.presionesRaw = resp.data || [];
        },

        aplicarFiltros: async function () {
            if (!this.presionesRaw.length) {
                await this.cargarResumen();
            }

            var obraId = Number(this.filtros.obraId || 0);
            var desde = this.filtros.desde;
            var hasta = this.filtros.hasta;

            if (obraId <= 0) {
                this.consultaAplicada = false;
                this.reporteAgrupado = [];
                this.kpi.presiones = 0;
                this.kpi.total = 0;
                this.kpi.adeudo = 0;
                Swal.fire("Selecciona una obra", "Para consultar el desglose debes elegir primero una obra.", "info");
                return;
            }

            if (desde && hasta && desde > hasta) {
                Swal.fire("Rango invalido", "La fecha Desde no puede ser mayor a Hasta.", "warning");
                return;
            }

            var filtradas = this.presionesRaw.filter((row) => {
                if (Number(row.presiones_obra) !== obraId) return false;

                var f = this.isoDate(row.presiones_fechaCreacion);
                if (desde && (!f || f < desde)) return false;
                if (hasta && (!f || f > hasta)) return false;

                return true;
            });

            var groups = {};
            filtradas.forEach((row) => {
                var obraNombre = row.obras_nombre || "SIN_OBRA";
                var fecha = this.isoDate(row.presiones_fechaCreacion) || "SIN_FECHA";
                var key = obraNombre + "|" + fecha;

                if (!groups[key]) {
                    groups[key] = {
                        key: key,
                        obra: obraNombre,
                        fecha: fecha,
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
                .sort((a, b) => a.fecha.localeCompare(b.fecha));

            this.kpi.presiones = filtradas.length;
            this.kpi.total = filtradas.reduce((s, r) => s + this.toNum(r.total_calculado), 0);
            this.kpi.adeudo = filtradas.reduce((s, r) => s + this.toNum(r.adeudo_calculado), 0);
            this.consultaAplicada = true;
        },

        exportarCsv: function () {
            if (!this.consultaAplicada || !this.reporteAgrupado.length) {
                Swal.fire("Sin datos", "Primero aplica filtros para generar el desglose.", "info");
                return;
            }

            var headers = ["OBRA", "FECHA", "PRESIONES", "HOJAS_LIGADAS", "TOTAL_GASTOS", "TOTAL_ADEUDO", "TICKET_PROM"];
            var lines = [headers.join(",")];

            this.reporteAgrupado.forEach((r) => {
                var line = [
                    r.obra,
                    r.fecha,
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
            a.download = "desglose_gastos_por_fecha.csv";
            a.click();
            URL.revokeObjectURL(a.href);
        },

        exportarPdf: function () {
            if (!this.consultaAplicada || !this.reporteAgrupado.length) {
                Swal.fire("Sin datos", "Primero aplica filtros para generar el desglose.", "info");
                return;
            }
            if (!window.jspdf || !window.jspdf.jsPDF) {
                Swal.fire("PDF no disponible", "No se encontro la libreria jsPDF para generar el archivo.", "warning");
                return;
            }

            var jsPDF = window.jspdf.jsPDF;
            var doc = new jsPDF({ orientation: "landscape", unit: "mm", format: "a4" });
            var hoy = new Date();
            var fechaGen = hoy.toISOString().slice(0, 10);

            doc.setFontSize(14);
            doc.text("Desglose Profesional de Gastos por Fecha", 14, 12);
            doc.setFontSize(9);
            doc.text("Fecha de generacion: " + fechaGen, 14, 18);

            var obraNombre = (this.obras.find((o) => String(o.obras_id) === String(this.filtros.obraId)) || {}).obras_nombre || "N/A";
            var filtroTxt = "Obra: " + obraNombre
                + " | Desde: " + (this.filtros.desde || "-")
                + " | Hasta: " + (this.filtros.hasta || "-");
            doc.text(filtroTxt, 14, 23);

            doc.text("Presiones: " + this.kpi.presiones + "   Total gastos: " + this.money(this.kpi.total) + "   Total adeudo: " + this.money(this.kpi.adeudo), 14, 28);

            doc.autoTable({
                startY: 32,
                head: [["Obra", "Fecha", "Presiones", "Hojas", "Total Gastos", "Total Adeudo", "Ticket Promedio"]],
                body: (this.reporteAgrupado || []).map((r) => [r.obra, r.fecha, r.presiones, r.hojas, this.money(r.total), this.money(r.adeudo), this.money(r.ticket)]),
                theme: "grid",
                styles: { fontSize: 8 }
            });

            doc.save("desglose_gastos_fecha_" + fechaGen + ".pdf");
        }
    },
    created: async function () {
        try {
            await this.cargarCatalogos();
        } catch (error) {
            console.error("Error cargando reportes KPI:", error);
            Swal.fire("Error", "No se pudo cargar la informacion KPI", "error");
        }
    }
});
