<?php
require_once __DIR__ . '/rector_common.php';

$stats = rector_stats($pdo);
$usuarios = rector_usuarios($pdo);
$historial = rector_historial($pdo);

rector_layout_start('Panel de Control', 'dashboard');
?>
<div class="welcome-banner">
  <div class="welcome-octopus">🐙</div>
  <div>
    <div class="welcome-title">¡Bienvenido, <?= htmlspecialchars($_SESSION['nombre'] ?? 'Rector') ?>!</div>
    <div class="welcome-sub">Panel de Rectoría — Control total del sistema SIDINO</div>
  </div>
</div>

<div class="stats-grid">
<?php
$cards = [
    ['blue', 'fa-users', $stats['usuarios'], 'Usuarios totales'],
    ['green', 'fa-user-graduate', $stats['estudiantes'], 'Estudiantes'],
    ['orange', 'fa-chalkboard-user', $stats['docentes'], 'Docentes'],
    ['purple', 'fa-door-open', $stats['cursos'], 'Cursos'],
    ['yellow', 'fa-book', $stats['materias'], 'Materias'],
    ['red', 'fa-calendar', $stats['periodos'], 'Periodos académicos'],
];
foreach ($cards as [$clase, $icono, $valor, $etiqueta]):
?>
  <div class="stat-card">
    <div class="stat-icon <?= $clase ?>"><i class="fa-solid <?= $icono ?>"></i></div>
    <div class="stat-body"><div class="stat-value"><?= $valor ?></div><div class="stat-label"><?= $etiqueta ?></div></div>
  </div>
<?php endforeach; ?>
</div>

<div class="content-grid">
  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-users"></i> Usuarios del sistema</div>
      <a href="usuarios.php" class="card-action">Ver todos</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th></tr></thead>
        <tbody>
        <?php foreach (array_slice($usuarios, 0, 8) as $usuario): ?>
          <tr>
            <td><?= htmlspecialchars($usuario['nombre']) ?></td>
            <td><?= htmlspecialchars($usuario['correo']) ?></td>
            <td><span class="badge <?= rector_badge((int)$usuario['id_rol']) ?>"><?= htmlspecialchars($usuario['nombre_rol']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title"><i class="fa-solid fa-clock-rotate-left"></i> Actividad reciente</div>
      <a href="historial.php" class="card-action">Ver todo</a>
    </div>
    <?php if (empty($historial)): ?>
      <div class="empty-state"><i class="fa-solid fa-inbox"></i><p>Sin actividad registrada aún</p></div>
    <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Usuario</th><th>Acción</th><th>Tabla</th><th>Fecha</th></tr></thead>
        <tbody>
        <?php foreach ($historial as $accion): ?>
          <tr>
            <td><?= htmlspecialchars($accion['nombre']) ?></td>
            <td><?= htmlspecialchars($accion['accion']) ?></td>
            <td><span class="badge blue"><?= htmlspecialchars($accion['tabla_afectada']) ?></span></td>
            <td><?= date('d/m H:i', strtotime($accion['fecha'])) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php rector_layout_end(); ?>
