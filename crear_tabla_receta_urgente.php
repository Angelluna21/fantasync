<?php
// crear_tabla_receta_urgente.php - Crea la tabla receta urgentemente
require_once 'db.php';

echo "<h1>🔧 Creando tabla 'receta'</h1>";

try {
    // Verificar si la tabla receta existe
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='receta'");
    if (!$result->fetch()) {
        // Crear tabla receta
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

        // Insertar algunas recetas de ejemplo si hay datos
        $platillosCount = $pdo->query("SELECT COUNT(*) FROM platillo")->fetchColumn();
        $ingredientesCount = $pdo->query("SELECT COUNT(*) FROM ingrediente")->fetchColumn();

        if ($platillosCount > 0 && $ingredientesCount > 0) {
            // Obtener el primer platillo y los primeros ingredientes
            $primerPlatillo = $pdo->query("SELECT id_platillo FROM platillo LIMIT 1")->fetchColumn();
            $ingredientes = $pdo->query("SELECT id_ingrediente FROM ingrediente LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);

            if ($primerPlatillo && count($ingredientes) > 0) {
                $stmt = $pdo->prepare("INSERT OR IGNORE INTO receta (id_platillo, id_ingrediente, cantidad_por_base) VALUES (?, ?, ?)");
                foreach ($ingredientes as $idx => $idIng) {
                    $stmt->execute([$primerPlatillo, $idIng, 1.000]);
                }
                echo "<p>✅ Recetas de ejemplo insertadas para el platillo ID: $primerPlatillo</p>";
            }
        }
    } else {
        echo "<p>ℹ️ La tabla 'receta' ya existe</p>";
    }

    // Verificar que la tabla existe
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='receta'");
    if ($result->fetch()) {
        $count = $pdo->query("SELECT COUNT(*) FROM receta")->fetchColumn();
        echo "<p>📊 Tabla 'receta' tiene $count registros</p>";
        echo "<h2 style='color:green'>✅ Tabla 'receta' está lista</h2>";
    } else {
        echo "<p style='color:red'>❌ No se pudo crear la tabla 'receta'</p>";
    }

    echo "<p><a href='evento_list.php'>Ir a evento_list.php</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}
