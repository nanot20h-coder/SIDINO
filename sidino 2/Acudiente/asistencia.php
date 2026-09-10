<?php
require_once __DIR__ . '/acudiente_common.php';

$pdo = getDB();
$id_acudiente = (int)($_SESSION['user_id'] ?? 0);
$estudiantes = acudiente_estudiantes($pdo, $id_acudiente);
$estudiante_id = (int)($_GET['estudiante'] ?? ($estudiantes[0]['id_usuario'] ?? 0));

$asistencias = [];
if ($estudiante_id) {
    $stmt = $pdo->prepare("
        SELECT a.fecha, a.estado, m.nombre AS materia, c.nombre AS curso
        FROM asistencia a
        JOIN matricula mat ON a.id_matricula = mat.id_matricula
        JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
        JOIN materia m ON aa.id_materia = m.id_materia
        JOIN curso c ON aa.id_curso = c.id_curso
        WHERE mat.id_estudiante = :id_estudiante
        ORDER BY a.fecha DESC
    ");
    $stmt->execute([':id_estudiante' => $estudiante_id]);
    $asistencias = $stmt->fetchAll();
}

acudiente_layout_start('Asistencia', 'asistencia');
?>
<div class='section-title'><i class='fa-solid fa-user-check'></i> Asistencia del estudiante</div>
<div class='card'>
  <?php if (empty($asistencias)): ?>
    <div class='empty-state'><i class='fa-solid fa-user-check'></i><p>No hay registros de asistencia para este estudiante.</p></div>
  <?php else: ?>
    <div class='table-wrap'>
      <table>
        <thead><tr><th>Fecha</th><th>Materia</th><th>Curso</th><th>Estado</th></tr></thead>
        <tbody>
          <?php foreach ($asistencias as $a): ?>
            <tr>
              <td><?= date('d/m/Y', strtotime($a['fecha'])) ?></td>
              <td><?= htmlspecialchars($a['materia']) ?></td>
              <td><span class='badge blue'><?= htmlspecialchars($a['curso']) ?></span></td>
              <td><?= htmlspecialchars($a['estado']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php acudiente_layout_end(); ?>
