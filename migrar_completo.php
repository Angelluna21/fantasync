<?php
// migrar_completo.php - Migración completa de la estructura de MySQL a SQLite
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔄 Migrando estructura completa de la base de datos</h1>";

$dbPath = __DIR__ . '/database/cocina_fantasy.sqlite';

try {
    // Hacer respaldo de la base de datos actual si existe
    if (file_exists($dbPath)) {
        $backup = $dbPath . '.backup_' . date('Ymd_His');
        copy($dbPath, $backup);
        echo "<p>📦 Respaldo creado: " . basename($backup) . "</p>";

        // Eliminar la base de datos actual para empezar de cero
        unlink($dbPath);
        echo "<p>🗑️ Base de datos anterior eliminada</p>";
    }

    // Crear nueva conexión
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p>✅ Nueva conexión SQLite establecida</p>";

    // =====================================================
    // CREAR TODAS LAS TABLAS CON LA ESTRUCTURA CORRECTA
    // =====================================================

    $sql = "
        -- Tabla: categoria_platillo
        CREATE TABLE categoria_platillo (
            id_categoria INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(60) NOT NULL UNIQUE,
            orden INTEGER NOT NULL DEFAULT 1
        );
        
        -- Tabla: salon
        CREATE TABLE salon (
            id_salon INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(80) NOT NULL UNIQUE,
            alias VARCHAR(20)
        );
        
        -- Tabla: evento
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
        
        -- Tabla: evento_salon
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
        
        -- Tabla: platillo
        CREATE TABLE platillo (
            id_platillo INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(150) NOT NULL UNIQUE,
            descripcion TEXT,
            id_categoria INTEGER,
            porciones_base INTEGER NOT NULL DEFAULT 100
        );
        
        -- Tabla: ingrediente
        CREATE TABLE ingrediente (
            id_ingrediente INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(120) NOT NULL UNIQUE,
            unidad VARCHAR(20) NOT NULL,
            tamanio_presentacion DECIMAL(10,3),
            presentacion_descripcion VARCHAR(80)
        );
        
        -- Tabla: receta
        CREATE TABLE receta (
            id_platillo INTEGER NOT NULL,
            id_ingrediente INTEGER NOT NULL,
            cantidad_por_base DECIMAL(10,3) NOT NULL,
            nota VARCHAR(120),
            PRIMARY KEY (id_platillo, id_ingrediente)
        );
        
        -- Tabla: evento_salon_platillo
        CREATE TABLE evento_salon_platillo (
            id_evento_salon_platillo INTEGER PRIMARY KEY AUTOINCREMENT,
            id_evento_salon INTEGER NOT NULL,
            id_platillo INTEGER NOT NULL,
            porciones_plan INTEGER NOT NULL,
            orden INTEGER,
            notas VARCHAR(120),
            UNIQUE(id_evento_salon, id_platillo)
        );
        
        -- Tabla: plan_compra
        CREATE TABLE plan_compra (
            id_plan INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha DATE NOT NULL,
            id_ingrediente INTEGER NOT NULL,
            cantidad DECIMAL(10,3) NOT NULL
        );
        
        -- Tabla: platillo_categoria (relación muchos a muchos)
        CREATE TABLE platillo_categoria (
            id_platillo INTEGER NOT NULL,
            id_categoria INTEGER NOT NULL,
            PRIMARY KEY (id_platillo, id_categoria)
        );
    ";

    $pdo->exec($sql);
    echo "<p style='color:green'>✅ Todas las tablas creadas correctamente</p>";

    // =====================================================
    // INSERTAR DATOS DE EJEMPLO
    // =====================================================

    // Insertar salones
    $pdo->exec("
        INSERT INTO salon (id_salon, nombre, alias) VALUES 
        (1, 'CARMELO', NULL),
        (2, 'SAN RAFAEL', NULL);
    ");
    echo "<p>✅ Salones insertados</p>";

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
    echo "<p>✅ Categorías insertadas</p>";

    // Insertar eventos de ejemplo
    $pdo->exec("
        INSERT INTO evento (id_evento, fecha, titulo, recepcion, inicio, descorche) VALUES 
        (35, '2025-11-24', 'SOCIAL', '21:53:00', NULL, 0),
        (37, '2025-11-29', 'Jazmin Casarrubias', '16:00:00', '16:30:00', 1),
        (38, '2025-11-28', 'Michell', '18:30:00', '19:00:00', 0);
    ");
    echo "<p>✅ Eventos insertados</p>";

    // Insertar relación evento_salon
    $pdo->exec("
        INSERT INTO evento_salon (id_evento_salon, id_evento, id_salon, adultos, ninos) VALUES 
        (38, 35, 1, 120, 25),
        (40, 37, 1, 60, 20),
        (41, 38, 2, 75, 25);
    ");
    echo "<p>✅ Evento_Salon insertados</p>";

    // =====================================================
    // VERIFICAR TODO
    // =====================================================

    echo "<h2>🔍 Verificación final:</h2>";

    // Verificar columnas de la tabla evento
    $columns = $pdo->query("PRAGMA table_info(evento)")->fetchAll();
    echo "<p>📋 Columnas en tabla 'evento':</p><ul>";
    foreach ($columns as $col) {
        echo "<li>{$col['name']} ({$col['type']})</li>";
    }
    echo "</ul>";

    // Mostrar eventos
    $eventos = $pdo->query("SELECT id_evento, titulo, fecha, descorche FROM evento")->fetchAll();
    echo "<h3>Eventos registrados:</h3>";
    echo "<pre>";
    print_r($eventos);
    echo "</pre>";

    echo "<hr>";
    echo "<h2 style='color:green'>✅ MIGRACIÓN COMPLETADA EXITOSAMENTE</h2>";
    echo "<p>Ahora prueba estas páginas:</p>";
    echo "<ul>";
    echo "<li><a href='salon_list.php'>🏢 salon_list.php</a></li>";
    echo "<li><a href='evento_list.php'>📅 evento_list.php</a></li>";
    echo "<li><a href='categoria_list.php'>📁 categoria_list.php</a></li>";
    echo "<li><a href='platillos.php'>🍽️ platillos.php</a></li>";
    echo "</ul>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}
