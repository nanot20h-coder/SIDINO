<?php
require_once 'auth.php';
require_auth([2]);

$pdo = getDB();

$total_obs  = $pdo->query("SELECT COUNT(*) FROM observador")->fetchColumn();
$total_cit  = $pdo->query("SELECT COUNT(*) FROM citacion")->fetchColumn();
$total_est  = $pdo->query("SELECT COUNT(*) FROM usuario WHERE id_rol=5")->fetchColumn();
$total_doc  = $pdo->query("SELECT COUNT(*) FROM usuario WHERE id_rol=4")->fetchColumn();

$observaciones = $pdo->query("
    SELECT o.descripcion, o.fecha, u.nombre AS estudiante, 
           d.nombre AS docente, c.nombre AS curso
    FROM observador o
    JOIN usuario u  ON o.id_estudiante = u.id_usuario
    JOIN asignacion_academica aa ON o.id_asignacion = aa.id_asignacion
    JOIN usuario d  ON aa.id_docente = d.id_usuario
    JOIN curso c    ON aa.id_curso   = c.id_curso
    ORDER BY o.fecha DESC LIMIT 15
")->fetchAll();

$citaciones = $pdo->query("
    SELECT ci.motivo, ci.fecha, u.nombre AS estudiante
    FROM citacion ci JOIN usuario u ON ci.id_estudiante = u.id_usuario
    ORDER BY ci.fecha DESC LIMIT 15
")->fetchAll();

$docentes = $pdo->query("
    SELECT u.nombre, u.correo,
           COUNT(aa.id_asignacion) AS clases
    FROM usuario u
    LEFT JOIN asignacion_academica aa ON u.id_usuario = aa.id_docente
    WHERE u.id_rol = 4
    GROUP BY u.id_usuario
    ORDER BY u.nombre
")->fetchAll();

$color = '#fb923c';
layout_head('Coordinador — Dashboard');
echo "<div class='app'>";
echo sidebar_html('dashboard', [
    ['id'=>'dashboard',    'href'=>'coordinador.php',                'icon'=>'fa-gauge',           'label'=>'Dashboard'],
    ['id'=>'observador',   'href'=>'coordinador.php?m=observador',   'icon'=>'fa-book-open',       'label'=>'Observador'],
    ['id'=>'citaciones',   'href'=>'coordinador.php?m=citaciones',   'icon'=>'fa-calendar-check',  'label'=>'Citaciones'],
    ['id'=>'docentes',     'href'=>'coordinador.php?m=docentes',     'icon'=>'fa-chalkboard-user', 'label'=>'Docentes'],
    ['id'=>'reportes',     'href'=>'coordinador.php?m=reportes',     'icon'=>'fa-chart-bar',       'label'=>'Reportes'],
]);
echo "<div class='main-content' id='mainContent'>";
echo topbar_html('Coordinación — Panel de Supervisión', $color);
echo "<div class='module-container'>";

$modulo = $_GET['m'] ?? 'dashboard';

if ($modulo === 'dashboard'):
?>
<div class="welcome-banner">
  <div class="welcome-octopus">🐙</div>
  <div>
    <div class="welcome-title">¡Hola, <?= htmlspecialchars($_SESSION['nombre']) ?>!</div>
    <div class="welcome-sub">Panel de Coordinación — Supervisión académica y disciplinaria</div>
  </div>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon green"><i class="fa-solid fa-user-graduate"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $total_est ?></div><div class="stat-label">Estudiantes</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa-solid fa-chalkboard-user"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $total_doc ?></div><div class="stat-label">Docentes</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fa-solid fa-book-open"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $total_obs ?></div><div class="stat-label">Observaciones</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red"><i class="fa-solid fa-calendar-check"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $total_cit ?></div><div class="stat-label">Citaciones</div></div>
  </div>
</div>

<div class="content-grid">
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-book-open"></i> Observaciones recientes</div>
      <a href="coordinador.php?m=observador" class="card-action">Ver todas</a>
    </div>
    <?php if(empty($observaciones)): ?>
      <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>Sin observaciones aún</p></div>
    <?php else: ?>
    <?php foreach(array_slice($observaciones, 0, 5) as $o): ?>
      <div class="list-item">
        <div class="stat-icon orange"><i class="fa-solid fa-pen"></i></div>
        <div class="list-body">
          <div class="list-title"><?= htmlspecialchars($o['estudiante']) ?> — <?= htmlspecialchars($o['curso']) ?></div>
          <div class="list-sub"><?= htmlspecialchars(substr($o['descripcion'], 0, 80)) ?>...</div>
        </div>
        <div class="list-meta"><?= date('d/m/Y', strtotime($o['fecha'])) ?></div>
      </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-calendar-check"></i> Citaciones recientes</div>
      <a href="coordinador.php?m=citaciones" class="card-action">Ver todas</a>
    </div>
    <?php if(empty($citaciones)): ?>
      <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>Sin citaciones aún</p></div>
    <?php else: ?>
    <?php foreach(array_slice($citaciones, 0, 5) as $c): ?>
      <div class="list-item">
        <div class="stat-icon red"><i class="fa-solid fa-user-clock"></i></div>
        <div class="list-body">
          <div class="list-title"><?= htmlspecialchars($c['estudiante']) ?></div>
          <div class="list-sub"><?= htmlspecialchars($c['motivo']) ?></div>
        </div>
        <div class="list-meta"><?= date('d/m/Y', strtotime($c['fecha'])) ?></div>
      </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php elseif($modulo === 'observador'): ?>
<div class="section-title"><i class="fa-solid fa-book-open"></i> Observador Estudiantil</div>
<div class="card">
  <div class="table-wrap">
    <?php if(empty($observaciones)): ?>
      <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No hay observaciones registradas</p></div>
    <?php else: ?>
    <table>
      <thead><tr><th>Estudiante</th><th>Curso</th><th>Docente</th><th>Descripción</th><th>Fecha</th></tr></thead>
      <tbody>
      <?php foreach($observaciones as $o): ?>
        <tr>
          <td><?= htmlspecialchars($o['estudiante']) ?></td>
          <td><span class="badge blue"><?= htmlspecialchars($o['curso']) ?></span></td>
          <td><?= htmlspecialchars($o['docente']) ?></td>
          <td><?= htmlspecialchars(substr($o['descripcion'], 0, 80)) ?>...</td>
          <td><?= date('d/m/Y', strtotime($o['fecha'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php elseif($modulo === 'citaciones'): ?>
<div class="section-title"><i class="fa-solid fa-calendar-check"></i> Citaciones</div>
<div class="card">
  <div class="table-wrap">
    <?php if(empty($citaciones)): ?>
      <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No hay citaciones registradas</p></div>
    <?php else: ?>
    <table>
      <thead><tr><th>Estudiante</th><th>Motivo</th><th>Fecha</th></tr></thead>
      <tbody>
      <?php foreach($citaciones as $c): ?>
        <tr>
          <td><?= htmlspecialchars($c['estudiante']) ?></td>
          <td><?= htmlspecialchars($c['motivo']) ?></td>
          <td><?= date('d/m/Y', strtotime($c['fecha'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<?php elseif($modulo === 'docentes'): ?>
<div class="section-title"><i class="fa-solid fa-chalkboard-user"></i> Docentes</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Nombre</th><th>Correo</th><th>Clases asignadas</th></tr></thead>
      <tbody>
      <?php foreach($docentes as $d): ?>
        <tr>
          <td><?= htmlspecialchars($d['nombre']) ?></td>
          <td><?= htmlspecialchars($d['correo']) ?></td>
          <td><span class="badge <?= $d['clases'] > 0 ? 'green' : 'red' ?>"><?= $d['clases'] ?> clases</span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif($modulo === 'reportes'): ?>
<div class="section-title"><i class="fa-solid fa-chart-bar"></i> Reportes de Coordinación</div>
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fa-solid fa-book-open"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $total_obs ?></div><div class="stat-label">Total observaciones</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red"><i class="fa-solid fa-calendar-check"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $total_cit ?></div><div class="stat-label">Total citaciones</div></div>
  </div>
</div>
<div class="alert info"><i class="fa-solid fa-circle-info"></i> Los reportes detallados estarán disponibles cuando se registren más datos en el sistema.</div>
<?php endif; ?>

<?php
echo "</div></div></div>";
layout_close();
?>
