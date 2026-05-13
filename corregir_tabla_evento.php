<?php
// corregir_tabla_evento.php - Agrega la columna descorche a la tabla evento
require_once 'db.php';

echo "<h1>🔧 Corrigiendo tabla 'evento'</h1>";

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

    // Columnas que necesita la tabla evento
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

    // Verificar estructura final
    echo "<h2>📋 Estructura final de la tabla 'evento':</h2>";
    $result = $pdo->query("PRAGMA table_info(evento)");
    echo "<ul>";
    foreach ($result->fetchAll() as $col) {
        echo "<li><strong>{$col['name']}</strong> - {$col['type']}</li>";
    }
    echo "</ul>";

    // Mostrar algunos eventos para verificar
    $eventos = $pdo->query("SELECT id_evento, fecha, titulo, descorche, cafe FROM evento LIMIT 5")->fetchAll();
    if (count($eventos) > 0) {
        echo "<h3>📅 Eventos en la base de datos:</h3>";
        echo "<ul>";
        foreach ($eventos as $e) {
            echo "<li>ID: {$e['id_evento']} - {$e['titulo']} - Descorche: " . ($e['descorche'] ? 'Sí' : 'No') . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>⚠️ No hay eventos. Puedes crear uno desde la interfaz.</p>";
    }

    echo "<h2 style='color:green'>✅ ¡Listo! Ahora evento_list.php debería funcionar</h2>";
    echo "<p><a href='evento_list.php'>Ir a evento_list.php</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
