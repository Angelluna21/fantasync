<?php
// db.php - Con creación automática de tablas
$dbPath = __DIR__ . '/database/cocina_fantasy.sqlite';

try {
  $pdo = new PDO("sqlite:" . $dbPath);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

  // Crear tablas automáticamente si no existen
  $pdo->exec("
        CREATE TABLE IF NOT EXISTS salon (
            id_salon INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(80) NOT NULL,
            alias VARCHAR(20)
        );
        
        CREATE TABLE IF NOT EXISTS categoria_platillo (
            id_categoria INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(60) NOT NULL,
            orden INTEGER NOT NULL DEFAULT 1
        );
        
        CREATE TABLE IF NOT EXISTS platillo (
            id_platillo INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(150) NOT NULL,
            descripcion TEXT,
            id_categoria INTEGER,
            porciones_base INTEGER NOT NULL DEFAULT 100
        );
        
        CREATE TABLE IF NOT EXISTS ingrediente (
            id_ingrediente INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(120) NOT NULL,
            unidad VARCHAR(20) NOT NULL
        );
        
        -- Tabla receta (NUEVA - necesaria para recetas.php)
        CREATE TABLE IF NOT EXISTS receta (
            id_platillo INTEGER NOT NULL,
            id_ingrediente INTEGER NOT NULL,
            cantidad_por_base DECIMAL(10,3) NOT NULL,
            nota VARCHAR(120),
            PRIMARY KEY (id_platillo, id_ingrediente)
        );
        
        CREATE TABLE IF NOT EXISTS evento (
            id_evento INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha DATE NOT NULL,
            titulo VARCHAR(150),
            misa TIME,
            recepcion TIME,
            inicio TIME,
            descorche BOOLEAN NOT NULL DEFAULT 0,
            cafe BOOLEAN NOT NULL DEFAULT 0,
            degustaciones VARCHAR(120),
            notas TEXT
        );
        
        CREATE TABLE IF NOT EXISTS evento_salon (
            id_evento_salon INTEGER PRIMARY KEY AUTOINCREMENT,
            id_evento INTEGER NOT NULL,
            id_salon INTEGER NOT NULL,
            adultos INTEGER NOT NULL DEFAULT 0,
            ninos INTEGER NOT NULL DEFAULT 0,
            misa TIME,
            recepcion TIME,
            inicio TIME,
            descorche BOOLEAN NOT NULL DEFAULT 0,
            cafe BOOLEAN NOT NULL DEFAULT 0,
            degustaciones VARCHAR(120),
            factor_nino DECIMAL(5,2) NOT NULL DEFAULT 0.70
        );
        
        CREATE TABLE IF NOT EXISTS evento_salon_platillo (
            id_evento_salon_platillo INTEGER PRIMARY KEY AUTOINCREMENT,
            id_evento_salon INTEGER NOT NULL,
            id_platillo INTEGER NOT NULL,
            porciones_plan INTEGER NOT NULL,
            orden INTEGER,
            notas VARCHAR(120)
        );
        
        -- Insertar datos de ejemplo si las tablas están vacías
        INSERT OR IGNORE INTO salon (id_salon, nombre) VALUES 
        (1, 'CARMELO'),
        (2, 'SAN RAFAEL');
        
        INSERT OR IGNORE INTO categoria_platillo (id_categoria, nombre, orden) VALUES 
        (1, 'GUISADOS', 1),
        (2, 'BUFFET INFANTIL', 2),
        (3, 'BEBIDAS', 3);
        
        -- Insertar platillos de ejemplo si no existen
        INSERT OR IGNORE INTO platillo (id_platillo, nombre, porciones_base) VALUES 
        (1, 'Platillo de ejemplo', 100);
        
        -- Insertar ingredientes de ejemplo si no existen
        INSERT OR IGNORE INTO ingrediente (id_ingrediente, nombre, unidad) VALUES 
        (1, 'Ingrediente ejemplo', 'kg');
    ");
} catch (PDOException $e) {
  die("Error de conexión: " . $e->getMessage());
}

function h($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$conn = $pdo;
