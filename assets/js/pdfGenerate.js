var IMAGE_LOGO_BASE64 = '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'; // Pega aquí el Base64 del logo
var image = IMAGE_LOGO_BASE64;
var ultimapagina = false;

function generarPDFRequisicion(Numero_Req, clave, requisicion, NameUser, itemsOrdenArray, obras) {
    console.log('primera llamada');
    var doc = new jsPDF('l', 'mm', 'a4', true);
    var pages = createPages(convertToArrayStrings(itemsOrdenArray));
    
    pages.forEach((ArrayItems, index) => {
        if (index == pages.length - 1) {
            ultimapagina = true
        }
        //crea el encabezado de la pagina
        creaEncabezadoOrden(doc);
        //datos de la empresa
        datosEmpresa(doc,Numero_Req, clave, requisicion);
        //datos del proveedor
        datosProveedor(doc,requisicion);
        //datos complementarios
        complementarios(doc,requisicion, obras);
        //se crea la tabla de los items
        itemDeOrden(doc,ArrayItems, requisicion, ultimapagina);
        //Crea el pie de pagina de la orden
        creaPieDeOrden(doc,NameUser, requisicion, index + 1, pages.length);
        if (index < pages.length - 1) {
            doc.addPage('a4', 'l');
        }
    });
    doc.save(Numero_Req+" "+'HOJA N°' + requisicion.hojaRequisicion_numero);
}

function generarPDFRequisicionBlob(Numero_Req, clave, requisicion, NameUser, itemsOrdenArray, obras) {
    var doc = new jsPDF('l', 'mm', 'a4', true);
    var pages = createPages(convertToArrayStrings(itemsOrdenArray));
    ultimapagina = false;

    pages.forEach((ArrayItems, index) => {
        if (index == pages.length - 1) {
            ultimapagina = true;
        }
        creaEncabezadoOrden(doc);
        datosEmpresa(doc, Numero_Req, clave, requisicion);
        datosProveedor(doc, requisicion);
        complementarios(doc, requisicion, obras);
        itemDeOrden(doc, ArrayItems, requisicion, ultimapagina);
        creaPieDeOrden(doc, NameUser, requisicion, index + 1, pages.length);
        if (index < pages.length - 1) {
            doc.addPage('a4', 'l');
        }
    });

    return doc.output('blob');
}
function creaEncabezadoOrden(doc) {
    doc.setFontSize(12);
    doc.setFontStyle('bold');
    doc.addImage(image, 'PNG', 10, 10, 25, 25,);
    doc.text('ORDEN DE COMPRA', 145, 18, 'center');
    doc.setFontSize(10);
    const textWidth = doc.getTextWidth('AREA DE RECURSOS MATERIALES Y SERVICIOS GENERALES');
    doc.setLineWidth(0.5);
    doc.line(92, 29, 92 + textWidth, 29);
    doc.text('AREA DE RECURSOS MATERIALES Y SERVICIOS GENERALES', 145, 28, 'center');
}
function datosEmpresa(doc,Numero_Req, clave, requisicion) {
    console.log('segunda llamada');
    console.log(requisicion);
    doc.setFontSize(8);
    doc.setLineWidth(0.2);
    doc.setDrawColor(0);
    doc.setFillColor(155, 168, 162);
    doc.rect(10, 37, 50, 6, 'FD');
    doc.rect(60, 37, 130, 6);
    doc.rect(190, 37, 95, 6);
    doc.rect(10, 43, 30, 12, 'FD');
    doc.rect(40, 43, 150, 12);
    doc.rect(190, 43, 95, 6);
    doc.rect(190, 49, 95, 6);
    doc.rect(10, 55, 30, 6, 'FD');
    doc.rect(40, 55, 245, 6);
    doc.rect(10, 61, 50, 6, 'FD');
    doc.rect(60, 61, 40, 6);
    doc.rect(100, 61, 30, 6, 'FD');
    doc.rect(130, 61, 50, 6);
    doc.rect(180, 61, 50, 6, 'FD');
    doc.rect(230, 61, 55, 6);

    doc.setFontStyle('normal');
    doc.text('NOMBRE DE LA EMPRESA', 35, 41, 'center');
    doc.text(requisicion.emisor_nombre, 125, 41, 'center');
    doc.text('ORDEN DE COMPRA No', 237, 41, 'center');

    doc.text('RFC', 25, 49, 'center');
    doc.text(requisicion.emisor_rfc, 115, 49, 'center');
    doc.text(Numero_Req, 237, 47, 'center');
    doc.text('HOJA N°' + requisicion.hojaRequisicion_numero, 237, 53, 'center');

    doc.text('DIRECCION', 25, 59, 'center');
    doc.text(requisicion.emisor_direccion, 162, 59, 'center');

    doc.text('TELEFONO', 35, 65, 'center');
    doc.text(requisicion.emisor_telefono, 80, 65, 'center');
    doc.text('FAX', 115, 65, 'center');
    doc.text(requisicion.emisor_fax, 155, 65, 'center');
    doc.text('C.P', 205, 65, 'center');
    doc.text(requisicion.emisor_zipCode, 257, 65, 'center');
}

function datosProveedor(doc, requisicion) {
    console.log('Tercera llamada');
    doc.setFontSize(8);
    doc.setFillColor(177, 223, 200);
    doc.rect(10, 69, 275, 6, 'FD');
    doc.rect(10, 75, 24, 6, 'FD');
    doc.rect(34, 75, 105, 6);
    doc.rect(139, 75, 40, 6, 'FD');
    doc.rect(179, 75, 40, 6);
    doc.rect(219, 75, 20, 6, 'FD');
    doc.rect(239, 75, 46, 6);
    doc.rect(10, 81, 24, 6, 'FD');
    doc.rect(34, 81, 30, 6);
    doc.rect(64, 81, 24, 6, 'FD');
    doc.rect(88, 81, 51, 6);
    doc.rect(139, 81, 40, 6, 'FD');
    doc.rect(179, 81, 40, 6);
    doc.rect(219, 81, 20, 6, 'FD');
    doc.rect(239, 81, 46, 6);
    doc.rect(10, 87, 24, 6, 'FD');
    doc.rect(34, 87, 105, 6);
    doc.rect(139, 87, 40, 6, 'FD');
    doc.rect(179, 87, 40, 6);
    doc.rect(219, 87, 20, 6, 'FD');
    doc.rect(239, 87, 46, 6);
    doc.setFillColor(155, 168, 162);
    doc.rect(10, 93, 40, 4, 'FD');
    doc.rect(50, 93, 235, 4);

    doc.setFontStyle('normal');
    doc.text('DATOS DEL PROVEEDOR', 137, 73, 'center');

    doc.text('PROVEEDOR', 22, 79, 'center');
    doc.text(requisicion.proveedor_nombre, 86, 79, 'center');
    doc.text('CLABE INTERBANCARIA', 159, 79, 'center');
    doc.text(formatString(requisicion.proveedor_clabe), 199, 79, 'center');
    doc.text('BANCO', 229, 79, 'center');
    doc.text(requisicion.proveedor_banco, 262, 79, 'center');

    doc.text('RFC', 22, 85, 'center');
    doc.text(requisicion.proveedor_rfc, 49, 85, 'center');
    doc.text('N° DE TARJETA', 76, 85, 'center');
    doc.text(formatString(requisicion.presiones_tarjetaBanco), 113, 85, 'center');
    doc.text('N° DE CUENTA', 159, 85, 'center');
    doc.text(formatString(requisicion.proveedor_numeroCuenta), 199, 85, 'center');
    doc.text('SUCURSAL', 229, 85, 'center');
    doc.text(requisicion.proveedor_sucursal, 262, 85, 'center');

    doc.text('CORREO', 22, 91, 'center');
    doc.text(requisicion.proveedor_email, 86, 91, 'center');
    doc.text('N° TELEFONICO', 159, 91, 'center');
    doc.text(requisicion.proveedor_telefono, 199, 91, 'center');
    doc.text('MONEDA', 229, 91, 'center');
    doc.text(requisicion.hojaRequisicion_formaPago, 262, 91, 'center');

    doc.text('TERMINO DE ENTREGA', 30, 96, 'center');
    doc.text('', 167, 96, 'center');
}

function complementarios(doc, requisicion, obras) {
    console.log('cuarta llamada');
    doc.setFontSize(8);
    doc.setFillColor(155, 168, 162);
    doc.rect(10, 99, 30, 12, 'FD');
    doc.rect(40, 99, 150, 12);
    doc.rect(190, 99, 40, 12, 'FD');
    doc.rect(230, 99, 55, 12);
    doc.rect(10, 113, 150, 4, 'F');
    doc.rect(190, 113, 40, 4, 'FD');
    doc.rect(230, 113, 55, 4);

    doc.setFontStyle('normal');
    doc.text('OBRA', 25, 106, 'center');
    doc.text(obras.obras_nombre + ", " + obras.ciudadesObras_nombre, 115, 106, 'center');
    doc.text('FECHA DE SOLICITUD', 210, 106, 'center');
    doc.text(requisicion.hojaRequisicion_FechaSolicitud, 257, 106, 'center');

    doc.text('Sirvase por este medio para suministrar los siguientes articulos', 85, 116, 'center');
    doc.text('FECHA DE ENTREGA', 210, 116, 'center');
    doc.text('', 257, 116, 'center');
}

function itemDeOrden(doc, ArrayString, requisicion, ultimapagina) {
    console.log('quinta llamada');
    console.log(ArrayString);
    var x = 10;
    var y = 125
    var xt = 0;
    var yt = 0;

    doc.setFontSize(8);
    doc.setFillColor(155, 168, 162);
    doc.rect(10, 119, 10, 6, 'FD');
    doc.rect(20, 119, 30, 6, 'FD');
    doc.rect(50, 119, 90, 6, 'FD');
    doc.rect(140, 119, 30, 6, 'FD');
    doc.rect(170, 119, 30, 6, 'FD');
    doc.rect(200, 119, 25, 6, 'FD');
    doc.rect(225, 119, 25, 6, 'FD');
    doc.rect(250, 119, 35, 6, 'FD');

    doc.text('LOTE', 15, 123, 'center');
    doc.text('UNIDAD', 35, 123, 'center');
    doc.text('PRODUCTO', 95, 123, 'center');
    doc.text('CANTIDAD', 155, 123, 'center');
    doc.text('PRECIO UNITARIO', 185, 123, 'center');
    doc.text('IVA', 212, 123, 'center');
    doc.text('RETENCIONES', 237, 123, 'center')
    doc.text('TOTAL', 267, 123, 'center')

    ArrayString.forEach(element => {
        yt = (y + 6) - 2;
        doc.rect(10, y, 10, element.tamaño);
        doc.rect(x + 10, y, 30, element.tamaño);
        doc.rect(x + 40, y, 90, element.tamaño);
        doc.rect(x + 130, y, 30, element.tamaño);
        doc.rect(x + 160, y, 30, element.tamaño);
        doc.rect(x + 190, y, 25, element.tamaño);
        doc.rect(x + 215, y, 25, element.tamaño);
        doc.rect(x + 240, y, 35, element.tamaño);

        doc.text(element.lote.toString(), 15, yt, 'center');
        doc.text(element.unidad, (x + 10) + 15, yt, 'center');
        doc.text(element.producto, (x + 40) + 45, yt, 'center');
        doc.text(element.cantidad.toString(), (x + 130) + 15, yt, 'center');
        doc.text("$ " + addCommas(element.precio), (x + 160) + 15, yt, 'center');
        doc.text("+$ " + addCommas(element.iva), (x + 190) + 15, yt, 'center');
        doc.text("-$ " + addCommas(element.retenciones), (x + 215) + 15, yt, 'center')
        doc.text("$ " + addCommas(element.total), (x + 240) + 15, yt, 'center')

        y = y + element.tamaño;
    });

    if (ultimapagina == true) {
        doc.setFillColor(155, 168, 162);
        doc.rect(225, y, 25, 6, 'FD');
        doc.rect(250, y, 35, 6, 'FD');

        doc.text('TOTAL', 237, (y + 6) - 2, 'center');
        doc.text("$ " + addCommas(requisicion.hojaRequisicion_total), 267, (y + 6) - 2, 'center');

        if (requisicion.hojaRequisicion_observaciones != "") {
            doc.setFillColor(255, 234, 0);
            doc.rect(10, y + 10, 275, 16, 'F');

            doc.text(convertToMultilines('NOTA:' + requisicion.hojaRequisicion_observaciones,151), 15, ((y + 10) + 7) - 2, 'left');
        }
    }
}

function creaPieDeOrden(doc, NameUser, requisicion, pagina, paginas) {
   /*  console.log('sexta llamada');
    doc.text('ELABORO', 55, 180, 'center');
    doc.line(20, 190, 100, 190);
    doc.text(NameUser, 55, 193, 'center');
    doc.text('SELLO Y FIRMA DE LA EMPRESA', 230, 180, 'center');
    doc.line(190, 190, 270, 190);
    doc.text(requisicion.emisor_nombre, 230, 193, 'center'); */
    doc.setFontStyle('bold');
    doc.text("Esta orden la elaboro "+NameUser+" el dia "+requisicion.hojaRequisicion_FechaSolicitud, 180, 200, 'left');
    doc.text("Pagina " + pagina + " de " + paginas, 143, 200, 'center');
}

function convertToArrayStrings(itemsOrdenArray) {
    console.log(itemsOrdenArray);
    var ArrayStringItems = [];
    var tamano = 0;

    for (var i = 0; i < itemsOrdenArray.length; i++) {
        if (itemsOrdenArray[i].itemRequisicion_producto.length > 204) {
            itemsOrdenArray[i].itemRequisicion_producto = convertToMultilines(itemsOrdenArray[i].itemRequisicion_producto,69);
            tamano = 17;
        }
        else if(itemsOrdenArray[i].itemRequisicion_producto.length > 136){
            itemsOrdenArray[i].itemRequisicion_producto = convertToMultilines(itemsOrdenArray[i].itemRequisicion_producto,69);
            tamano = 13;
        }
        else if(itemsOrdenArray[i].itemRequisicion_producto.length > 68){
            itemsOrdenArray[i].itemRequisicion_producto = convertToMultilines(itemsOrdenArray[i].itemRequisicion_producto,69);
            tamano = 9;
        }
        else {
            tamano = 6;
        }
        var JsonAux = {
            'lote': (i + 1),
            'unidad': itemsOrdenArray[i].itemRequisicion_unidad,
            'producto': itemsOrdenArray[i].itemRequisicion_producto,
            'cantidad': Number.parseFloat(itemsOrdenArray[i].itemRequisicion_cantidad).toFixed(2),
            'precio': Number.parseFloat(itemsOrdenArray[i].itemRequisicion_precio).toFixed(2),
            'iva': Number.parseFloat(itemsOrdenArray[i].itemRequisicion_iva).toFixed(2),
            'retenciones': Number.parseFloat(itemsOrdenArray[i].itemRequisicion_retenciones).toFixed(2),
            'total': Number.parseFloat((((Number.parseFloat(itemsOrdenArray[i].itemRequisicion_cantidad) * Number.parseFloat(itemsOrdenArray[i].itemRequisicion_precio)) + Number.parseFloat(itemsOrdenArray[i].itemRequisicion_iva)) - Number.parseFloat(itemsOrdenArray[i].itemRequisicion_retenciones))).toFixed(2),
            'tamaño': tamano
        }
        console.log(JsonAux);
        ArrayStringItems.push(JsonAux);
    }
    return ArrayStringItems;
}

function createPages(ArrayString) {
    console.log(ArrayString);
    var pages = [];
    var page = [];
    var i = 0;
    ArrayString.forEach((item) => {
        i = i + item.tamaño;
        if (i < 42) {
            page.push(item);

        }
        else {
            page.push(item);
            pages.push(page);
            i = 0;
            page = [];
        }
    });
    if (i < 42) {
        pages.push(page);
    }
    console.log("paginas");
    console.log(pages);
    return pages;
}

function convertToMultilines(cadena, lengthCad) {
    var cadenaAuxiliar = "";
    var indexMultilines = 0;

    for (i = 0; i < cadena.length; i++) {
        if (indexMultilines == lengthCad) {
            cadenaAuxiliar = cadenaAuxiliar + "\n";
            indexMultilines = 0;
        }
        cadenaAuxiliar = cadenaAuxiliar + cadena[i];
        indexMultilines++;
    }

    return cadenaAuxiliar;
}

function addCommas(nStr) {
    nStr += '';
    x = nStr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
}

function formatString(inputString) {
    if (inputString === '') {
        return '0';
    }
    const formattedString = inputString.match(/.{1,4}/g).join('-');
    return formattedString;
}





