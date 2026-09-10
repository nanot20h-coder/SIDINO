<?php
require_once __DIR__ . '/acudiente_common.php';

$pdo = getDB();
$id_acudiente = (int)($_SESSION['user_id'] ?? 0);
$estudiantes = acudiente_estudiantes($pdo, $id_acudiente);
$estudiante_id = (int)($_GET['estudiante'] ?? ($estudiantes[0]['id_usuario'] ?? 0));

$actividades = [];
if ($estudiante_id) {
    $stmt = $pdo->prepare("
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
    $stmt->execute([':id_estudiante' => $estudiante_id]);
    $actividades = $stmt->fetchAll();
}

acudiente_layout_start('Actividades', 'actividades');
?>
<div class='section-title'><i class='fa-solid fa-folder-open'></i> Actividades del estudiante</div>
<div class='card'>
  <?php if (empty($actividades)): ?>
    <div class='empty-state'><i class='fa-solid fa-inbox'></i><p>No hay actividades asignadas para este estudiante.</p></div>
  <?php else: ?>
    <div class='table-wrap'>
      <table>
        <thead><tr><th>Materia</th><th>Curso</th><th>Docente</th><th>Actividad</th><th>Descripción</th><th>Entrega</th></tr></thead>
        <tbody>
          <?php foreach ($actividades as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['materia']) ?></td>
              <td><span class='badge blue'><?= htmlspecialchars($a['curso']) ?></span></td>
              <td><?= htmlspecialchars($a['docente']) ?></td>
              <td><strong><?= htmlspecialchars($a['titulo']) ?></strong></td>
              <td><?= htmlspecialchars(substr($a['descripcion'] ?? '', 0, 120)) ?><?= strlen($a['descripcion'] ?? '') > 120 ? '...' : '' ?></td>
              <td><?= $a['fecha_entrega'] ? date('d/m/Y', strtotime($a['fecha_entrega'])) : 'Sin fecha' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php acudiente_layout_end(); ?>
