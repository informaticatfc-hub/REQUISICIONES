var url = "../api/crud_new_requisicion.php";
var url2 = ".";

const appRequesition = new Vue({
    el: "#AppReq",
    data: {
        emisores: [],
        proveedores: [],
        Items: [],
        users: [],
        obras: [],
        selected_Provedor: "",
        //Datos del Emisor
        Emisor_Id: "",
        Emisor_Nombre: "",
        Emisor_RFC: "",
        Emisor_Adress: "",
        Emisor_Phone: "",
        Emisor_Fax: "",
        Emisor_ZipCode: "",
        //Datos del Proveedor
        Prov_Id: "",
        Prov_Nombre: "",
        Prov_RFC: "",
        Prov_Clabe: "",
        Prov_Cuenta: "",
        Prov_Email: "",
        Prov_Phone: "",
        Prov_SucBank: "",
        Prov_RefBank: "",
        Prov_Bank: "",
        //Datos del Item;
        Item_Nombre: "",
        Item_Unidad: "",
        Item_Cant: "",
        Item_Precio: "",
        Item_Lote: 0,
        //Retenciones
        RetFlete: false,
        RetFisica: false,
        RetResico: false,
        indexFlete: 0,
        indexFisica: 0,
        indexResico: 0,
        retenciones: 0,
        //Datos Generales
        PagoTrans: true,
        FormaPago: "Transferencia",
        Date_Req: "",
        IVA: 0,
        Total_Pagar: 0,
        SubTotal: 0,
        Subtotal_Mostrar: Number.parseFloat(0).toFixed(2),
        Total_Pagar_Mostrar: Number.parseFloat(0).toFixed(2),
        NameUser: "",
        htmlWinRet: "",
        observaciones: "",
        timeNow: ""
    },
    methods: {
        pagoTransaccionActivado: async function () {
            if (this.PagoTrans == false) {
                this.PagoTrans = true;
                this.FormaPago = "Transferencia";
                this.htmlWinRet = '<div class="col"><hr /><div class="row form-group mx-0 my-3"><div class="col d-flex flex-column"><label class="text-start py-2" for="Producto">Producto</label><textarea class="form-control" placeholder="Ingresa los datos de tu Producto" id="Producto" name="Producto"rows="3"></textarea></div></div><div class="row form-group mx-0 my-3"><div class="col-4 d-flex flex-column"><label class="text-start py-2" for="Unidad">Unidad</label><select class="form-select" aria-label="Default select example" id="Unidad"><option> Selecciona Clave</option><option value="DISEÑO">DISEÑO</option><option value="PIEZAS">PIEZAS</option><option value="BULTOS">BULTOS</option><option value="PESOS">PESOS</option><option value="LITROS">LITROS</option><option value="SERVICIOS">SERVICIO</option><option value="MENSUALIDAD">MENSUALIDAD</option><option value="RENTA">RENTA</option><option value="CUBETAS">CUBETAS</option><option value="TONELADAS">TONELADAS</option><option value="METROS">METROS</option><option value="METROS CUADRADOS">METROS CUADRADOS</option><option value="METROS CUBICOS">METROS CUBICOS</option><option value="KILOGRAMOS">KILOGRAMOS</option></select></div><div class="col-4 d-flex flex-column"><label class="text-start py-2" for="Cantidad">Cantidad</label><input type="number" min="0" placeholder="0" class="form-control" id="Cantidad" name="Cantidad"></div><div class="col-4 d-flex flex-column"><label class="text-start py-2" for="UnitedPrice">Precio Unitario</label><input type="number" min="0" placeholder="0" class="form-control" id="UnitedPrice" name="UnitedPrice"></div></div><hr /><div class="row mx-0 my-3"><div class="col"><h5 class="text-start fw-bold">Activa las Requisiciones Necesarias</h5></div></div><div class="row form-group mx-0 my-3"><div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="RetFlete"><label class="form-check-label" for="RetFlete">Retencion por Flete (4%)</label></div></div><div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="RetPersonaFIsica"><label class="form-check-label" for="RetPersonaFIsica">Retencion por Renta PersonaFisica(10.67%)</label></div></div></div><div class="row form-group mx-0 my-3"><div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="RetencionRESICO"><label class="form-check-label" for="RetencionRESICO">Retencion por RESICO (1.25%)</label></div></div></div></div>';
            }
            else {
                this.PagoTrans = false;
                this.FormaPago = "Efectivo";
                this.htmlWinRet = '<div class="col"><hr /><div class="row form-group mx-0 my-3"><div class="col d-flex flex-column"><label class="text-start py-2" for="Producto">Producto</label><textarea class="form-control" placeholder="Ingresa los datos de tu Producto" id="Producto" name="Producto"rows="3"></textarea></div></div><div class="row form-group mx-0 my-3"><div class="col-4 d-flex flex-column"><label class="text-start py-2" for="Unidad">Unidad</label><select class="form-select" aria-label="Default select example" id="Unidad"><option> Selecciona Clave</option><option value="DISEÑO">DISEÑO</option><option value="PIEZAS">PIEZAS</option><option value="BULTOS">BULTOS</option><option value="PESOS">PESOS</option><option value="LITROS">LITROS</option><option value="SERVICIOS">SERVICIO</option><option value="MENSUALIDAD">MENSUALIDAD</option><option value="RENTA">RENTA</option><option value="CUBETAS">CUBETAS</option><option value="TONELADAS">TONELADAS</option><option value="METROS">METROS</option><option value="METROS CUADRADOS">METROS CUADRADOS</option><option value="METROS CUBICOS">METROS CUBICOS</option><option value="KILOGRAMOS">KILOGRAMOS</option></select></div><div class="col-4 d-flex flex-column"><label class="text-start py-2" for="Cantidad">Cantidad</label><input type="number" min="0" placeholder="0" class="form-control" id="Cantidad" name="Cantidad"></div><div class="col-4 d-flex flex-column"><label class="text-start py-2" for="UnitedPrice">Precio Unitario</label><input type="number" min="0" placeholder="0" class="form-control" id="UnitedPrice" name="UnitedPrice"></div></div><hr /></div>';
            }
        },
        agregarRequisicion: async function () {
            const { value: formValues } = await Swal.fire({
                title: "¿Quieres guardar la Requisicion?",
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: "Guardar y Salir",
                denyButtonText: `Guardar`
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    this.guardarRequisicion(localStorage.getItem("IdPresion"));
                    Swal.fire("La requisición fue guardada con éxito", "", "success").then(() => {
                        window.location.href = url2 + "/requisiciones.php";
                    });
                } else if (result.isDenied) {
                    // Acción para "Guardar"
                    this.guardarRequisicion(localStorage.getItem("IdPresion"));
                    Swal.fire("La requisición fue guardada con éxito", "", "success").then(() => {
                        window.location.href = url2 + "/nueva_requisicion.php";
                    });
                }
            });
        },
        showModalAddItem: async function () {
            let ItemElement = {
                'Nombre': "",
                'Unidad': "",
                'Cantidad': "",
                'UnitedPrice': "",
                'IVA': "",
                'Retenciones': "",
                'bandFlete': false,
                'bandFisico': false,
                'bandResico': false,
                'STotal': "",
                'Lote': ""
            };
            const { value: formValues } = await Swal.fire({
                title: "Nuevo Item",
                html: this.htmlWinRet,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Agregar',
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#dc3545',
                preConfirm: () => {
                    if (this.PagoTrans == true) {
                        return [
                            ItemElement['Nombre'] = document.getElementById("Producto").value,
                            ItemElement['Unidad'] = document.getElementById("Unidad").value,
                            ItemElement['Cantidad'] = document.getElementById("Cantidad").value,
                            ItemElement['UnitedPrice'] = document.getElementById("UnitedPrice").value,
                            this.RetFlete = document.getElementById("RetFlete").checked,
                            this.RetFisica = document.getElementById("RetPersonaFIsica").checked,
                            this.RetResico = document.getElementById("RetencionRESICO").checked,
                        ];
                    }
                    else {
                        return [
                            ItemElement['Nombre'] = document.getElementById("Producto").value,
                            ItemElement['Unidad'] = document.getElementById("Unidad").value,
                            ItemElement['Cantidad'] = document.getElementById("Cantidad").value,
                            ItemElement['UnitedPrice'] = document.getElementById("UnitedPrice").value,
                        ];
                    }

                }
            });
            if (ItemElement['Nombre'] == '' || ItemElement['Cantidad'] == 0 || ItemElement['UnitedPrice'] == 0) {
                Swal.fire({
                    icon: 'info',
                    title: 'Datos Incompletos',
                });
            }
            else {
                this.agregarItem(ItemElement);

                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
                Toast.fire({
                    icon: 'success',
                    title: 'Item agregado'
                })
            }
        },
        agregarItem: async function (ItemElement) {
            if (this.PagoTrans == true) {
                var aux;
                this.indexFisica = 0;
                this.indexFlete = 0;
                this.indexResico = 0;
                aux = ItemElement['UnitedPrice'] * ItemElement['Cantidad'];
                if (this.RetFlete == true) {
                    this.indexFlete = aux * 0.04;
                    ItemElement['bandFlete'] = true;
                }
                if (this.RetFisica == true) {
                    this.indexFisica = aux * 0.1067;
                    ItemElement['bandFisico'] = true;
                }
                if (this.RetResico == true) {
                    this.indexResico = aux * 0.0125;
                    ItemElement['bandResico'] = true;
                }
                this.IVA = aux * 0.16;
                this.retenciones = this.indexFisica + this.indexFlete + this.indexResico;
                ItemElement['IVA'] = this.IVA;
                ItemElement['Retenciones'] = this.retenciones;
                ItemElement['STotal'] = aux - this.retenciones + this.IVA;
            }
            else {
                ItemElement['IVA'] = 0;
                ItemElement['Retenciones'] = 0;
                ItemElement['STotal'] = ItemElement['UnitedPrice'] * ItemElement['Cantidad'];
            }
            this.SubTotal = this.SubTotal + Number.parseFloat(ItemElement['STotal']);
            this.Subtotal_Mostrar = Number.parseFloat(this.SubTotal).toFixed(2);
            this.Item_Lote++;
            var HtmlTableRow = '<tr><th scope="row" class="py-3 celda_Item">' + this.Item_Lote + '</th><td class="py-3 celda_Item">' + ItemElement['Unidad'] + '</td><td class="py-3 celda_Item text-break">' + ItemElement['Nombre'] + '</td><td class="py-3 celda_Item">' + ItemElement['Cantidad'] + '</td><td class="py-3 celda_Item">$' + Number.parseFloat(ItemElement['UnitedPrice']).toFixed(2) + '</td><td class="py-3 celda_Item">+ $' + Number.parseFloat(this.IVA).toFixed(2) + '</td><td class="py-3 celda_Item">- $' + Number.parseFloat(this.retenciones).toFixed(2) + '</td><td class="py-3 celda_Item">$' + Number.parseFloat(ItemElement['STotal']).toFixed(2) + '</td></tr>';
            $('#Tabla_Items').append(HtmlTableRow);
            ItemElement['Lote'] = this.Item_Lote;
            this.Items.unshift(ItemElement);
            this.Total_Pagar = this.SubTotal;
            this.Total_Pagar_Mostrar = Number.parseFloat(this.Total_Pagar).toFixed(2);
            $("#PagoTransfs").prop('disabled', true);
            console.log(ItemElement);
            console.log(this.Items);
        },
        validarProv: async function (selected_Provedor) {
            axios.post(url, { accion: 4, id_prov: selected_Provedor }).then(response => {
                this.provedores = response.data;
                this.Prov_Id = this.provedores[0].proveedor_id;
                this.Prov_RFC = this.provedores[0].proveedor_rfc;
                this.Prov_Clabe = this.provedores[0].proveedor_clabe;
                this.Prov_Cuenta = this.provedores[0].proveedor_numeroCuenta;
                this.Prov_Email = this.provedores[0].proveedor_email;
                this.Prov_Phone = this.provedores[0].proveedor_telefono;
                this.Prov_SucBank = this.provedores[0].proveedor_sucursal;
                this.Prov_RefBank = this.provedores[0].proveedor_refBanco;
                this.Prov_Bank = this.provedores[0].proveedor_banco;
                console.log(this.provedores);
            });
        },
        agregarEmisor: function () {
            axios.post(url, { accion: 2 }).then(response => {
                this.emisores = response.data;
                this.Emisor_Id = this.emisores[0].emisor_id;
                this.Emisor_Nombre = this.emisores[0].emisor_nombre;
                this.Emisor_RFC = this.emisores[0].emisor_rfc;
                this.Emisor_Adress = this.emisores[0].emisor_direccion;
                this.Emisor_Phone = this.emisores[0].emisor_telefono;
                this.Emisor_ZipCode = this.emisores[0].emisor_zipCode;
                this.Emisor_Fax = this.emisores[0].emisor_fax;
                console.log(this.emisores);
            });
        },
        mostrarProvedores: function () {
            axios.post(url, { accion: 3 }).then(response => {
                this.proveedores = response.data;
                console.log(this.proveedores);
            });
        },
        guardarRequisicion: function (idPresion) {
            this.timeNow = this.getTime();
            const fecha = new Date();
            var year = fecha.getFullYear();
            var mes = fecha.getMonth() + 1;
            var dia = fecha.getDate();
            mes = mes < 10 ? '0' + mes : mes;
            dia = dia < 10 ? '0' + dia : dia;
            FechaReq = year + "-" + mes + "-" + dia;
            console.log(this.Items + " Forma de Pago " + this.FormaPago);
            axios.post(url, { accion: 1, time: this.timeNow, id_emisor: this.Emisor_Id, id_prov: this.Prov_Id, Total: this.Total_Pagar, formaPago: this.FormaPago, fechaSolicitud: FechaReq, items: JSON.stringify(this.Items), id_Presion: idPresion, observaciones: this.observaciones }).then(response => {
                console.log(response.data);
            });
        },
        consultarUsuario: function (user_id) {
            axios.post(url, { accion: 5, id_user: user_id }).then(response => {
                this.users = response.data;
                this.NameUser = this.users[0].user_name;
                console.log(this.users);
            });
        },
        listarObras: function () {
            axios.post(url, { accion: 6 }).then(response => {
                this.obras = response.data;
                console.log(this.obras);
            });
        },
        getTime: function () {
            const currentTime = new Date();
            const hours = currentTime.getHours();
            const minutes = currentTime.getMinutes();
            const seconds = currentTime.getSeconds();

            const formattedTime = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

            return formattedTime;
        },
        irPresion(idPresion) {
            localStorage.setItem("obraActiva", idPresion);
            window.location.href = url2 + "/presiones.php";
        },
        irMenuCatalago: function(){
            window.location.href = url2 + "/menu_catalago.php";
        }
    },
    created: function () {
        this.listarObras();
        this.agregarEmisor();
        this.consultarUsuario(localStorage.getItem("NameUser"));
        this.mostrarProvedores();
        this.htmlWinRet = '<div class="col"><hr /><div class="row form-group mx-0 my-3"><div class="col d-flex flex-column"><label class="text-start py-2" for="Producto">Producto</label><textarea class="form-control" placeholder="Ingresa los datos de tu Producto" id="Producto" name="Producto"rows="3"></textarea></div></div><div class="row form-group mx-0 my-3"><div class="col-4 d-flex flex-column"><label class="text-start py-2" for="Unidad">Unidad</label><select class="form-select" aria-label="Default select example" id="Unidad"><option> Selecciona Clave</option><option value="DISEÑO">DISEÑO</option><option value="PIEZAS">PIEZAS</option><option value="BULTOS">BULTOS</option><option value="PESOS">PESOS</option><option value="LITROS">LITROS</option><option value="SERVICIOS">SERVICIO</option><option value="MENSUALIDAD">MENSUALIDAD</option><option value="RENTA">RENTA</option><option value="CUBETAS">CUBETAS</option><option value="TONELADAS">TONELADAS</option><option value="METROS">METROS</option><option value="METROS CUADRADOS">METROS CUADRADOS</option><option value="METROS CUBICOS">METROS CUBICOS</option><option value="KILOGRAMOS">KILOGRAMOS</option></select></div><div class="col-4 d-flex flex-column"><label class="text-start py-2" for="Cantidad">Cantidad</label><input type="number" min="0" placeholder="0" class="form-control" id="Cantidad" name="Cantidad"></div><div class="col-4 d-flex flex-column"><label class="text-start py-2" for="UnitedPrice">Precio Unitario</label><input type="number" min="0" placeholder="0" class="form-control" id="UnitedPrice" name="UnitedPrice"></div></div><hr /><div class="row mx-0 my-3"><div class="col"><h5 class="text-start fw-bold">Activa las Requisiciones Necesarias</h5></div></div><div class="row form-group mx-0 my-3"><div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="RetFlete"><label class="form-check-label" for="RetFlete">Retencion por Flete (4%)</label></div></div><div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="RetPersonaFIsica"><label class="form-check-label" for="RetPersonaFIsica">Retencion por Renta PersonaFisica(10.67%)</label></div></div></div><div class="row form-group mx-0 my-3"><div class="col-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="RetencionRESICO"><label class="form-check-label" for="RetencionRESICO">Retencion por RESICO (1.25%)</label></div></div></div></div>';;

    },
    computed: {
    }
});
