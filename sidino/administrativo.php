<?php
require_once 'auth.php';
require_auth([3]);

$pdo = getDB();

$total_est  = $pdo->query("SELECT COUNT(*) FROM usuario WHERE id_rol=5")->fetchColumn();
$total_bol  = $pdo->query("SELECT COUNT(*) FROM boletin")->fetchColumn();
$total_mat  = $pdo->query("SELECT COUNT(*) FROM matricula")->fetchColumn();
$total_hist = $pdo->query("SELECT COUNT(*) FROM historial_accion")->fetchColumn();

$estudiantes = $pdo->query("
    SELECT u.id_usuario, u.nombre, u.correo,
           COUNT(m.id_matricula) AS materias_matriculadas
    FROM usuario u
    LEFT JOIN matricula m ON u.id_usuario = m.id_estudiante
    WHERE u.id_rol = 5
    GROUP BY u.id_usuario
    ORDER BY u.nombre
")->fetchAll();

$boletines = $pdo->query("
    SELECT b.id_boletin, b.fecha, u.nombre AS estudiante, p.nombre AS periodo
    FROM boletin b
    JOIN usuario u ON b.id_estudiante = u.id_usuario
    JOIN periodo_academico p ON b.id_periodo = p.id_periodo
    ORDER BY b.fecha DESC LIMIT 20
")->fetchAll();

$historial = $pdo->query("
    SELECT h.accion, h.tabla_afectada, h.fecha, u.nombre
    FROM historial_accion h JOIN usuario u ON h.id_usuario = u.id_usuario
    ORDER BY h.fecha DESC LIMIT 15
")->fetchAll();

$color = '#fbbf24';
layout_head('Administrativo — Dashboard');
echo "<div class='app'>";
echo sidebar_html('dashboard', [
    ['id'=>'dashboard',   'href'=>'administrativo.php',              'icon'=>'fa-gauge',       'label'=>'Dashboard'],
    ['id'=>'estudiantes', 'href'=>'administrativo.php?m=estudiantes','icon'=>'fa-user-graduate','label'=>'Estudiantes'],
    ['id'=>'boletines',   'href'=>'administrativo.php?m=boletines',  'icon'=>'fa-file-invoice', 'label'=>'Boletines'],
    ['id'=>'historial',   'href'=>'administrativo.php?m=historial',  'icon'=>'fa-clock-rotate-left','label'=>'Historial'],
]);
echo "<div class='main-content' id='mainContent'>";
echo topbar_html('Administrativo — Gestión', $color);
echo "<div class='module-container'>";

$modulo = $_GET['m'] ?? 'dashboard';

if ($modulo === 'dashboard'):
?>
<div class="welcome-banner">
  <div class="welcome-octopus">🐙</div>
  <div>
    <div class="welcome-title">¡Bienvenido, <?= htmlspecialchars($_SESSION['nombre']) ?>!</div>
    <div class="welcome-sub">Panel Administrativo — Gestión de datos y documentos</div>
  </div>
</div>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon green"><i class="fa-solid fa-user-graduate"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $total_est ?></div><div class="stat-label">Estudiantes registrados</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa-solid fa-file-invoice"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $total_bol ?></div><div class="stat-label">Boletines generados</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fa-solid fa-id-card"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $total_mat ?></div><div class="stat-label">Matrículas</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow"><i class="fa-solid fa-clock-rotate-left"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $total_hist ?></div><div class="stat-label">Acciones registradas</div></div>
  </div>
</div>

<div class="content-grid">
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-user-graduate"></i> Estudiantes</div>
      <a href="administrativo.php?m=estudiantes" class="card-action">Ver todos</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Nombre</th><th>Correo</th><th>Materias</th></tr></thead>
        <tbody>
        <?php foreach(array_slice($estudiantes, 0, 6) as $e): ?>
          <tr>
            <td><?= htmlspecialchars($e['nombre']) ?></td>
            <td><?= htmlspecialchars($e['correo']) ?></td>
            <td><span class="badge <?= $e['materias_matriculadas'] > 0 ? 'green' : 'red' ?>"><?= $e['materias_matriculadas'] ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-file-invoice"></i> Boletines</div>
      <a href="administrativo.php?m=boletines" class="card-action">Ver todos</a>
    </div>
    <?php if(empty($boletines)): ?>
      <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>Sin boletines generados aún</p></div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Estudiante</th><th>Periodo</th><th>Fecha</th></tr></thead>
        <tbody>
        <?php foreach(array_slice($boletines, 0, 6) as $b): ?>
          <tr>
            <td><?= htmlspecialchars($b['estudiante']) ?></td>
            <td><span class="badge blue"><?= htmlspecialchars($b['periodo']) ?></span></td>
            <td><?= date('d/m/Y', strtotime($b['fecha'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php elseif($modulo === 'estudiantes'): ?>
<div class="section-title"><i class="fa-solid fa-user-graduate"></i> Listado de Estudiantes</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Nombre</th><th>Correo</th><th>Materias matriculadas</th></tr></thead>
      <tbody>
      <?php foreach($estudiantes as $e): ?>
        <tr>
          <td><?= $e['id_usuario'] ?></td>
          <td><?= htmlspecialchars($e['nombre']) ?></td>
          <td><?= htmlspecialchars($e['correo']) ?></td>
          <td><span class="badge <?= $e['materias_matriculadas'] > 0 ? 'green' : 'red' ?>"><?= $e['materias_matriculadas'] ?> materias</span></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php elseif($modulo === 'boletines'): ?>
<div class="section-title"><i class="fa-solid fa-file-invoice"></i> Boletines Académicos</div>
<div class="card">
  <?php if(empty($boletines)): ?>
    <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>No hay boletines generados aún</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Estudiante</th><th>Periodo</th><th>Fecha de emisión</th></tr></thead>
      <tbody>
      <?php foreach($boletines as $b): ?>
        <tr>
          <td><?= $b['id_boletin'] ?></td>
          <td><?= htmlspecialchars($b['estudiante']) ?></td>
          <td><span class="badge blue"><?= htmlspecialchars($b['periodo']) ?></span></td>
          <td><?= date('d/m/Y', strtotime($b['fecha'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php elseif($modulo === 'historial'): ?>
<div class="section-title"><i class="fa-solid fa-clock-rotate-left"></i> Historial del Sistema</div>
<div class="card">
  <?php if(empty($historial)): ?>
    <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>Sin actividad registrada</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Usuario</th><th>Acción</th><th>Tabla</th><th>Fecha</th></tr></thead>
      <tbody>
      <?php foreach($historial as $h): ?>
        <tr>
          <td><?= htmlspecialchars($h['nombre']) ?></td>
          <td><?= htmlspecialchars($h['accion']) ?></td>
          <td><span class="badge blue"><?= htmlspecialchars($h['tabla_afectada']) ?></span></td>
          <td><?= date('d/m/Y H:i', strtotime($h['fecha'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php
echo "</div></div></div>";
layout_close();
?>
