<?php
// diagnostico_y_reparar_evento.php - Diagnóstico y reparación completa
require_once 'db.php';

echo "<h1>🔧 Diagnóstico y Reparación de Tabla Evento</h1>";

try {
    // =====================================================
    // 1. Verificar si la tabla existe
    // =====================================================
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='evento'");
    $tablaExiste = $result->fetch();

    if (!$tablaExiste) {
        echo "<p>⚠️ La tabla 'evento' NO existe. Creándola...</p>";
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
        echo "<p style='color:green'>✅ Tabla 'evento' creada correctamente</p>";
    } else {
        echo "<p>✅ La tabla 'evento' existe</p>";

        // Verificar columnas
        $columns = $pdo->query("PRAGMA table_info(evento)")->fetchAll();
        $columnNames = array_column($columns, 'name');

        echo "<h2>📋 Columnas actuales:</h2>";
        echo "<ul>";
        foreach ($columnNames as $col) {
            echo "<li>$col</li>";
        }
        echo "</ul>";

        // Verificar si falta descorche
        if (!in_array('descorche', $columnNames)) {
            echo "<p style='color:orange'>⚠️ Falta la columna 'descorche'. Agregándola...</p>";
            $pdo->exec("ALTER TABLE evento ADD COLUMN descorche BOOLEAN NOT NULL DEFAULT 0");
            echo "<p style='color:green'>✅ Columna 'descorche' agregada</p>";
        }

        if (!in_array('cafe', $columnNames)) {
            echo "<p>⚠️ Falta la columna 'cafe'. Agregándola...</p>";
            $pdo->exec("ALTER TABLE evento ADD COLUMN cafe BOOLEAN NOT NULL DEFAULT 0");
            echo "<p style='color:green'>✅ Columna 'cafe' agregada</p>";
        }

        if (!in_array('misa', $columnNames)) {
            $pdo->exec("ALTER TABLE evento ADD COLUMN misa TIME");
            echo "<p style='color:green'>✅ Columna 'misa' agregada</p>";
        }

        if (!in_array('recepcion', $columnNames)) {
            $pdo->exec("ALTER TABLE evento ADD COLUMN recepcion TIME");
            echo "<p style='color:green'>✅ Columna 'recepcion' agregada</p>";
        }

        if (!in_array('inicio', $columnNames)) {
            $pdo->exec("ALTER TABLE evento ADD COLUMN inicio TIME");
            echo "<p style='color:green'>✅ Columna 'inicio' agregada</p>";
        }

        if (!in_array('degustaciones', $columnNames)) {
            $pdo->exec("ALTER TABLE evento ADD COLUMN degustaciones VARCHAR(120)");
            echo "<p style='color:green'>✅ Columna 'degustaciones' agregada</p>";
        }

        if (!in_array('notas', $columnNames)) {
            $pdo->exec("ALTER TABLE evento ADD COLUMN notas TEXT");
            echo "<p style='color:green'>✅ Columna 'notas' agregada</p>";
        }
    }

    // =====================================================
    // 2. Verificar tabla evento_salon
    // =====================================================
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='evento_salon'");
    if (!$result->fetch()) {
        echo "<p>⚠️ Tabla 'evento_salon' no existe. Creándola...</p>";
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
        echo "<p>✅ Tabla 'evento_salon' existe</p>";
    }

    // =====================================================
    // 3. Verificar tabla evento_salon_platillo
    // =====================================================
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='evento_salon_platillo'");
    if (!$result->fetch()) {
        echo "<p>⚠️ Tabla 'evento_salon_platillo' no existe. Creándola...</p>";
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
        echo "<p>✅ Tabla 'evento_salon_platillo' existe</p>";
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
    // 5. Verificar que hay al menos un evento de ejemplo
    // =====================================================
    $eventosCount = $pdo->query("SELECT COUNT(*) FROM evento")->fetchColumn();
    if ($eventosCount == 0) {
        $pdo->exec("
            INSERT INTO evento (id_evento, fecha, titulo, descorche, cafe) VALUES 
            (1, date('now'), 'Evento de prueba', 0, 0)
        ");
        echo "<p style='color:green'>✅ Evento de ejemplo insertado</p>";
    }

    // =====================================================
    // 6. Mostrar estructura final de evento
    // =====================================================
    echo "<h2>📋 Estructura FINAL de la tabla 'evento':</h2>";
    $columns = $pdo->query("PRAGMA table_info(evento)")->fetchAll();
    echo "<table border='1' cellpadding='8' cellspacing='0'>";
    echo "<tr><th>Columna</th><th>Tipo</th><th>Permite NULL</th><th>Valor por defecto</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['name']}</strong></td>";
        echo "<td>{$col['type']}</td>";
        echo "<td>" . ($col['notnull'] ? 'NO' : 'SÍ') . "</td>";
        echo "<td>" . ($col['dflt_value'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // =====================================================
    // 7. Mostrar eventos existentes
    // =====================================================
    $eventos = $pdo->query("SELECT id_evento, fecha, titulo, descorche FROM evento")->fetchAll();
    if (count($eventos) > 0) {
        echo "<h3>📅 Eventos en la base de datos:</h3>";
        echo "<ul>";
        foreach ($eventos as $e) {
            echo "<li>ID: {$e['id_evento']} - {$e['titulo']} - Fecha: {$e['fecha']} - Descorche: " . ($e['descorche'] ? '✅ Sí' : '❌ No') . "</li>";
        }
        echo "</ul>";
    }

    echo "<h2 style='color:green'>✅ ¡REPARACIÓN COMPLETADA!</h2>";
    echo "<p><a href='evento_list.php'>Ir a evento_list.php</a></p>";
    echo "<p><a href='index.php'>Volver al inicio</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}
