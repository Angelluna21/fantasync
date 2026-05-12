-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 04-12-2025 a las 06:28:54
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `cocina_fantasy`
--

DELIMITER $$
--
-- Funciones
--
CREATE DEFINER=`root`@`localhost` FUNCTION `fmt_cant_human` (`val` DECIMAL(12,3)) RETURNS VARCHAR(32) CHARSET utf8mb4 COLLATE utf8mb4_general_ci DETERMINISTIC BEGIN
  DECLARE ent INT; DECLARE frac DECIMAL(5,3);
  SET val  = ROUND(val, 3);
  SET ent  = FLOOR(val + 1e-9);
  SET frac = ROUND(val - ent, 2);
  IF frac BETWEEN 0.24 AND 0.26 THEN RETURN TRIM(CONCAT(ent,' 1/4'));
  ELSEIF frac BETWEEN 0.49 AND 0.51 THEN RETURN TRIM(CONCAT(ent,' 1/2'));
  ELSEIF frac BETWEEN 0.74 AND 0.76 THEN RETURN TRIM(CONCAT(ent,' 3/4')); END IF;
  RETURN TRIM(TRAILING '.' FROM TRIM(TRAILING '0' FROM FORMAT(val,2)));
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria_platillo`
--

CREATE TABLE `categoria_platillo` (
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(60) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `categoria_platillo`
--

INSERT INTO `categoria_platillo` (`id_categoria`, `nombre`, `orden`) VALUES
(1, 'GUISADOS', 1),
(2, 'BUFFET INFANTIL', 11),
(3, 'BEBIDAS', 8),
(4, 'SALSAS', 9),
(5, 'GUARNICIONES', 2),
(6, '2 INFANTIL', 13),
(8, '3 TIEMPOS', 5),
(9, 'DESAYUNOS', 6),
(10, 'Parillada', 7),
(12, '1 INFANTIL', 12),
(14, 'MENU INFANTIL', 10),
(16, '3 INFANTIL', 14);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evento`
--

CREATE TABLE `evento` (
  `id_evento` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `titulo` varchar(150) DEFAULT NULL,
  `misa` time DEFAULT NULL,
  `recepcion` time DEFAULT NULL,
  `inicio` time DEFAULT NULL,
  `descorche` tinyint(1) NOT NULL DEFAULT 0,
  `cafe` tinyint(1) NOT NULL DEFAULT 0,
  `degustaciones` varchar(120) DEFAULT NULL,
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `evento`
--

INSERT INTO `evento` (`id_evento`, `fecha`, `titulo`, `misa`, `recepcion`, `inicio`, `descorche`, `cafe`, `degustaciones`, `notas`) VALUES
(35, '2025-11-24', 'SOCIAL', NULL, '21:53:00', NULL, 0, 0, NULL, NULL),
(37, '2025-11-29', 'Jazmin Casarrubias', NULL, '16:00:00', '16:30:00', 1, 0, NULL, NULL),
(38, '2025-11-28', 'Michell', NULL, '18:30:00', '19:00:00', 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evento_salon`
--

CREATE TABLE `evento_salon` (
  `id_evento_salon` int(11) NOT NULL,
  `id_evento` int(11) NOT NULL,
  `id_salon` int(11) NOT NULL,
  `adultos` int(11) NOT NULL DEFAULT 0,
  `ninos` int(11) NOT NULL DEFAULT 0,
  `misa` time DEFAULT NULL,
  `recepcion` time DEFAULT NULL,
  `inicio` time DEFAULT NULL,
  `descorche` tinyint(1) NOT NULL DEFAULT 0,
  `cafe` tinyint(1) NOT NULL DEFAULT 0,
  `degustaciones` varchar(120) DEFAULT NULL,
  `factor_nino` decimal(5,2) NOT NULL DEFAULT 0.70
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `evento_salon`
--

INSERT INTO `evento_salon` (`id_evento_salon`, `id_evento`, `id_salon`, `adultos`, `ninos`, `misa`, `recepcion`, `inicio`, `descorche`, `cafe`, `degustaciones`, `factor_nino`) VALUES
(38, 35, 1, 120, 25, NULL, '21:53:00', NULL, 0, 0, NULL, 0.70),
(40, 37, 1, 60, 20, NULL, '16:00:00', '16:30:00', 1, 0, NULL, 0.70),
(41, 38, 2, 75, 25, NULL, '18:30:00', '19:00:00', 0, 0, NULL, 0.70);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evento_salon_platillo`
--

CREATE TABLE `evento_salon_platillo` (
  `id_evento_salon_platillo` int(11) NOT NULL,
  `id_evento_salon` int(11) NOT NULL,
  `id_platillo` int(11) NOT NULL,
  `porciones_plan` int(11) NOT NULL,
  `orden` int(11) DEFAULT NULL,
  `notas` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `evento_salon_platillo`
--

INSERT INTO `evento_salon_platillo` (`id_evento_salon_platillo`, `id_evento_salon`, `id_platillo`, `porciones_plan`, `orden`, `notas`) VALUES
(325, 38, 30, 120, NULL, NULL),
(326, 38, 31, 120, NULL, NULL),
(327, 38, 33, 120, NULL, NULL),
(328, 38, 80, 120, NULL, NULL),
(329, 38, 124, 25, NULL, NULL),
(330, 38, 103, 25, NULL, NULL),
(331, 38, 98, 25, NULL, NULL),
(332, 38, 7, 25, NULL, NULL),
(333, 38, 104, 25, NULL, NULL),
(334, 38, 116, 25, NULL, NULL),
(335, 38, 114, 120, NULL, NULL),
(337, 38, 118, 25, NULL, NULL),
(338, 38, 94, 25, NULL, NULL),
(339, 38, 119, 25, NULL, NULL),
(340, 38, 101, 120, NULL, NULL),
(341, 38, 68, 120, NULL, NULL),
(342, 40, 4, 60, NULL, NULL),
(343, 40, 31, 60, NULL, NULL),
(344, 40, 5, 60, NULL, NULL),
(345, 40, 22, 60, NULL, NULL),
(346, 40, 28, 60, NULL, NULL),
(348, 40, 103, 20, NULL, NULL),
(349, 40, 98, 20, NULL, NULL),
(350, 40, 7, 20, NULL, NULL),
(351, 40, 116, 20, NULL, NULL),
(352, 40, 124, 20, NULL, NULL),
(353, 40, 14, 60, NULL, NULL),
(354, 40, 59, 60, NULL, NULL),
(355, 40, 64, 60, NULL, NULL),
(356, 40, 78, 60, NULL, NULL),
(357, 40, 72, 60, NULL, NULL),
(358, 40, 107, 60, NULL, NULL),
(359, 40, 109, 60, NULL, NULL),
(360, 41, 25, 75, NULL, NULL),
(361, 41, 4, 75, NULL, NULL),
(362, 41, 47, 75, NULL, NULL),
(363, 41, 5, 75, NULL, NULL),
(365, 41, 53, 75, NULL, NULL),
(366, 41, 14, 75, NULL, NULL),
(367, 41, 1, 75, NULL, NULL),
(368, 41, 3, 75, NULL, NULL),
(369, 40, 11, 60, NULL, NULL),
(372, 40, 94, 20, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingrediente`
--

CREATE TABLE `ingrediente` (
  `id_ingrediente` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `unidad` varchar(20) NOT NULL,
  `tamanio_presentacion` decimal(10,3) DEFAULT NULL,
  `presentacion_descripcion` varchar(80) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `ingrediente`
--

INSERT INTO `ingrediente` (`id_ingrediente`, `nombre`, `unidad`, `tamanio_presentacion`, `presentacion_descripcion`) VALUES
(1, 'Nopales', 'pz', NULL, NULL),
(2, 'Cebolla', 'kg', 0.000, ''),
(3, 'Cilantro', 'manojo', NULL, NULL),
(4, 'Jitomate', 'kg', 0.000, ''),
(5, 'Queso panela', 'kg', NULL, NULL),
(6, 'Frijol', 'kg', NULL, NULL),
(7, 'Tortilla para totopos', 'kg', NULL, NULL),
(8, 'Queso rallado', 'kg', NULL, NULL),
(9, 'Chile poblano', 'kg', NULL, NULL),
(10, 'Elote desgranado', 'kg', NULL, NULL),
(11, 'Arroz', 'kg', NULL, NULL),
(12, 'Pimiento morrón', 'kg', NULL, NULL),
(13, 'Queso Oaxaca', 'kg', NULL, NULL),
(14, 'Jamón', 'kg', NULL, NULL),
(15, 'Tocino', 'kg', NULL, NULL),
(16, 'Maciza de cerdo', 'kg', NULL, NULL),
(17, 'Espinazo de cerdo', 'kg', NULL, NULL),
(18, 'Limón', 'kg', NULL, NULL),
(19, 'Achiote', 'kg', NULL, NULL),
(20, 'Costilla de cerdo', 'kg', NULL, NULL),
(21, 'Chile morita', 'kg', NULL, NULL),
(22, 'Papa', 'kg', NULL, NULL),
(23, 'Pechuga de pollo', 'kg', NULL, NULL),
(24, 'Queso parmesano', 'kg', NULL, NULL),
(25, 'Mantequilla', 'kg', NULL, NULL),
(26, 'Leche', 'litro', NULL, NULL),
(27, 'Crema', 'litro', NULL, NULL),
(28, 'Salchicha', 'kg', NULL, NULL),
(29, 'Chile cuaresmeño', 'kg', NULL, NULL),
(30, 'Chile guajillo', 'kg', NULL, NULL),
(31, 'Chile ancho', 'kg', NULL, NULL),
(32, 'Chile pasilla', 'kg', NULL, NULL),
(33, 'Tortillas para sopa', 'kg', NULL, NULL),
(34, 'Sandía', 'pieza', NULL, NULL),
(35, 'Gelatina', 'pieza', NULL, NULL),
(38, 'Azúcar', 'kg', NULL, NULL),
(39, 'Longaniza', 'kg', NULL, NULL),
(40, 'Chicharrón', 'kg', NULL, NULL),
(41, 'Carne para suadero', 'kg', NULL, NULL),
(42, 'Carne al pastor', 'kg', NULL, NULL),
(44, 'Chorizo', 'kg', NULL, NULL),
(45, 'Calabaza italiana', 'kg', NULL, NULL),
(46, 'Chilacayote', 'kg', NULL, NULL),
(47, 'Tomatillo', 'kg', NULL, NULL),
(48, 'Zanahoria', 'kg', NULL, NULL),
(49, 'Lechuga', 'kg', NULL, NULL),
(50, 'Champiñón', 'kg', NULL, NULL),
(51, 'Zetas', 'kg', 0.000, ''),
(52, 'Aguacate', 'kg', NULL, NULL),
(53, 'Chile habanero', 'kg', NULL, NULL),
(54, 'Chile manzano', 'kg', NULL, NULL),
(55, 'Salsa de pipián', 'litro', NULL, NULL),
(56, 'Chipotle ', 'kg', 0.000, 'A granel o lata'),
(57, 'Queso Doble crema', 'kg', 0.000, ''),
(58, 'Quesillo (Oaxaca)', 'kg', NULL, 'Alias para quesillo'),
(59, 'Bisteck de Res', 'kg', 0.000, ''),
(60, 'Lomo de cerdo', 'kg', 0.000, ''),
(61, 'Pechuga', 'kg', 0.000, 'Pollo'),
(62, 'Rajas', 'kg', 0.000, ''),
(63, 'Hierba de olor', 'Manojo', 0.000, ''),
(65, 'Mermelada de chabacano', 'kilo', 0.000, ''),
(66, 'Ajonjoli', 'kilo', 0.000, ''),
(67, 'Arandano', 'kilo', 0.000, ''),
(68, 'Espinaca', 'Manojo', 0.000, ''),
(69, 'Mole don pancho', 'Bolsas', 0.000, ''),
(70, 'Lechuga Sangria', 'pz', 0.000, ''),
(71, 'Lechuga italiana ', 'pz', 0.000, ''),
(72, 'Lechuga Orejona', 'pz', 0.000, ''),
(73, 'Costilla', 'Kg', 0.000, ''),
(74, 'Hoja Santa', 'Manojo', 0.000, ''),
(75, 'Harina', 'kg', 0.000, ''),
(76, 'Huevo', 'kg', 0.000, ''),
(77, 'Lata de Piña en almibar', 'lata', 0.000, ''),
(78, 'Chicharron Delgado', 'Kg', 0.000, ''),
(79, 'Chicharron Grueso', 'kg', 0.000, ''),
(80, 'Pollo', 'kg', 0.000, ''),
(83, 'Chile serrano', 'kg', 0.000, ''),
(84, 'Chile de Arbol Seco', 'kg', 0.000, ''),
(85, 'Piña en almibar', 'Lata', 0.000, ''),
(86, 'Codito', 'gr', 0.000, ''),
(87, 'Huacal con Ala', 'kg', 0.000, ''),
(88, 'Perejil', 'Manojo', 0.000, ''),
(89, 'Apio', 'varas', 0.000, ''),
(90, 'Ejote', 'kg', 0.000, ''),
(91, 'Costilla de Puerco', 'kg', 0.000, ''),
(92, 'Masa ', 'kg', 0.000, ''),
(93, 'Elote', 'kg', 0.000, ''),
(94, 'Pepita', 'kg', 0.000, ''),
(96, 'Tomate', 'kg', 0.000, ''),
(97, 'Verdur para Mole Verde', 'Manojo', 0.000, ''),
(99, 'Fajitas de Pollo', 'kg', 0.000, ''),
(100, 'Cafe de Grano', 'gr', 0.000, ''),
(101, 'Canela', 'Rajas', 0.000, ''),
(103, 'Lechera', 'latas', 0.000, ''),
(104, 'Manzana Amarilla', 'kg', 0.000, ''),
(105, 'Tortilla', 'kg', 0.000, ''),
(106, 'Piña', 'pz', 0.000, ''),
(107, 'Rabano', 'kg', 0.000, ''),
(108, 'Harina de Trigo', 'kg', 0.000, ''),
(109, 'Espaguetti', 'gr', 0.000, ''),
(111, 'Pepino', 'pz', 0.000, ''),
(112, 'Suadero', 'kg', 0.000, ''),
(113, 'Leche clavel', 'latas', 0.000, ''),
(114, 'queso manchego', 'kg', 0.000, ''),
(115, 'Puntas de cerdo', 'kg', 0.000, ''),
(116, 'Epazote', 'Manojo', 0.000, ''),
(117, 'Pasta Fettuccine', 'gr', 0.000, ''),
(119, 'Pipian', 'kg', 0.000, ''),
(120, 'Pepita Verde', 'gr', 0.000, ''),
(122, 'Carne Molida de Res', 'kg', 0.000, ''),
(123, 'Hierbas de Olor', 'Manojo', 0.000, ''),
(124, 'Chicharo', 'kg', 0.000, ''),
(125, 'Carne de Res para deshebrar ', 'kg', 0.000, ''),
(126, 'Cebolla Morada', 'kg', 0.000, ''),
(127, 'Cebolla cambray', 'kg', 0.000, ''),
(133, 'Jamaica', 'kg', 0.000, ''),
(134, 'Tamarindo ', 'kg', 0.000, ''),
(136, 'Salchicha(pieza)', 'pz', 0.000, ''),
(137, 'Pechuga en 4', 'kg', 0.000, ''),
(138, 'Pechuga Aplanada ', 'kg', 0.000, ''),
(139, 'Pechuga en Fajas', 'kg', 0.000, ''),
(140, 'Chipotle seco', 'gr', 0.000, ''),
(141, 'chipotle Lata', 'Lata', 0.000, ''),
(142, 'Pechuga p. Deshebrar', 'kg', 0.000, ''),
(143, 'Peperoni', 'pz', 0.000, ''),
(144, 'Nopales para guisado', 'pz', 0.000, ''),
(145, 'Nopales para ensalada', 'pz', 0.000, ''),
(146, 'Sopa de codito', 'bolsas', 0.000, ''),
(147, 'cebolla pieza', 'pz', 0.000, ''),
(148, 'Horchata', 'botellas', 0.000, ''),
(149, 'Pan Molido', 'kg', 0.000, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `plan_compra`
--

CREATE TABLE `plan_compra` (
  `id_plan` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `id_ingrediente` int(11) NOT NULL,
  `cantidad` decimal(10,3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `plan_compra`
--

INSERT INTO `plan_compra` (`id_plan`, `fecha`, `id_ingrediente`, `cantidad`) VALUES
(1, '2025-08-29', 9, 3.000),
(2, '2025-08-29', 22, 3.000),
(3, '2025-08-29', 2, 1.000),
(4, '2025-08-29', 16, 3.000),
(5, '2025-08-29', 17, 1.000),
(6, '2025-08-29', 18, 2.000),
(7, '2025-08-29', 19, 0.500),
(8, '2025-08-29', 20, 2.000),
(9, '2025-08-29', 4, 1.000),
(10, '2025-08-29', 5, 0.750),
(11, '2025-08-30', 6, 5.000),
(12, '2025-08-30', 28, 1.000),
(13, '2025-08-30', 14, 0.500),
(14, '2025-08-30', 13, 1.000),
(15, '2025-08-30', 11, 1.000),
(16, '2025-08-30', 4, 1.000),
(17, '2025-08-30', 10, 0.500),
(18, '2025-08-30', 33, 2.000),
(19, '2025-08-30', 4, 2.000),
(20, '2025-08-30', 30, 0.750),
(21, '2025-08-30', 31, 1.000),
(22, '2025-08-30', 11, 1.000),
(23, '2025-08-30', 23, 8.000),
(24, '2025-08-30', 19, 0.500),
(25, '2025-08-30', 30, 1.000),
(35, '2025-09-19', 19, 78.000),
(37, '2025-09-19', 38, 40.000),
(50, '2025-09-22', 1, 1.000),
(51, '2025-09-22', 3, 1.000),
(52, '2025-09-22', 7, 1.000),
(53, '2025-09-22', 12, 1.000),
(54, '2025-09-22', 15, 1.000),
(55, '2025-09-22', 21, 1.000),
(56, '2025-09-22', 32, 1.000);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `platillo`
--

CREATE TABLE `platillo` (
  `id_platillo` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `id_categoria` int(11) DEFAULT NULL,
  `porciones_base` int(11) NOT NULL DEFAULT 100
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `platillo`
--

INSERT INTO `platillo` (`id_platillo`, `nombre`, `descripcion`, `id_categoria`, `porciones_base`) VALUES
(1, 'Ensalada de nopales', 'Nopales con jitomate, cebolla, cilantro y queso panela', 5, 100),
(2, 'Frijoles refritos', 'Frijoles molidos con cebolla y totopos', 5, 100),
(3, 'Arroz blanco con poblano y elote', 'Arroz con chile poblano y granos de elote', 5, 100),
(4, 'Alambre', 'Pimiento morrón, jamón, tocino y queso Oaxaca', 1, 100),
(5, 'Cochinita pibil', 'Carne de cerdo marinada en achiote', 1, 100),
(6, 'Costilla en morita', 'Costilla de cerdo guisada con chile morita', 1, 100),
(7, 'Papas a la francesa', 'Papas fritas en tiras', 2, 100),
(8, 'Pechugas con queso y tocino BBQ', 'Pechugas rellenas de Oaxaca y tocino', 1, 100),
(9, 'Lomo adobado', 'Lomo de cerdo adobado con guajillo y ancho', 1, 100),
(10, 'Sopa azteca*', 'Sopa con tortillas fritas, jitomate y pasilla', 8, 100),
(11, 'Fettuccine Alfredo', 'Pasta con crema, leche y parmesano', 8, 100),
(12, 'Pollo en pibil', 'Pollo en adobo de achiote', 1, 100),
(13, 'Ensalada florentina', 'Verduras con jamón y queso panela', 5, 100),
(14, 'Frijoles charros', 'Frijoles con salchicha, jamón y tocino', 5, 100),
(15, 'Xnipec (rajas habanero)', 'Cebolla morada, limón y habanero', 4, 100),
(19, 'Costilla en salsa verde', 'Con nopales', 1, 100),
(21, 'Tinga de pollo', 'Pollo deshebrado en jitomate, cebolla y chipotle', 1, 100),
(22, 'Pechuga deshebrada con rajas, crema y champiñones', 'Pollo con rajas de poblano, crema y champiñón', 1, 100),
(23, 'Tortitas de pechuga de pollo en salsa roja', 'Tortitas de pollo en salsa roja', 1, 100),
(24, 'Tortitas de pechuga de pollo en salsa verde', 'Tortitas de pollo en salsa verde', 1, 100),
(25, 'Pollo en pipián con chilacayotes', 'Pollo en salsa de pipián con chilacayote', 1, 100),
(26, 'Pollo deshebrado en mole rojo', 'Muslo/pechuga en mole rojo', 1, 100),
(27, 'Pollo en chipotle', 'Pollo en salsa de chipotle', 1, 100),
(28, 'Pollo con mole', 'Pollo en mole rojo', 1, 100),
(29, 'Pollo en mole verde', 'Pollo en mole verde', 1, 100),
(30, 'Bistec a la mexicana', 'Res con jitomate, cebolla y chile', 1, 100),
(31, 'Bistec en chile pasilla con papas', 'Res con papas en pasilla', 1, 100),
(32, 'Bistec en salsa verde con calabazas', 'Res en salsa verde con calabacitas', 1, 100),
(33, 'Bistec en salsa de jitomate con papas', 'Res con papas en caldillo de jitomate', 1, 100),
(34, 'Picadillo de res', 'Res molida con verduras', 1, 100),
(35, 'Tortitas de carne de res en salsa verde', 'Tortitas de res en salsa verde', 1, 100),
(36, 'Tortitas de carne de res en salsa roja', 'Tortitas de res en salsa roja', 1, 100),
(39, 'Longaniza con papas', 'Longaniza guisada con papa', 1, 100),
(40, 'Longaniza con nopales y rajas', 'Longaniza con nopal y rajas', 1, 100),
(41, 'Chicharrón en salsa verde', 'Chicharrón en salsa verde', 1, 100),
(42, 'Chicharrón en salsa roja o ranchera', 'Chicharrón en salsa roja', 1, 100),
(43, 'Puntas de cerdo en mole verde', 'Puntas de cerdo en pipián/verde', 1, 100),
(44, 'Costilla en chile pasilla con papas', 'Costilla con papas en pasilla', 1, 100),
(45, 'Puntas de cerdo en salsa roja', 'Puntas de cerdo en salsa roja', 1, 100),
(46, 'Puntas de cerdo en salsa verde', 'Puntas de cerdo en salsa verde', 1, 100),
(47, 'Carne de cerdo en morita con nopales', 'Cerdo en morita con nopales', 1, 100),
(48, 'Cerdo al pastor', 'Cerdo adobado al pastor', 1, 100),
(49, 'Rajas con papas', 'Rajas de poblano con papa', 1, 100),
(51, 'Rajas con crema', 'Rajas de poblano con crema', 1, 100),
(52, 'Cochinita vegetariana (setas)', 'Setas estilo pibil', 1, 100),
(53, 'Sopecitos de frijol ', 'Sopes mini con frijol y nopal', 1, 100),
(54, 'Timbal de esquites con calabazas y queso crema', 'Esquites con calabaza y queso crema', 1, 100),
(55, 'Salchichas a la mexicana', 'Salchicha con jitomate, cebolla y chile', 1, 100),
(56, 'Salchichas con papas', 'Salchicha con papa', 1, 100),
(57, 'Salchichas en chipotle', 'Salchicha en salsa de chipotle', 1, 100),
(58, 'Enfrijoladas con chorizo', 'Tortillas en frijol con chorizo', 1, 100),
(59, 'Nopales en escabeche', 'Nopales con vinagre y especias', 5, 100),
(60, 'Nopales con guajillo y elote', 'Nopales con guajillo y elote', 1, 100),
(61, 'Nopales con queso', 'Nopales salteados con queso', 1, 100),
(63, 'Arroz blanco con verdura', 'Arroz con elote y zanahoria', 5, 100),
(64, 'Arroz verde con elote', 'Arroz verde con elote', 5, 100),
(67, 'Ensalada de lechuga', 'Ensalada simple de lechuga', 5, 100),
(68, 'Choriquezo', 'Chorizo con queso fundido', 1, 100),
(69, 'Pechuga Desebrada en chipotle', 'Tiras de pollo al chipotle', 1, 100),
(70, 'Suadero', 'Carne para tacos de suadero', 1, 100),
(71, 'Pastor', 'Carne al pastor para tacos', 1, 100),
(72, 'Guacamole', 'Aguacate machacado con condimentos', 4, 100),
(73, 'Papas gratinadas', 'Papas al horno con queso', 1, 100),
(74, 'Zetas al pibil', 'Setas guisadas en adobo pibil', 1, 100),
(75, 'Habanero', 'Salsa/pico de habanero', 1, 100),
(76, 'Bofe', 'Bofe guisado', 1, 100),
(77, 'Rajas de manzano', 'Rajas de chile manzano', 1, 100),
(78, 'Salsa roja', 'Salsa de chiles rojos', 4, 100),
(79, 'Salsa verde', 'Salsa de tomatillo', 4, 100),
(80, 'Arroz Blanco', '', 5, 100),
(81, 'Ensalada de Tres Lechugas', '', 1, 100),
(82, 'Codiito a la Hawaiana', '', 1, 100),
(83, 'Crema de Elote', '', 1, 100),
(84, 'Pure de Papa', '', 1, 100),
(86, 'Alambre Vegetariano', '', 1, 100),
(87, 'Cafe', '', 3, 100),
(88, 'Espaguetti Alfredo', '', 1, 100),
(89, 'Rajas con papas(sin crema)', '', 1, 100),
(90, 'Ensalada de Manzana', '', 1, 100),
(91, 'Ensalada de Lechuga, Pepino, Rabano ', '', 1, 100),
(92, 'Arroz rojo con zanahoria y ejote', '', 5, 100),
(94, 'Fajas de Pollo (kentoki)', '', 12, 100),
(95, 'Pechuga Rellenas de Jamon y Queso empanizada', '', 1, 100),
(96, 'Esquites con calabaza', '', 1, 100),
(98, 'Gelatina', '', 2, 100),
(100, 'Ropa Vieja', '', 1, 100),
(101, 'Chicharron en salsa Norteña', '', 1, 100),
(102, 'Complementos de parrillada', '', 10, 100),
(103, 'Fruta', '', 2, 100),
(104, 'Pechuga Empanizada', '', 5, 100),
(105, 'Pozole', '', 9, 100),
(106, 'Chilaquilles', '', 9, 100),
(107, 'Agua de Jamaica', '', 3, 100),
(108, 'Agua de Limon', '', 3, 100),
(109, 'Agua de Tamarindo', '', 3, 100),
(110, 'Agua de Sandia', '', 3, 100),
(114, 'Caldo Tlalpeño', '', 9, 100),
(115, 'Arroz rojo con zanahoria y elote ', '', 5, 100),
(116, 'Pizza', '', 2, 100),
(118, 'Espaguetti', '', 12, 100),
(119, 'Papaz a la francesa', '', 12, 100),
(120, 'Flautas de pollo con lechuga y queso', '', 6, 100),
(121, 'Esppaguetti', '', 6, 100),
(122, 'Papas a la francesas', '', 6, 100),
(124, 'spagguetti', '', 2, 100),
(126, 'TEMPORAL INFANTIL', NULL, 14, 100),
(127, 'Nuggets de pollo', '', 2, 100),
(128, 'fajittas de pollo', '', 6, 100),
(130, 'spaguetti', '', 16, 100),
(131, 'pizzaa', '', 16, 100),
(132, 'papaz a la franceza', '', 16, 100),
(133, 'Agua de Horchata', '', 3, 100);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `platillo_categoria`
--

CREATE TABLE `platillo_categoria` (
  `id_platillo` int(11) NOT NULL,
  `id_categoria` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `platillo_categoria`
--

INSERT INTO `platillo_categoria` (`id_platillo`, `id_categoria`) VALUES
(1, 5),
(2, 5),
(3, 5),
(4, 1),
(5, 1),
(6, 1),
(7, 2),
(7, 6),
(7, 12),
(8, 1),
(9, 1),
(10, 8),
(11, 8),
(12, 1),
(13, 5),
(14, 1),
(15, 4),
(19, 1),
(21, 1),
(22, 1),
(23, 1),
(24, 1),
(25, 1),
(26, 1),
(27, 1),
(28, 1),
(29, 1),
(30, 1),
(31, 1),
(32, 1),
(33, 1),
(34, 1),
(35, 1),
(36, 1),
(39, 1),
(40, 1),
(41, 1),
(42, 1),
(43, 1),
(44, 1),
(45, 1),
(46, 1),
(47, 1),
(48, 1),
(49, 1),
(51, 1),
(52, 1),
(53, 1),
(54, 1),
(55, 1),
(56, 1),
(57, 1),
(58, 1),
(59, 1),
(60, 1),
(61, 1),
(63, 5),
(64, 5),
(67, 5),
(68, 1),
(69, 1),
(70, 1),
(71, 1),
(72, 4),
(73, 1),
(74, 1),
(75, 1),
(76, 1),
(77, 1),
(78, 4),
(79, 4),
(80, 5),
(81, 1),
(82, 1),
(83, 1),
(84, 1),
(86, 1),
(87, 3),
(88, 1),
(89, 1),
(90, 1),
(91, 1),
(92, 5),
(94, 8),
(95, 1),
(96, 1),
(98, 6),
(100, 1),
(101, 1),
(102, 10),
(103, 2),
(104, 6),
(105, 9),
(106, 9),
(107, 3),
(108, 3),
(109, 3),
(110, 3),
(114, 9),
(115, 5),
(116, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `receta`
--

CREATE TABLE `receta` (
  `id_platillo` int(11) NOT NULL,
  `id_ingrediente` int(11) NOT NULL,
  `cantidad_por_base` decimal(10,3) NOT NULL,
  `nota` varchar(120) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `receta`
--

INSERT INTO `receta` (`id_platillo`, `id_ingrediente`, `cantidad_por_base`, `nota`) VALUES
(1, 1, 50.000, NULL),
(1, 2, 0.500, NULL),
(1, 3, 10.000, NULL),
(1, 4, 0.500, NULL),
(1, 5, 0.750, NULL),
(1, 22, 10.000, NULL),
(1, 133, 900.000, NULL),
(2, 2, 0.500, NULL),
(2, 6, 5.000, NULL),
(2, 7, 2.500, NULL),
(2, 136, 4.000, NULL),
(3, 9, 1.000, NULL),
(3, 10, 0.500, NULL),
(3, 11, 1.500, NULL),
(4, 2, 1.500, NULL),
(4, 12, 4.000, NULL),
(4, 13, 0.750, NULL),
(4, 14, 0.750, NULL),
(4, 15, 0.750, NULL),
(4, 59, 4.000, NULL),
(5, 16, 3.000, NULL),
(5, 17, 1.000, NULL),
(5, 18, 2.000, NULL),
(5, 19, 0.500, NULL),
(6, 1, 25.000, NULL),
(6, 2, 1.000, NULL),
(6, 20, 4.000, NULL),
(6, 21, 0.250, NULL),
(6, 47, 4.000, NULL),
(7, 22, 10.000, NULL),
(8, 13, 1.000, NULL),
(8, 15, 1.000, NULL),
(8, 23, 10.000, NULL),
(9, 30, 0.750, NULL),
(9, 31, 0.500, NULL),
(9, 60, 1.700, NULL),
(10, 4, 2.000, NULL),
(10, 30, 0.750, NULL),
(10, 31, 0.500, NULL),
(10, 32, 0.500, NULL),
(10, 33, 3.000, NULL),
(11, 2, 1.000, NULL),
(11, 24, 0.500, NULL),
(11, 25, 1.000, NULL),
(11, 26, 5.000, NULL),
(11, 27, 5.000, NULL),
(11, 88, 1.000, NULL),
(11, 117, 500.000, NULL),
(12, 19, 0.500, NULL),
(12, 23, 8.000, NULL),
(12, 30, 1.000, NULL),
(13, 2, 1.000, NULL),
(13, 5, 0.750, NULL),
(13, 24, 0.750, NULL),
(13, 25, 0.500, NULL),
(14, 6, 5.000, NULL),
(14, 14, 0.500, NULL),
(14, 15, 0.600, NULL),
(14, 28, 1.000, NULL),
(15, 18, 1.000, NULL),
(15, 53, 0.250, NULL),
(15, 126, 0.500, NULL),
(19, 1, 25.000, NULL),
(19, 2, 1.500, NULL),
(19, 3, 1.000, NULL),
(19, 20, 4.000, NULL),
(19, 29, 0.500, NULL),
(19, 47, 4.000, NULL),
(21, 2, 1.500, NULL),
(21, 4, 4.000, NULL),
(21, 56, 1.000, NULL),
(22, 27, 4.000, NULL),
(22, 50, 1.000, NULL),
(22, 61, 4.000, NULL),
(23, 1, 25.000, NULL),
(23, 2, 1.500, NULL),
(23, 4, 4.000, NULL),
(23, 29, 0.500, NULL),
(23, 76, 2.500, NULL),
(24, 1, 25.000, NULL),
(24, 2, 1.500, NULL),
(24, 3, 1.000, NULL),
(24, 29, 0.500, NULL),
(24, 61, 4.000, NULL),
(24, 76, 0.500, NULL),
(24, 96, 4.000, NULL),
(25, 23, 4.000, NULL),
(25, 46, 2.000, NULL),
(25, 119, 2.000, NULL),
(28, 61, 4.000, NULL),
(28, 69, 2.000, NULL),
(29, 2, 1.000, NULL),
(29, 9, 0.500, NULL),
(29, 29, 0.250, NULL),
(29, 61, 4.000, NULL),
(29, 66, 0.500, NULL),
(29, 68, 1.000, NULL),
(29, 96, 0.500, NULL),
(29, 97, 1.000, NULL),
(29, 120, 0.750, NULL),
(30, 2, 1.500, NULL),
(30, 3, 1.000, NULL),
(30, 4, 3.000, NULL),
(30, 29, 0.750, NULL),
(30, 59, 4.000, NULL),
(31, 2, 1.000, NULL),
(31, 4, 1.500, NULL),
(31, 22, 3.000, NULL),
(31, 32, 0.750, NULL),
(31, 59, 4.000, NULL),
(32, 2, 1.000, NULL),
(32, 3, 5.000, NULL),
(32, 29, 0.250, NULL),
(32, 45, 3.000, NULL),
(32, 59, 4.000, NULL),
(32, 96, 4.000, NULL),
(33, 2, 1.500, NULL),
(33, 4, 4.000, NULL),
(33, 22, 3.000, NULL),
(33, 59, 4.000, NULL),
(33, 83, 0.250, NULL),
(34, 2, 1.000, NULL),
(34, 4, 4.000, NULL),
(34, 22, 1.000, NULL),
(34, 48, 1.000, NULL),
(34, 59, 4.000, NULL),
(34, 124, 1.000, NULL),
(35, 1, 25.000, NULL),
(35, 2, 1.500, NULL),
(35, 3, 1.000, NULL),
(35, 29, 0.500, NULL),
(35, 76, 2.500, NULL),
(35, 96, 4.000, NULL),
(35, 125, 4.000, NULL),
(36, 1, 25.000, NULL),
(36, 2, 1.500, NULL),
(36, 4, 4.000, NULL),
(36, 76, 2.500, NULL),
(36, 84, 0.500, NULL),
(36, 125, 4.000, NULL),
(39, 2, 1.500, NULL),
(39, 22, 2.500, NULL),
(39, 39, 4.000, NULL),
(39, 114, 0.500, NULL),
(41, 1, 25.000, NULL),
(41, 2, 1.500, NULL),
(41, 3, 1.000, NULL),
(41, 29, 0.500, NULL),
(41, 47, 4.000, NULL),
(41, 78, 0.750, NULL),
(41, 79, 0.250, NULL),
(43, 9, 0.500, NULL),
(43, 29, 0.500, NULL),
(43, 49, 1.000, NULL),
(43, 66, 0.500, NULL),
(43, 74, 1.000, NULL),
(43, 83, 0.250, NULL),
(43, 94, 0.750, NULL),
(43, 96, 0.500, NULL),
(43, 97, 1.000, NULL),
(43, 115, 4.000, NULL),
(44, 2, 1.500, NULL),
(44, 4, 2.000, NULL),
(44, 22, 2.000, NULL),
(44, 32, 0.500, NULL),
(44, 73, 4.000, NULL),
(45, 2, 1.500, NULL),
(45, 4, 4.000, NULL),
(45, 22, 2.500, NULL),
(45, 84, 0.250, NULL),
(45, 115, 4.000, NULL),
(46, 1, 25.000, NULL),
(46, 2, 1.500, NULL),
(46, 3, 1.000, NULL),
(46, 83, 0.500, NULL),
(46, 96, 4.000, NULL),
(46, 115, 4.000, NULL),
(51, 2, 1.500, NULL),
(51, 9, 6.000, NULL),
(51, 27, 4.000, NULL),
(53, 6, 1.500, NULL),
(53, 49, 2.000, NULL),
(53, 92, 8.000, NULL),
(55, 2, 1.500, NULL),
(55, 3, 1.000, NULL),
(55, 4, 4.000, NULL),
(55, 28, 4.000, NULL),
(55, 29, 0.500, NULL),
(56, 2, 1.500, NULL),
(56, 22, 2.500, NULL),
(56, 28, 4.000, NULL),
(57, 56, 1.000, NULL),
(58, 6, 1.500, NULL),
(58, 8, 0.500, NULL),
(58, 27, 3.000, NULL),
(58, 39, 4.000, NULL),
(58, 105, 6.000, NULL),
(59, 1, 50.000, NULL),
(59, 2, 1.000, NULL),
(59, 48, 1.500, NULL),
(60, 1, 50.000, NULL),
(60, 2, 1.000, NULL),
(60, 10, 0.500, NULL),
(60, 30, 0.750, NULL),
(60, 123, 1.000, NULL),
(64, 2, 1.000, NULL),
(64, 9, 1.000, NULL),
(64, 10, 0.500, NULL),
(64, 11, 1.500, NULL),
(68, 1, 25.000, NULL),
(68, 2, 1.500, NULL),
(68, 29, 0.250, NULL),
(68, 39, 4.000, NULL),
(68, 114, 0.750, NULL),
(69, 4, 4.000, NULL),
(70, 2, 1.500, NULL),
(70, 22, 3.000, NULL),
(70, 29, 0.250, NULL),
(70, 112, 4.000, NULL),
(71, 2, 1.500, NULL),
(71, 30, 0.750, NULL),
(71, 59, 4.000, NULL),
(71, 106, 1.000, NULL),
(72, 2, 1.000, NULL),
(72, 3, 1.000, NULL),
(72, 52, 1.000, NULL),
(72, 83, 0.500, NULL),
(72, 96, 1.000, NULL),
(73, 13, 1.000, NULL),
(73, 14, 1.000, NULL),
(73, 22, 16.000, NULL),
(73, 25, 1.000, NULL),
(73, 27, 4.000, NULL),
(73, 88, 1.000, NULL),
(74, 18, 2.000, NULL),
(74, 19, 0.250, NULL),
(74, 30, 0.250, NULL),
(74, 51, 4.000, NULL),
(78, 2, 1.000, NULL),
(78, 4, 1.000, NULL),
(78, 84, 0.250, NULL),
(78, 96, 1.000, NULL),
(79, 2, 1.000, NULL),
(79, 3, 1.000, NULL),
(79, 83, 0.250, NULL),
(79, 96, 1.000, NULL),
(80, 10, 0.380, NULL),
(80, 11, 1.500, NULL),
(80, 48, 1.000, NULL),
(80, 93, 0.500, NULL),
(80, 124, 1.000, NULL),
(80, 147, 1.000, NULL),
(81, 65, 1.000, NULL),
(81, 66, 0.750, NULL),
(81, 67, 1.000, NULL),
(81, 68, 1.000, NULL),
(81, 70, 3.000, NULL),
(81, 71, 3.000, NULL),
(81, 72, 3.000, NULL),
(82, 14, 1.000, NULL),
(82, 25, 0.500, NULL),
(82, 27, 6.000, NULL),
(82, 77, 2.000, NULL),
(83, 10, 10.200, NULL),
(83, 26, 14.300, NULL),
(83, 27, 6.000, NULL),
(83, 87, 4.000, NULL),
(83, 89, 3.000, NULL),
(84, 22, 14.500, NULL),
(84, 25, 1.000, NULL),
(84, 26, 3.000, NULL),
(84, 27, 3.000, NULL),
(86, 2, 1.500, NULL),
(86, 12, 4.000, NULL),
(86, 13, 0.750, NULL),
(86, 14, 0.750, NULL),
(86, 50, 4.000, NULL),
(87, 38, 1.500, NULL),
(87, 100, 400.000, NULL),
(87, 101, 3.000, NULL),
(88, 24, 0.500, NULL),
(88, 25, 1.000, NULL),
(88, 26, 14.000, NULL),
(88, 27, 6.000, NULL),
(88, 88, 1.000, NULL),
(88, 109, 200.000, NULL),
(89, 2, 1.500, NULL),
(89, 9, 6.000, NULL),
(89, 22, 4.000, NULL),
(90, 27, 6.000, NULL),
(90, 104, 10.000, NULL),
(90, 113, 4.000, NULL),
(91, 18, 1.000, NULL),
(91, 49, 2.000, NULL),
(91, 107, 0.500, NULL),
(91, 111, 5.000, NULL),
(92, 4, 1.000, NULL),
(92, 11, 1.500, NULL),
(92, 48, 1.000, NULL),
(92, 90, 0.500, NULL),
(94, 75, 1.000, NULL),
(94, 76, 0.500, NULL),
(94, 99, 15.000, NULL),
(94, 149, 2.000, NULL),
(95, 13, 2.750, NULL),
(95, 14, 2.750, NULL),
(95, 61, 105.000, NULL),
(95, 75, 1.000, NULL),
(95, 76, 2.000, NULL),
(96, 2, 0.500, NULL),
(96, 10, 4.000, NULL),
(96, 27, 1.500, NULL),
(96, 29, 0.500, NULL),
(96, 45, 3.000, NULL),
(96, 116, 1.000, NULL),
(98, 35, 1.000, NULL),
(100, 2, 105.000, NULL),
(100, 4, 4.000, NULL),
(100, 9, 2.500, NULL),
(100, 22, 2.000, NULL),
(100, 124, 1.500, NULL),
(100, 125, 4.000, NULL),
(101, 1, 25.000, NULL),
(101, 2, 1.500, NULL),
(101, 3, 1.000, NULL),
(101, 4, 4.000, NULL),
(101, 29, 0.500, NULL),
(101, 78, 0.750, NULL),
(101, 79, 0.250, NULL),
(102, 2, 1.000, NULL),
(102, 18, 0.500, NULL),
(102, 54, 0.500, NULL),
(102, 83, 0.750, NULL),
(102, 106, 1.000, NULL),
(102, 127, 2.000, NULL),
(103, 34, 0.250, NULL),
(106, 2, 1.000, NULL),
(106, 8, 0.750, NULL),
(106, 23, 4.000, NULL),
(106, 27, 3.000, NULL),
(106, 29, 0.500, NULL),
(106, 83, 0.250, NULL),
(106, 105, 12.000, NULL),
(106, 116, 1.000, NULL),
(107, 38, 2.000, NULL),
(107, 133, 1.000, NULL),
(108, 18, 2.500, NULL),
(108, 38, 2.500, NULL),
(109, 38, 2.500, NULL),
(109, 134, 2.000, NULL),
(110, 34, 1.000, NULL),
(110, 38, 2.000, NULL),
(114, 4, 1.000, NULL),
(114, 22, 1.500, NULL),
(114, 48, 2.000, NULL),
(114, 87, 3.000, NULL),
(114, 116, 1.000, NULL),
(114, 141, 2.000, NULL),
(114, 142, 3.000, NULL),
(116, 143, 1.000, NULL),
(118, 14, 0.250, NULL),
(118, 27, 1.000, NULL),
(118, 146, 1.500, NULL),
(119, 22, 10.000, NULL),
(121, 14, 0.250, NULL),
(121, 27, 1.000, NULL),
(121, 86, 1.500, NULL),
(122, 22, 10.000, NULL),
(124, 14, 0.250, NULL),
(124, 27, 1.000, NULL),
(124, 146, 1.500, NULL),
(127, 75, 1.000, NULL),
(127, 76, 0.500, NULL),
(127, 99, 15.000, NULL),
(128, 75, 1.000, NULL),
(128, 76, 0.500, NULL),
(128, 99, 15.000, NULL),
(131, 143, 1.000, NULL),
(133, 148, 3.000, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `salon`
--

CREATE TABLE `salon` (
  `id_salon` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `alias` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `salon`
--

INSERT INTO `salon` (`id_salon`, `nombre`, `alias`) VALUES
(1, 'CARMELO', NULL),
(2, 'SAN RAFAEL', NULL);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_evento_compra_por_salon`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_evento_compra_por_salon` (
`id_evento` int(11)
,`id_evento_salon` int(11)
,`salon` varchar(80)
,`id_ingrediente` int(11)
,`ingrediente` varchar(120)
,`unidad` varchar(20)
,`cantidad` decimal(47,3)
,`cantidad_mostrada` varchar(32)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_evento_compra_total`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_evento_compra_total` (
`id_evento` int(11)
,`id_ingrediente` int(11)
,`ingrediente` varchar(120)
,`unidad` varchar(20)
,`cantidad` decimal(47,3)
,`cantidad_mostrada` varchar(32)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_evento_salon_header`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_evento_salon_header` (
`id_evento_salon` int(11)
,`id_evento` int(11)
,`fecha` date
,`salon` varchar(80)
,`adultos` int(11)
,`ninos` int(11)
,`misa` time
,`recepcion` time
,`inicio` time
,`descorche` varchar(2)
,`cafe` varchar(2)
,`degustaciones` varchar(120)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_evento_salon_platillo_ingrediente`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_evento_salon_platillo_ingrediente` (
`id_evento` int(11)
,`salon` varchar(80)
,`seccion` varchar(60)
,`orden_seccion` int(11)
,`orden_platillo` int(11)
,`platillo` varchar(150)
,`ingrediente` varchar(120)
,`unidad` varchar(20)
,`presentacion_descripcion` varchar(80)
,`nota_receta` varchar(120)
,`cantidad_calc` decimal(25,3)
,`cantidad_mostrada` varchar(32)
,`id_evento_salon` int(11)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `vw_platillo_receta`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `vw_platillo_receta` (
`id_platillo` int(11)
,`platillo` varchar(150)
,`porciones_base` int(11)
,`id_categoria` int(11)
,`id_ingrediente` int(11)
,`ingrediente` varchar(120)
,`unidad` varchar(20)
,`presentacion_descripcion` varchar(80)
,`cantidad_por_base` decimal(10,3)
,`nota_receta` varchar(120)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_evento_compra_por_salon`
--
DROP TABLE IF EXISTS `vw_evento_compra_por_salon`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_evento_compra_por_salon`  AS SELECT `t`.`id_evento` AS `id_evento`, `t`.`id_evento_salon` AS `id_evento_salon`, `t`.`salon` AS `salon`, `t`.`id_ingrediente` AS `id_ingrediente`, `t`.`ingrediente` AS `ingrediente`, `t`.`unidad` AS `unidad`, sum(round(`t`.`cantidad_calc`,3)) AS `cantidad`, `fmt_cant_human`(sum(round(`t`.`cantidad_calc`,3))) AS `cantidad_mostrada` FROM (select `es`.`id_evento` AS `id_evento`,`es`.`id_evento_salon` AS `id_evento_salon`,coalesce(`s`.`alias`,`s`.`nombre`) AS `salon`,`i`.`id_ingrediente` AS `id_ingrediente`,`i`.`nombre` AS `ingrediente`,`i`.`unidad` AS `unidad`,round(`r`.`cantidad_por_base` * `esp`.`porciones_plan` / nullif(`p`.`porciones_base`,0),3) AS `cantidad_calc` from (((((`evento_salon_platillo` `esp` join `evento_salon` `es` on(`es`.`id_evento_salon` = `esp`.`id_evento_salon`)) join `salon` `s` on(`s`.`id_salon` = `es`.`id_salon`)) join `platillo` `p` on(`p`.`id_platillo` = `esp`.`id_platillo`)) join `receta` `r` on(`r`.`id_platillo` = `p`.`id_platillo`)) join `ingrediente` `i` on(`i`.`id_ingrediente` = `r`.`id_ingrediente`)) union all select `es`.`id_evento` AS `id_evento`,`es`.`id_evento_salon` AS `id_evento_salon`,coalesce(`s`.`alias`,`s`.`nombre`) AS `salon`,`i`.`id_ingrediente` AS `id_ingrediente`,`i`.`nombre` AS `ingrediente`,`i`.`unidad` AS `unidad`,round(`r`.`cantidad_por_base` * (`es`.`adultos` + `es`.`factor_nino` * `es`.`ninos`) / nullif(`p`.`porciones_base`,0),3) AS `cantidad_calc` from ((((`evento_salon` `es` join `salon` `s` on(`s`.`id_salon` = `es`.`id_salon`)) join `platillo` `p` on(`p`.`id_platillo` = 87)) join `receta` `r` on(`r`.`id_platillo` = `p`.`id_platillo`)) join `ingrediente` `i` on(`i`.`id_ingrediente` = `r`.`id_ingrediente`)) where `es`.`cafe` = 1 union all select `es`.`id_evento` AS `id_evento`,`es`.`id_evento_salon` AS `id_evento_salon`,coalesce(`s`.`alias`,`s`.`nombre`) AS `salon`,`i`.`id_ingrediente` AS `id_ingrediente`,`i`.`nombre` AS `ingrediente`,`i`.`unidad` AS `unidad`,round(`es`.`adultos`,3) AS `cantidad_calc` from ((`evento_salon` `es` join `salon` `s` on(`s`.`id_salon` = `es`.`id_salon`)) join `ingrediente` `i` on(`i`.`id_ingrediente` = 18)) where `es`.`descorche` = 1) AS `t` GROUP BY `t`.`id_evento`, `t`.`id_evento_salon`, `t`.`id_ingrediente`, `t`.`ingrediente`, `t`.`unidad`, `t`.`salon` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_evento_compra_total`
--
DROP TABLE IF EXISTS `vw_evento_compra_total`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_evento_compra_total`  AS SELECT `t`.`id_evento` AS `id_evento`, `t`.`id_ingrediente` AS `id_ingrediente`, `t`.`ingrediente` AS `ingrediente`, `t`.`unidad` AS `unidad`, sum(round(`t`.`cantidad_calc`,3)) AS `cantidad`, `fmt_cant_human`(sum(round(`t`.`cantidad_calc`,3))) AS `cantidad_mostrada` FROM (select `es`.`id_evento` AS `id_evento`,`i`.`id_ingrediente` AS `id_ingrediente`,`i`.`nombre` AS `ingrediente`,`i`.`unidad` AS `unidad`,round(`r`.`cantidad_por_base` * `esp`.`porciones_plan` / nullif(`p`.`porciones_base`,0),3) AS `cantidad_calc` from ((((`evento_salon_platillo` `esp` join `evento_salon` `es` on(`es`.`id_evento_salon` = `esp`.`id_evento_salon`)) join `platillo` `p` on(`p`.`id_platillo` = `esp`.`id_platillo`)) join `receta` `r` on(`r`.`id_platillo` = `p`.`id_platillo`)) join `ingrediente` `i` on(`i`.`id_ingrediente` = `r`.`id_ingrediente`)) union all select `es`.`id_evento` AS `id_evento`,`i`.`id_ingrediente` AS `id_ingrediente`,`i`.`nombre` AS `ingrediente`,`i`.`unidad` AS `unidad`,round(`r`.`cantidad_por_base` * (`es`.`adultos` + `es`.`factor_nino` * `es`.`ninos`) / nullif(`p`.`porciones_base`,0),3) AS `cantidad_calc` from (((`evento_salon` `es` join `platillo` `p` on(`p`.`id_platillo` = 87)) join `receta` `r` on(`r`.`id_platillo` = `p`.`id_platillo`)) join `ingrediente` `i` on(`i`.`id_ingrediente` = `r`.`id_ingrediente`)) where `es`.`cafe` = 1 union all select `es`.`id_evento` AS `id_evento`,`i`.`id_ingrediente` AS `id_ingrediente`,`i`.`nombre` AS `ingrediente`,`i`.`unidad` AS `unidad`,round(`es`.`adultos`,3) AS `cantidad_calc` from (`evento_salon` `es` join `ingrediente` `i` on(`i`.`id_ingrediente` = 18)) where `es`.`descorche` = 1) AS `t` GROUP BY `t`.`id_evento`, `t`.`id_ingrediente`, `t`.`ingrediente`, `t`.`unidad` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_evento_salon_header`
--
DROP TABLE IF EXISTS `vw_evento_salon_header`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_evento_salon_header`  AS SELECT `es`.`id_evento_salon` AS `id_evento_salon`, `e`.`id_evento` AS `id_evento`, `e`.`fecha` AS `fecha`, coalesce(`s`.`alias`,`s`.`nombre`) AS `salon`, `es`.`adultos` AS `adultos`, `es`.`ninos` AS `ninos`, coalesce(`es`.`misa`,`e`.`misa`) AS `misa`, coalesce(`es`.`recepcion`,`e`.`recepcion`) AS `recepcion`, coalesce(`es`.`inicio`,`e`.`inicio`) AS `inicio`, if(coalesce(`es`.`descorche`,`e`.`descorche`) = 1,'SI','NO') AS `descorche`, if(coalesce(`es`.`cafe`,`e`.`cafe`) = 1,'SI','NO') AS `cafe`, coalesce(`es`.`degustaciones`,`e`.`degustaciones`) AS `degustaciones` FROM ((`evento_salon` `es` join `evento` `e` on(`e`.`id_evento` = `es`.`id_evento`)) join `salon` `s` on(`s`.`id_salon` = `es`.`id_salon`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_evento_salon_platillo_ingrediente`
--
DROP TABLE IF EXISTS `vw_evento_salon_platillo_ingrediente`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_evento_salon_platillo_ingrediente`  AS SELECT `es`.`id_evento` AS `id_evento`, coalesce(`s`.`alias`,`s`.`nombre`) AS `salon`, `cp`.`nombre` AS `seccion`, `cp`.`orden` AS `orden_seccion`, `esp`.`orden` AS `orden_platillo`, `p`.`nombre` AS `platillo`, `i`.`nombre` AS `ingrediente`, `i`.`unidad` AS `unidad`, `i`.`presentacion_descripcion` AS `presentacion_descripcion`, `r`.`nota` AS `nota_receta`, round(`r`.`cantidad_por_base` * `esp`.`porciones_plan` / nullif(`p`.`porciones_base`,0),3) AS `cantidad_calc`, `fmt_cant_human`(round(`r`.`cantidad_por_base` * `esp`.`porciones_plan` / nullif(`p`.`porciones_base`,0),3)) AS `cantidad_mostrada`, `es`.`id_evento_salon` AS `id_evento_salon` FROM ((((((`evento_salon_platillo` `esp` join `evento_salon` `es` on(`es`.`id_evento_salon` = `esp`.`id_evento_salon`)) join `salon` `s` on(`s`.`id_salon` = `es`.`id_salon`)) join `platillo` `p` on(`p`.`id_platillo` = `esp`.`id_platillo`)) left join `categoria_platillo` `cp` on(`cp`.`id_categoria` = `p`.`id_categoria`)) join `receta` `r` on(`r`.`id_platillo` = `p`.`id_platillo`)) join `ingrediente` `i` on(`i`.`id_ingrediente` = `r`.`id_ingrediente`))union all select `es`.`id_evento` AS `id_evento`,coalesce(`s`.`alias`,`s`.`nombre`) AS `salon`,'BEBIDAS' AS `seccion`,(select `categoria_platillo`.`orden` from `categoria_platillo` where `categoria_platillo`.`nombre` = 'BEBIDAS' limit 1) AS `orden_seccion`,NULL AS `orden_platillo`,`p`.`nombre` AS `platillo`,`i`.`nombre` AS `ingrediente`,`i`.`unidad` AS `unidad`,`i`.`presentacion_descripcion` AS `presentacion_descripcion`,`r`.`nota` AS `nota_receta`,round(`r`.`cantidad_por_base` * (`es`.`adultos` + `es`.`factor_nino` * `es`.`ninos`) / nullif(`p`.`porciones_base`,0),3) AS `cantidad_calc`,`fmt_cant_human`(round(`r`.`cantidad_por_base` * (`es`.`adultos` + `es`.`factor_nino` * `es`.`ninos`) / nullif(`p`.`porciones_base`,0),3)) AS `cantidad_mostrada`,`es`.`id_evento_salon` AS `id_evento_salon` from ((((`evento_salon` `es` join `salon` `s` on(`s`.`id_salon` = `es`.`id_salon`)) join `platillo` `p` on(`p`.`id_platillo` = 87)) join `receta` `r` on(`r`.`id_platillo` = `p`.`id_platillo`)) join `ingrediente` `i` on(`i`.`id_ingrediente` = `r`.`id_ingrediente`)) where `es`.`cafe` = 1 union all select `es`.`id_evento` AS `id_evento`,coalesce(`s`.`alias`,`s`.`nombre`) AS `salon`,'DESCORCHE' AS `seccion`,NULL AS `orden_seccion`,NULL AS `orden_platillo`,'DESCORCHE (Limón)' AS `platillo`,`i`.`nombre` AS `ingrediente`,`i`.`unidad` AS `unidad`,`i`.`presentacion_descripcion` AS `presentacion_descripcion`,NULL AS `nota_receta`,round(`es`.`adultos`,3) AS `cantidad_calc`,`fmt_cant_human`(round(`es`.`adultos`,3)) AS `cantidad_mostrada`,`es`.`id_evento_salon` AS `id_evento_salon` from ((`evento_salon` `es` join `salon` `s` on(`s`.`id_salon` = `es`.`id_salon`)) join `ingrediente` `i` on(`i`.`id_ingrediente` = 18)) where `es`.`descorche` = 1  ;

-- --------------------------------------------------------

--
-- Estructura para la vista `vw_platillo_receta`
--
DROP TABLE IF EXISTS `vw_platillo_receta`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_platillo_receta`  AS SELECT `r`.`id_platillo` AS `id_platillo`, `p`.`nombre` AS `platillo`, `p`.`porciones_base` AS `porciones_base`, `p`.`id_categoria` AS `id_categoria`, `r`.`id_ingrediente` AS `id_ingrediente`, `i`.`nombre` AS `ingrediente`, `i`.`unidad` AS `unidad`, `i`.`presentacion_descripcion` AS `presentacion_descripcion`, `r`.`cantidad_por_base` AS `cantidad_por_base`, `r`.`nota` AS `nota_receta` FROM ((`receta` `r` join `platillo` `p` on(`p`.`id_platillo` = `r`.`id_platillo`)) join `ingrediente` `i` on(`i`.`id_ingrediente` = `r`.`id_ingrediente`)) ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categoria_platillo`
--
ALTER TABLE `categoria_platillo`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `uk_categoria_nombre` (`nombre`);

--
-- Indices de la tabla `evento`
--
ALTER TABLE `evento`
  ADD PRIMARY KEY (`id_evento`);

--
-- Indices de la tabla `evento_salon`
--
ALTER TABLE `evento_salon`
  ADD PRIMARY KEY (`id_evento_salon`),
  ADD UNIQUE KEY `uk_evento_salon` (`id_evento`,`id_salon`),
  ADD KEY `fk_es_salon` (`id_salon`);

--
-- Indices de la tabla `evento_salon_platillo`
--
ALTER TABLE `evento_salon_platillo`
  ADD PRIMARY KEY (`id_evento_salon_platillo`),
  ADD UNIQUE KEY `uk_es_platillo` (`id_evento_salon`,`id_platillo`),
  ADD KEY `fk_esp_pl` (`id_platillo`);

--
-- Indices de la tabla `ingrediente`
--
ALTER TABLE `ingrediente`
  ADD PRIMARY KEY (`id_ingrediente`),
  ADD UNIQUE KEY `uk_ingrediente_nombre` (`nombre`);

--
-- Indices de la tabla `plan_compra`
--
ALTER TABLE `plan_compra`
  ADD PRIMARY KEY (`id_plan`),
  ADD KEY `idx_pc_ingrediente` (`id_ingrediente`);

--
-- Indices de la tabla `platillo`
--
ALTER TABLE `platillo`
  ADD PRIMARY KEY (`id_platillo`),
  ADD UNIQUE KEY `uk_platillo_nombre` (`nombre`);

--
-- Indices de la tabla `platillo_categoria`
--
ALTER TABLE `platillo_categoria`
  ADD PRIMARY KEY (`id_platillo`,`id_categoria`),
  ADD KEY `id_categoria` (`id_categoria`);

--
-- Indices de la tabla `receta`
--
ALTER TABLE `receta`
  ADD PRIMARY KEY (`id_platillo`,`id_ingrediente`),
  ADD KEY `idx_receta_ingrediente` (`id_ingrediente`);

--
-- Indices de la tabla `salon`
--
ALTER TABLE `salon`
  ADD PRIMARY KEY (`id_salon`),
  ADD UNIQUE KEY `uk_salon_nombre` (`nombre`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categoria_platillo`
--
ALTER TABLE `categoria_platillo`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de la tabla `evento`
--
ALTER TABLE `evento`
  MODIFY `id_evento` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de la tabla `evento_salon`
--
ALTER TABLE `evento_salon`
  MODIFY `id_evento_salon` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `evento_salon_platillo`
--
ALTER TABLE `evento_salon_platillo`
  MODIFY `id_evento_salon_platillo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=374;

--
-- AUTO_INCREMENT de la tabla `ingrediente`
--
ALTER TABLE `ingrediente`
  MODIFY `id_ingrediente` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT de la tabla `plan_compra`
--
ALTER TABLE `plan_compra`
  MODIFY `id_plan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT de la tabla `platillo`
--
ALTER TABLE `platillo`
  MODIFY `id_platillo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=134;

--
-- AUTO_INCREMENT de la tabla `salon`
--
ALTER TABLE `salon`
  MODIFY `id_salon` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `evento_salon`
--
ALTER TABLE `evento_salon`
  ADD CONSTRAINT `fk_es_evento` FOREIGN KEY (`id_evento`) REFERENCES `evento` (`id_evento`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_es_salon` FOREIGN KEY (`id_salon`) REFERENCES `salon` (`id_salon`) ON DELETE CASCADE;

--
-- Filtros para la tabla `evento_salon_platillo`
--
ALTER TABLE `evento_salon_platillo`
  ADD CONSTRAINT `fk_esp_es` FOREIGN KEY (`id_evento_salon`) REFERENCES `evento_salon` (`id_evento_salon`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_esp_pl` FOREIGN KEY (`id_platillo`) REFERENCES `platillo` (`id_platillo`) ON DELETE CASCADE;

--
-- Filtros para la tabla `plan_compra`
--
ALTER TABLE `plan_compra`
  ADD CONSTRAINT `plan_compra_ibfk_1` FOREIGN KEY (`id_ingrediente`) REFERENCES `ingrediente` (`id_ingrediente`) ON DELETE CASCADE;

--
-- Filtros para la tabla `platillo_categoria`
--
ALTER TABLE `platillo_categoria`
  ADD CONSTRAINT `platillo_categoria_ibfk_1` FOREIGN KEY (`id_platillo`) REFERENCES `platillo` (`id_platillo`),
  ADD CONSTRAINT `platillo_categoria_ibfk_2` FOREIGN KEY (`id_categoria`) REFERENCES `categoria_platillo` (`id_categoria`);

--
-- Filtros para la tabla `receta`
--
ALTER TABLE `receta`
  ADD CONSTRAINT `receta_ibfk_1` FOREIGN KEY (`id_platillo`) REFERENCES `platillo` (`id_platillo`) ON DELETE CASCADE,
  ADD CONSTRAINT `receta_ibfk_2` FOREIGN KEY (`id_ingrediente`) REFERENCES `ingrediente` (`id_ingrediente`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
