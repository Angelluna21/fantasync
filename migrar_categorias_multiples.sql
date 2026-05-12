-- Script para migrar a categorías múltiples por platillo
-- Ejecuta este script en phpMyAdmin o desde la línea de comandos

-- 1. Crear tabla intermedia para relación muchos-a-muchos
CREATE TABLE IF NOT EXISTS `platillo_categoria` (
  `id_platillo` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL,
  PRIMARY KEY (`id_platillo`, `id_categoria`),
  KEY `fk_pc_platillo` (`id_platillo`),
  KEY `fk_pc_categoria` (`id_categoria`),
  CONSTRAINT `fk_pc_platillo` FOREIGN KEY (`id_platillo`) REFERENCES `platillo` (`id_platillo`) ON DELETE CASCADE,
  CONSTRAINT `fk_pc_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria_platillo` (`id_categoria`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2. Migrar datos existentes: copiar id_categoria de platillo a la nueva tabla
INSERT INTO `platillo_categoria` (`id_platillo`, `id_categoria`)
SELECT `id_platillo`, `id_categoria`
FROM `platillo`
WHERE `id_categoria` IS NOT NULL
ON DUPLICATE KEY UPDATE `id_categoria` = `id_categoria`;

-- 3. (Opcional) Mantener id_categoria en platillo como categoría principal
-- O puedes eliminarlo después si prefieres solo usar la tabla intermedia
-- ALTER TABLE `platillo` DROP FOREIGN KEY `fk_platillo_categoria`;
-- ALTER TABLE `platillo` DROP COLUMN `id_categoria`;


