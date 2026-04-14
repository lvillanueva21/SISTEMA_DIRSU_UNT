-- SQL de alineacion estructural (old -> final)
-- Generado por analisis de CREATE TABLE; no incluye INSERT/UPDATE de datos.
SET FOREIGN_KEY_CHECKS=0;

-- ubigeo_peru_departments: alinear charset/collation de tabla
ALTER TABLE `ubigeo_peru_departments` DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_uca1400_ai_ci;

-- ubigeo_peru_districts: alinear charset/collation de tabla
ALTER TABLE `ubigeo_peru_districts` DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_uca1400_ai_ci;

-- ubigeo_peru_provinces: alinear charset/collation de tabla
ALTER TABLE `ubigeo_peru_provinces` DEFAULT CHARACTER SET utf8mb3 COLLATE utf8mb3_uca1400_ai_ci;

-- Alineacion estricta de constraints (opcional; semantica equivalente en la mayoria de casos)
-- Tabla `departamentos`
ALTER TABLE `departamentos` DROP FOREIGN KEY `fk_departamentos_facultades`;
ALTER TABLE `departamentos` ADD CONSTRAINT `fk_departamentos_facultades` FOREIGN KEY (`id_facultad`) REFERENCES `facultades` (`id`) ON DELETE CASCADE;

-- Tabla `directorio`
ALTER TABLE `directorio` DROP FOREIGN KEY `fk_directorio_rol`;
ALTER TABLE `directorio` ADD CONSTRAINT `fk_directorio_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id`) ON UPDATE CASCADE;

-- Tabla `escuelas`
ALTER TABLE `escuelas` DROP FOREIGN KEY `fk_escuelas_facultades`;
ALTER TABLE `escuelas` ADD CONSTRAINT `fk_escuelas_facultades` FOREIGN KEY (`id_facultad`) REFERENCES `facultades` (`id`) ON DELETE CASCADE;

-- Tabla `eva_calificaciones`
ALTER TABLE `eva_calificaciones` DROP FOREIGN KEY `fk_cal_oficina`;
ALTER TABLE `eva_calificaciones` ADD CONSTRAINT `fk_cal_oficina` FOREIGN KEY (`id_oficina`) REFERENCES `eva_oficinas` (`id`) ON UPDATE CASCADE;

-- Tabla `eva_oficina_instancias`
ALTER TABLE `eva_oficina_instancias` DROP FOREIGN KEY `fk_inst_oficina`;
ALTER TABLE `eva_oficina_instancias` ADD CONSTRAINT `fk_inst_oficina` FOREIGN KEY (`id_oficina`) REFERENCES `eva_oficinas` (`id`) ON UPDATE CASCADE;

-- Tabla `l2601_usuarios`
ALTER TABLE `l2601_usuarios` DROP FOREIGN KEY `fk_usuarios_roles`;
ALTER TABLE `l2601_usuarios` ADD CONSTRAINT `fk_usuarios_roles` FOREIGN KEY (`rol_id`) REFERENCES `l2601_roles` (`id`) ON UPDATE CASCADE;
-- Nota: DROP CHECK requiere MySQL 8.0.16+ (en MariaDB puede variar a DROP CONSTRAINT).
ALTER TABLE `l2601_usuarios` DROP CHECK `chk_dni_8dig`;
ALTER TABLE `l2601_usuarios` ADD CONSTRAINT `chk_dni_8dig` CHECK (`dni` regexp '^[0-9]{8}$');

-- Tabla `programa_ods`
ALTER TABLE `programa_ods` DROP FOREIGN KEY `fk_progods_ods`;
ALTER TABLE `programa_ods` ADD CONSTRAINT `fk_progods_ods` FOREIGN KEY (`ods_id`) REFERENCES `ods` (`id`) ON UPDATE CASCADE;

-- Tabla `sm_respuesta_items`
ALTER TABLE `sm_respuesta_items` DROP FOREIGN KEY `fk_ri_item`;
ALTER TABLE `sm_respuesta_items` ADD CONSTRAINT `fk_ri_item` FOREIGN KEY (`id_item`) REFERENCES `sm_items` (`id`) ON UPDATE CASCADE;

-- Tabla `sm_respuestas`
ALTER TABLE `sm_respuestas` DROP FOREIGN KEY `fk_resp_crono`;
ALTER TABLE `sm_respuestas` ADD CONSTRAINT `fk_resp_crono` FOREIGN KEY (`id_cronograma`) REFERENCES `sm_cronogramas` (`id`) ON UPDATE CASCADE;
ALTER TABLE `sm_respuestas` DROP FOREIGN KEY `fk_resp_form`;
ALTER TABLE `sm_respuestas` ADD CONSTRAINT `fk_resp_form` FOREIGN KEY (`id_formulario`) REFERENCES `sm_formularios` (`id`) ON UPDATE CASCADE;
ALTER TABLE `sm_respuestas` DROP FOREIGN KEY `fk_resp_sem`;
ALTER TABLE `sm_respuestas` ADD CONSTRAINT `fk_resp_sem` FOREIGN KEY (`id_semestre`) REFERENCES `sm_proyecto_semestres` (`id`) ON UPDATE CASCADE;

-- Tabla `usuarios`
ALTER TABLE `usuarios` DROP FOREIGN KEY `fk_usuarios_departamentos`;
ALTER TABLE `usuarios` ADD CONSTRAINT `fk_usuarios_departamentos` FOREIGN KEY (`id_depa`) REFERENCES `departamentos` (`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS=1;
