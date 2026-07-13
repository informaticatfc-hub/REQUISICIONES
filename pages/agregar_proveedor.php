<?php
include_once '../validarSesion.php';
require_once __DIR__ . '/../api/rbac.php';
require_once __DIR__ . '/../api/conexion.php';

$__pdo  = (new Conexion())->Conectar();
$__user = tf_current_user($__pdo);

tf_require_any_permission($__pdo, ['proveedores.manage', 'catalogos.view'], 'No tienes permiso para agregar proveedores');

$usuario_nombre  = $__user['user_name']    ?? '';
$usuario_rol     = $__user['role']['name'] ?? 'Usuario';
$usuario_rolCode = $__user['role']['code'] ?? 'usuario';
$usuario_perms   = $__user['permissions']  ?? [];

$tf_page_title     = 'Agregar Proveedor';
$tf_active_nav     = 'catalogos';
$tf_breadcrumb     = [['Inicio', './index.php'], ['Catalogos', './menu_catalago.php'], ['Agregar Proveedor', '#']];
$tf_user           = [
    'name'        => $usuario_nombre,
    'role'        => $usuario_rol,
    'roleCode'    => $usuario_rolCode,
    'initials'    => '',
    'permissions' => $usuario_perms,
];
$tf_show_direccion = in_array($usuario_rolCode, ['admin','director'], true);
$tf_show_admin     = in_array($usuario_rolCode, ['admin', 'desarrollador'], true) || tf_has_permission('admin.users.view', $__user);
$tf_show_subbar    = true;
$tf_user_id_js     = (string)($__user['user_id'] ?? '');

include __DIR__ . '/../includes/layout_top.php';
?>

<div id="AppNewProv" class="tf-page-inner" x-data="addProveedorApp()" x-init="init()" x-cloak>
    <header class="tf-page-header">
        <div>
            <span class="tf-eyebrow">Catalogo de proveedores</span>
            <h1 class="tf-page-title">Agregar Proveedor</h1>
            <p class="tf-page-lead">Registra un nuevo proveedor con sus datos fiscales y bancarios.</p>
        </div>
    </header>

    <section class="tf-card">
        <div class="tf-card-body">
            <div class="row my-3">
                <div class="col">
                    <label for="nameProv" class="form-label">Nombre del Proveedor</label>
                    <input type="text" class="form-control" id="nameProv" placeholder="Ingresa informacion..." x-model="nombre_prov" require>
                </div>
            </div>
            <div class="row my-3">
                <div class="col">
                    <label for="rfcProv" class="form-label">RFC del Proveedor</label>
                    <input type="text" class="form-control" id="rfcProv" placeholder="Ej: ABCD0102031A2" x-model="rfc_prov" x-on:input="normalizarRFC" x-bind:class="rfc_prov.length ? (rfcValido ? 'is-valid' : 'is-invalid') : ''" required>
                    <div class="form-text">Formato esperado: 12 o 13 caracteres (persona moral/física).</div>
                    <div class="invalid-feedback d-block" x-show="rfc_prov.length && !rfcValido">RFC inválido.</div>
                </div>
                <div class="col">
                    <label for="clabeProv" class="form-label">Clave Bancaria del Proveedor</label>
                    <input type="text" class="form-control" id="clabeProv" placeholder="18 dígitos" x-model="clabe_prov" x-on:input="normalizarClabe" x-bind:class="clabe_prov.length ? (clabeValida ? 'is-valid' : 'is-invalid') : ''" maxlength="18" required>
                    <div class="form-text">La CLABE debe tener 18 dígitos y dígito verificador válido.</div>
                    <div class="invalid-feedback d-block" x-show="clabe_prov.length && !clabeValida">CLABE inválida.</div>
                </div>
                <div class="col">
                    <label for="cuentaProv" class="form-label">Cuenta Bancaria del Proveedor</label>
                    <input type="text" class="form-control" id="cuentaProv" placeholder="Ingresa informacion..." x-model="cuenta_prov" required>
                </div>
            </div>
            <div class="row my-3">
                <div class="col">
                    <label class="form-label">Numero de Tarjeta</label>
                    <input type="text" class="form-control" placeholder="Ingresa informacion..." x-model="tarjeta_prov">
                </div>
                <div class="col">
                    <label class="form-label">Numero de Referencia del Proveedor</label>
                    <input type="text" class="form-control" placeholder="Ingresa informacion..." x-model="referencia_prov">
                </div>
                <div class="col">
                    <label class="form-label">Tipo de Proveedor</label>
                    <input type="text" class="form-control" placeholder="Ingresa informacion..." x-model="tipo_prov">
                </div>
            </div>
            <div class="row my-3">
                <div class="col">
                    <label class="form-label">Banco del Proveedor</label>
                    <select class="form-select" x-model="selected_Banco">
                        <option value="">Selecciona Banco</option>
                        <template x-for="(banco, indice) in bancos" :key="banco.banco_id">
                            <option :value="banco.banco_nombreComercial" x-text="banco.banco_id + '- ' + banco.banco_nombreComercial"></option>
                        </template>
                    </select>
                </div>
                <div class="col">
                    <label class="form-label">Sucursal Bancaria del Proveedor</label>
                    <input type="text" class="form-control" placeholder="Ingresa informacion..." x-model="suc_prov">
                </div>
            </div>
            <div class="row my-3">
                <div class="col">
                    <label class="form-label">Direccion del Proveedor</label>
                    <input type="text" class="form-control" placeholder="Ingresa informacion..." x-model="direccion_prov">
                </div>
            </div>
            <div class="row my-3">
                <div class="col">
                    <label class="form-label">Telefono del Proveedor</label>
                    <input type="text" class="form-control" placeholder="Ingresa informacion..." x-model="tel_prov">
                </div>
                <div class="col">
                    <label class="form-label">Correo Electronico del Proveedor</label>
                    <input type="email" class="form-control" placeholder="Ingresa correo del proveedor" x-model="email_prov">
                </div>
            </div>
            <div class="row w-100 mt-5 mb-2 mx-auto">
                <div class="col px-0 d-grid gap-2">
                    <button class="tf-btn tf-btn-primary" x-on:click="agregarProveedor" title="Agregar Proveedor" x-bind:disabled="!puedeGuardar">
                        <span class="text-center">Agregar Proveedor</span>
                    </button>
                </div>
            </div>
        </div>
    </section>
</div>

<?php
$tf_use_vue = false;
$tf_use_jquery = true;
$tf_extra_head = '<style>[x-cloak]{display:none !important;}</style>';
$tf_extra_scripts = '<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script><script src="../assets/js/agregar_proveedor.js?v=fase08b"></script>';
include __DIR__ . '/../includes/layout_bottom.php';
?>
