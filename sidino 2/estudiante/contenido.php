<?php
require_once __DIR__ . '/estudiante_common.php';

$pdo = getDB();
$id_estudiante = (int)($_SESSION['user_id'] ?? 0);

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

estudiante_layout_start('Actividades', 'actividades');
?>
<div class='section-title'><i class='fa-solid fa-folder-open'></i> Actividades de mis clases</div>
<div class='card'>
  <?php if (empty($actividades)): ?>
    <div class='empty-state'><i class='fa-solid fa-inbox'></i><p>Aún no hay actividades publicadas en tus clases.</p></div>
  <?php else: ?>
    <div class='table-wrap'>
      <table>
        <thead>
          <tr>
            <th>Materia</th>
            <th>Curso</th>
            <th>Docente</th>
            <th>Actividad</th>
            <th>Descripción</th>
            <th>Entrega</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($actividades as $actividad): ?>
            <tr>
              <td><?= htmlspecialchars($actividad['materia']) ?></td>
              <td><span class='badge blue'><?= htmlspecialchars($actividad['curso']) ?></span></td>
              <td><?= htmlspecialchars($actividad['docente']) ?></td>
              <td><strong><?= htmlspecialchars($actividad['titulo']) ?></strong></td>
              <td><?= htmlspecialchars(substr($actividad['descripcion'] ?? '', 0, 120)) ?><?= strlen($actividad['descripcion'] ?? '') > 120 ? '...' : '' ?></td>
              <td><?= $actividad['fecha_entrega'] ? date('d/m/Y', strtotime($actividad['fecha_entrega'])) : 'Sin fecha' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php estudiante_layout_end(); ?>
