<?php
require_once __DIR__ . '/auth.php';
require_auth([6]);

$pdo = getDB();
$color = '#a78bfa';

function acudiente_nav(string $activo): array {
    return [
        ['id'=>'dashboard', 'href'=>'acudiente.php', 'icon'=>'fa-gauge', 'label'=>'Dashboard'],
        ['id'=>'asistencia', 'href'=>'asistencia.php', 'icon'=>'fa-user-check', 'label'=>'Asistencia'],
        ['id'=>'actividades', 'href'=>'contenido.php', 'icon'=>'fa-folder-open', 'label'=>'Actividades'],
        ['id'=>'calendario', 'href'=>'calendario.php', 'icon'=>'fa-calendar-days', 'label'=>'Calendario'],
    ];
}

function acudiente_layout_start(string $titulo, string $activo): void {
    global $color;
    layout_head('Acudiente — ' . $titulo);
    echo "<div class='app'>";
    echo sidebar_html($activo, acudiente_nav($activo));
    echo "<div class='main-content' id='mainContent'>";
    echo topbar_html('Acudiente — ' . $titulo, $color);
    echo "<div class='module-container'>";
}

function acudiente_layout_end(): void {
    echo "</div></div></div>";
    layout_close();
}

function acudiente_estudiantes(PDO $pdo, int $id_acudiente): array {
    return $pdo->query("SELECT u.id_usuario, u.nombre, c.nombre AS curso
        FROM acudiente_estudiante ae
        JOIN usuario u ON ae.id_estudiante = u.id_usuario
        LEFT JOIN matricula mat ON mat.id_estudiante = u.id_usuario
        LEFT JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
        LEFT JOIN curso c ON aa.id_curso = c.id_curso
        WHERE ae.id_acudiente = $id_acudiente
        GROUP BY u.id_usuario, u.nombre, c.nombre
        ORDER BY u.nombre")->fetchAll();
}

function acudiente_badge(int $id_rol): string {
    return ['1'=>'red','2'=>'orange','3'=>'yellow','4'=>'blue','5'=>'green','6'=>'purple'][$id_rol] ?? 'purple';
}
