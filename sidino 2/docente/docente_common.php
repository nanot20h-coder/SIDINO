<?php
require_once __DIR__ . '/auth.php';
require_auth([4]);

$pdo = getDB();
$color = '#f87171';

function docente_nav(string $activo): array {
    return [
  ['id'=>'dashboard',  'href'=>'docente.php',       'icon'=>'fa-gauge',        'label'=>'Dashboard'],
  ['id'=>'asistencia', 'href'=>'asistencia.php',    'icon'=>'fa-user-check',  'label'=>'Asistencia'],
  ['id'=>'horario',    'href'=>'horario.php',       'icon'=>'fa-calendar-days','label'=>'Mis clases'],
  ['id'=>'notas',      'href'=>'generar_nota.php',         'icon'=>'fa-star',         'label'=>'Registrar notas'],
  ['id'=>'observador', 'href'=>'observador.php',    'icon'=>'fa-book-open',    'label'=>'Observador'],
  ['id'=>'contenido',  'href'=>'contenido.php',     'icon'=>'fa-folder-open',  'label'=>'Contenido'],
    
    ];
}

function docente_layout_start(string $titulo, string $activo): void {
    global $color;
    layout_head('Docente — ' . $titulo);
    echo "<div class='app'>";
    echo sidebar_html($activo, docente_nav($activo));
    echo "<div class='main-content' id='mainContent'>";
    echo topbar_html('Docente — ' . $titulo, $color);
    echo "<div class='module-container'>";
}

function docente_layout_end(): void {
    echo "</div></div></div>";
    layout_close();
}

function docente_stats(PDO $pdo): array {
    return [
        'usuarios' => $pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn(),
        'docentes' => $pdo->query("SELECT COUNT(*) FROM usuario WHERE id_rol=4")->fetchColumn(),
        'estudiantes' => $pdo->query("SELECT COUNT(*) FROM usuario WHERE id_rol=5")->fetchColumn(),
        'cursos' => $pdo->query("SELECT COUNT(*) FROM curso")->fetchColumn(),
        'materias' => $pdo->query("SELECT COUNT(*) FROM materia")->fetchColumn(),
        'periodos' => $pdo->query("SELECT COUNT(*) FROM periodo_academico")->fetchColumn(),
    ];
}

function docente_usuarios(PDO $pdo): array {
    return $pdo->query("SELECT u.id_usuario, u.nombre, u.correo, r.nombre_rol, u.id_rol
        FROM usuario u JOIN rol r ON u.id_rol = r.id_rol ORDER BY u.id_rol, u.nombre")->fetchAll();
}

function docente_historial(PDO $pdo): array {
    return $pdo->query("SELECT h.accion, h.tabla_afectada, h.fecha, u.nombre
        FROM historial_accion h JOIN usuario u ON h.id_usuario = u.id_usuario
        ORDER BY h.fecha DESC LIMIT 10")->fetchAll();
}

function docente_badge(int $id_rol): string {
    return ['1'=>'red','2'=>'orange','3'=>'yellow','4'=>'blue','5'=>'green','6'=>'purple'][$id_rol] ?? 'blue';
}
