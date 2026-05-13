<?php
// crear_todo_final.php - Crea todas las tablas necesarias
require_once 'db.php';

echo "<h1>🚀 Creando todas las tablas necesarias</h1>";

try {
    // =====================================================
    // 1. Crear tabla receta
    // =====================================================
    echo "<h2>📝 Creando tabla 'receta'...</h2>";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS receta (
            id_platillo INTEGER NOT NULL,
            id_ingrediente INTEGER NOT NULL,
            cantidad_por_base DECIMAL(10,3) NOT NULL,
            nota VARCHAR(120),
            PRIMARY KEY (id_platillo, id_ingrediente)
        )
    ");
    echo "<p style='color:green'>✅ Tabla 'receta' creada/verificada</p>";

    // =====================================================
    // 2. Crear tabla plan_compra
    // =====================================================
    echo "<h2>📝 Creando tabla 'plan_compra'...</h2>";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS plan_compra (
            id_plan INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha DATE NOT NULL,
            id_ingrediente INTEGER NOT NULL,
            cantidad DECIMAL(10,3) NOT NULL
        )
    ");
    echo "<p style='color:green'>✅ Tabla 'plan_compra' creada/verificada</p>";

    // =====================================================
    // 3. Verificar que las tablas de eventos existen
    // =====================================================
    echo "<h2>🔍 Verificando tablas de eventos...</h2>";

    // Verificar tabla evento
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
        $pdo->exec("INSERT INTO evento (id_evento, fecha, titulo) VALUES (1, date('now'), 'Evento de ejemplo')");
    } else {
        echo "<p>✅ Tabla 'evento' ya existe</p>";
    }

    // Verificar tabla evento_salon
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='evento_salon'");
    if (!$result->fetch()) {
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
    } else {
        echo "<p>✅ Tabla 'evento_salon' ya existe</p>";
    }

    // Verificar tabla evento_salon_platillo
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='evento_salon_platillo'");
    if (!$result->fetch()) {
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
    } else {
        echo "<p>✅ Tabla 'evento_salon_platillo' ya existe</p>";
    }

    // =====================================================
    // 4. Verificar salones
    // =====================================================
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
    // 5. Verificar todo
    // =====================================================
    echo "<h2>📋 Resumen final:</h2>";

    $tables = ['evento', 'evento_salon', 'evento_salon_platillo', 'receta', 'plan_compra', 'salon'];
    echo "<ul>";
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        $icon = $count > 0 ? "✅" : "⚠️";
        echo "<li>$icon Tabla '$table': $count registros</li>";
    }
    echo "</ul>";

    // Verificar específicamente la columna descorche en evento
    $columns = $pdo->query("PRAGMA table_info(evento)")->fetchAll();
    $hasDescorche = false;
    foreach ($columns as $col) {
        if ($col['name'] == 'descorche') {
            $hasDescorche = true;
            break;
        }
    }

    if ($hasDescorche) {
        echo "<p style='color:green'>✅ La columna 'descorche' existe en la tabla 'evento'</p>";
    } else {
        echo "<p style='color:red'>❌ La columna 'descorche' NO existe. Agregándola...</p>";
        $pdo->exec("ALTER TABLE evento ADD COLUMN descorche BOOLEAN NOT NULL DEFAULT 0");
        echo "<p style='color:green'>✅ Columna 'descorche' agregada</p>";
    }

    echo "<h2 style='color:green'>✅ ¡TODAS LAS TABLAS ESTÁN LISTAS!</h2>";
    echo "<p><a href='evento_list.php'>Ir a evento_list.php</a></p>";
    echo "<p><a href='index.php'>Volver al inicio</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}
