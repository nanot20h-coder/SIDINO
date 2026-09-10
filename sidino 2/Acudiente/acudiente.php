<?php
require_once __DIR__ . '/acudiente_common.php';

$pdo = getDB();
$id_acudiente = (int)($_SESSION['user_id'] ?? 0);

$estudiantes = acudiente_estudiantes($pdo, $id_acudiente);
$estudiante_id = (int)($_GET['estudiante'] ?? ($estudiantes[0]['id_usuario'] ?? 0));

$estudiante_actual = null;
foreach ($estudiantes as $est) {
    if ((int)$est['id_usuario'] === $estudiante_id) {
        $estudiante_actual = $est;
        break;
    }
}

if ($estudiante_actual === null && !empty($estudiantes)) {
    $estudiante_actual = $estudiantes[0];
    $estudiante_id = (int)$estudiantes[0]['id_usuario'];
}

$clases = [];
if ($estudiante_id) {
    $clases = $pdo->prepare("
        SELECT aa.id_asignacion, m.nombre AS materia, c.nombre AS curso,
               u.nombre AS docente, s.nombre AS salon, h.dia, h.hora_inicio, h.hora_fin,
               p.nombre AS periodo
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
    $clases->execute([':id_estudiante' => $estudiante_id]);
    $clases = $clases->fetchAll();
}

$asistencias = [];
if ($estudiante_id) {
    $asistencias_stmt = $pdo->prepare("
        SELECT a.fecha, a.estado, m.nombre AS materia, c.nombre AS curso
        FROM asistencia a
        JOIN matricula mat ON a.id_matricula = mat.id_matricula
        JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
        JOIN materia m ON aa.id_materia = m.id_materia
        JOIN curso c ON aa.id_curso = c.id_curso
        WHERE mat.id_estudiante = :id_estudiante
        ORDER BY a.fecha DESC
        LIMIT 20
    ");
    $asistencias_stmt->execute([':id_estudiante' => $estudiante_id]);
    $asistencias = $asistencias_stmt->fetchAll();
}

$actividades = [];
if ($estudiante_id) {
    $actividades_stmt = $pdo->prepare("
        SELECT co.titulo, co.descripcion, co.fecha_entrega, m.nombre AS materia, c.nombre AS curso, u.nombre AS docente
        FROM contenido co
        JOIN asignacion_academica aa ON co.id_asignacion = aa.id_asignacion
        JOIN matricula mat ON mat.id_asignacion = aa.id_asignacion
        JOIN materia m ON aa.id_materia = m.id_materia
        JOIN curso c ON aa.id_curso = c.id_curso
        JOIN usuario u ON aa.id_docente = u.id_usuario
        WHERE mat.id_estudiante = :id_estudiante AND co.tipo = 'actividad'
        ORDER BY co.fecha_entrega DESC, co.id_contenido DESC
    ");
    $actividades_stmt->execute([':id_estudiante' => $estudiante_id]);
    $actividades = $actividades_stmt->fetchAll();
}

$citaciones = [];
if ($estudiante_id) {
    $citaciones_stmt = $pdo->prepare("
        SELECT c.fecha, c.motivo, m.nombre AS materia
        FROM citacion c
        JOIN asignacion_academica aa ON c.id_asignacion = aa.id_asignacion
        JOIN materia m ON aa.id_materia = m.id_materia
        WHERE c.id_estudiante = :id_estudiante
        ORDER BY c.fecha DESC
        LIMIT 20
    ");
    $citaciones_stmt->execute([':id_estudiante' => $estudiante_id]);
    $citaciones = $citaciones_stmt->fetchAll();
}

$boletines = [];
if ($estudiante_id) {
    $boletines_stmt = $pdo->prepare("
        SELECT b.fecha, p.nombre AS periodo
        FROM boletin b
        JOIN periodo_academico p ON b.id_periodo = p.id_periodo
        WHERE b.id_estudiante = :id_estudiante
        ORDER BY b.fecha DESC
        LIMIT 10
    ");
    $boletines_stmt->execute([':id_estudiante' => $estudiante_id]);
    $boletines = $boletines_stmt->fetchAll();
}

acudiente_layout_start('Dashboard', 'dashboard');
?>
<div class='welcome-banner'>
  <div class='welcome-octopus'>🐙</div>
  <div>
    <div class='welcome-title'>¡Hola, <?= htmlspecialchars($_SESSION['nombre']) ?>!</div>
    <div class='welcome-sub'>Seguimiento del estudiante</div>
  </div>
</div>

<?php if (!empty($estudiantes)): ?>
<div class='card' style='margin-bottom:1.25rem;'>
  <div class='card-header'>
    <div class='card-title'><i class='fa-solid fa-user-graduate'></i> Estudiante</div>
  </div>
  <form method='GET' action='acudiente.php' style='padding:1rem 0 0;'>
    <div class='field-group'>
      <label class='field-label'>Seleccionar estudiante</label>
      <select name='estudiante' class='field-input' onchange='this.form.submit()'>
        <?php foreach ($estudiantes as $est): ?>
          <option value='<?= (int)$est['id_usuario'] ?>' <?= ((int)$est['id_usuario'] === $estudiante_id) ? 'selected' : '' ?>><?= htmlspecialchars($est['nombre']) ?><?= $est['curso'] ? ' · ' . htmlspecialchars($est['curso']) : '' ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>
</div>
<?php endif; ?>

<?php if (!$estudiante_actual): ?>
  <div class='card'><div class='empty-state'><i class='fa-solid fa-user-slash'></i><p>No tienes estudiantes asociados para consultar.</p></div></div>
<?php else: ?>
<div class='stats-grid'>
  <div class='stat-card'>
    <div class='stat-icon blue'><i class='fa-solid fa-calendar-days'></i></div>
    <div class='stat-body'><div class='stat-value'><?= count($clases) ?></div><div class='stat-label'>Clases</div></div>
  </div>
  <div class='stat-card'>
    <div class='stat-icon green'><i class='fa-solid fa-user-check'></i></div>
    <div class='stat-body'><div class='stat-value'><?= count($asistencias) ?></div><div class='stat-label'>Asistencias</div></div>
  </div>
  <div class='stat-card'>
    <div class='stat-icon orange'><i class='fa-solid fa-book-open'></i></div>
    <div class='stat-body'><div class='stat-value'><?= count($actividades) ?></div><div class='stat-label'>Actividades</div></div>
  </div>
  <div class='stat-card'>
    <div class='stat-icon purple'><i class='fa-solid fa-file-circle-exclamation'></i></div>
    <div class='stat-body'><div class='stat-value'><?= count($citaciones) ?></div><div class='stat-label'>Citaciones</div></div>
  </div>
</div>

<div class='content-grid'>
  <div class='card'>
    <div class='card-header'>
      <div class='card-title'><i class='fa-solid fa-calendar-days'></i> Horario del estudiante</div>
      <a href='calendario.php?estudiante=<?= (int)$estudiante_id ?>' class='card-action'>Ver calendario</a>
    </div>
    <?php if (empty($clases)): ?>
      <div class='empty-state'><i class='fa-solid fa-calendar-xmark'></i><p>No hay clases asignadas.</p></div>
    <?php else: ?>
      <div class='table-wrap'>
        <table>
          <thead><tr><th>Materia</th><th>Curso</th><th>Docente</th><th>Día</th><th>Hora</th><th>Salón</th></tr></thead>
          <tbody>
            <?php foreach ($clases as $clase): ?>
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
      <div class='card-title'><i class='fa-solid fa-list-check'></i> Asistencia reciente</div>
      <a href='asistencia.php?estudiante=<?= (int)$estudiante_id ?>' class='card-action'>Ver detalle</a>
    </div>
    <?php if (empty($asistencias)): ?>
      <div class='empty-state'><i class='fa-solid fa-user-check'></i><p>Sin registros de asistencia.</p></div>
    <?php else: ?>
      <?php foreach (array_slice($asistencias, 0, 6) as $a): ?>
        <div class='list-item'>
          <div class='stat-icon green'><i class='fa-solid fa-check'></i></div>
          <div class='list-body'>
            <div class='list-title'><?= htmlspecialchars($a['materia']) ?> · <?= htmlspecialchars($a['curso']) ?></div>
            <div class='list-sub'><?= htmlspecialchars($a['estado']) ?> · <?= date('d/m/Y', strtotime($a['fecha'])) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<div class='content-grid'>
  <div class='card'>
    <div class='card-header'>
      <div class='card-title'><i class='fa-solid fa-book-open'></i> Actividades del estudiante</div>
      <a href='contenido.php?estudiante=<?= (int)$estudiante_id ?>' class='card-action'>Ver actividades</a>
    </div>
    <?php if (empty($actividades)): ?>
      <div class='empty-state'><i class='fa-solid fa-inbox'></i><p>No hay actividades asignadas.</p></div>
    <?php else: ?>
      <?php foreach (array_slice($actividades, 0, 5) as $act): ?>
        <div class='list-item'>
          <div class='stat-icon orange'><i class='fa-solid fa-school'></i></div>
          <div class='list-body'>
            <div class='list-title'><?= htmlspecialchars($act['titulo']) ?></div>
            <div class='list-sub'><?= htmlspecialchars($act['materia']) ?> · <?= htmlspecialchars($act['docente']) ?></div>
          </div>
          <div class='list-meta'><?= $act['fecha_entrega'] ? date('d/m/Y', strtotime($act['fecha_entrega'])) : 'Sin fecha' ?></div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class='card'>
    <div class='card-header'>
      <div class='card-title'><i class='fa-solid fa-file-circle-exclamation'></i> Citaciones disciplinarias</div>
    </div>
    <?php if (empty($citaciones)): ?>
      <div class='empty-state'><i class='fa-solid fa-circle-check'></i><p>Sin citaciones registradas.</p></div>
    <?php else: ?>
      <?php foreach (array_slice($citaciones, 0, 5) as $cit): ?>
        <div class='list-item'>
          <div class='stat-icon purple'><i class='fa-solid fa-exclamation'></i></div>
          <div class='list-body'>
            <div class='list-title'><?= htmlspecialchars($cit['motivo']) ?></div>
            <div class='list-sub'><?= htmlspecialchars($cit['materia']) ?> · <?= date('d/m/Y', strtotime($cit['fecha'])) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<?php acudiente_layout_end(); ?>
