var url = "../api/crud_nueva_hoja.php";
var url2 = ".";

function nuevaHojaApp() {
    return {
        emisores: [],
        proveedores: [],
        Items: [],
        users: [],
        obras: [],
        selected_Provedor: "",
        // C-M1: búsqueda combinada por nombre/RFC/CLABE
        provSearchText: "",
        provDropOpen: false,
        Prov_Nombre_Display: "",
        get provSearchResults() {
            var q = (this.provSearchText || "").trim().toLowerCase();
            if (!q) return [];
            return this.proveedores.filter(function (p) {
                return (String(p.proveedor_nombre || "").toLowerCase().indexOf(q) !== -1)
                    || (String(p.proveedor_rfc || "").toLowerCase().indexOf(q) !== -1)
                    || (String(p.proveedor_clabe || "").toLowerCase().indexOf(q) !== -1);
            }).slice(0, 20);
        },
        seleccionarProveedor: function (p) {
            this.provSearchText = p.proveedor_nombre;
            this.provDropOpen   = false;
            this.Prov_Id        = p.proveedor_id;
            this.Prov_Nombre_Display = p.proveedor_nombre;
            this.Prov_RFC       = p.proveedor_rfc        || "";
            this.Prov_Clabe     = p.proveedor_clabe      || "";
            this.Prov_Cuenta    = p.proveedor_numeroCuenta || "";
            this.Prov_Email     = p.proveedor_email      || "";
            this.Prov_Phone     = p.proveedor_telefono   || "";
            this.Prov_SucBank   = p.proveedor_sucursal   || "";
            this.Prov_RefBank   = p.proveedor_refBanco   || "";
            this.Prov_Bank      = p.proveedor_banco      || "";
            this.Prov_BankCard  = p.presiones_tarjetaBanco || "";
        },
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
        Prov_BankCard: "",
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
        RetISR: false,
        indexFlete: 0,
        indexFisica: 0,
        indexResico: 0,
        indexISR: 0,
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
        timeNow: "",
        idHoja: "",
        conceptoUnico: false,
        conceptoUnicoText: ""
    ,
        pagoTransaccionActivado: async function () {
            if (this.PagoTrans == false) {
                this.PagoTrans = true;
                this.FormaPago = "Transferencia";
                this.htmlWinRet = `
                <div class="col">
                    <hr />
                    <div class="row form-group mx-0 my-3">
                        <div class="col d-flex flex-column"><label class="text-start py-2" for="Producto">Producto</label>
                            <textarea class="form-control" placeholder="Ingresa los datos de tu Producto" id="Producto" name="Producto" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="row form-group mx-0 my-3">
                        <div class="col-4 d-flex flex-column"><label class="text-start py-2" for="Unidad">Unidad</label>
                            <select class="form-select" aria-label="Default select example" id="Unidad">
                                <option value="Selecciona Unidad">SELECCIONA UNIDAD</option>
                                <option value="DISEÑO">DISEÑO</option>
                                <option value="PIEZAS">PIEZAS</option>
                                <option value="BULTOS">BULTOS</option>
                                <option value="PESOS">PESOS</option>
                                <option value="LITROS">LITROS</option>
                                <option value="SERVICIOS">SERVICIO</option>
                                <option value="MENSUALIDAD">MENSUALIDAD</option>
                                <option value="RENTA">RENTA</option>
                                <option value="CUBETAS">CUBETAS</option>
                                <option value="TONELADAS">TONELADAS</option>
                                <option value="METROS">METROS</option>
                                <option value="METROS CUADRADOS">METROS CUADRADOS</option>
                                <option value="METROS CUBICOS">METROS CUBICOS</option>
                                <option value="KILOGRAMOS">KILOGRAMOS</option>
                                <option value="VIAJES">VIAJES</option>
                            </select>
                        </div>
                        <div class="col-4 d-flex flex-column"><label class="text-start py-2" for="Cantidad">Cantidad</label>
                            <input type="number" min="0" placeholder="0" class="form-control" id="Cantidad" name="Cantidad">
                        </div>
                        <div class="col-4 d-flex flex-column"><label class="text-start py-2" for="UnitedPrice">Precio Unitario</label>
                            <input type="number" min="0" placeholder="0" class="form-control" id="UnitedPrice" name="UnitedPrice">
                        </div>
                    </div>
                    <hr />
                    <div class="row mx-0 my-3">
                        <div class="col">
                            <h5 class="text-start fw-bold">Activa las Requisiciones Necesarias</h5>
                        </div>
                    </div>
                    <div class="row form-group mx-0 my-3">
                        <div class="col-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="RetFlete">
                                <label class="form-check-label" for="RetFlete">Retencion por Flete (4%)</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="RetPersonaFIsica">
                                <label class="form-check-label" for="RetPersonaFIsica">Retencion por Renta PersonaFisica(10.67%)</label>
                            </div>
                        </div>
                    </div>
                    <div class="row form-group mx-0 my-3">
                        <div class="col-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="RetencionRESICO">
                                <label class="form-check-label" for="RetencionRESICO">Retencion por RESICO (1.25%)</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="RetencionISR">
                                <label class="form-check-label" for="RetencionISR">Retencion por ISR (10%)</label>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            }
            else {
                this.PagoTrans = false;
                this.FormaPago = "Efectivo";
                this.htmlWinRet = `
                <div class="col">
                    <hr />
                    <div class="row form-group mx-0 my-3">
                        <div class="col d-flex flex-column">
                            <label class="text-start py-2" for="Producto">Producto</label>
                            <textarea class="form-control" placeholder="Ingresa los datos de tu Producto" id="Producto" name="Producto" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="row form-group mx-0 my-3">
                        <div class="col-4 d-flex flex-column">
                            <label class="text-start py-2" for="Unidad">Unidad</label>
                            <select class="form-select" aria-label="Default select example" id="Unidad">
                                <option value="Selecciona Unidad">SELECCIONA UNIDAD</option>
                                <option value="DISEÑO">DISEÑO</option>
                                <option value="PIEZAS">PIEZAS</option>
                                <option value="BULTOS">BULTOS</option>
                                <option value="PESOS">PESOS</option>
                                <option value="LITROS">LITROS</option>
                                <option value="SERVICIOS">SERVICIO</option>
                                <option value="MENSUALIDAD">MENSUALIDAD</option>
                                <option value="RENTA">RENTA</option>
                                <option value="CUBETAS">CUBETAS</option>
                                <option value="TONELADAS">TONELADAS</option>
                                <option value="METROS">METROS</option>
                                <option value="METROS CUADRADOS">METROS CUADRADOS</option>
                                <option value="METROS CUBICOS">METROS CUBICOS</option>
                                <option value="KILOGRAMOS">KILOGRAMOS</option>
                                <option value="VIAJES">VIAJES</option>
                            </select>
                        </div>
                        <div class="col-4 d-flex flex-column">
                            <label class="text-start py-2" for="Cantidad">Cantidad</label>
                            <input type="number" min="0" placeholder="0" class="form-control" id="Cantidad" name="Cantidad">
                        </div>
                        <div class="col-4 d-flex flex-column">
                            <label class="text-start py-2" for="UnitedPrice">Precio Unitario</label>
                            <input type="number" min="0" placeholder="0" class="form-control" id="UnitedPrice" name="UnitedPrice">
                        </div>
                    </div>
                    <hr />
                </div>
                `;
            }
        },
        agregarRequisicion: async function () {
            const { value: formValues } = await Swal.fire({
                title: "¿Quieres guardar la Requisicion?",
                showDenyButton: true,
                showCancelButton: true,
                confirmButtonText: "Guardar y Continuar",
                denyButtonText: "Guardar y Salir"
            }).then((result) => {
                /* Read more about isConfirmed, isDenied below */
                if (result.isConfirmed) {
                    this.guardarRequisicion(localStorage.getItem("idRequisicion"));
                    Swal.fire("La requisición fue guardada con éxito", "", "success").then(() => {
                        localStorage.setItem("idHoja", this.idHoja);
                        localStorage.setItem("validate", false);
                        window.location.href = url2 + "/items_requisicion.php"; // Cambia esto por la URL de tu página
                    });
                } else if (result.isDenied) {
                    // Acción para "Guardar"
                    this.guardarRequisicion(localStorage.getItem("idRequisicion"));
                    Swal.fire("La requisición fue guardada con éxito", "", "success").then(() => {
                        // Redirigir a otra página
                        window.location.href = url2 + "/hojas_requisicion.php"; // Cambia esto por la URL de tu página
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
                'bandISR' : false,
                'STotal': "",
                'Lote': ""
            };
            const { value: formValues } = await Swal.fire({
                title: "Nuevo Item",
                html: this.htmlWinRet,
                customClass: {
                    popup: 'custom-popup' // Clase personalizada
                },
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Agregar',
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#dc3545',
                preConfirm: () => {
                    const productoValue = this.eliminarSaltosDeLinea(document.getElementById("Producto").value);
                    if (this.PagoTrans == true) {
                        ItemElement['Nombre'] = productoValue;
                        ItemElement['Unidad'] = document.getElementById("Unidad").value;
                        ItemElement['Cantidad'] = document.getElementById("Cantidad").value;
                        ItemElement['UnitedPrice'] = document.getElementById("UnitedPrice").value;
                        this.RetFlete = document.getElementById("RetFlete").checked;
                        this.RetFisica = document.getElementById("RetPersonaFIsica").checked;
                        this.RetResico = document.getElementById("RetencionRESICO").checked;
                        this.RetISR = document.getElementById("RetencionISR").checked;
                        if (productoValue.length > 272) {
                            Swal.showValidationMessage('El campo Producto no puede exceder los 200 caracteres.');
                            return false;
                        }
                        if (!productoValue || ItemElement['Unidad'] === "Selecciona Unidad" || !ItemElement['Cantidad'] || !ItemElement['UnitedPrice']) {
                            Swal.showValidationMessage('Por favor completa todos los campos');
                            return false;
                        }
                        return true;
                    } else {
                        ItemElement['Nombre'] = productoValue;
                        ItemElement['Unidad'] = document.getElementById("Unidad").value;
                        ItemElement['Cantidad'] = document.getElementById("Cantidad").value;
                        ItemElement['UnitedPrice'] = document.getElementById("UnitedPrice").value;
                        if (productoValue.length > 272) {
                            Swal.showValidationMessage('El campo Producto no puede exceder los 200 caracteres.');
                            return false;
                        }
                        if (!productoValue || ItemElement['Unidad'] === "Selecciona Unidad" || !ItemElement['Cantidad'] || !ItemElement['UnitedPrice']) {
                            Swal.showValidationMessage('Por favor completa todos los campos');
                            return false;
                        }
                        return true;
                    }
                }
            });
            if (formValues) {
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
                });
            };
        },
        agregarItem: async function (ItemElement) {
            if(this.Items.length > 4)
            {
                const swalWithBootstrapButtons = await Swal.mixin({
                    customClass: {
                        confirmButton: "btn btn-success",
                        cancelButton: "btn btn-danger"
                    },
                    buttonsStyling: true
                });
                swalWithBootstrapButtons.fire({
                    title: "¡ESPERA!",
                    text: 'Estas agregando muchos conceptos en la hoja y podras saturar la presion con muchos conceptos que se pueden resumir en un concepto unico ¿Te gustaria hacer un concepto resumido unico?',
                    icon: "info",
                    showCancelButton: true,
                    confirmButtonText: "SI",
                    cancelButtonText: "NO",
                    reverseButtons: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        this.conceptoUnico = true;
                        swalWithBootstrapButtons.fire({
                            title: "LISTO",
                            text: "Se agrego un campo para que puedas rescribir el concepto unico y puedes seguir agregando los conceptos que quieras",
                            icon: "success"
                        })
                    }
                });
            }
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
                    this.indexFisica = aux * 0.106667;
                    ItemElement['bandFisico'] = true;
                }
                if (this.RetResico == true) {
                    this.indexResico = aux * 0.0125;
                    ItemElement['bandResico'] = true;
                }
                if(this.RetISR == true){
                    this.indexISR = aux * 0.1;
                    ItemElement['bandISR'] = true;
                }
                this.IVA = aux * 0.16;
                this.retenciones = this.indexFisica + this.indexFlete + this.indexResico + this.indexISR;
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
            ItemElement['Lote'] = this.Item_Lote;
            this.Items.unshift(ItemElement);
            this.Total_Pagar = this.SubTotal;
            this.Total_Pagar_Mostrar = Number.parseFloat(this.Total_Pagar).toFixed(2);
            $("#PagoTransfs").prop('disabled', true);
            console.log(ItemElement);
            console.log(this.Items);
        },
        validarProv: async function (selected_Provedor) {
            axios.post(url, { accion: 4, id_prov:this.obtenerNumeroAntesDelGuion(selected_Provedor)}).then(response => {
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
                 this.Prov_BankCard = this.provedores[0].presiones_tarjetaBanco;
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
        guardarRequisicion: function (idReq) {
            this.timeNow = this.getTime();
            const fecha = new Date();
            var year = fecha.getFullYear();
            var mes = fecha.getMonth() + 1;
            var dia = fecha.getDate();
            mes = mes < 10 ? '0' + mes : mes;
            dia = dia < 10 ? '0' + dia : dia;
            FechaReq = year + "-" + mes + "-" + dia;
            console.log(this.Items);
            axios.post(url, { accion: 1, time: this.timeNow, id_emisor: this.Emisor_Id, id_prov: this.Prov_Id, Total: this.Total_Pagar, formaPago: this.FormaPago, fechaSolicitud: FechaReq, items: JSON.stringify(this.Items), idReq: idReq, observaciones: this.observaciones, conceptoUnico: this.conceptoUnicoText })
            .then(response => {
                this.idHoja = response.data;
                console.log(response.data);
            }); 
        },
        consultarUsuario: function () {
            // Fase 5: identidad por sesion server-side (sin localStorage.NameUser)
            axios.post(url, { accion: 5 }).then(response => {
                this.users = response.data || [];
                if (this.users[0] && this.users[0].user_name) {
                    this.NameUser = this.users[0].user_name;
                }
            }).catch(err => console.error("consultarUsuario:", err));
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
        irObra(idObra) {
            localStorage.setItem("obraActiva", idObra);
            window.location.href = url2 + "/obras.php";
        },
        irDireecion: function () {
            window.location.href = url2 + "/direccion.php";
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
        obtenerNumeroAntesDelGuion: function (cadena) {
            // Buscar el índice del primer guion
            const indiceGuion = cadena.indexOf('-');

            // Si no se encuentra un guion, retornar null o un mensaje
            if (indiceGuion === -1) {
                return null; // O puedes retornar un mensaje como "No se encontró un guion"
            }

            // Obtener la parte de la cadena antes del guion
            const parteAntesDelGuion = cadena.substring(0, indiceGuion).trim();

            // Verificar si la parte antes del guion es un número
            const numero = parseInt(parteAntesDelGuion, 10);

            // Retornar el número si es válido, de lo contrario retornar null
            return isNaN(numero) ? null : numero;
        },
        eliminarSaltosDeLinea: function(cadena) {
            // Utiliza el método replace para eliminar los saltos de línea
            return cadena.replace(/(\r\n|\n|\r)/g, "");
        },
        irMenuCatalago: function(){
            window.location.href = url2 + "/menu_catalago.php";
        },
        editarItem: async function(indice){
            const item = this.Items[indice];
            const { value: formValues } = await Swal.fire({
                title: "Modificar Item",
                html: `
                    <div class="col">
                        <hr />
                        <div class="row form-group mx-0 my-3">
                            <div class="col d-flex flex-column"><label class="text-start py-2" for="ProductoEdit">Producto</label>
                                <textarea class="form-control" id="ProductoEdit" rows="3">${item.Nombre}</textarea>
                            </div>
                        </div>
                        <div class="row form-group mx-0 my-3">
                            <div class="col-4 d-flex flex-column"><label class="text-start py-2" for="UnidadEdit">Unidad</label>
                                <select class="form-select" id="UnidadEdit">
                                    <option value="DISEÑO" ${item.Unidad === 'DISEÑO' ? 'selected' : ''}>DISEÑO</option>
                                    <option value="PIEZAS" ${item.Unidad === 'PIEZAS' ? 'selected' : ''}>PIEZAS</option>
                                    <option value="BULTOS" ${item.Unidad === 'BULTOS' ? 'selected' : ''}>BULTOS</option>
                                    <option value="PESOS" ${item.Unidad === 'PESOS' ? 'selected' : ''}>PESOS</option>
                                    <option value="LITROS" ${item.Unidad === 'LITROS' ? 'selected' : ''}>LITROS</option>
                                    <option value="SERVICIOS" ${item.Unidad === 'SERVICIOS' ? 'selected' : ''}>SERVICIOS</option>
                                    <option value="MENSUALIDAD" ${item.Unidad === 'MENSUALIDAD' ? 'selected' : ''}>MENSUALIDAD</option>
                                    <option value="RENTA" ${item.Unidad === 'RENTA' ? 'selected' : ''}>RENTA</option>
                                    <option value="CUBETAS" ${item.Unidad === 'CUBETAS' ? 'selected' : ''}>CUBETAS</option>
                                    <option value="TONELADAS" ${item.Unidad === 'TONELADAS' ? 'selected' : ''}>TONELADAS</option>
                                    <option value="METROS" ${item.Unidad === 'METROS' ? 'selected' : ''}>METROS</option>
                                    <option value="METROS CUADRADOS" ${item.Unidad === 'METROS CUADRADOS' ? 'selected' : ''}>METROS CUADRADOS</option>
                                    <option value="METROS CUBICOS" ${item.Unidad === 'METROS CUBICOS' ? 'selected' : ''}>METROS CUBICOS</option>
                                    <option value="KILOGRAMOS" ${item.Unidad === 'KILOGRAMOS' ? 'selected' : ''}>KILOGRAMOS</option>
                                    <option value="VIAJES" ${item.Unidad === 'VIAJES' ? 'selected' : ''}>VIAJES</option>
                                </select>
                            </div>
                            <div class="col-4 d-flex flex-column"><label class="text-start py-2" for="CantidadEdit">Cantidad</label>
                                <input type="number" min="0" class="form-control" id="CantidadEdit" value="${item.Cantidad}">
                            </div>
                            <div class="col-4 d-flex flex-column"><label class="text-start py-2" for="PrecioEdit">Precio Unitario</label>
                                <input type="number" min="0" class="form-control" id="PrecioEdit" value="${item.UnitedPrice}">
                            </div>
                        </div>
                        ${this.PagoTrans ? `
                        <hr />
                        <div class="row mx-0 my-3">
                            <div class="col">
                                <h5 class="text-start fw-bold">Retenciones</h5>
                            </div>
                        </div>
                        <div class="row form-group mx-0 my-3">
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="RetFleteEdit" ${item.bandFlete ? 'checked' : ''}>
                                    <label class="form-check-label" for="RetFleteEdit">Flete (4%)</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="RetFisicaEdit" ${item.bandFisico ? 'checked' : ''}>
                                    <label class="form-check-label" for="RetFisicaEdit">Persona Física (10.67%)</label>
                                </div>
                            </div>
                        </div>
                        <div class="row form-group mx-0 my-3">
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="RetResicoEdit" ${item.bandResico ? 'checked' : ''}>
                                    <label class="form-check-label" for="RetResicoEdit">RESICO (1.25%)</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="RetISREdit" ${item.bandISR ? 'checked' : ''}>
                                    <label class="form-check-label" for="RetISREdit">ISR (10%)</label>
                                </div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Actualizar',
                confirmButtonColor: '#0d6efd',
                cancelButtonColor: '#dc3545',
                preConfirm: () => {
                    const producto = document.getElementById('ProductoEdit').value;
                    const unidad = document.getElementById('UnidadEdit').value;
                    const cantidad = document.getElementById('CantidadEdit').value;
                    const precio = document.getElementById('PrecioEdit').value;

                    if (!producto || !unidad || !cantidad || !precio) {
                        Swal.showValidationMessage('Por favor completa todos los campos');
                        return false;
                    }
                    return {producto, unidad, cantidad: parseFloat(cantidad), precio: parseFloat(precio)};
                }
            });

            if (formValues) {
                const aux = formValues.cantidad * formValues.precio;
                const totalAnterior = item.STotal;

                item.Nombre = this.eliminarSaltosDeLinea(formValues.producto);
                item.Unidad = formValues.unidad;
                item.Cantidad = formValues.cantidad;
                item.UnitedPrice = formValues.precio;

                if (this.PagoTrans) {
                    item.bandFlete = document.getElementById('RetFleteEdit').checked;
                    item.bandFisico = document.getElementById('RetFisicaEdit').checked;
                    item.bandResico = document.getElementById('RetResicoEdit').checked;
                    item.bandISR = document.getElementById('RetISREdit').checked;

                    let ret = 0;
                    if (item.bandFlete) ret += aux * 0.04;
                    if (item.bandFisico) ret += aux * 0.106667;
                    if (item.bandResico) ret += aux * 0.0125;
                    if (item.bandISR) ret += aux * 0.1;

                    item.IVA = aux * 0.16;
                    item.Retenciones = ret;
                    item.STotal = aux - ret + item.IVA;
                } else {
                    item.IVA = 0;
                    item.Retenciones = 0;
                    item.STotal = aux;
                }

                this.SubTotal = this.SubTotal - totalAnterior + item.STotal;
                this.Total_Pagar = this.SubTotal;
                this.Total_Pagar_Mostrar = Number.parseFloat(this.Total_Pagar).toFixed(2);

                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });
                Toast.fire({
                    icon: 'success',
                    title: 'Item actualizado'
                });
            }
        },
        eliminarItem: function(indice){
            var totalAnterior = this.Items[indice]['STotal'];
            this.SubTotal = Number.parseFloat(this.Total_Pagar_Mostrar) - Number.parseFloat(totalAnterior)
            this.Total_Pagar_Mostrar = (this.SubTotal).toFixed(2);
            this.Items.splice(indice,1);
            this.recalcularLotes();
            if(this.Items.length == 0){
                 $("#PagoTransfs").prop('disabled', false);
            }
        },
        recalcularLotes: function(){
            var index = 0;

            for(index = 0; index < this.Items.length;index++)
            {
                this.Items[index]['Lote'] = String(index + 1);
            }
            this.Item_Lote = this.Items.length;
        }
    ,
    init: function () {
        this.listarObras();
        this.agregarEmisor();
        this.consultarUsuario();
        this.mostrarProvedores();
        this.htmlWinRet = `
            <div class="col">
                <hr />
                <div class="row form-group mx-0 my-3">
                    <div class="col d-flex flex-column"><label class="text-start py-2" for="Producto">Producto</label>
                        <textarea class="form-control" placeholder="Ingresa los datos de tu Producto" id="Producto" name="Producto" rows="3"></textarea>
                    </div>
                </div>
                <div class="row form-group mx-0 my-3">
                    <div class="col-4 d-flex flex-column"><label class="text-start py-2" for="Unidad">Unidad</label>
                        <select class="form-select" aria-label="Default select example" id="Unidad">
                            <option value="Selecciona Unidad">SELECCIONA UNIDAD</option>
                            <option value="DISEÑO">DISEÑO</option>
                            <option value="PIEZAS">PIEZAS</option>
                            <option value="BULTOS">BULTOS</option>
                            <option value="PESOS">PESOS</option>
                            <option value="LITROS">LITROS</option>
                            <option value="SERVICIOS">SERVICIO</option>
                            <option value="MENSUALIDAD">MENSUALIDAD</option>
                            <option value="RENTA">RENTA</option>
                            <option value="CUBETAS">CUBETAS</option>
                            <option value="TONELADAS">TONELADAS</option>
                            <option value="METROS">METROS</option>
                            <option value="METROS CUADRADOS">METROS CUADRADOS</option>
                            <option value="METROS CUBICOS">METROS CUBICOS</option>
                            <option value="KILOGRAMOS">KILOGRAMOS</option>
                            <option value="VIAJES">VIAJES</option>
                        </select>
                    </div>
                    <div class="col-4 d-flex flex-column"><label class="text-start py-2" for="Cantidad">Cantidad</label>
                        <input type="number" min="0" placeholder="0" class="form-control" id="Cantidad" name="Cantidad">
                    </div>
                    <div class="col-4 d-flex flex-column"><label class="text-start py-2" for="UnitedPrice">Precio Unitario</label>
                        <input type="number" min="0" placeholder="0" class="form-control" id="UnitedPrice" name="UnitedPrice">
                    </div>
                </div>
                <hr />
                <div class="row mx-0 my-3">
                    <div class="col">
                        <h5 class="text-start fw-bold">Activa las Requisiciones Necesarias</h5>
                    </div>
                </div>
                <div class="row form-group mx-0 my-3">
                    <div class="col-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="RetFlete">
                            <label class="form-check-label" for="RetFlete">Retencion por Flete (4%)</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="RetPersonaFIsica">
                            <label class="form-check-label" for="RetPersonaFIsica">Retencion por Renta PersonaFisica(10.67%)</label>
                        </div>
                    </div>
                </div>
                <div class="row form-group mx-0 my-3">
                    <div class="col-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="RetencionRESICO">
                            <label class="form-check-label" for="RetencionRESICO">Retencion por RESICO (1.25%)</label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="RetencionISR">
                            <label class="form-check-label" for="RetencionISR">Retencion por ISR (10%)</label>
                        </div>
                    </div>
                </div>
            </div>
        `;

    }
};
}
