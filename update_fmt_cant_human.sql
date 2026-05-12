-- Script para actualizar la función fmt_cant_human con la nueva lógica de redondeo
-- Reglas:
-- - Si el valor es >= 0.07 kg (70 gramos) → redondear a 1 kg
-- - Si el valor es <= 0.06 kg (60 gramos) → redondear a 0.5 kg
-- NOTA: La regla especial para nopales (62 o menos → 60) se maneja en PHP
-- porque la función SQL no tiene acceso al nombre del ingrediente

DROP FUNCTION IF EXISTS `fmt_cant_human`;

DELIMITER $$

CREATE DEFINER=`root`@`localhost` FUNCTION `fmt_cant_human` (`val` DECIMAL(12,3)) RETURNS VARCHAR(32) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC 
BEGIN
  DECLARE ent INT; 
  DECLARE frac DECIMAL(5,3);
  
  SET val = ROUND(val, 3);
  
  -- Aplicar regla de redondeo para valores menores a 1 kg
  -- Si es >= 0.07 kg (70 gramos) → redondear a 1 kg
  -- Si es <= 0.06 kg (60 gramos) → redondear a 0.5 kg
  IF val > 0 AND val < 1 THEN
    IF val >= 0.07 THEN
      SET val = 1.0;
    ELSEIF val <= 0.06 THEN
      SET val = 0.5;
    END IF;
  END IF;
  
  SET ent  = FLOOR(val + 1e-9);
  SET frac = ROUND(val - ent, 2);
  
  -- Manejar fracciones comunes
  IF frac BETWEEN 0.24 AND 0.26 THEN 
    RETURN TRIM(CONCAT(ent,' 1/4'));
  ELSEIF frac BETWEEN 0.49 AND 0.51 THEN 
    RETURN TRIM(CONCAT(ent,' 1/2'));
  ELSEIF frac BETWEEN 0.74 AND 0.76 THEN 
    RETURN TRIM(CONCAT(ent,' 3/4'));
  END IF;
  
  RETURN TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM FORMAT(val,2)));
END$$

DELIMITER ;

