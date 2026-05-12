<?php
require_once __DIR__ . '/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) { die('No hay conexión a la base de datos. Revisa db.php'); }
require_once __DIR__ . '/header.php';
$id_evento = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ev = $pdo->prepare("SELECT * FROM evento WHERE id_evento=?"); $ev->execute([$id_evento]); $ev = $ev->fetch();
if(!$ev){ echo "<div class='card'>Evento no encontrado.</div>"; require_once __DIR__ . '/footer.php'; exit; }

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['create'])){
    $stmt=$pdo->prepare("INSERT INTO evento_salon (id_evento,id_salon,adultos,ninos,misa,recepcion,inicio,descorche,cafe,degustaciones,factor_nino) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$id_evento,(int)$_POST['id_salon'],(int)$_POST['adultos'],(int)$_POST['ninos'],
                    $_POST['misa'] ?: null, $_POST['recepcion'] ?: null, $_POST['inicio'] ?: null,
                    isset($_POST['descorche'])?1:0, isset($_POST['cafe'])?1:0,
                    trim($_POST['degustaciones']??'') ?: null,
                    $_POST['factor_nino']!==''? (float)$_POST['factor_nino'] : 0.70]);
    echo '<div class="card">Salón agregado al evento.</div>';
}
if(isset($_GET['del'])){
    $stmt=$pdo->prepare("DELETE FROM evento_salon WHERE id_evento_salon=? AND id_evento=?");
    $stmt->execute([(int)$_GET['del'],$id_evento]);
    echo '<div class="card">Salón eliminado del evento.</div>';
}

$salones = $pdo->query("SELECT * FROM salon ORDER BY nombre")->fetchAll();
$rows = $pdo->prepare("SELECT es.*, s.nombre AS salon FROM evento_salon es JOIN salon s ON s.id_salon=es.id_salon WHERE es.id_evento=? ORDER BY salon");
$rows->execute([$id_evento]); $rows = $rows->fetchAll();
?>
<h1>Salones del evento #<?php echo $id_evento; ?> – <?php echo h($ev['fecha']); ?></h1>
<p><a class="btn" href="evento_list.php">← Volver a eventos</a> | <a class="btn" href="reporte_evento.php?id=<?php echo $id_evento; ?>">Ver reporte</a></p>

<div class="card">
  <h2>Agregar salón</h2>
  <form method="post">
    <div class="input-row">
      <label>Salón
        <select name="id_salon" required>
          <option value="">-- Selecciona --</option>
          <?php foreach($salones as $s): ?>
            <option value="<?php echo (int)$s['id_salon']; ?>"><?php echo h($s['nombre']); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Adultos<input type="number" name="adultos" value="0" min="0"></label>
      <label>Niños<input type="number" name="ninos" value="0" min="0"></label>
      <label>Factor niño<input type="number" step="0.05" name="factor_nino" value="0.70"></label>
    </div>
    <div class="input-row">
      <label>Misa<input type="time" name="misa"></label>
      <label>Recepción<input type="time" name="recepcion" value="<?php echo h($ev['recepcion']); ?>"></label>
      <label>Inicio<input type="time" name="inicio" value="<?php echo h($ev['inicio']); ?>"></label>
      <label><span>Descorche</span><input type="checkbox" name="descorche" <?php echo $ev['descorche']?'checked':''; ?> ></label>
      <label><span>Café</span><input type="checkbox" name="cafe" <?php echo $ev['cafe']?'checked':''; ?> ></label>
    </div>
    <div class="input-row">
      <label>Degustaciones<input type="text" name="degustaciones" value="<?php echo h($ev['degustaciones']); ?>"></label>
    </div>
    <button class="btn primary" name="create">Agregar al evento</button>
  </form>
</div>

<div class="card">
  <h2>Asignados</h2>
  <table>
    <thead><tr><th>Salón</th><th>Adultos</th><th>Niños</th><th>Horarios</th><th>Opciones</th><th>Menú</th><th>Quitar</th></tr></thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td><?php echo h($r['salon']); ?></td>
          <td><?php echo (int)$r['adultos']; ?></td>
          <td><?php echo (int)$r['ninos']; ?></td>
          <td>
            <?php if($r['misa']) echo 'Misa '.$r['misa'].' · '; ?>
            Recepción <?php echo h($r['recepcion']); ?> · Inicio <?php echo h($r['inicio']); ?>
            <?php if($r['descorche']) echo " · Descorche SI"; ?>
            <?php if($r['cafe']) echo " · Café SI"; ?>
          </td>
          <td><?php echo $r['degustaciones']? h($r['degustaciones']) : ''; ?></td>
          <td class="actions">
            <a class="btn" href="evento_menu.php?id_es=<?php echo (int)$r['id_evento_salon']; ?>">Menú y porciones</a>
          </td>
          <td><a class="btn danger" href="?id=<?php echo $id_evento; ?>&del=<?php echo (int)$r['id_evento_salon']; ?>" onclick="return confirm('¿Quitar este salón del evento?');">Eliminar</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
