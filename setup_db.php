<?php
// setup_db.php - Ejecutar UNA SOLA VEZ para crear la tabla 'salon'
// La consola no está disponible en Render Gratis, así que hacemos esto por código.

// Incluir la conexión a la base de datos
require_once 'db.php';

echo "<h1>Configuración Inicial de la Base de Datos</h1>";

try {
    // Intentar crear la tabla 'salon' si no existe
    // Usamos "IF NOT EXISTS" para que no dé error si ya existe
    $sql = "CREATE TABLE IF NOT EXISTS salon (
                id_salon INTEGER PRIMARY KEY AUTOINCREMENT,
                nombre VARCHAR(80) NOT NULL,
                alias VARCHAR(20)
            );";

    $pdo->exec($sql);
    echo "<p style='color:green'>✅ Tabla 'salon' creada/verificada correctamente.</p>";

    // Insertar los datos de ejemplo si la tabla está vacía
    $checkStmt = $pdo->query("SELECT COUNT(*) as count FROM salon");
    $count = $checkStmt->fetch()['count'];

    if ($count == 0) {
        $insertSql = "INSERT INTO salon (id_salon, nombre, alias) VALUES 
                      (1, 'CARMELO', NULL),
                      (2, 'SAN RAFAEL', NULL);";
        $pdo->exec($insertSql);
        echo "<p style='color:green'>✅ Datos de ejemplo insertados en 'salon'.</p>";
    } else {
        echo "<p>ℹ️ La tabla 'salon' ya tiene datos. No se insertaron ejemplos.</p>";
    }

    echo "<h2 style='color:green'>¡Problema solucionado!</h2>";
    echo "<p>Ya puedes visitar tu <a href='salon_list.php'>lista de salones</a>.</p>";
} catch (PDOException $e) {
    echo "<p style='color:red'>❌ Error al configurar la base de datos: " . $e->getMessage() . "</p>";
}
