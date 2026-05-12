<?php
// Conexión BD
$host="localhost"; 
$user="root"; 
$pass=""; 
$db="cocina_fantasy";
$conn=new mysqli($host,$user,$pass,$db);
if($conn->connect_error) die("Error: ".$conn->connect_error);

// Listar platillos para el select
$platillos=$conn->query("SELECT id_platillo,nombre,porciones_base FROM platillo ORDER BY nombre");

// Variables
$resultado = [];
$platillo_nombre = "";
$porciones_base = 0;
$porciones_requeridas = 0;

// Si se envió el formulario
if (isset($_POST['calcular'])) {
    $id_platillo = intval($_POST['id_platillo']);
    $porciones_requeridas = intval($_POST['porciones']);

    // Datos del platillo
    $plat = $conn->query("SELECT * FROM platillo WHERE id_platillo=$id_platillo")->fetch_assoc();
    $platillo_nombre = $plat['nombre'];
    $porciones_base = $plat['porciones_base'];

    // Traer ingredientes y calcular cantidades
    $sql="
        SELECT i.nombre,i.unidad,r.cantidad_por_base
        FROM receta r
        JOIN ingrediente i ON r.id_ingrediente=i.id_ingrediente
        WHERE r.id_platillo=$id_platillo
    ";
    $res=$conn->query($sql);

    while($row=$res->fetch_assoc()){
        $cantidad_base = $row['cantidad_por_base'];
        // Regla de 3
        $cantidad_final = ($cantidad_base / $porciones_base) * $porciones_requeridas;
        $resultado[]=[
            'ingrediente'=>$row['nombre'],
            'unidad'=>$row['unidad'],
            'cantidad'=>round($cantidad_final,3)
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Calcular Ingredientes</title>
<style>
 body{font-family:Arial;margin:20px;}
 table{border-collapse:collapse;width:60%;margin-top:15px;}
 th,td{border:1px solid #ccc;padding:8px;text-align:left;}
 th{background:#f3f4f6;}
 input,select{padding:5px;}
 button{padding:6px 10px;margin-top:10px;}
</style>
</head>
<body>
<h1>🥘 Calcular Ingredientes</h1>

<form method="post">
  <label>Platillo:</label>
  <select name="id_platillo" required>
    <?php while($p=$platillos->fetch_assoc()): ?>
      <option value="<?=$p['id_platillo']?>"
        <?=isset($_POST['id_platillo']) && $_POST['id_platillo']==$p['id_platillo'] ? 'selected':''?>>
        <?=$p['nombre']?> (Base: <?=$p['porciones_base']?> porciones)
      </option>
    <?php endwhile; ?>
  </select><br><br>

  <label>Porciones a preparar:</label>
  <input type="number" name="porciones" min="1" required value="<?=$porciones_requeridas?>"><br><br>

  <button type="submit" name="calcular">📊 Calcular</button>
</form>

<?php if (!empty($resultado)): ?>
<h2>Ingredientes para <?=$porciones_requeridas?> porciones de <i><?=$platillo_nombre?></i></h2>
<table>
  <tr><th>Ingrediente</th><th>Cantidad</th><th>Unidad</th></tr>
  <?php foreach($resultado as $r): ?>
  <tr>
    <td><?=$r['ingrediente']?></td>
    <td><?=$r['cantidad']?></td>
    <td><?=$r['unidad']?></td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>

</body>
</html>
