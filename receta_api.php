<?php
require_once __DIR__ . '/db.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0){
  echo json_encode([]);
  exit;
}

$sql = "
  SELECT r.cantidad_por_base, i.nombre AS ingrediente, i.unidad
  FROM receta r
  JOIN ingrediente i ON i.id_ingrediente = r.id_ingrediente
  WHERE r.id_platillo = $id
  ORDER BY i.nombre ASC
";

$res = $pdo->query($sql);
$data = $res->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($data);
