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

estudiante_layout_start('Mis clases', 'clases');
?>
<div class='section-title'><i class='fa-solid fa-calendar-days'></i> Horario de mis clases</div>
<div class='card'>
  <?php if (empty($asignaturas)): ?>
    <div class='empty-state'><i class='fa-solid fa-calendar-xmark'></i><p>Aún no tienes clases matriculadas.</p></div>
  <?php else: ?>
    <div class='table-wrap'>
      <table>
        <thead>
          <tr>
            <th>Materia</th>
            <th>Curso</th>
            <th>Docente</th>
            <th>Día</th>
            <th>Hora inicio</th>
            <th>Hora fin</th>
            <th>Salón</th>
            <th>Periodo</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($asignaturas as $clase): ?>
            <tr>
              <td><?= htmlspecialchars($clase['materia']) ?></td>
              <td><span class='badge blue'><?= htmlspecialchars($clase['curso']) ?></span></td>
              <td><?= htmlspecialchars($clase['docente']) ?></td>
              <td><?= htmlspecialchars($clase['dia']) ?></td>
              <td><?= substr($clase['hora_inicio'], 0, 5) ?></td>
              <td><?= substr($clase['hora_fin'], 0, 5) ?></td>
              <td><?= htmlspecialchars($clase['salon']) ?></td>
              <td><?= htmlspecialchars($clase['periodo']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php estudiante_layout_end(); ?>
