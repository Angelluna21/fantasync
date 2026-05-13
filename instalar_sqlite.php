<?php
// instalar_sqlite.php - Instalación COMPLETA (no depende de db.php)
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>📦 Instalando base de datos SQLite para Cocina Fantasy</h1>";

try {
    // Definir ruta de la base de datos
    $dbDir = __DIR__ . '/database';
    $dbPath = $dbDir . '/cocina_fantasy.sqlite';

    // Eliminar base de datos existente para empezar limpio
    if (file_exists($dbPath)) {
        unlink($dbPath);
        echo "<p>🗑️ Base de datos anterior eliminada</p>";
    }

    // Crear carpeta si no existe
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0777, true);
        echo "<p>📁 Carpeta 'database' creada</p>";
    }

    // Crear conexión DIRECTA (sin usar db.php)
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    echo "<p>✅ Conexión SQLite establecida</p>";

    // =====================================================
    // CREAR TABLAS
    // =====================================================

    $sql = "
        -- Tabla salon (la que necesitas URGENTEMENTE)
        CREATE TABLE salon (
            id_salon INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(80) NOT NULL UNIQUE,
            alias VARCHAR(20)
        );
        
        -- Tabla categoria_platillo
        CREATE TABLE categoria_platillo (
            id_categoria INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(60) NOT NULL UNIQUE,
            orden INTEGER NOT NULL DEFAULT 1
        );
        
        -- Tabla platillo
        CREATE TABLE platillo (
            id_platillo INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(150) NOT NULL UNIQUE,
            descripcion TEXT,
            id_categoria INTEGER,
            porciones_base INTEGER NOT NULL DEFAULT 100
        );
        
        -- Tabla ingrediente
        CREATE TABLE ingrediente (
            id_ingrediente INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(120) NOT NULL UNIQUE,
            unidad VARCHAR(20) NOT NULL,
            tamanio_presentacion DECIMAL(10,3),
            presentacion_descripcion VARCHAR(80)
        );
        
        -- Tabla receta
        CREATE TABLE receta (
            id_platillo INTEGER NOT NULL,
            id_ingrediente INTEGER NOT NULL,
            cantidad_por_base DECIMAL(10,3) NOT NULL,
            nota VARCHAR(120),
            PRIMARY KEY (id_platillo, id_ingrediente)
        );
        
        -- Tabla evento
        CREATE TABLE evento (
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
        
        -- Tabla evento_salon
        CREATE TABLE evento_salon (
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
            factor_nino DECIMAL(5,2) NOT NULL DEFAULT 0.70,
            UNIQUE(id_evento, id_salon)
        );
        
        -- Tabla evento_salon_platillo
        CREATE TABLE evento_salon_platillo (
            id_evento_salon_platillo INTEGER PRIMARY KEY AUTOINCREMENT,
            id_evento_salon INTEGER NOT NULL,
            id_platillo INTEGER NOT NULL,
            porciones_plan INTEGER NOT NULL,
            orden INTEGER,
            notas VARCHAR(120),
            UNIQUE(id_evento_salon, id_platillo)
        );
        
        -- Tabla plan_compra
        CREATE TABLE plan_compra (
            id_plan INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha DATE NOT NULL,
            id_ingrediente INTEGER NOT NULL,
            cantidad DECIMAL(10,3) NOT NULL
        );
        
        -- Tabla platillo_categoria
        CREATE TABLE platillo_categoria (
            id_platillo INTEGER NOT NULL,
            id_categoria INTEGER NOT NULL,
            PRIMARY KEY (id_platillo, id_categoria)
        );
    ";

    // Ejecutar creación de tablas
    $pdo->exec($sql);
    echo "<p style='color:green'>✅ Tablas creadas correctamente</p>";

    // =====================================================
    // INSERTAR DATOS BÁSICOS
    // =====================================================

    echo "<h2>📥 Insertando datos...</h2>";

    // Insertar salones
    $pdo->exec("
        INSERT INTO salon (id_salon, nombre, alias) VALUES 
        (1, 'CARMELO', NULL),
        (2, 'SAN RAFAEL', NULL);
    ");
    echo "<p>✅ Salones insertados (2 registros)</p>";

    // Insertar categorías
    $pdo->exec("
        INSERT INTO categoria_platillo (id_categoria, nombre, orden) VALUES 
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
    ");
    echo "<p>✅ Categorías insertadas (12 registros)</p>";

    // Insertar ingredientes básicos
    $pdo->exec("
        INSERT INTO ingrediente (id_ingrediente, nombre, unidad) VALUES 
        (1, 'Nopales', 'pz'),
        (2, 'Cebolla', 'kg'),
        (3, 'Cilantro', 'manojo'),
        (4, 'Jitomate', 'kg'),
        (5, 'Queso panela', 'kg'),
        (18, 'Limón', 'kg'),
        (22, 'Papa', 'kg'),
        (23, 'Pechuga de pollo', 'kg'),
        (30, 'Chile guajillo', 'kg');
    ");
    echo "<p>✅ Ingredientes insertados (9 registros)</p>";

    // Insertar platillos básicos
    $pdo->exec("
        INSERT INTO platillo (id_platillo, nombre, id_categoria, porciones_base) VALUES 
        (1, 'Ensalada de nopales', 5, 100),
        (4, 'Alambre', 1, 100),
        (5, 'Cochinita pibil', 1, 100),
        (30, 'Bistec a la mexicana', 1, 100);
    ");
    echo "<p>✅ Platillos insertados (4 registros)</p>";

    // =====================================================
    // VERIFICAR QUE TODO ESTÁ BIEN
    // =====================================================

    echo "<h2>🔍 Verificación:</h2>";

    // Verificar tabla salon
    $result = $pdo->query("SELECT COUNT(*) as total FROM salon");
    $count = $result->fetchColumn();
    echo "<p>📊 Tabla 'salon': $count registros</p>";

    // Verificar todas las tablas
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    $tableList = $tables->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>📋 Tablas creadas: " . implode(', ', $tableList) . "</p>";

    // Mostrar salones
    $salones = $pdo->query("SELECT * FROM salon")->fetchAll();
    echo "<h3>Contenido de tabla 'salon':</h3>";
    echo "<pre>";
    print_r($salones);
    echo "</pre>";

    echo "<hr>";
    echo "<h2 style='color:green'>✅ INSTALACIÓN COMPLETADA EXITOSAMENTE</h2>";
    echo "<p>Ahora prueba estas páginas:</p>";
    echo "<ul>";
    echo "<li><a href='salon_list.php'>📋 salon_list.php</a></li>";
    echo "<li><a href='categoria_list.php'>📁 categoria_list.php</a></li>";
    echo "<li><a href='platillos.php'>🍽️ platillos.php</a></li>";
    echo "<li><a href='index.php'>🏠 index.php</a></li>";
    echo "</ul>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}
