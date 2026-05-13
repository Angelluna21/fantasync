<?php
// crear_todo_completo.php - Crea todas las tablas necesarias
require_once 'db.php';

echo "<h1>🚀 Creando todas las tablas necesarias</h1>";

try {
    // =====================================================
    // 1. Crear tabla evento (si no existe)
    // =====================================================
    echo "<h2>📝 Verificando/Creando tabla 'evento'...</h2>";

    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='evento'");
    if (!$result->fetch()) {
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

        // Insertar evento de ejemplo
        $pdo->exec("
            INSERT INTO evento (id_evento, fecha, titulo, descorche, cafe) VALUES 
            (1, date('now'), 'Evento de ejemplo 1', 0, 0),
            (2, date('now', '+1 day'), 'Evento de ejemplo 2', 1, 1)
        ");
        echo "<p>✅ Eventos de ejemplo insertados</p>";
    } else {
        echo "<p>✅ Tabla 'evento' ya existe</p>";
    }

    // =====================================================
    // 2. Crear tabla evento_salon
    // =====================================================
    echo "<h2>📝 Creando tabla 'evento_salon'...</h2>";

    $pdo->exec("DROP TABLE IF EXISTS evento_salon");
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

    // Insertar relaciones de ejemplo
    $pdo->exec("
        INSERT INTO evento_salon (id_evento_salon, id_evento, id_salon, adultos, ninos) VALUES 
        (1, 1, 1, 100, 20),
        (2, 2, 2, 75, 15)
    ");
    echo "<p>✅ Relaciones evento_salon insertadas</p>";

    // =====================================================
    // 3. Crear tabla evento_salon_platillo
    // =====================================================
    echo "<h2>📝 Creando tabla 'evento_salon_platillo'...</h2>";

    $pdo->exec("DROP TABLE IF EXISTS evento_salon_platillo");
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
    // 4. Crear tabla receta
    // =====================================================
    echo "<h2>📝 Creando tabla 'receta'...</h2>";

    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='receta'");
    if (!$result->fetch()) {
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
    } else {
        echo "<p>✅ Tabla 'receta' ya existe</p>";
    }

    // =====================================================
    // 5. Verificar salones
    // =====================================================
    echo "<h2>📝 Verificando salones...</h2>";

    $salonesCount = $pdo->query("SELECT COUNT(*) FROM salon")->fetchColumn();
    if ($salonesCount == 0) {
        $pdo->exec("
            INSERT INTO salon (id_salon, nombre) VALUES 
            (1, 'CARMELO'),
            (2, 'SAN RAFAEL')
        ");
        echo "<p style='color:green'>✅ Salones insertados</p>";
    } else {
        echo "<p>✅ Salones ya existen: $salonesCount registros</p>";
    }

    // =====================================================
    // 6. Verificar platillos
    // =====================================================
    $platillosCount = $pdo->query("SELECT COUNT(*) FROM platillo")->fetchColumn();
    if ($platillosCount == 0) {
        $pdo->exec("
            INSERT INTO platillo (id_platillo, nombre, porciones_base) VALUES 
            (1, 'Platillo de ejemplo 1', 100),
            (2, 'Platillo de ejemplo 2', 100)
        ");
        echo "<p style='color:green'>✅ Platillos insertados</p>";
    } else {
        echo "<p>✅ Platillos ya existen: $platillosCount registros</p>";
    }

    // =====================================================
    // 7. Verificar todo
    // =====================================================
    echo "<h2>🔍 Verificación final:</h2>";

    $tables = ['evento', 'evento_salon', 'evento_salon_platillo', 'receta', 'salon', 'platillo'];
    echo "<ul>";
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        $icon = $count > 0 ? "✅" : "⚠️";
        echo "<li>$icon Tabla '$table': $count registros</li>";
    }
    echo "</ul>";

    // Mostrar eventos
    $eventos = $pdo->query("
        SELECT e.id_evento, e.fecha, e.titulo, e.descorche, e.cafe,
               COALESCE(s.nombre, 'Sin salón') as salon
        FROM evento e
        LEFT JOIN evento_salon es ON e.id_evento = es.id_evento
        LEFT JOIN salon s ON es.id_salon = s.id_salon
        GROUP BY e.id_evento
    ")->fetchAll();

    if (count($eventos) > 0) {
        echo "<h3>📅 Eventos disponibles:</h3>";
        echo "<table border='1' cellpadding='8' style='border-collapse:collapse'>";
        echo "<tr><th>ID</th><th>Fecha</th><th>Título</th><th>Salón</th><th>Descorche</th><th>Café</th></tr>";
        foreach ($eventos as $e) {
            echo "<tr>";
            echo "<td>{$e['id_evento']}</td>";
            echo "<td>{$e['fecha']}</td>";
            echo "<td>{$e['titulo']}</td>";
            echo "<td>{$e['salon']}</td>";
            echo "<td>" . ($e['descorche'] ? '✅ Sí' : '❌ No') . "</td>";
            echo "<td>" . ($e['cafe'] ? '✅ Sí' : '❌ No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<h2 style='color:green'>✅ ¡TODAS LAS TABLAS ESTÁN LISTAS!</h2>";
    echo "<p><a href='evento_list.php'>Ir a evento_list.php</a></p>";
    echo "<p><a href='index.php'>Volver al inicio</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}
