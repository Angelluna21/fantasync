<?php
// limpiar_columna_hora.php - Elimina la columna hora si existe
require_once 'db.php';

echo "<h1>🧹 Limpiando columna 'hora'</h1>";

try {
    // Verificar si la columna hora existe
    $columns = $pdo->query("PRAGMA table_info(evento)")->fetchAll();
    $columnNames = array_column($columns, 'name');

    if (in_array('hora', $columnNames)) {
        // SQLite no permite DROP COLUMN directamente, necesitamos recrear la tabla
        echo "<p>⚠️ La columna 'hora' existe. Eliminándola...</p>";

        // Crear una nueva tabla sin la columna hora
        $pdo->exec("
            CREATE TABLE evento_nueva (
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

        // Copiar los datos existentes
        $pdo->exec("
            INSERT INTO evento_nueva (id_evento, fecha, titulo, misa, recepcion, inicio, descorche, cafe, degustaciones, notas)
            SELECT id_evento, fecha, titulo, misa, recepcion, inicio, descorche, cafe, degustaciones, notas
            FROM evento
        ");

        // Eliminar la tabla vieja
        $pdo->exec("DROP TABLE evento");

        // Renombrar la nueva tabla
        $pdo->exec("ALTER TABLE evento_nueva RENAME TO evento");

        echo "<p style='color:green'>✅ Columna 'hora' eliminada correctamente</p>";
    } else {
        echo "<p>✅ La columna 'hora' no existe, todo está bien</p>";
    }

    // Mostrar estructura final
    echo "<h2>📋 Estructura final de la tabla 'evento':</h2>";
    $columns = $pdo->query("PRAGMA table_info(evento)")->fetchAll();
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li><strong>{$col['name']}</strong> - {$col['type']}</li>";
    }
    echo "</ul>";

    echo "<p><a href='evento_list.php'>Ir a evento_list.php</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
