# Fase 5 — Eliminación de `localStorage.NameUser` y consolidación de vistas SQL

> Rama: `genspark_v2_fase5`
> Estado: en revisión (PR `genspark_v2_fase5` → `main`).

## Objetivos

1. **Eliminar la identidad client-side** (`localStorage.NameUser`) de los 17 JS legacy.
   La identidad del usuario ya vive en la sesión PHP (`tf_current_user()`) desde la Fase 2,
   pero los JS seguían enviando un `id_user` desde `localStorage` (que el server-side ignora
   desde la Fase 2/3 porque resuelve la identidad por sesión). Mantener ese `localStorage.NameUser`
   era un riesgo de seguridad por dos razones:
   - Sugería que el cliente podía elegir su identidad.
   - Podía quedar desincronizado tras logout / cambio de usuario / pestañas múltiples.
2. **Aprovechar las vistas SQL** creadas en la migración `2026_05_14_002_adaptacion_bd_real.sql`
   (Fase 4) en los endpoints PHP relevantes, para evitar reescribir lógica de agregación.
3. Documentar todos los cambios en este archivo.

---

## 1. JavaScript: eliminación de `localStorage.NameUser`

### 1.1 Patrón aplicado (resumen)

**Antes:**

```js
consultarUsuario: function (user_id) {
    axios.post(url, { accion: 2, id_user: user_id }).then(response => {
        this.users = response.data;
        this.NameUser = this.users[0].user_name;
        console.log(this.users);
    });
},
// ...
this.consultarUsuario(localStorage.getItem("NameUser"));
```

**Después:**

```js
consultarUsuario: function () {
    // Fase 5: identidad por sesion server-side (sin localStorage.NameUser)
    axios.post(url, { accion: 2 }).then(response => {
        this.users = response.data || [];
        if (this.users[0] && this.users[0].user_name) {
            this.NameUser = this.users[0].user_name;
        }
    }).catch(err => console.error("consultarUsuario:", err));
},
// ...
this.consultarUsuario();
```

- Se quita el parámetro `user_id` del método.
- Se quita `id_user` del payload axios.
- El server-side ya resolvía la identidad por sesión desde la Fase 2/3 (`tf_current_user`).
- Se conserva la propiedad reactiva `NameUser` en el `data` de Vue para no romper bindings.
- Se añade `catch` y guardas (`this.users[0] &&`) por defensa.

### 1.2 Archivos modificados (12)

| Archivo | Cambios |
|---|---|
| `assets/js/agregar_proveedor.js`      | método + callsite |
| `assets/js/all_presiones.js`          | método + callsite |
| `assets/js/bancos.js`                 | método + callsite |
| `assets/js/catalago.js`               | método + callsite |
| `assets/js/direccion.js`              | método + callsite |
| `assets/js/enlazar_requisiciones.js`  | método (async) + callsite |
| `assets/js/hojas_requisicion.js`      | método (async) + callsite |
| `assets/js/index.js`                  | método + callsite |
| `assets/js/item_requisicion.js`       | método + callsite |
| `assets/js/menu_catalagos.js`         | método (con fallback) + callsite |
| `assets/js/nueva_hoja.js`             | método + callsite |
| `assets/js/nueva_requisicion.js`      | método + callsite |
| `assets/js/obras.js`                  | método + callsite |
| `assets/js/presiones.js`              | método (async) + callsite + se elimina `console.log("Name user es ...")` |
| `assets/js/presiones_detalles.js`     | método (async) + callsite |
| `assets/js/proveedor.js`              | método + callsite |
| `assets/js/requisiciones.js`          | método (async) + callsite |
| `assets/js/login.js`                  | se elimina `localStorage.setItem("NameUser", ...)` |

### 1.3 Lo que NO se tocó

- `localStorage.getItem("obraActiva")`, `IdPresion`, `idRequisicion`, `idHoja`, `validate`,
  etc. — son **estado de navegación legítimo**, no identidad de usuario.
- `sessionStorage.setItem("tf_user_id", ...)` en `login.js` — se conserva porque solo lo
  consume la propia UI para visualización (no es enviado al server como identidad).
- `pdfGenerate.js` — `NameUser` ahí es un parámetro de función, no `localStorage`.

### 1.4 Verificación

```bash
grep -rn 'localStorage\.\(get\|set\)Item("NameUser"' assets/js/
# → sin matches funcionales (solo comentarios "Fase 5: ...")
```

---

## 2. PHP: uso de vistas SQL

Las vistas se crearon en `api/migrations/2026_05_14_002_adaptacion_bd_real.sql` (Fase 4).

### 2.1 `api/crud_admin.php` — case 1 (listar usuarios) → `v_users_full`

**Antes:** JOIN manual de `users` + `roles`.

**Después:** lectura de `v_users_full`, que ya entrega:

- `user_id`, `user_nameUser`, `user_name`, `email` (con COALESCE),
  `user_estatus`, `user_lastLogin`, `user_directionAcess`
- `role_codigo`, `role_nombre`, `role_nivel`
- **`permisos_count`** (nuevo dato disponible en la UI: cuántos permisos tiene el rol)

Se conserva un `LEFT JOIN users` para obtener `user_role_id`, que la UI de admin necesita
para el `<select>` de cambio de rol (la vista no lo expone).

### 2.2 `api/crud_all_presiones.php` — `case 8` (nuevo) → `v_presiones_summary`

Se añade un nuevo `accion: 8` que retorna el **resumen agregado** de las presiones usando
la vista `v_presiones_summary`. La vista calcula `total_calculado` y `adeudo_calculado`
**desde las hojas reales** porque los campos `presiones_adeudo` y `presiones_gastosObra`
viven en `presiones` pero están en `0.00` en el dump de producción.

El payload soporta filtro opcional por obra:

```js
axios.post(url, { accion: 8, obra: idObra }); // o sin obra para todas
```

Respuesta (por presion):

```json
{
  "presiones_id": 12,
  "presiones_nombre": "Presion 12",
  "presiones_alias": "S-12",
  "presiones_semana": "12",
  "presiones_dia": "Viernes",
  "presiones_fechaCreacion": "2025-03-22",
  "presiones_obra": 4,
  "obras_nombre": "Obra Fuentes Norte",
  "presiones_estatus": "PENDIENTE",
  "hojas_ligadas": 17,
  "total_calculado": 184230.55,
  "adeudo_calculado": 132100.00
}
```

**No se reemplaza `case 3`** (vista detallada por obra) porque su query pivotea hoja a hoja
con sub-agregados por tipo de pago, y no encaja en una sola vista de SQL sin perder
granularidad. El `case 8` queda como complemento para el dashboard.

---

## 3. Resumen de seguridad

| Vector | Antes Fase 5 | Después Fase 5 |
|---|---|---|
| Identidad enviada al server | `id_user` desde `localStorage` (ignorado pero presente) | `tf_current_user()` por sesión PHP |
| Persistencia de identidad en cliente | `localStorage.NameUser` | `sessionStorage.tf_user_id` (solo display) |
| Riesgo de identity-spoofing client-side | Mitigado server-side pero superficie visible | Eliminado de la API JS |

---

## 4. Plan de prueba manual

1. **Login** → verificar que NO se escribe `localStorage.NameUser` (DevTools → Application → Local Storage).
2. **Cada página** (proveedores, obras, presiones, presiones_detalles, requisiciones,
   hojas, items, nueva_hoja, nueva_requisicion, enlazar, catalago, direccion, bancos,
   menu_catalagos, all_presiones, agregar_proveedor, index, admin) → la cabecera debe
   seguir mostrando el nombre del usuario (`this.NameUser`).
3. **Admin** → `crud_admin.php?accion=1` debe traer ahora `permisos_count` por usuario.
4. **Dashboard de presiones** → `crud_all_presiones.php` con `accion: 8` retorna el
   resumen agregado correcto (total > 0 para presiones con hojas ligadas).
5. **Logout** → el `sessionStorage` se limpia; nada queda en `localStorage` con identidad.

---

## 5. Próximos pasos sugeridos (Fase 6)

- Reemplazar el resto de `console.log` ruidosos por `console.debug` condicionado a un flag.
- Consolidar `consultarUsuario` en un helper compartido (`assets/js/tf-session.js`) para
  evitar duplicación en 17 archivos.
- Tipar el contrato de respuesta con un `interface User` documentado.
- Migrar `assets/js/requisiciones.js` y `assets/js/hojas_requisicion.js` al nuevo layout v4.
