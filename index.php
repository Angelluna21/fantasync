<?php
require_once __DIR__ . '/db.php';
if (!isset($pdo) || !($pdo instanceof PDO)) { die('No hay conexión a la base de datos. Revisa db.php'); }
require_once __DIR__ . '/header.php';
?>

<div class="hero-section">
  <h1>🍽️ Cocina Fantasy</h1>
  <p class="hero-subtitle">Sistema de gestión de eventos y menús</p>
  <p class="hero-description">Administra salones, eventos, platillos e ingredientes para crear experiencias culinarias únicas.</p>
</div>

<div class="dashboard-grid">
  <div class="dashboard-card primary">
    <div class="card-icon">🏢</div>
    <h3>Salones</h3>
    <p>Gestiona los espacios disponibles para eventos</p>
    <a class="card-btn primary" href="salon_list.php">Ver salones</a>
  </div>

  <div class="dashboard-card secondary">
    <div class="card-icon">📅</div>
    <h3>Eventos</h3>
    <p>Planifica y organiza eventos especiales</p>
    <a class="card-btn secondary" href="evento_list.php">Ver eventos</a>
  </div>

  <div class="dashboard-card accent">
    <div class="card-icon">🍽️</div>
    <h3>Platillos</h3>
    <p>Administra el catálogo de platillos disponibles</p>
    <a class="card-btn accent" href="platillos.php">Ver platillos</a>
  </div>

  <div class="dashboard-card info">
    <div class="card-icon">📋</div>
    <h3>Categorías</h3>
    <p>Organiza platillos por categorías</p>
    <a class="card-btn info" href="categoria_list.php">Ver categorías</a>
  </div>

  <div class="dashboard-card warning">
    <div class="card-icon">🥘</div>
    <h3>Ingredientes</h3>
    <p>Controla el inventario de ingredientes</p>
    <a class="card-btn warning" href="ingredientes.php">Ver ingredientes</a>
  </div>

  <div class="dashboard-card success">
    <div class="card-icon">📖</div>
    <h3>Recetas</h3>
    <p>Gestiona las recetas y sus ingredientes</p>
    <a class="card-btn success" href="recetas.php">Ver recetas</a>
  </div>
</div>

<style>
.hero-section {
  text-align: center;
  padding: 40px 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 16px;
  margin-bottom: 30px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.hero-section h1 {
  font-size: 3rem;
  margin: 0 0 10px 0;
  font-weight: 700;
  text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.hero-subtitle {
  font-size: 1.3rem;
  margin: 0 0 15px 0;
  opacity: 0.9;
  font-weight: 300;
}

.hero-description {
  font-size: 1.1rem;
  margin: 0;
  opacity: 0.8;
  max-width: 600px;
  margin: 0 auto;
  line-height: 1.6;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
  margin-top: 30px;
}

.dashboard-card {
  background: white;
  border-radius: 16px;
  padding: 30px;
  text-align: center;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  transition: all 0.3s ease;
  border: 1px solid #f0f0f0;
  position: relative;
  overflow: hidden;
}

.dashboard-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, #667eea, #764ba2);
}

.dashboard-card.primary::before { background: linear-gradient(90deg, #667eea, #764ba2); }
.dashboard-card.secondary::before { background: linear-gradient(90deg, #f093fb, #f5576c); }
.dashboard-card.accent::before { background: linear-gradient(90deg, #4facfe, #00f2fe); }
.dashboard-card.info::before { background: linear-gradient(90deg, #43e97b, #38f9d7); }
.dashboard-card.warning::before { background: linear-gradient(90deg, #fa709a, #fee140); }
.dashboard-card.success::before { background: linear-gradient(90deg, #a8edea, #fed6e3); }

.dashboard-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.card-icon {
  font-size: 3rem;
  margin-bottom: 20px;
  display: block;
}

.dashboard-card h3 {
  font-size: 1.5rem;
  margin: 0 0 15px 0;
  color: #333;
  font-weight: 600;
}

.dashboard-card p {
  color: #666;
  margin: 0 0 25px 0;
  line-height: 1.5;
  font-size: 1rem;
}

.card-btn {
  display: inline-block;
  padding: 12px 24px;
  border-radius: 25px;
  text-decoration: none;
  font-weight: 600;
  transition: all 0.3s ease;
  border: 2px solid transparent;
  font-size: 1rem;
}

.card-btn.primary {
  background: linear-gradient(135deg, #667eea, #764ba2);
  color: white;
}

.card-btn.secondary {
  background: linear-gradient(135deg, #f093fb, #f5576c);
  color: white;
}

.card-btn.accent {
  background: linear-gradient(135deg, #4facfe, #00f2fe);
  color: white;
}

.card-btn.info {
  background: linear-gradient(135deg, #43e97b, #38f9d7);
  color: white;
}

.card-btn.warning {
  background: linear-gradient(135deg, #fa709a, #fee140);
  color: white;
}

.card-btn.success {
  background: linear-gradient(135deg, #a8edea, #fed6e3);
  color: #333;
}

.card-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
  .hero-section h1 {
    font-size: 2.2rem;
  }
  
  .dashboard-grid {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .dashboard-card {
    padding: 20px;
  }
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
