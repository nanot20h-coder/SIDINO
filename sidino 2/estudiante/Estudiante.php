<?php
require_once __DIR__ . '/estudiante_common.php';

$pdo = getDB();
$id_estudiante = (int)($_SESSION['user_id'] ?? 0);

$clases = $pdo->prepare("
    SELECT aa.id_asignacion, m.nombre AS materia, c.nombre AS curso,
           s.nombre AS salon, h.dia, h.hora_inicio, h.hora_fin,
           u.nombre AS docente, p.nombre AS periodo
    FROM matricula mat
    JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
    JOIN materia m ON aa.id_materia = m.id_materia
    JOIN curso c ON aa.id_curso = c.id_curso
    JOIN salon s ON aa.id_salon = s.id_salon
    JOIN horario h ON aa.id_horario = h.id_horario
    JOIN usuario u ON aa.id_docente = u.id_usuario
    JOIN periodo_academico p ON aa.id_periodo = p.id_periodo
    WHERE mat.id_estudiante = :id_estudiante
    ORDER BY FIELD(h.dia, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'), h.hora_inicio
");
$clases->execute([':id_estudiante' => $id_estudiante]);
$asignaturas = $clases->fetchAll();

$actividades = $pdo->prepare("
    SELECT co.id_contenido, co.titulo, co.descripcion, co.fecha_entrega,
           m.nombre AS materia, c.nombre AS curso, u.nombre AS docente
    FROM contenido co
    JOIN asignacion_academica aa ON co.id_asignacion = aa.id_asignacion
    JOIN matricula mat ON mat.id_asignacion = aa.id_asignacion
    JOIN materia m ON aa.id_materia = m.id_materia
    JOIN curso c ON aa.id_curso = c.id_curso
    JOIN usuario u ON aa.id_docente = u.id_usuario
    WHERE mat.id_estudiante = :id_estudiante AND co.tipo = 'actividad'
    ORDER BY co.fecha_entrega IS NULL, co.fecha_entrega DESC, co.id_contenido DESC
");
$actividades->execute([':id_estudiante' => $id_estudiante]);
$actividades = $actividades->fetchAll();

estudiante_layout_start('Dashboard', 'dashboard');
?>
<div class='welcome-banner'>
  <div class='welcome-octopus'>🐙</div>
  <div>
    <div class='welcome-title'>¡Hola, <?= htmlspecialchars($_SESSION['nombre']) ?>!</div>
    <div class='welcome-sub'>Panel Estudiante — tus clases y actividades</div>
  </div>
</div>

<div class='stats-grid'>
  <div class='stat-card'>
    <div class='stat-icon blue'><i class='fa-solid fa-calendar-days'></i></div>
    <div class='stat-body'><div class='stat-value'><?= count($asignaturas) ?></div><div class='stat-label'>Clases</div></div>
  </div>
  <div class='stat-card'>
    <div class='stat-icon green'><i class='fa-solid fa-list-check'></i></div>
    <div class='stat-body'><div class='stat-value'><?= count($actividades) ?></div><div class='stat-label'>Actividades</div></div>
  </div>
</div>

<div class='content-grid'>
  <div class='card'>
    <div class='card-header'>
      <div class='card-title'><i class='fa-solid fa-calendar-days'></i> Mis clases</div>
      <a href='mis_clases.php' class='card-action'>Ver horario</a>
    </div>
    <?php if (empty($asignaturas)): ?>
      <div class='empty-state'><i class='fa-solid fa-calendar-xmark'></i><p>No tienes clases matriculadas aún.</p></div>
    <?php else: ?>
      <div class='table-wrap'>
        <table>
          <thead><tr><th>Materia</th><th>Curso</th><th>Docente</th><th>Día</th><th>Hora</th><th>Salón</th></tr></thead>
          <tbody>
            <?php foreach ($asignaturas as $clase): ?>
              <tr>
                <td><?= htmlspecialchars($clase['materia']) ?></td>
                <td><span class='badge blue'><?= htmlspecialchars($clase['curso']) ?></span></td>
                <td><?= htmlspecialchars($clase['docente']) ?></td>
                <td><?= htmlspecialchars($clase['dia']) ?></td>
                <td><?= substr($clase['hora_inicio'], 0, 5) ?> - <?= substr($clase['hora_fin'], 0, 5) ?></td>
                <td><?= htmlspecialchars($clase['salon']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <div class='card'>
    <div class='card-header'>
      <div class='card-title'><i class='fa-solid fa-clipboard-list'></i> Actividades recientes</div>
      <a href='contenido.php' class='card-action'>Abrir contenido</a>
    </div>
    <?php if (empty($actividades)): ?>
      <div class='empty-state'><i class='fa-solid fa-inbox'></i><p>No hay actividades nuevas para ti.</p></div>
    <?php else: ?>
      <?php foreach (array_slice($actividades, 0, 5) as $actividad): ?>
        <div class='list-item'>
          <div class='stat-icon orange'><i class='fa-solid fa-book-open'></i></div>
          <div class='list-body'>
            <div class='list-title'><?= htmlspecialchars($actividad['titulo']) ?></div>
            <div class='list-sub'><?= htmlspecialchars($actividad['materia']) ?> · <?= htmlspecialchars($actividad['curso']) ?> · <?= htmlspecialchars($actividad['docente']) ?></div>
          </div>
          <div class='list-meta'><?= $actividad['fecha_entrega'] ? date('d/m/Y', strtotime($actividad['fecha_entrega'])) : 'Sin fecha' ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?php estudiante_layout_end(); ?>
