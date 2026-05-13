<?php
// reparar_db.php - Reparación completa de la base de datos
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Reparando Base de Datos</h1>";

// Eliminar archivo de base de datos existente
$dbFile = __DIR__ . '/database/cocina_fantasy.sqlite';
if (file_exists($dbFile)) {
    unlink($dbFile);
    echo "<p>✅ Base de datos eliminada</p>";
}

// Asegurar que la carpeta existe
if (!is_dir(__DIR__ . '/database')) {
    mkdir(__DIR__ . '/database', 0777, true);
    echo "<p>✅ Carpeta database creada</p>";
}

// Crear nueva conexión
try {
    $pdo = new PDO("sqlite:" . $dbFile);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p>✅ Conexión creada</p>";

    // Crear tabla salon (la que necesitas URGENTEMENTE)
    $pdo->exec("
        CREATE TABLE salon (
            id_salon INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(80) NOT NULL,
            alias VARCHAR(20)
        );
        
        INSERT INTO salon (id_salon, nombre, alias) VALUES 
        (1, 'CARMELO', NULL),
        (2, 'SAN RAFAEL', NULL);
    ");
    echo "<p style='color:green'>✅ Tabla 'salon' creada con 2 registros</p>";

    // Verificar que la tabla existe
    $result = $pdo->query("SELECT * FROM salon");
    $salones = $result->fetchAll();
    echo "<p>📊 Salones encontrados: " . count($salones) . "</p>";

    // Crear tabla categoria_platillo
    $pdo->exec("
        CREATE TABLE categoria_platillo (
            id_categoria INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(60) NOT NULL,
            orden INTEGER NOT NULL DEFAULT 1
        );
        
        INSERT INTO categoria_platillo (id_categoria, nombre, orden) VALUES 
        (1, 'GUISADOS', 1),
        (2, 'BUFFET INFANTIL', 11),
        (3, 'BEBIDAS', 8),
        (4, 'SALSAS', 9),
        (5, 'GUARNICIONES', 2);
    ");
    echo "<p style='color:green'>✅ Tabla 'categoria_platillo' creada</p>";

    // Crear tabla platillo
    $pdo->exec("
        CREATE TABLE platillo (
            id_platillo INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(150) NOT NULL,
            descripcion TEXT,
            id_categoria INTEGER,
            porciones_base INTEGER NOT NULL DEFAULT 100
        );
        
        INSERT INTO platillo (id_platillo, nombre, id_categoria) VALUES 
        (1, 'Ensalada de nopales', 5),
        (4, 'Alambre', 1),
        (5, 'Cochinita pibil', 1);
    ");
    echo "<p style='color:green'>✅ Tabla 'platillo' creada</p>";

    // Crear tabla ingrediente
    $pdo->exec("
        CREATE TABLE ingrediente (
            id_ingrediente INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre VARCHAR(120) NOT NULL,
            unidad VARCHAR(20) NOT NULL
        );
        
        INSERT INTO ingrediente (id_ingrediente, nombre, unidad) VALUES 
        (1, 'Nopales', 'pz'),
        (2, 'Cebolla', 'kg'),
        (3, 'Cilantro', 'manojo');
    ");
    echo "<p style='color:green'>✅ Tabla 'ingrediente' creada</p>";

    echo "<hr>";
    echo "<h2 style='color:green'>🎉 ¡REPARACIÓN COMPLETADA!</h2>";
    echo "<p>Ahora prueba estas páginas:</p>";
    echo "<ul>";
    echo "<li><a href='salon_list.php'>salon_list.php</a> - " . (file_exists('salon_list.php') ? "✅ existe" : "❌ no existe") . "</li>";
    echo "<li><a href='categoria_list.php'>categoria_list.php</a> - " . (file_exists('categoria_list.php') ? "✅ existe" : "❌ no existe") . "</li>";
    echo "<li><a href='index.php'>index.php</a> - " . (file_exists('index.php') ? "✅ existe" : "❌ no existe") . "</li>";
    echo "</ul>";

    // Probar consulta
    echo "<h3>Prueba de consulta:</h3>";
    $test = $pdo->query("SELECT * FROM salon");
    echo "<pre>";
    print_r($test->fetchAll());
    echo "</pre>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>En archivo: " . $e->getFile() . " línea " . $e->getLine() . "</p>";
}
