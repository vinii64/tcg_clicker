-- Migración: sistema de intercambios entre usuarios
-- Ejecutar una sola vez sobre la base `tcgdb` (phpMyAdmin > Importar, o `mysql tcgdb < intercambios.sql`)

CREATE TABLE IF NOT EXISTS `intercambios` (
  `id_intercambio` int(11) NOT NULL AUTO_INCREMENT,
  `id_emisor` int(11) NOT NULL,                       -- quien propone
  `id_receptor` int(11) NOT NULL,                     -- quien recibe la propuesta
  `estado` enum('pendiente','aceptado','rechazado','cancelado') NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_resolucion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_intercambio`),
  KEY `idx_receptor_estado` (`id_receptor`,`estado`),
  KEY `idx_emisor_estado` (`id_emisor`,`estado`),
  CONSTRAINT `intercambios_emisor` FOREIGN KEY (`id_emisor`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `intercambios_receptor` FOREIGN KEY (`id_receptor`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Cartas de cada propuesta.
-- lado = 'ofrece' -> carta que entrega el emisor
-- lado = 'pide'   -> carta que entrega el receptor
CREATE TABLE IF NOT EXISTS `intercambio_cartas` (
  `id_intercambio` int(11) NOT NULL,
  `id_carta` int(11) NOT NULL,
  `lado` enum('ofrece','pide') NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_intercambio`,`id_carta`,`lado`),
  KEY `idx_carta` (`id_carta`),
  CONSTRAINT `ic_intercambio` FOREIGN KEY (`id_intercambio`) REFERENCES `intercambios` (`id_intercambio`) ON DELETE CASCADE,
  CONSTRAINT `ic_carta` FOREIGN KEY (`id_carta`) REFERENCES `cartas` (`id_carta`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
