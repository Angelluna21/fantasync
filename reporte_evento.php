<?php 
require_once __DIR__ . '/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) { die('No hay conexión a la base de datos. Revisa db.php'); }
require_once __DIR__ . '/header.php';

if (!function_exists('h')) { function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

/* Inicialización de la variable $porSalon */
$porSalon = []; // Aseguramos que esté definida antes de su uso

/* ========= Utilidades ========= */
function diaES($ymd){
  static $dias = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];
  $ts = strtotime($ymd);
  if ($ts===false) return '';
  return $dias[(int)date('w',$ts)];
}

function fmt_human($val, $ingrediente = null, $unidad = null){
  $val = round((float)$val, 3);

  if ($ingrediente && stripos($ingrediente, 'nopal') !== false && $unidad && strtolower($unidad) === 'pz') {
    if ($val > 0 && $val <= 62) $val = 60;
  }

  if ($val > 0 && $val < 1) {
    if ($val >= 0.07) $val = 1.0;
    elseif ($val <= 0.06) $val = 0.5;
  }

  $ent = floor($val + 1e-9);
  $frac = round($val - $ent, 2);

  if ($frac >= 0.24 && $frac <= 0.26) return trim(($ent? $ent : '').' 1/4');
  if ($frac >= 0.49 && $frac <= 0.51) return trim(($ent? $ent : '').' 1/2');
  if ($frac >= 0.74 && $frac <= 0.76) return trim(($ent? $ent : '').' 3/4');

   if ($unidad && strtolower($unidad) === 'kg') {
      if ($val > 0 && $val < 1) {
          $gramos = round($val * 1000);
          return $gramos . ' gr';
      }
  }

 // === Reglas especiales para unidad "gr" ===
if ($unidad && strtolower($unidad) === 'gr') {

    // 1) Menos de 0.5 kg → mostrar gramos
    if ($val < 0.5) {
        $gramos = round($val * 1000);
        return $gramos . ' gr';
    }

    // 2) Entre 0.5 y 0.59 → dejar como kg con decimales reales
    if ($val >= 0.5 && $val < 0.6) {
        $kg = rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.');
        return $kg . ' kg';
    }

    // 3) Entre 0.6 y 0.9 → subir a 1 kg
    if ($val >= 0.6 && $val < 1.0) {
        return '1 kg';
    }

    // 4) 1.0 kg o más → dejar su valor real
    $kg = rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.');
    return $kg . ' kg';
}


  $s = number_format($val, 2, '.', '');
  $s = rtrim(rtrim($s,'0'),'.');
  return $s==='' ? '0' : $s;
}

function norm($s){
    if ($s === null) return '';
    $s = trim((string)$s);
    $s = mb_strtolower($s, 'UTF-8');
    $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
    $s = preg_replace('/[^a-z0-9\s]/i', '', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

function calcular_receta_por_porciones($pdo, $id_platillo, $porciones_plan){
  $out = [];
  $pstmt = $pdo->prepare("SELECT porciones_base, nombre FROM platillo WHERE id_platillo=?");
  $pstmt->execute([$id_platillo]);
  $pinfo = $pstmt->fetch(PDO::FETCH_ASSOC);
  if (!$pinfo) return $out;
  $porciones_base = (float)($pinfo['porciones_base'] ?: 100); // Se mantiene a base de 100 si no está configurado de otra forma
  $platillo_name = $pinfo['nombre'];

  $r = $pdo->prepare("
    SELECT r.id_ingrediente, i.nombre AS ingrediente, i.unidad, i.presentacion_descripcion,
           r.cantidad_por_base, r.nota
    FROM receta r
    JOIN ingrediente i ON i.id_ingrediente = r.id_ingrediente
    WHERE r.id_platillo = ?
  ");
  $r->execute([$id_platillo]);
  $rows = $r->fetchAll(PDO::FETCH_ASSOC);

  foreach($rows as $rw){
    
    // Ajustamos el cálculo para el número exacto de porciones
    $cantidad_calc = round( (float)$rw['cantidad_por_base'] * ($porciones_plan / max(1, $porciones_base)), 3);

    // --- Convertir kg pequeños a gramos ---
    $unidad = $rw['unidad'];
    if ($unidad && strtolower($unidad) === 'kg') {
        if ($cantidad_calc > 0 && $cantidad_calc < 1) {
            $cantidad_calc = $cantidad_calc * 1000; // pasa a gramos
            $unidad = 'gr'; // cambia unidad
        }
    }

    $out[] = [
      'platillo' => $platillo_name,
      'id_ingrediente' => $rw['id_ingrediente'],
      'ingrediente' => $rw['ingrediente'],
      'unidad' => $unidad,  // unidad corregida
      'presentacion_descripcion' => $rw['presentacion_descripcion'],
      'nota_receta' => $rw['nota'],
      'cantidad_calc' => $cantidad_calc,
      'cantidad_mostrada' => fmt_human($cantidad_calc, $rw['ingrediente'], $unidad)
    ];
}

return $out;
}
/* ========= Parámetro ========= */
$id_evento = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_evento <= 0) { echo "<div class='card'>Falta el parámetro ?id=</div>"; require_once __DIR__ . '/footer.php'; exit; }

/* ========= Notas del evento ========= */
$evtStmt = $pdo->prepare("SELECT notas FROM evento WHERE id_evento=?");
$evtStmt->execute([$id_evento]);
$eventoRow   = $evtStmt->fetch(PDO::FETCH_ASSOC);
$notas_evento = trim($eventoRow['notas'] ?? '');

/* ========= Headers por salón ========= */
try {
  $q = $pdo->prepare("SELECT * FROM vw_evento_salon_header WHERE id_evento=? ORDER BY salon");
  $q->execute([$id_evento]);
  $headers = $q->fetchAll(PDO::FETCH_ASSOC);
  foreach ($headers as &$hh) {
    $hh['descorche'] = (isset($hh['descorche']) && strtoupper($hh['descorche'])==='SI') ? 'SI' : 'NO';
    $hh['cafe'] = (isset($hh['cafe']) && strtoupper($hh['cafe'])==='SI') ? 'SI' : 'NO';
  }
  unset($hh);
} catch (Throwable $e) {
  $q = $pdo->prepare("
    SELECT es.id_evento_salon, e.id_evento, e.fecha,
           COALESCE(s.alias, s.nombre) AS salon,
           es.adultos, es.ninos,
           COALESCE(es.misa, e.misa) AS misa,
           COALESCE(es.recepcion, e.recepcion) AS recepcion,
           COALESCE(es.inicio, e.inicio) AS inicio,
           CASE WHEN COALESCE(es.descorche,e.descorche)=1 THEN 'SI' ELSE 'NO' END AS descorche,
           CASE WHEN COALESCE(es.cafe,     e.cafe)=1      THEN 'SI' ELSE 'NO' END AS cafe,
           COALESCE(es.degustaciones, e.degustaciones) AS degustaciones
    FROM evento_salon es
    JOIN evento e ON e.id_evento = es.id_evento
    JOIN salon  s ON s.id_salon  = es.id_salon
    WHERE es.id_evento=? 
    ORDER BY salon
  ");
  $q->execute([$id_evento]);
  $headers = $q->fetchAll(PDO::FETCH_ASSOC);
}

if(!$headers){ echo "<div class='card'>No hay salones asignados para este evento.</div>"; require_once __DIR__ . '/footer.php'; exit; }

$detStmt = $pdo->prepare("
  SELECT
    id_evento_salon,
    salon,
    seccion,
    orden_seccion,
    orden_platillo,
    platillo,
    ingrediente,
    unidad,
    presentacion_descripcion,
    nota_receta,
    cantidad_calc,
    cantidad_mostrada
  FROM vw_evento_salon_platillo_ingrediente
  WHERE id_evento = :id
  ORDER BY salon, orden_seccion, seccion, COALESCE(orden_platillo,999), platillo, ingrediente
");
$detStmt->execute([':id' => $id_evento]);
$det = $detStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($det as &$r) {
    $platilloLower = strtolower($r['platillo'] ?? '');
    
    // Ajuste pizza y gelatina
    if (strpos($platilloLower, 'pizza') !== false || strpos($platilloLower, 'gelatina') !== false) {
        $totalPersonas = 0;
        foreach ($headers as $h) {
            if ((int)$h['id_evento_salon'] === (int)$r['id_evento_salon']) {
                $totalPersonas = max(0, (int)($h['ninos'] ?? 0));
                break;
            }
        }

        $r['cantidad_calc']     = $totalPersonas;
        $r['cantidad_mostrada'] = fmt_human($totalPersonas, $r['ingrediente'], 'pieza');
        $r['unidad']            = 'pieza';
    }

    // Ajuste para FRUTA (1 pieza por niño)
    if (strpos($platilloLower, 'fruta') !== false) {
        $totalPersonas = 0;
        foreach ($headers as $h) {
            if ((int)$h['id_evento_salon'] === (int)$r['id_evento_salon']) {
                $totalPersonas = max(0, (int)($h['ninos'] ?? 0));
                break;
            }
        }

        $r['cantidad_calc']     = $totalPersonas;
        $r['cantidad_mostrada'] = fmt_human($totalPersonas, $r['ingrediente'], 'pieza');
        $r['unidad']            = 'pieza';
    }
}
unset($r);

$det = array_values(array_filter($det, function($r){
    return !(isset($r['seccion']) && strtoupper(trim($r['seccion'])) === 'DESCORCHE');
}));

foreach ($det as &$row) {
  if (!empty($row['platillo']) && stripos($row['platillo'], 'cafe') !== false) {
    $row['seccion'] = 'BEBIDAS';
    if (empty($row['orden_seccion'])) $row['orden_seccion'] = 900;
  }
}
unset($row);

// Ajuste para calcular Limón correctamente en función de las personas del evento
foreach ($headers as $h) {
    $id_es = (int)($h['id_evento_salon'] ?? 0);
    $total_adultos = max(0, (int)($h['adultos'] ?? 0));  // Evitar negativos

    if (isset($h['descorche']) && strtoupper($h['descorche']) === 'SI') {
        $base_adultos = 100;   // Base de 100 adultos
        $kg_por_base = 2.5;    // 2.5 kg por cada 100 adultos

        // Cálculo proporcional
        $cantidad_calc = round($total_adultos * $kg_por_base / $base_adultos, 2);

        // Solo agregar si hay cantidad > 0
        if ($cantidad_calc > 0) {
            $det[] = [
                'id_evento_salon' => $id_es,
                'salon' => $h['salon'] ?? '',
                'seccion' => 'DESCORCHE',
                'orden_seccion' => 850,
                'orden_platillo' => null,
                'platillo' => 'Descorche',
                'ingrediente' => 'Limón',
                'unidad' => 'kg',
                'presentacion_descripcion' => null,
                'nota_receta' => null,
                'cantidad_calc' => $cantidad_calc,
                'cantidad_mostrada' => fmt_human($cantidad_calc, 'Limón', 'kg')
            ];
        }
    }
}

/* === Lógica de totales y mapas (aunque ya no se imprimen tablas) === */
$totStmt = $pdo->prepare("SELECT * FROM vw_evento_compra_total WHERE id_evento=? ORDER BY ingrediente");
$totStmt->execute([$id_evento]);
$tot = $totStmt->fetchAll(PDO::FETCH_ASSOC);

// Filtrar Limón 120 kg
$tot = array_values(array_filter($tot, function($r) {
    if (stripos($r['ingrediente'] ?? '', 'limon') !== false) {
        if ((float)($r['cantidad'] ?? 0) === 120) {
            return false;
        }
    }
    return true;
}));

// Mantener solo Limón de 3 kg
$tot = array_values(array_filter($tot, function($r) {
    if (stripos($r['ingrediente'] ?? '', 'limon') !== false) {
        if ((float)($r['cantidad'] ?? 0) === 3) {
            return true;
        }
    }
    return false;
}));

$ps_map = [];
foreach ($porSalon as $r) {
    $ps_map[(int)$r['id_evento_salon']][trim($r['ingrediente'])] = true;
}
$tot_map = [];
foreach ($tot as $t) {
    $tot_map[trim($t['ingrediente'])] = true;
}

// Agregar Limón si no existe
foreach ($det as $r) {
    $cant = isset($r['cantidad_calc']) ? (float)$r['cantidad_calc'] : 0.0;
    if ($cant <= 0) continue;

    $id_es = (int)$r['id_evento_salon'];
    $ing = trim($r['ingrediente'] ?? '');
    if ($ing === '') continue;

    if (!isset($ps_map[$id_es][$ing])) {
        $porSalon[] = [
            'id_evento_salon' => $id_es,
            'salon' => $r['salon'] ?? '',
            'ingrediente' => $ing,
            'unidad' => $r['unidad'] ?? '',
            'cantidad' => $cant,
            'cantidad_mostrada' => $r['cantidad_mostrada'] ?? fmt_human($cant, $ing, $r['unidad'] ?? '')
        ];
        $ps_map[$id_es][$ing] = true;
    }

    if (!isset($ps_map[$ing])) {
        $tot[] = [
            'ingrediente' => $ing,
            'unidad' => $r['unidad'] ?? '',
            'cantidad' => $cant,
            'cantidad_mostrada' => $r['cantidad_mostrada'] ?? fmt_human($cant, $ing, $r['unidad'] ?? ' ')
        ];
        $ps_map[$ing] = true;
    }
}

// Resumen de salones
$fechaEvento = $headers[0]['fecha'] ?? null;
$diaCol = $fechaEvento ? diaES($fechaEvento) : '';
$aliasStmt = $pdo->prepare("
  SELECT es.id_evento_salon, COALESCE(s.alias, s.nombre) AS alias
  FROM evento_salon es
  JOIN salon s ON s.id_salon = es.id_salon
  WHERE es.id_evento=?
  ORDER BY alias
");
$aliasStmt->execute([$id_evento]);
$aliasRows = $aliasStmt->fetchAll(PDO::FETCH_KEY_PAIR);
$salonCols = [];
foreach ($headers as $h) {
  $id_es = (int)($h['id_evento_salon'] ?? 0);
  $alias = $aliasRows[$id_es] ?? ($h['salon'] ?? '');
  $salonCols[$id_es] = trim($diaCol.' '.$alias);
}

// Cálculo de número visual de reporte
$eventos = $pdo->query("SELECT id_evento FROM evento ORDER BY fecha DESC")->fetchAll(PDO::FETCH_COLUMN);
$id_visual = 0;
$contador = 1;
foreach ($eventos as $ev) {
    if ((int)$ev === $id_evento) {
        $id_visual = $contador;
        break;
    }
    $contador++;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Reporte del evento</title>
<style>
.section-title{font-size:16px;margin-top:10px;padding:6px 8px;background-color:#e6f1ff;border-left:6px solid #1a73e8;border-radius:8px}
.section-title.buffet-infantil{border-color:#ff6b45}
.section-title.guarniciones{border-color:#3aa8ff}
.section-title.guisados{border-color:#28c76f}
.section-title.menú-infantil{border-color:#ff3ea9}
.section-title.tres-tiempos{border-color:#9c27b0}
.section-title.desayunos{border-color:#ff9800}
.section-title.bebidas{border-color:#00bcd4}
.section-title.salsas{border-color:#f44336}
.section-title.descorche{border-color:#ffc107}
table{width:100%;border-collapse:collapse;margin-top:10px}
table th,table td{border:1px solid #ccc;padding:4px 6px;text-align:left}
table th.right,table td.right{text-align:right}
.platillo-title{font-weight:bold;margin:8px 0 4px 10px}
.ingrediente-row{display:flex;justify-content:space-between;margin-left:20px;padding:2px 0}
.small{color:#666;font-size:12px}
.card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin:12px 0}
.grid{display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(280px,1fr))}
.header-card h3{margin:0 0 8px}
.btn{display:inline-block;padding:8px 12px;border-radius:8px;border:1px solid #d0d7de;background:#fff;text-decoration:none;color:#111;margin:2px}
</style>
</head>
<body>
 <h1>Reporte Cocina #<?= $id_visual ?></h1>
  <p>
    <a class="btn" href="evento_list.php">← Volver</a>
    <a class="btn" href="pedido_cliente.php?id=<?php echo h($id_evento); ?>">Pedido del cliente</a>
    <a class="btn" href="pedido_compra.php?id=<?php echo h($id_evento); ?>">Pedido de compra</a>
    <button class="btn" onclick="window.print()">Imprimir</button>
  </p>
  <div class="grid">
    <?php foreach($headers as $h): ?>
      <div class="card header-card">
        <h3><?php echo h($h['salon']); ?></h3>
        <div class="small">Fecha: <?php echo h($h['fecha']); ?></div>
        <div>Misa: <strong><?php echo h($h['misa'] ?: '—'); ?></strong></div>
        <div>Recepción: <strong><?php echo h($h['recepcion'] ?: '—'); ?></strong></div>
        <div>Inicio: <strong><?php echo h($h['inicio'] ?: '—'); ?></strong></div>
        <div>Descorche: <strong><?php echo h((isset($h['descorche']) && $h['descorche']==='SI') ? 'SI' : 'NO'); ?></strong></div>
        <div>Café: <strong><?php echo h((isset($h['cafe']) && $h['cafe']==='SI') ? 'SI' : 'NO'); ?></strong></div>
        <?php if(!empty($h['degustaciones'])): ?>
          <div>Degustaciones: <strong><?php echo h($h['degustaciones']); ?></strong></div>
        <?php endif; ?>

        <?php if(!empty($notas_evento)): ?>
          <div>Notas: <strong><?php echo h($notas_evento); ?></strong></div>
        <?php endif; ?>

        <div class="small">Comensales: <?php echo (int)$h['adultos']; ?> adultos, <?php echo (int)$h['ninos']; ?> niños</div>
      </div>
    <?php endforeach; ?>
  </div>
  
  <?php
  // Orden de secciones: incluimos DESCORCHE para que aparezca en el lugar correcto
  $orden_secciones = ['GUISADOS','GUARNICIONES', 'BUFFET INFANTIL','1 INFANTIL', '2 INFANTIL','3 TIEMPOS','DESAYUNOS','BEBIDAS','DESCORCHE','SALSAS', '2 TIEMPOS', 'PARRILLADA'];

  foreach($headers as $h){
      $id_es = (int)($h['id_evento_salon'] ?? 0);
      echo '<div class="card"><h2>'.h($h['salon'] ?? '').'</h2>';

      foreach($orden_secciones as $sec){
          $clase_css = strtolower(str_replace(' ', '-', $sec));
          if (preg_match('/^[0-9]/', $clase_css)) {
              $clase_css = 'tres-' . preg_replace('/^[0-9]+-?/', '', $clase_css);
          }
          
          // Filtrar platillos por sección
          $filtradas = array_values(array_filter($det, function($r) use ($id_es, $sec){
              return ((int)$r['id_evento_salon'] === $id_es) && (strtoupper($r['seccion']) === strtoupper($sec));
          }));

          // Si no hay platillos en esta sección, no mostrar la sección
          if (empty($filtradas)) {
              continue;  // Salta esta iteración y no muestra la sección
          }

          echo '<div class="section-title '.h($clase_css).'"><strong>'.h(ucfirst(strtolower($sec))).'</strong></div>';

          $platActual = null;
          foreach($filtradas as $r){
              if ($platActual !== $r['platillo']) {
                  $platActual = $r['platillo'];
                  $ord = (!empty($r['orden_platillo'])) ? (h($r['orden_platillo']).".- ") : "";
                  echo '<div class="platillo-title">'.($ord?h($ord):'').h($r['platillo']).'</div>';
              }
              $nota = $r['nota_receta'] ?: ($r['presentacion_descripcion'] ?? '');
              $cant = $r['cantidad_mostrada'] ?: fmt_human($r['cantidad_calc'], $r['ingrediente'], $r['unidad']);
              echo '<div class="ingrediente-row"><span>' 
                   .h($r['ingrediente']).' <span class="small">'.h($r['unidad']).'</span>' 
                   .'</span><span><strong>'.h($cant).'</strong>' 
                   .($nota ? ' <span class="small">· '.h($nota).'</span>' : '') 
                   .'</span></div>';
          }
      }
      echo '</div>';
  }
  ?>

<?php require_once __DIR__ . '/footer.php'; ?>
