/*esta es la consulta del inner join para tener toda la informacion de la contruccion de la presion para enviar*/
SELECT * FROM `presiones` 
JOIN ciudadesObras 
ON ciudadesObras.ciudadesObras_id = presiones.presiones_cuidad 
JOIN obras 
ON obras.obras_id = presiones.presiones_obra 
JOIN requisiciones 
ON requisiciones.requisicion_presionID = presiones.presiones_id 
JOIN itemrequisicion 
ON itemrequisicion.itemRequisicion_idReq = requisiciones.requisicion_id;

SELECT `presiones_clave`,`requisicion_Numero`,`proveedor_nombre`,`itemRequisicion_producto`,`requisicion_total`,`requisicion_observaciones`,`requisicion_formaPago` FROM `presiones`
JOIN requisiciones
ON presiones.presiones_id = requisiciones.requisicion_idPresion
JOIN itemrequisicion
on requisiciones.requisicion_id = itemrequisicion.itemRequisicion_idReq
JOIN provedores
on requisiciones.requisicion_receptorID = provedores.proveedor_id
WHERE`presiones_semana`= 29 AND `presiones_dia` = 'LUNES' AND `presiones_obra` = 1

ALTER TABLE `hojasrequisicion` ADD `hojarequisicion_comentariosValidacion` VARCHAR(250) NULL AFTER `hojaRequisicion_observaciones`, ADD `hojarequisicion_comentariosAutorizacion` VARCHAR(250) NULL AFTER `hojarequisicion_comentariosValidacion`;



ALTER TABLE `itemrequisicion` CHANGE `itemRequisicion_producto` `itemRequisicion_producto` VARCHAR(272) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL;

ALTER TABLE `itemrequisicion` CHANGE `itemRequisicion_iva` `itemRequisicion_iva` DECIMAL(16,6) NOT NULL, CHANGE `itemRequisicion_retenciones` `itemRequisicion_retenciones` DECIMAL(16,6) NOT NULL, CHANGE `itemRequisicion_precio` `itemRequisicion_precio` DECIMAL(16,6) NOT NULL, CHANGE `itemRequisicion_cantidad` `itemRequisicion_cantidad` DECIMAL(16,6) NOT NULL, CHANGE `itemRequisicion_parcialidad` `itemRequisicion_parcialidad` DECIMAL(16,6) NULL DEFAULT NULL;



ALTER TABLE `hojasrequisicion` ADD `hojarequisicion_conceptoUnico` VARCHAR(250) NOT NULL AFTER `hojarequisicion_comentariosAutorizacion`;
