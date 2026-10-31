-- =====================================================================
--  tcg_clicker  ·  base de datos completa (tcgdb)
--  Importar desde phpMyAdmin (XAMPP): pestaña "Importar" > elegir este archivo > Continuar.
--  No hace falta crear la base antes: el script la crea si no existe.
--  Usa CREATE TABLE IF NOT EXISTS, así que NO pisa tablas que ya existan.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `tcgdb` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `tcgdb`;

-- ---------------------------------------------------------------------
--  usuarios  (login.php, register.php, index.php: puntos del clicker)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`          int(11)      NOT NULL AUTO_INCREMENT,
  `email`       varchar(100) NOT NULL,
  `usuario`     varchar(50)  NOT NULL,
  `admin`       tinyint(1)   NOT NULL DEFAULT 0,
  `contrasenia` varchar(255) NOT NULL,               -- hoy se guarda con md5()
  `puntos`      int(11)      NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
--  cartas  (panel de admin: la imagen se guarda dentro de la base)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `cartas` (
  `id_carta` int(11)     NOT NULL AUTO_INCREMENT,
  `imagen`   longblob    NOT NULL,
  `rareza`   enum('comun','especial','raro','epico','legendario','mitico','divino') NOT NULL DEFAULT 'comun',
  PRIMARY KEY (`id_carta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
--  inventario  (cada fila = una pila: usuario + carta + edición)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `inventario` (
  `id_usuario` int(11)  NOT NULL,
  `id_carta`   int(11)  NOT NULL,
  `edicion`    enum('normal','brillante','holografica','policroma','negativa') NOT NULL DEFAULT 'normal',
  `cantidad`   int(20)  NOT NULL,
  KEY `cartas_id`    (`id_carta`),
  KEY `usuarios_id`  (`id_usuario`) USING BTREE,
  CONSTRAINT `cartas_id`  FOREIGN KEY (`id_carta`)   REFERENCES `cartas`   (`id_carta`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `usuario_id` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`)       ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
--  intercambios  (propuestas entre usuarios)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `intercambios` (
  `id_intercambio`   int(11)  NOT NULL AUTO_INCREMENT,
  `id_emisor`        int(11)  NOT NULL,
  `id_receptor`      int(11)  NOT NULL,
  `estado`           enum('pendiente','aceptado','rechazado','cancelado') NOT NULL DEFAULT 'pendiente',
  `fecha_creacion`   datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_resolucion` datetime DEFAULT NULL,
  PRIMARY KEY (`id_intercambio`),
  KEY `idx_emisor`   (`id_emisor`),
  KEY `idx_receptor` (`id_receptor`),
  CONSTRAINT `int_emisor`   FOREIGN KEY (`id_emisor`)   REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `int_receptor` FOREIGN KEY (`id_receptor`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
--  intercambio_cartas  (qué cartas ofrece / pide cada propuesta)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `intercambio_cartas` (
  `id_intercambio` int(11) NOT NULL,
  `id_carta`       int(11) NOT NULL,
  `edicion`        enum('normal','brillante','holografica','policroma','negativa') NOT NULL DEFAULT 'normal',
  `lado`           enum('ofrece','pide') NOT NULL,
  `cantidad`       int(11) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id_intercambio`,`id_carta`,`edicion`,`lado`),
  KEY `idx_carta` (`id_carta`),
  CONSTRAINT `ic_carta`       FOREIGN KEY (`id_carta`)       REFERENCES `cartas`       (`id_carta`)       ON DELETE CASCADE,
  CONSTRAINT `ic_intercambio` FOREIGN KEY (`id_intercambio`) REFERENCES `intercambios` (`id_intercambio`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ---------------------------------------------------------------------
--  Usuario administrador inicial (para entrar al panel y subir cartas)
--    email: admin@gmail.com   ·   contraseña: admin   (cambiala después)
-- ---------------------------------------------------------------------
INSERT INTO `usuarios` (`email`, `usuario`, `admin`, `contrasenia`, `puntos`)
SELECT 'admin@gmail.com', 'admin', 1, '21232f297a57a5a743894a0e4a801fc3', 1000
WHERE NOT EXISTS (SELECT 1 FROM `usuarios` WHERE `email` = 'admin@gmail.com');
