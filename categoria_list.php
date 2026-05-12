<?php
require_once __DIR__ . '/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) { die('No hay conexión a la base de datos. Revisa db.php'); }
require_once __DIR__ . '/header.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['create'])){
        try {
            $stmt=$pdo->prepare("INSERT INTO categoria_platillo (nombre,orden) VALUES (?,?)");
            $stmt->execute([trim($_POST['nombre']), (int)$_POST['orden']]);
            echo "<div class='card' style='background:#d4edda;border-color:#c3e6cb;color:#155724;'>✅ Categoría creada correctamente.</div>";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo "<div class='card' style='background:#f8d7da;border-color:#f5c6cb;color:#721c24;'>❌ Error: Ya existe una categoría con el nombre '".htmlspecialchars(trim($_POST['nombre']))."'. Por favor, elige un nombre diferente.</div>";
            } else {
                echo "<div class='card' style='background:#f8d7da;border-color:#f5c6cb;color:#721c24;'>❌ Error al crear la categoría: ".htmlspecialchars($e->getMessage())."</div>";
            }
        }
    }elseif(isset($_POST['update'])){
        try {
            $stmt=$pdo->prepare("UPDATE categoria_platillo SET nombre=?, orden=? WHERE id_categoria=?");
            $stmt->execute([trim($_POST['nombre']), (int)$_POST['orden'], (int)$_POST['id_categoria']]);
            echo "<div class='card' style='background:#d4edda;border-color:#c3e6cb;color:#155724;'>✅ Categoría actualizada correctamente.</div>";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                echo "<div class='card' style='background:#f8d7da;border-color:#f5c6cb;color:#721c24;'>❌ Error: Ya existe una categoría con el nombre '".htmlspecialchars(trim($_POST['nombre']))."'. Por favor, elige un nombre diferente.</div>";
            } else {
                echo "<div class='card' style='background:#f8d7da;border-color:#f5c6cb;color:#721c24;'>❌ Error al actualizar la categoría: ".htmlspecialchars($e->getMessage())."</div>";
            }
        }
    }
}
if(isset($_GET['del'])){
    try {
        $stmt=$pdo->prepare("DELETE FROM categoria_platillo WHERE id_categoria=?");
        $stmt->execute([(int)$_GET['del']]);
        echo "<div class='card' style='background:#d4edda;border-color:#c3e6cb;color:#155724;'>✅ Categoría eliminada correctamente.</div>";
    } catch (PDOException $e) {
        echo "<div class='card' style='background:#f8d7da;border-color:#f5c6cb;color:#721c24;'>❌ Error al eliminar la categoría: ".htmlspecialchars($e->getMessage())."</div>";
    }
}

$edit=null;
if(isset($_GET['edit'])){
    $st=$pdo->prepare("SELECT * FROM categoria_platillo WHERE id_categoria=?");
    $st->execute([(int)$_GET['edit']]);
    $edit=$st->fetch();
}
$rows = $pdo->query("SELECT * FROM categoria_platillo ORDER BY orden, nombre")->fetchAll();
?>
<h1>Categorías de platillo</h1>
<p>
  <a class="btn" href="index.php">← Volver al inicio</a>
</p>

<div class="card">
  <h2><?php echo $edit?'Editar':'Nueva'; ?> categoría</h2>
  <form method="post" class="input-row">
    <label>Nombre<input required name="nombre" type="text" value="<?php echo h($edit['nombre']??''); ?>"></label>
    <label>Orden<input required name="orden" type="number" value="<?php echo h($edit['orden']??1); ?>"></label>
    <?php if($edit): ?>
      <input type="hidden" name="id_categoria" value="<?php echo (int)$edit['id_categoria']; ?>">
      <button class="btn primary" name="update">Guardar</button>
      <a class="btn" href="categoria_list.php">Cancelar</a>
    <?php else: ?>
      <button class="btn primary" name="create">Agregar</button>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <h2>Listado</h2>
  <table>
    <thead><tr><th>ID</th><th>Nombre</th><th>Orden</th><th>Acciones</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?php echo (int)$r['id_categoria']; ?></td>
          <td><?php echo h($r['nombre']); ?></td>
          <td><?php echo (int)$r['orden']; ?></td>
          <td class="actions">
            <a href="?edit=<?php echo (int)$r['id_categoria']; ?>">Editar</a>
            <a class="btn danger" href="?del=<?php echo (int)$r['id_categoria']; ?>" onclick="return confirm('¿Eliminar categoría?');">Eliminar</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
