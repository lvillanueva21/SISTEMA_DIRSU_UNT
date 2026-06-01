-- ORDEN DE EJECUCION
-- 1) Tabla principal de portadas
CREATE TABLE `l2601_portadas_paginas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_key` varchar(80) NOT NULL,
  `imagen_portada` varchar(255) DEFAULT NULL,
  `descripcion` varchar(500) DEFAULT NULL,
  `creado_por` bigint(20) unsigned DEFAULT NULL,
  `actualizado_por` bigint(20) unsigned DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_portada_page_key` (`page_key`),
  KEY `fk_portada_creado_por` (`creado_por`),
  KEY `fk_portada_actualizado_por` (`actualizado_por`),
  CONSTRAINT `fk_portada_creado_por`
    FOREIGN KEY (`creado_por`) REFERENCES `l2601_usuarios` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_portada_actualizado_por`
    FOREIGN KEY (`actualizado_por`) REFERENCES `l2601_usuarios` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- 2) Tabla de auditoria/log
CREATE TABLE `l2601_portadas_paginas_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `page_key` varchar(80) NOT NULL,
  `accion` varchar(20) NOT NULL,
  `usuario_id` bigint(20) unsigned DEFAULT NULL,
  `snapshot_json` text DEFAULT NULL,
  `creado_en` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_portada_log_page` (`page_key`),
  KEY `idx_portada_log_accion` (`accion`),
  KEY `idx_portada_log_usuario` (`usuario_id`),
  CONSTRAINT `fk_portada_log_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `l2601_usuarios` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;