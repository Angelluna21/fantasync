<?php
require_once __DIR__ . '/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) { die('No hay conexión a la base de datos. Revisa db.php'); }
require_once __DIR__ . '/header.php';

if($_SERVER['REQUEST_METHOD']==='POST'){
    if(isset($_POST['create'])){
        $stmt=$pdo->prepare("INSERT INTO salon (nombre,alias) VALUES (?,?)");
        $stmt->execute([trim($_POST['nombre']), trim($_POST['alias']??'') ?: null]);
        echo '<div class="card">Salón creado.</div>';
    }elseif(isset($_POST['update'])){
        $stmt=$pdo->prepare("UPDATE salon SET nombre=?, alias=? WHERE id_salon=?");
        $stmt->execute([trim($_POST['nombre']), trim($_POST['alias']??'') ?: null, (int)$_POST['id_salon']]);
        echo '<div class="card">Salón actualizado.</div>';
    }
}
if(isset($_GET['del'])){
    $stmt=$pdo->prepare("DELETE FROM salon WHERE id_salon=?");
    $stmt->execute([(int)$_GET['del']]);
    echo '<div class="card">Salón eliminado.</div>';
}

$edit=null;
if(isset($_GET['edit'])){
    $st=$pdo->prepare("SELECT * FROM salon WHERE id_salon=?");
    $st->execute([(int)$_GET['edit']]);
    $edit=$st->fetch();
}
$rows = $pdo->query("SELECT * FROM salon ORDER BY nombre")->fetchAll();
?>
<h1>Salones</h1>
<p>
  <a class="btn" href="index.php">← Volver al inicio</a>
</p>

<div class="card">
  <h2><?php echo $edit?'Editar':'Nuevo'; ?> salón</h2>
  <form method="post" class="input-row">
    <label>Nombre<input required name="nombre" type="text" value="<?php echo h($edit['nombre']??''); ?>"></label>
    <label>Alias<input name="alias" type="text" value="<?php echo h($edit['alias']??''); ?>"></label>
    <?php if($edit): ?>
      <input type="hidden" name="id_salon" value="<?php echo (int)$edit['id_salon']; ?>">
      <button class="btn primary" name="update">Guardar cambios</button>
      <a class="btn" href="salon_list.php">Cancelar</a>
    <?php else: ?>
      <button class="btn primary" name="create">Agregar</button>
    <?php endif; ?>
  </form>
</div>

<div class="card">
  <h2>Listado</h2>
  <table>
    <thead><tr><th>ID</th><th>Nombre</th><th>Alias</th><th>Acciones</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?php echo (int)$r['id_salon']; ?></td>
          <td><?php echo h($r['nombre']); ?></td>
          <td><?php echo h($r['alias']); ?></td>
          <td class="actions">
            <a href="?edit=<?php echo (int)$r['id_salon']; ?>">Editar</a>
            <a class="btn danger" href="?del=<?php echo (int)$r['id_salon']; ?>" onclick="return confirm('¿Eliminar salón?');">Eliminar</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
