<?php
require_once __DIR__ . '/acudiente_common.php';

$pdo = getDB();
$id_acudiente = (int)($_SESSION['user_id'] ?? 0);
$estudiantes = acudiente_estudiantes($pdo, $id_acudiente);
$estudiante_id = (int)($_GET['estudiante'] ?? ($estudiantes[0]['id_usuario'] ?? 0));

$eventos = [];
if ($estudiante_id) {
    $stmt = $pdo->prepare("
        SELECT m.nombre AS materia, c.nombre AS curso, u.nombre AS docente,
               h.dia, h.hora_inicio, h.hora_fin, 'clase' AS tipo
        FROM matricula mat
        JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
        JOIN materia m ON aa.id_materia = m.id_materia
        JOIN curso c ON aa.id_curso = c.id_curso
        JOIN horario h ON aa.id_horario = h.id_horario
        JOIN usuario u ON aa.id_docente = u.id_usuario
        WHERE mat.id_estudiante = :id_estudiante
        UNION ALL
        SELECT 'Reunión de acudientes' AS materia, 'General' AS curso, '' AS docente,
               'Lunes' AS dia, '18:00:00' AS hora_inicio, '19:00:00' AS hora_fin, 'reunion' AS tipo
        FROM usuario WHERE id_usuario = :id_estudiante
    ");
    $stmt->execute([':id_estudiante' => $estudiante_id]);
    $eventos = $stmt->fetchAll();
}

acudiente_layout_start('Calendario', 'calendario');
?>
<div class='section-title'><i class='fa-solid fa-calendar-days'></i> Calendario del estudiante</div>
<div class='card'>
  <?php if (empty($eventos)): ?>
    <div class='empty-state'><i class='fa-solid fa-calendar-xmark'></i><p>No hay eventos programados.</p></div>
  <?php else: ?>
    <div class='table-wrap'>
      <table>
        <thead><tr><th>Tipo</th><th>Materia / Evento</th><th>Curso</th><th>Docente</th><th>Día</th><th>Hora</th></tr></thead>
        <tbody>
          <?php foreach ($eventos as $evento): ?>
            <tr>
              <td><?= htmlspecialchars($evento['tipo']) === 'reunion' ? 'Reunión' : 'Clase' ?></td>
              <td><?= htmlspecialchars($evento['materia']) ?></td>
              <td><?= htmlspecialchars($evento['curso']) ?></td>
              <td><?= htmlspecialchars($evento['docente'] ?? '-') ?></td>
              <td><?= htmlspecialchars($evento['dia']) ?></td>
              <td><?= substr($evento['hora_inicio'], 0, 5) ?> - <?= substr($evento['hora_fin'], 0, 5) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php acudiente_layout_end(); ?>
