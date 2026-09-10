<?php
require_once 'auth.php';
require_auth([4]);

$pdo = getDB();
$id_docente = (int)($_SESSION['user_id'] ?? 0);

$clases = $pdo->prepare("
    SELECT aa.id_asignacion, m.nombre AS materia, c.nombre AS curso,
           s.nombre AS salon, h.dia, h.hora_inicio, h.hora_fin, p.nombre AS periodo
    FROM asignacion_academica aa
    JOIN materia m ON aa.id_materia = m.id_materia
    JOIN curso c ON aa.id_curso = c.id_curso
    JOIN salon s ON aa.id_salon = s.id_salon
    JOIN horario h ON aa.id_horario = h.id_horario
    JOIN periodo_academico p ON aa.id_periodo = p.id_periodo
    WHERE aa.id_docente = :id_docente
    ORDER BY FIELD(h.dia, 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'), h.hora_inicio
");
$clases->execute([':id_docente' => $id_docente]);
$asignaciones = $clases->fetchAll();

$menu_activo = [
  'docente.php' => 'dashboard',
  'asistencia.php' => 'asistencia',
  'horario.php' => 'horario',
  'generar_nota.php' => 'notas',
  'observador.php' => 'observador',
  'contenido.php' => 'contenido',
][basename($_SERVER['PHP_SELF'])] ?? 'dashboard';

layout_head('Docente — Mi Horario');
echo "<div class='app'>";
echo sidebar_html($menu_activo, [
  ['id'=>'dashboard', 'href'=>'docente.php', 'icon'=>'fa-gauge', 'label'=>'Dashboard'],
  ['id'=>'asistencia', 'href'=>'asistencia.php', 'icon'=>'fa-user-check', 'label'=>'Asistencia'],
  ['id'=>'horario', 'href'=>'horario.php', 'icon'=>'fa-calendar-days', 'label'=>'Mis clases'],
  ['id'=>'notas', 'href'=>'generar_nota.php', 'icon'=>'fa-star', 'label'=>'Registrar notas'],
  ['id'=>'observador', 'href'=>'observador.php', 'icon'=>'fa-book-open', 'label'=>'Observador'],
  ['id'=>'contenido', 'href'=>'contenido.php', 'icon'=>'fa-folder-open', 'label'=>'Contenido'],
]);
echo "<div class='main-content' id='mainContent'>";
echo topbar_html('Docente — Mi Horario', '#38bdf8');
echo "<div class='module-container'>";
echo "<div class='section-title'><i class='fa-solid fa-calendar-days'></i> Mi horario de clases</div>";

if (empty($asignaciones)) {
    echo "<div class='card'>
            <div class='empty-state'>
                <i class='fa-solid fa-calendar-xmark'></i>
                <p>Aún no tienes clases asignadas por el rector.</p>
            </div>
          </div>";
} else {
    echo "<div class='card'>
            <div class='table-wrap'>
                <table>
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Curso</th>
                            <th>Salón</th>
                            <th>Día</th>
                            <th>Hora inicio</th>
                            <th>Hora fin</th>
                            <th>Periodo</th>
                        </tr>
                    </thead>
                    <tbody>";

    foreach ($asignaciones as $a) {
        echo "<tr>
                <td>" . htmlspecialchars($a['materia']) . "</td>
                <td><span class='badge blue'>" . htmlspecialchars($a['curso']) . "</span></td>
                <td>" . htmlspecialchars($a['salon']) . "</td>
                <td>" . htmlspecialchars($a['dia']) . "</td>
                <td>" . substr($a['hora_inicio'], 0, 5) . "</td>
                <td>" . substr($a['hora_fin'], 0, 5) . "</td>
                <td>" . htmlspecialchars($a['periodo']) . "</td>
              </tr>";
    }

    echo "</tbody></table></div></div>";
}

echo "</div></div></div>";
layout_close();
?>