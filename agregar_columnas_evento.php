<?php
// agregar_columnas_evento.php - Agrega todas las columnas necesarias a la tabla evento
require_once 'db.php';

echo "<h1>🔧 Agregando columnas a la tabla 'evento'</h1>";

try {
    // Verificar las columnas existentes
    $result = $pdo->query("PRAGMA table_info(evento)");
    $existingColumns = [];
    foreach ($result->fetchAll() as $col) {
        $existingColumns[] = $col['name'];
    }

    echo "<h2>📋 Columnas existentes:</h2>";
    echo "<ul>";
    foreach ($existingColumns as $col) {
        echo "<li>✅ $col</li>";
    }
    echo "</ul>";

    // Lista de columnas que debería tener la tabla evento
    $columnsToAdd = [
        'descorche' => "BOOLEAN NOT NULL DEFAULT 0",
        'cafe' => "BOOLEAN NOT NULL DEFAULT 0",
        'degustaciones' => "VARCHAR(120)",
        'notas' => "TEXT",
        'misa' => "TIME",
        'recepcion' => "TIME",
        'inicio' => "TIME"
    ];

    $added = [];

    foreach ($columnsToAdd as $column => $definition) {
        if (!in_array($column, $existingColumns)) {
            $pdo->exec("ALTER TABLE evento ADD COLUMN $column $definition");
            $added[] = $column;
            echo "<p style='color:green'>✅ Columna '$column' agregada</p>";
        } else {
            echo "<p>ℹ️ Columna '$column' ya existe</p>";
        }
    }

    // Insertar algunos datos de ejemplo si la tabla está vacía
    $count = $pdo->query("SELECT COUNT(*) FROM evento")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("
            INSERT INTO evento (fecha, titulo, descorche, cafe) VALUES 
            (date('now'), 'Evento de ejemplo', 0, 0),
            (date('now', '+1 day'), 'Otro evento', 1, 1)
        ");
        echo "<p style='color:green'>✅ Datos de ejemplo insertados</p>";
    }

    // Verificar estructura final
    echo "<h2>📋 Estructura final de la tabla 'evento':</h2>";
    $result = $pdo->query("PRAGMA table_info(evento)");
    echo "<ul>";
    foreach ($result->fetchAll() as $col) {
        echo "<li><strong>{$col['name']}</strong> - {$col['type']}</li>";
    }
    echo "</ul>";

    echo "<h2 style='color:green'>✅ ¡Listo! Ahora evento_list.php debería funcionar</h2>";
    echo "<p><a href='evento_list.php'>Ir a evento_list.php</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
