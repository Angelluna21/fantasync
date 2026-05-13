<?php
// listar_archivos.php - Muestra todos los archivos en el servidor
echo "<h1>📁 Archivos en el servidor</h1>";
echo "<ul>";
$files = scandir(__DIR__);
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "<li>$file</li>";
    }
}
echo "</ul>";
