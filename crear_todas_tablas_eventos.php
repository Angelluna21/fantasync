<?php
// crear_todas_tablas_eventos.php - Crea todas las tablas necesarias para eventos
require_once 'db.php';

echo "<h1>🔧 Creando todas las tablas para eventos</h1>";

try {
    // =====================================================
    // 1. Verificar/crear tabla evento
    // =====================================================
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
        $pdo->exec("INSERT INTO evento (fecha, titulo) VALUES (date('now'), 'Evento de ejemplo')");
    } else {
        echo "<p>ℹ️ Tabla 'evento' ya existe</p>";
    }

    // =====================================================
    // 2. Verificar/crear tabla evento_salon
    // =====================================================
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
                factor_nino DECIMAL(5,2) NOT NULL DEFAULT 0.70,
                UNIQUE(id_evento, id_salon)
            )
        ");
        echo "<p style='color:green'>✅ Tabla 'evento_salon' creada</p>";
    } else {
        echo "<p>ℹ️ Tabla 'evento_salon' ya existe</p>";
    }

    // =====================================================
    // 3. Verificar/crear tabla evento_salon_platillo
    // =====================================================
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='evento_salon_platillo'");
    if (!$result->fetch()) {
        $pdo->exec("
            CREATE TABLE evento_salon_platillo (
                id_evento_salon_platillo INTEGER PRIMARY KEY AUTOINCREMENT,
                id_evento_salon INTEGER NOT NULL,
                id_platillo INTEGER NOT NULL,
                porciones_plan INTEGER NOT NULL,
                orden INTEGER,
                notas VARCHAR(120),
                UNIQUE(id_evento_salon, id_platillo)
            )
        ");
        echo "<p style='color:green'>✅ Tabla 'evento_salon_platillo' creada</p>";
    } else {
        echo "<p>ℹ️ Tabla 'evento_salon_platillo' ya existe</p>";
    }

    // =====================================================
    // 4. Verificar que hay salones
    // =====================================================
    $salonesCount = $pdo->query("SELECT COUNT(*) FROM salon")->fetchColumn();
    if ($salonesCount == 0) {
        $pdo->exec("
            INSERT INTO salon (id_salon, nombre) VALUES 
            (1, 'CARMELO'),
            (2, 'SAN RAFAEL')
        ");
        echo "<p style='color:green'>✅ Salones insertados</p>";
    }

    // =====================================================
    // 5. Verificar estructura final
    // =====================================================
    echo "<h2>🔍 Verificación final:</h2>";

    $tables = ['evento', 'evento_salon', 'evento_salon_platillo', 'salon'];
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        $status = $count > 0 ? "✅" : "⚠️";
        echo "<p>$status Tabla '$table': $count registros</p>";
    }

    echo "<h2 style='color:green'>✅ ¡Todas las tablas están listas!</h2>";
    echo "<p><a href='evento_list.php'>Ir a evento_list.php</a></p>";
    echo "<p><a href='index.php'>Volver al inicio</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}
