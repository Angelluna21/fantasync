<?php
// crear_tabla_receta_final.php - Crea la tabla receta y verifica
require_once 'db.php';

echo "<h1>🔧 Creando tabla 'receta'</h1>";

try {
    // Verificar si la tabla ya existe
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='receta'");
    if ($result->fetch()) {
        echo "<p>ℹ️ La tabla 'receta' ya existe</p>";

        // Mostrar estructura actual
        $columns = $pdo->query("PRAGMA table_info(receta)")->fetchAll();
        echo "<h2>📋 Estructura actual:</h2>";
        echo "<ul>";
        foreach ($columns as $col) {
            echo "<li>{$col['name']} - {$col['type']}</li>";
        }
        echo "</ul>";
    } else {
        // Crear la tabla receta
        $pdo->exec("
            CREATE TABLE receta (
                id_platillo INTEGER NOT NULL,
                id_ingrediente INTEGER NOT NULL,
                cantidad_por_base DECIMAL(10,3) NOT NULL,
                nota VARCHAR(120),
                PRIMARY KEY (id_platillo, id_ingrediente)
            )
        ");
        echo "<p style='color:green'>✅ Tabla 'receta' creada correctamente</p>";

        // Insertar algunas recetas de ejemplo si hay platillos e ingredientes
        $platillosCount = $pdo->query("SELECT COUNT(*) FROM platillo")->fetchColumn();
        $ingredientesCount = $pdo->query("SELECT COUNT(*) FROM ingrediente")->fetchColumn();

        if ($platillosCount > 0 && $ingredientesCount > 0) {
            echo "<p>📝 Insertando recetas de ejemplo...</p>";

            // Obtener el primer platillo y primeros ingredientes
            $primerPlatillo = $pdo->query("SELECT id_platillo FROM platillo LIMIT 1")->fetchColumn();
            $primerIngrediente = $pdo->query("SELECT id_ingrediente FROM ingrediente LIMIT 1")->fetchColumn();
            $segundoIngrediente = $pdo->query("SELECT id_ingrediente FROM ingrediente LIMIT 1 OFFSET 1")->fetchColumn();

            if ($primerPlatillo && $primerIngrediente) {
                $stmt = $pdo->prepare("INSERT OR IGNORE INTO receta (id_platillo, id_ingrediente, cantidad_por_base) VALUES (?, ?, ?)");
                $stmt->execute([$primerPlatillo, $primerIngrediente, 1.000]);
                echo "<p>✅ Receta de ejemplo insertada</p>";
            }
        }
    }

    // Verificar que la tabla existe ahora
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='receta'");
    if ($result->fetch()) {
        echo "<h2 style='color:green'>✅ La tabla 'receta' está lista</h2>";

        // Mostrar recetas existentes
        $recetas = $pdo->query("SELECT COUNT(*) as total FROM receta")->fetchColumn();
        echo "<p>📊 Total de recetas: $recetas</p>";

        echo "<p><a href='recetas.php'>Ir a recetas.php</a></p>";
    } else {
        echo "<p style='color:red'>❌ No se pudo crear la tabla</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
