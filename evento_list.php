<?php
// evento_list.php - Listado de eventos
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/header.php';

if (!isset($pdo) || !($pdo instanceof PDO)) {
    die('No hay conexión a la base de datos. Revisa db.php');
}

if (!function_exists('h')) {
    function h($s)
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }
}

// Obtener todos los eventos
$eventos = $pdo->query("
    SELECT id_evento, fecha, titulo, 
           CASE WHEN descorche = 1 THEN 'SI' ELSE 'NO' END as descorche,
           CASE WHEN cafe = 1 THEN 'SI' ELSE 'NO' END as cafe
    FROM evento 
    ORDER BY fecha DESC
")->fetchAll();
?>

<div class="page-header">
    <h1>📅 Listado de Eventos</h1>
    <div class="header-actions">
        <a class="btn secondary" href="index.php">← Volver al inicio</a>
        <a class="btn primary" href="evento_edit.php">➕ Crear nuevo evento</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-icon">📋</div>
        <h2>Eventos</h2>
    </div>

    <?php if (count($eventos) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Fecha</th>
                    <th>Título</th>
                    <th>Descorche</th>
                    <th>Café</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($eventos as $evento): ?>
                    <tr>
                        <td><?= h($evento['id_evento']) ?></td>
                        <td><?= h($evento['fecha']) ?></td>
                        <td><?= h($evento['titulo'] ?? 'Sin título') ?></td>
                        <td><?= h($evento['descorche']) ?></td>
                        <td><?= h($evento['cafe']) ?></td>
                        <td class="actions">
                            <a href="evento_edit.php?id=<?= $evento['id_evento'] ?>">✏️ Editar</a>
                            <a href="reporte_evento.php?id=<?= $evento['id_evento'] ?>">📊 Reporte</a>
                            <a href="?del=<?= $evento['id_evento'] ?>" onclick="return confirm('¿Eliminar evento?')" class="danger">🗑 Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="padding: 20px; text-align: center;">No hay eventos registrados. <a href="evento_edit.php">Crear primer evento</a></p>
    <?php endif; ?>
</div>

<style>
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        border-radius: 16px;
        margin-bottom: 30px;
        text-align: center;
    }

    .header-actions {
        margin-top: 15px;
        display: flex;
        gap: 10px;
        justify-content: center;
    }

    .btn {
        display: inline-block;
        padding: 10px 20px;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .btn.primary {
        background: white;
        color: #667eea;
    }

    .btn.secondary {
        background: rgba(255, 255, 255, 0.2);
        color: white;
    }

    .card {
        background: white;
        border-radius: 16px;
        margin: 20px 0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        padding: 16px 22px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .card-icon {
        font-size: 1.6rem;
    }

    .card h2 {
        margin: 0;
        color: #333;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th,
    td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
    }

    th {
        background: #f8f9fa;
        font-weight: 600;
    }

    .actions a {
        margin-right: 10px;
        text-decoration: none;
    }

    .actions a.danger {
        color: #dc3545;
    }
</style>

<?php require_once __DIR__ . '/footer.php'; ?>