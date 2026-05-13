<?php
// crear_tablas_receta.php - Crea todas las tablas necesarias para recetas
require_once 'db.php';

echo "<h1>🔧 Creando tablas para recetas</h1>";

try {
    // Verificar tablas existentes
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
    $existing = $result->fetchAll(PDO::FETCH_COLUMN);

    echo "<h2>📋 Tablas existentes:</h2>";
    echo "<ul>";
    foreach ($existing as $t) {
        echo "<li>$t</li>";
    }
    echo "</ul>";

    // =====================================================
    // Crear tabla receta
    // =====================================================
    if (!in_array('receta', $existing)) {
        $pdo->exec("
            CREATE TABLE receta (
                id_platillo INTEGER NOT NULL,
                id_ingrediente INTEGER NOT NULL,
                cantidad_por_base DECIMAL(10,3) NOT NULL,
                nota VARCHAR(120),
                PRIMARY KEY (id_platillo, id_ingrediente)
            )
        ");
        echo "<p style='color:green'>✅ Tabla 'receta' creada</p>";
    } else {
        echo "<p>ℹ️ Tabla 'receta' ya existe</p>";
    }

    // =====================================================
    // Crear tabla plan_compra (si no existe)
    // =====================================================
    if (!in_array('plan_compra', $existing)) {
        $pdo->exec("
            CREATE TABLE plan_compra (
                id_plan INTEGER PRIMARY KEY AUTOINCREMENT,
                fecha DATE NOT NULL,
                id_ingrediente INTEGER NOT NULL,
                cantidad DECIMAL(10,3) NOT NULL
            )
        ");
        echo "<p style='color:green'>✅ Tabla 'plan_compra' creada</p>";
    } else {
        echo "<p>ℹ️ Tabla 'plan_compra' ya existe</p>";
    }

    // =====================================================
    // Verificar que platillo e ingrediente tienen datos
    // =====================================================
    $platillosCount = $pdo->query("SELECT COUNT(*) FROM platillo")->fetchColumn();
    if ($platillosCount == 0) {
        echo "<p>⚠️ No hay platillos. Insertando algunos...</p>";
        $pdo->exec("
            INSERT INTO platillo (id_platillo, nombre, porciones_base) VALUES 
            (1, 'Ensalada de nopales', 100),
            (2, 'Alambre', 100),
            (3, 'Cochinita pibil', 100)
        ");
        echo "<p>✅ Platillos insertados</p>";
    }

    $ingredientesCount = $pdo->query("SELECT COUNT(*) FROM ingrediente")->fetchColumn();
    if ($ingredientesCount == 0) {
        echo "<p>⚠️ No hay ingredientes. Insertando algunos...</p>";
        $pdo->exec("
            INSERT INTO ingrediente (id_ingrediente, nombre, unidad) VALUES 
            (1, 'Nopales', 'pz'),
            (2, 'Cebolla', 'kg'),
            (3, 'Cilantro', 'manojo'),
            (4, 'Jitomate', 'kg')
        ");
        echo "<p>✅ Ingredientes insertados</p>";
    }

    // =====================================================
    // Insertar algunas recetas de ejemplo
    // =====================================================
    $recetasCount = $pdo->query("SELECT COUNT(*) FROM receta")->fetchColumn();
    if ($recetasCount == 0) {
        echo "<p>📝 Insertando recetas de ejemplo...</p>";
        $pdo->exec("
            INSERT INTO receta (id_platillo, id_ingrediente, cantidad_por_base) VALUES 
            (1, 1, 50.000),
            (1, 2, 0.500),
            (1, 3, 0.100),
            (1, 4, 0.500)
        ");
        echo "<p>✅ Recetas de ejemplo insertadas</p>";
    }

    // =====================================================
    // Verificación final
    // =====================================================
    echo "<h2>🔍 Verificación final:</h2>";

    // Verificar estructura de receta
    $columns = $pdo->query("PRAGMA table_info(receta)")->fetchAll();
    echo "<p><strong>Columnas en 'receta':</strong> ";
    echo implode(', ', array_column($columns, 'name'));
    echo "</p>";

    // Mostrar recetas
    $recetas = $pdo->query("
        SELECT p.nombre AS platillo, i.nombre AS ingrediente, r.cantidad_por_base
        FROM receta r
        JOIN platillo p ON r.id_platillo = p.id_platillo
        JOIN ingrediente i ON r.id_ingrediente = i.id_ingrediente
        LIMIT 5
    ")->fetchAll();

    if (count($recetas) > 0) {
        echo "<h3>📋 Ejemplo de recetas:</h3>";
        echo "<ul>";
        foreach ($recetas as $r) {
            echo "<li>{$r['platillo']} → {$r['ingrediente']}: {$r['cantidad_por_base']}</li>";
        }
        echo "</ul>";
    }

    echo "<h2 style='color:green'>✅ ¡Todo listo!</h2>";
    echo "<p><a href='recetas.php'>Ir a recetas.php</a></p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Línea: " . $e->getLine() . "</p>";
}
