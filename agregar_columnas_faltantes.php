<?php
// agregar_columnas_faltantes.php - Agrega todas las columnas que necesita evento_list.php
require_once 'db.php';

echo "<h1>🔧 Agregando columnas faltantes a la tabla 'evento'</h1>";

try {
    // Verificar columnas actuales
    $columns = $pdo->query("PRAGMA table_info(evento)")->fetchAll();
    $columnNames = array_column($columns, 'name');

    echo "<h2>📋 Columnas actuales:</h2>";
    echo "<ul>";
    foreach ($columnNames as $col) {
        echo "<li>$col</li>";
    }
    echo "</ul>";

    // Columnas que necesita evento_list.php
    $columnsToAdd = [
        'cafe' => "BOOLEAN NOT NULL DEFAULT 0",
        'descorche' => "BOOLEAN NOT NULL DEFAULT 0",
        'misa' => "TIME",
        'recepcion' => "TIME",
        'inicio' => "TIME",
        'degustaciones' => "VARCHAR(120)",
        'notas' => "TEXT"
    ];

    $added = [];

    foreach ($columnsToAdd as $column => $definition) {
        if (!in_array($column, $columnNames)) {
            try {
                $pdo->exec("ALTER TABLE evento ADD COLUMN $column $definition");
                $added[] = $column;
                echo "<p style='color:green'>✅ Columna '$column' agregada</p>";
            } catch (PDOException $e) {
                echo "<p style='color:orange'>⚠️ No se pudo agregar '$column': " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p>ℹ️ Columna '$column' ya existe</p>";
        }
    }

    // Verificar resultado final
    echo "<h2>📋 Columnas finales de la tabla 'evento':</h2>";
    $columns = $pdo->query("PRAGMA table_info(evento)")->fetchAll();
    echo "<ul>";
    foreach ($columns as $col) {
        $icon = in_array($col['name'], array_keys($columnsToAdd)) ? "✅" : "📌";
        echo "<li>$icon <strong>{$col['name']}</strong> - {$col['type']}</li>";
    }
    echo "</ul>";

    // Mostrar eventos de ejemplo
    $eventos = $pdo->query("SELECT id_evento, fecha, titulo, descorche, cafe FROM evento LIMIT 3")->fetchAll();
    if (count($eventos) > 0) {
        echo "<h3>📅 Eventos actuales:</h3>";
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>ID</th><th>Fecha</th><th>Título</th><th>Descorche</th><th>Café</th></tr>";
        foreach ($eventos as $e) {
            echo "<tr>";
            echo "<td>{$e['id_evento']}</td>";
            echo "<td>{$e['fecha']}</td>";
            echo "<td>{$e['titulo']}</td>";
            echo "<td>" . ($e['descorche'] ? '✅ Sí' : '❌ No') . "</td>";
            echo "<td>" . ($e['cafe'] ? '✅ Sí' : '❌ No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<h2 style='color:green'>✅ ¡COLUMNAS AGREGADAS!</h2>";
    echo "<p><a href='evento_list.php'>Ir a evento_list.php</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
