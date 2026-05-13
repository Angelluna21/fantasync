<?php
// corregir_evento.php - Agrega la columna descorche a la tabla evento
require_once 'db.php';

echo "<h1>🔧 Corrigiendo tabla 'evento'</h1>";

try {
    // Verificar si la columna descorche existe
    $result = $pdo->query("PRAGMA table_info(evento)");
    $columns = $result->fetchAll();
    $hasDescorche = false;

    foreach ($columns as $col) {
        if ($col['name'] == 'descorche') {
            $hasDescorche = true;
            break;
        }
    }

    if (!$hasDescorche) {
        // Agregar la columna descorche
        $pdo->exec("ALTER TABLE evento ADD COLUMN descorche BOOLEAN NOT NULL DEFAULT 0");
        echo "<p style='color:green'>✅ Columna 'descorche' agregada a la tabla 'evento'</p>";
    } else {
        echo "<p>ℹ️ La columna 'descorche' ya existe</p>";
    }

    // Verificar otras columnas que podrían faltar
    $columnsNeeded = ['cafe', 'degustaciones', 'notas', 'misa', 'recepcion', 'inicio'];

    foreach ($columnsNeeded as $colName) {
        $exists = false;
        foreach ($columns as $col) {
            if ($col['name'] == $colName) {
                $exists = true;
                break;
            }
        }
        if (!$exists) {
            if ($colName == 'cafe') {
                $pdo->exec("ALTER TABLE evento ADD COLUMN cafe BOOLEAN NOT NULL DEFAULT 0");
                echo "<p style='color:green'>✅ Columna 'cafe' agregada</p>";
            } elseif ($colName == 'degustaciones') {
                $pdo->exec("ALTER TABLE evento ADD COLUMN degustaciones VARCHAR(120)");
                echo "<p style='color:green'>✅ Columna 'degustaciones' agregada</p>";
            } elseif ($colName == 'notas') {
                $pdo->exec("ALTER TABLE evento ADD COLUMN notas TEXT");
                echo "<p style='color:green'>✅ Columna 'notas' agregada</p>";
            } elseif ($colName == 'misa') {
                $pdo->exec("ALTER TABLE evento ADD COLUMN misa TIME");
                echo "<p style='color:green'>✅ Columna 'misa' agregada</p>";
            } elseif ($colName == 'recepcion') {
                $pdo->exec("ALTER TABLE evento ADD COLUMN recepcion TIME");
                echo "<p style='color:green'>✅ Columna 'recepcion' agregada</p>";
            } elseif ($colName == 'inicio') {
                $pdo->exec("ALTER TABLE evento ADD COLUMN inicio TIME");
                echo "<p style='color:green'>✅ Columna 'inicio' agregada</p>";
            }
        }
    }

    // Mostrar estructura actual de la tabla
    echo "<h2>📋 Estructura actual de la tabla 'evento':</h2>";
    $result = $pdo->query("PRAGMA table_info(evento)");
    echo "<ul>";
    foreach ($result->fetchAll() as $col) {
        echo "<li><strong>{$col['name']}</strong> - {$col['type']}</li>";
    }
    echo "</ul>";

    echo "<h2 style='color:green'>✅ Reparación completada</h2>";
    echo "<p><a href='evento_list.php'>Ir a evento_list.php</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
