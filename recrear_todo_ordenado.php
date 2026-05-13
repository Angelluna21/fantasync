<?php
// recrear_todo_ordenado.php - Recrea todas las tablas en el orden correcto
require_once 'db.php';

echo "<h1>🔄 Recreando todas las tablas en orden</h1>";

try {
    // =====================================================
    // 1. Eliminar tablas en orden inverso (respetando dependencias)
    // =====================================================
    echo "<h2>🗑️ Eliminando tablas existentes...</h2>";

    $pdo->exec("DROP TABLE IF EXISTS evento_salon_platillo");
    echo "<p>✅ Tabla 'evento_salon_platillo' eliminada</p>";

    $pdo->exec("DROP TABLE IF EXISTS evento_salon");
    echo "<p>✅ Tabla 'evento_salon' eliminada</p>";

    $pdo->exec("DROP TABLE IF EXISTS evento");
    echo "<p>✅ Tabla 'evento' eliminada</p>";

    $pdo->exec("DROP TABLE IF EXISTS receta");
    echo "<p>✅ Tabla 'receta' eliminada</p>";

    // =====================================================
    // 2. Crear tabla evento
    // =====================================================
    echo "<h2>📝 Creando tabla 'evento'...</h2>";

    $pdo->exec("
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
        )
    ");
    echo "<p style='color:green'>✅ Tabla 'evento' creada</p>";

    // =====================================================
    // 3. Crear tabla evento_salon
    // =====================================================
    echo "<h2>📝 Creando tabla 'evento_salon'...</h2>";

    $pdo->exec("
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
            factor_nino DECIMAL(5,2) NOT NULL DEFAULT 0.70
        )
    ");
    echo "<p style='color:green'>✅ Tabla 'evento_salon' creada</p>";

    // =====================================================
    // 4. Crear tabla evento_salon_platillo
    // =====================================================
    echo "<h2>📝 Creando tabla 'evento_salon_platillo'...</h2>";

    $pdo->exec("
        CREATE TABLE evento_salon_platillo (
            id_evento_salon_platillo INTEGER PRIMARY KEY AUTOINCREMENT,
            id_evento_salon INTEGER NOT NULL,
            id_platillo INTEGER NOT NULL,
            porciones_plan INTEGER NOT NULL,
            orden INTEGER,
            notas VARCHAR(120)
        )
    ");
    echo "<p style='color:green'>✅ Tabla 'evento_salon_platillo' creada</p>";

    // =====================================================
    // 5. Crear tabla receta
    // =====================================================
    echo "<h2>📝 Creando tabla 'receta'...</h2>";

    $pdo->exec("
        CREATE TABLE receta (
            id_platillo INTEGER NOT NULL,
            id_ingrediente INTEGER NOT NULL,
            cantidad_por_base DECIMAL(10,3) NOT NULL,
            nota VARCHAR(120),
            PRIMARY KEY (id_platillo, id_ingrediente)
        )
    ");
    echo "<p style='color:green'>✅ Tabla 'receta' creada</p>";

    // =====================================================
    // 6. Insertar datos de ejemplo
    // =====================================================
    echo "<h2>📥 Insertando datos de ejemplo...</h2>";

    // Insertar eventos
    $pdo->exec("
        INSERT INTO evento (id_evento, fecha, titulo, descorche, cafe) VALUES 
        (1, date('now'), 'Evento de prueba 1 - CARMELO', 0, 0),
        (2, date('now', '+1 day'), 'Evento de prueba 2 - SAN RAFAEL', 1, 1),
        (3, date('now', '-1 day'), 'Evento pasado', 0, 1)
    ");
    echo "<p>✅ 3 eventos insertados</p>";

    // Verificar salones
    $salonesCount = $pdo->query("SELECT COUNT(*) FROM salon")->fetchColumn();
    if ($salonesCount == 0) {
        $pdo->exec("
            INSERT INTO salon (id_salon, nombre) VALUES 
            (1, 'CARMELO'),
            (2, 'SAN RAFAEL')
        ");
        echo "<p>✅ Salones insertados</p>";
    } else {
        echo "<p>✅ Salones ya existen: $salonesCount registros</p>";
    }

    // Insertar relaciones evento_salon
    $pdo->exec("
        INSERT INTO evento_salon (id_evento_salon, id_evento, id_salon, adultos, ninos) VALUES 
        (1, 1, 1, 100, 20),
        (2, 2, 2, 75, 15)
    ");
    echo "<p>✅ Relaciones evento_salon insertadas</p>";

    // Verificar platillos
    $platillosCount = $pdo->query("SELECT COUNT(*) FROM platillo")->fetchColumn();
    if ($platillosCount == 0) {
        $pdo->exec("
            INSERT INTO platillo (id_platillo, nombre, porciones_base) VALUES 
            (1, 'Platillo ejemplo 1', 100),
            (2, 'Platillo ejemplo 2', 100)
        ");
        echo "<p>✅ Platillos insertados</p>";
    } else {
        echo "<p>✅ Platillos ya existen: $platillosCount registros</p>";
    }

    // =====================================================
    // 7. Verificar todo
    // =====================================================
    echo "<h2>🔍 Verificación final:</h2>";

    // Mostrar eventos
    $eventos = $pdo->query("
        SELECT id_evento, fecha, titulo, 
               CASE WHEN descorche = 1 THEN 'SI' ELSE 'NO' END as descorche,
               CASE WHEN cafe = 1 THEN 'SI' ELSE 'NO' END as cafe
        FROM evento
    ")->fetchAll();

    echo "<h3>📅 Eventos:</h3>";
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse'>";
    echo "<tr><th>ID</th><th>Fecha</th><th>Título</th><th>Descorche</th><th>Café</th></tr>";
    foreach ($eventos as $e) {
        echo "<tr>";
        echo "<td>{$e['id_evento']}</td>";
        echo "<td>{$e['fecha']}</td>";
        echo "<td>{$e['titulo']}</td>";
        echo "<td>{$e['descorche']}</td>";
        echo "<td>{$e['cafe']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // Mostrar relaciones
    $relaciones = $pdo->query("
        SELECT es.id_evento_salon, e.titulo as evento, s.nombre as salon, es.adultos, es.ninos
        FROM evento_salon es
        JOIN evento e ON es.id_evento = e.id_evento
        JOIN salon s ON es.id_salon = s.id_salon
    ")->fetchAll();

    echo "<h3>🏢 Relaciones Evento-Salón:</h3>";
    echo "<table border='1' cellpadding='8' style='border-collapse:collapse'>";
    echo "<tr><th>ID</th><th>Evento</th><th>Salón</th><th>Adultos</th><th>Niños</th></tr>";
    foreach ($relaciones as $r) {
        echo "<tr>";
        echo "<td>{$r['id_evento_salon']}</td>";
        echo "<td>{$r['evento']}</td>";
        echo "<td>{$r['salon']}</td>";
        echo "<td>{$r['adultos']}</td>";
        echo "<td>{$r['ninos']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<h2 style='color:green'>✅ ¡TODAS LAS TABLAS ESTÁN LISTAS!</h2>";
    echo "<p><a href='evento_list.php'>Ir a evento_list.php</a></p>";
    echo "<p><a href='index.php'>Volver al inicio</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}
