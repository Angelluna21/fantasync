<?php 
require_once __DIR__ . '/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) { die('No hay conexión a la base de datos.'); }

$id_es     = isset($_GET['id_es']) ? (int)$_GET['id_es'] : 0;
$id_evento = isset($_GET['id'])    ? (int)$_GET['id']    : 0;

// --------------------------------------------------------
// FUNCIONES
// --------------------------------------------------------

function fecha_larga_es($ymd){
    $dias  = ['DOMINGO','LUNES','MARTES','MIÉRCOLES','JUEVES','VIERNES','SÁBADO'];
    $meses = ['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO','JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
    $t = strtotime($ymd);
    return $dias[(int)date('w',$t)].' '.date('d',$t).' '.$meses[(int)date('n',$t)-1];
}

function norm($s){
    if ($s === null) return '';
    $s = trim($s);
    $s = mb_strtolower($s, 'UTF-8');
    $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
    $s = preg_replace('/[^a-z0-9\s]/i', '', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

// AGRUPA CATEGORÍAS VISIBLES EN EL MENÚ DEL CLIENTE
function grupo_visible($categoria){
    $cat = strtoupper(trim($categoria));
    switch($cat){
        case 'SALSAS':           return 'SALSAS';
        case 'GUARNICIONES':     return 'GUARNICIONES';
        case 'BUFFET INFANTIL':  return 'BUFFET INFANTIL';

        // Menú infantil (con y sin acento)
        case 'MENÚ INFANTIL':
        case 'MENU INFANTIL':    return 'MENÚ INFANTIL';

        // Infantiles numerados
        case '1 INFANTIL':       return '1 INFANTIL';
        case '2 INFANTIL':       return '2 INFANTIL';
        case '3 INFANTIL':       return '3 INFANTIL';

        case 'DESAYUNOS':        return 'DESAYUNOS';
        case 'BEBIDAS':          return 'BEBIDAS';
        case '3 TIEMPOS':        return '3 TIEMPOS';

        default:                 return 'GUISADOS';
    }
}

function categoria_platillo_cafe($pdo){
    $q = $pdo->prepare("
        SELECT COALESCE(cp.nombre,'')
        FROM platillo p
        LEFT JOIN categoria_platillo cp ON cp.id_categoria = p.id_categoria
        WHERE LOWER(p.nombre) LIKE '%cafe%'
        LIMIT 1
    ");
    $q->execute();
    $cat = $q->fetchColumn();
    return $cat ?: null;
}


// --------------------------------------------------------
// RENDER DE CADA HOJA
// --------------------------------------------------------

function render_hoja($pdo, $id_es){

    // Obtener categoría real del café
    $cat_cafe = categoria_platillo_cafe($pdo);

    // Datos principales del salón
    $q = $pdo->prepare("
        SELECT
          es.id_evento_salon,
          es.id_evento,
          COALESCE(s.alias, s.nombre) AS salon,
          e.fecha,
          e.titulo,
          es.adultos,
          es.ninos,
          COALESCE(es.recepcion, e.recepcion) AS recepcion,
          COALESCE(es.inicio, e.inicio) AS inicio,
          COALESCE(es.degustaciones, e.degustaciones) AS degustaciones,
          COALESCE(es.cafe, e.cafe) AS cafe,
          COALESCE(es.descorche, e.descorche) AS descorche
        FROM evento_salon es
        JOIN salon s ON s.id_salon=es.id_salon
        JOIN evento e ON e.id_evento=es.id_evento
        WHERE es.id_evento_salon=?
    ");
    $q->execute([$id_es]);
    $info = $q->fetch(PDO::FETCH_ASSOC);

    if(!$info){
        echo "<div style='padding:12px'>No existe el salón.</div>";
        return;
    }

    // Obtener platillos
    $rows = $pdo->prepare("
        SELECT esp.orden, p.nombre AS platillo, COALESCE(cp.nombre,'') AS categoria
        FROM evento_salon_platillo esp
        JOIN platillo p ON p.id_platillo = esp.id_platillo
        LEFT JOIN categoria_platillo cp ON cp.id_categoria = p.id_categoria
        WHERE esp.id_evento_salon=?
        ORDER BY COALESCE(esp.orden,9999), p.nombre
    ");
    $rows->execute([$id_es]);
    $items = $rows->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar por categoría visible
    $gr = [];
    foreach($items as $it){
        $sec = grupo_visible($it['categoria']);
        if (!isset($gr[$sec])) $gr[$sec] = [];
        $gr[$sec][] = $it['platillo'];
    }

    // FORZAR CAFÉ SI EL EVENTO LO TIENE ACTIVADO
    if ($info['cafe'] == 1) {

        $yaExiste = false;
        foreach ($gr as $lista) {
            foreach ($lista as $p) {
                if (stripos($p, 'cafe') !== false) {
                    $yaExiste = true;
                    break 2;
                }
            }
        }

        if (!$yaExiste) {
            $secCafe = $cat_cafe ? grupo_visible($cat_cafe) : "BEBIDAS";
            if (!isset($gr[$secCafe])) $gr[$secCafe] = [];
            $gr[$secCafe][] = "Café";
        }
    }

    // DESCORCHE
    if ($info['descorche'] == 1) {

        $secD = "DESCORCHE";
        if (!isset($gr[$secD])) $gr[$secD] = [];

        $adultos = max(0, (int)$info['adultos']);

        if ($adultos > 0) {
            $limon_qty  = round($adultos * 0.025, 3);
            $limon_show = number_format($limon_qty, 2, '.', '') . " kg";
            $texto = "Descorche (Limón: $limon_show)";
        } else {
            $texto = "Descorche (Limón)";
        }

        $duplicado = false;
        foreach ($gr[$secD] as $p) {
            if (strpos(norm($p), 'descorche') !== false) {
                $duplicado = true;
                break;
            }
        }

        if (!$duplicado) {
            $gr[$secD][] = $texto;
        }
    }

?>
<section class="sheet">
    <div class="banner">
        <div class="fecha"><?= fecha_larga_es($info['fecha']) ?></div>
        <div class="salon"><?= htmlspecialchars($info['salon']) ?></div>
        <div class="horas">
            <div><span>Recepción:</span> <?= htmlspecialchars($info['recepcion']?:'—') ?></div>
            <div><span>Inicio:</span> <?= htmlspecialchars($info['inicio']?:'—') ?></div>
        </div>
        <div class="flags">
            <div><span>Café</span><b><?= $info['cafe']?'SI':'NO' ?></b></div>
            <div><span>Degustación</span><b><?= $info['degustaciones']?'SI':'NO' ?></b></div>
            <div><span>Descorche</span><b><?= $info['descorche']?'SI':'NO' ?></b></div>
        </div>
        <div class="personas">
            <div class="box a"><div>Adultos</div><b><?= (int)$info['adultos'] ?></b></div>
            <div class="box n"><div>Niños</div><b><?= (int)$info['ninos'] ?></b></div>
        </div>
    </div>

    <?php foreach($gr as $sec => $lista): ?>
        <?php if (!empty($lista)): ?>
            <h2><?= htmlspecialchars($sec) ?>:</h2>
            <ol class="lista">
                <?php foreach($lista as $p): ?>
                    <li><?= htmlspecialchars($p) ?></li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    <?php endforeach; ?>
</section>
<?php
} // fin render_hoja



// --------------------------------------------------------
// OBTENER LISTA DE SALONES
// --------------------------------------------------------

if($id_evento>0){
    $q=$pdo->prepare("SELECT id_evento_salon FROM evento_salon WHERE id_evento=? ORDER BY id_evento_salon");
    $q->execute([$id_evento]);
    $ids = $q->fetchAll(PDO::FETCH_COLUMN);
    if(!$ids){ die('El evento no tiene salones.'); }
} else {
    $ids = [$id_es];
}

?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Menú del evento</title>
<?php
// Obtener título y fecha del evento para el pie de página
if ($id_evento > 0) {
    $ev = $pdo->prepare("SELECT titulo, fecha FROM evento WHERE id_evento = ? LIMIT 1");
    $ev->execute([$id_evento]);
    $eventoFooter = $ev->fetch(PDO::FETCH_ASSOC);
} else {
    $ev = $pdo->prepare("
        SELECT e.titulo, e.fecha
        FROM evento_salon es
        JOIN evento e ON e.id_evento = es.id_evento
        WHERE es.id_evento_salon = ?
        LIMIT 1
    ");
    $ev->execute([$id_es]);
    $eventoFooter = $ev->fetch(PDO::FETCH_ASSOC);
}
?>

<style>
body {
    counter-reset: evento;
}

/* ================================================
   PIE DE PÁGINA CORREGIDO (FUNCIONA HOJA POR HOJA)
================================================ */

@page {
    size: A4 portrait;
    margin: 8mm;

    @bottom-center {
        content: "Evento: <?= addslashes($eventoFooter['titulo']) ?> — Fecha: <?= addslashes(fecha_larga_es($eventoFooter['fecha'])) ?> — Hoja " counter(page);
        font-size: 18px;
        font-weight: bold;
        border-top: 3px solid #000;
        padding-top: 6px;
    }
}

/* el footer HTML ya no se usa */
body {
    counter-reset: evento;
}

.sheet {
    counter-increment: evento;
}
footer.print-footer::after {
    content: "Evento: <?= addslashes($eventoFooter['titulo']) ?> — Fecha: <?= addslashes(fecha_larga_es($eventoFooter['fecha'])) ?> — Evento # " counter(evento);
}



/* =================================================
   TU CSS ORIGINAL (NO MODIFICADO)
================================================= */

body{font-family:Arial,Helvetica,sans-serif;margin:0;background:#f8fafc;color:#111}
header{padding:10px 16px;border-bottom:1px solid #d1d5db;display:flex;gap:12px;align-items:center;background:#fff}
h1{margin:0;font-size:20px}
.btn{padding:6px 10px;border:1px solid #999;border-radius:6px;background:#fff;text-decoration:none;color:#111;margin-left:auto}
.wrap{padding:12px;display:grid;gap:16px}

.sheet{background:#fff;border:1px solid #d1d5db;border-radius:12px;padding:16px;page-break-inside:avoid}
.banner{display:grid;grid-template-columns:1.6fr 1fr 1fr 1.4fr 0.9fr;gap:10px;align-items:center}
.banner .fecha{font-size:22px;font-weight:900;color:#111;text-transform:uppercase}
.banner .salon{background:#fde68a;border-radius:10px;padding:8px 10px;font-weight:900;text-align:center}
.banner .horas div{margin:2px 0}
.banner .horas span{font-weight:700}
.flags{display:flex;gap:10px;justify-content:flex-start}
.flags > div{border:1px solid #93c5fd;background:#e0f2fe;border-radius:8px;padding:6px 8px;text-align:center}
.flags span{display:block;font-size:12px}
.flags b{display:block;margin-top:2px}
.personas{display:flex;gap:10px;justify-content:flex-end}
.box{border:1px solid #d1d5db;border-radius:10px;padding:6px 10px;text-align:center;min-width:80px}
.box.a{background:#ffe4e6}
.box.n{background:#fff7cc}
.box b{font-size:20px}

h2{
    margin:18px 0 10px;
    text-transform:uppercase;
    font-size: 26px;
    font-weight: 900;
    letter-spacing: 0.8px;
}

ol.lista{
    margin:0 0 10px 24px;
    padding:0;
}

ol.lista li{
    margin:10px 0;
    font-size: 23px;
    font-weight: 700;
    line-height: 1.35;
}

@media print {
    header,.btn{display:none !important;}
    *{-webkit-print-color-adjust:exact !important; print-color-adjust:exact !important;}
    @page{size:A4 portrait;margin:10mm;}
}
</style>

</head>
<body>
<header>
    <h1>Menú del evento</h1>
    <?php if($id_evento>0): ?>
    <a class="btn" href="reporte_evento.php?id=<?= $id_evento ?>">← Orden de Producción</a>
    <?php endif; ?>
    <button class="btn" onclick="window.print()">Imprimir</button>
</header>
<div class="wrap">
    <?php foreach($ids as $es) render_hoja($pdo, (int)$es); ?>
</div>
</body>
<footer class="print-footer"></footer>

</html>
