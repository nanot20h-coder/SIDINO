<?php
require_once __DIR__ . '/auth.php';
require_auth([1]);

$pdo = getDB();
$color = '#f87171';

function rector_nav(string $activo): array {
    return [
        ['id'=>'dashboard',    'href'=>'rector.php',              'icon'=>'fa-gauge',          'label'=>'Dashboard'],
        ['id'=>'usuarios',     'href'=>'usuarios.php',             'icon'=>'fa-users',          'label'=>'Usuarios'],
        ['id'=>'crear_usuario','href'=>'crear_usuario.php',        'icon'=>'fa-user-plus',      'label'=>'Crear Usuario'],
        ['id'=>'asignar_profesor','href'=>'asignar_profesor.php',  'icon'=>'fa-chalkboard-user','label'=>'Asignar Profesor'],
        
        ['id'=>'historial',    'href'=>'historial.php',            'icon'=>'fa-clock-rotate-left','label'=>'Historial'],
        ['id'=>'reportes',     'href'=>'reportes.php',             'icon'=>'fa-chart-bar',      'label'=>'Reportes'],
    
    ];
}

function rector_layout_start(string $titulo, string $activo): void {
    global $color;
    layout_head('Rector — ' . $titulo);
    echo "<div class='app'>";
    echo sidebar_html($activo, rector_nav($activo));
    echo "<div class='main-content' id='mainContent'>";
    echo topbar_html('Rector — ' . $titulo, $color);
    echo "<div class='module-container'>";
}

function rector_layout_end(): void {
    echo "</div></div></div>";
    layout_close();
}

function rector_stats(PDO $pdo): array {
    return [
        'usuarios' => $pdo->query("SELECT COUNT(*) FROM usuario")->fetchColumn(),
        'docentes' => $pdo->query("SELECT COUNT(*) FROM usuario WHERE id_rol=4")->fetchColumn(),
        'estudiantes' => $pdo->query("SELECT COUNT(*) FROM usuario WHERE id_rol=5")->fetchColumn(),
        'cursos' => $pdo->query("SELECT COUNT(*) FROM curso")->fetchColumn(),
        'materias' => $pdo->query("SELECT COUNT(*) FROM materia")->fetchColumn(),
        'periodos' => $pdo->query("SELECT COUNT(*) FROM periodo_academico")->fetchColumn(),
    ];
}

function rector_usuarios(PDO $pdo): array {
    return $pdo->query("SELECT u.id_usuario, u.nombre, u.correo, r.nombre_rol, u.id_rol
        FROM usuario u JOIN rol r ON u.id_rol = r.id_rol ORDER BY u.id_rol, u.nombre")->fetchAll();
}

function rector_historial(PDO $pdo): array {
    return $pdo->query("SELECT h.accion, h.tabla_afectada, h.fecha, u.nombre
        FROM historial_accion h JOIN usuario u ON h.id_usuario = u.id_usuario
        ORDER BY h.fecha DESC LIMIT 10")->fetchAll();
}

function rector_badge(int $id_rol): string {
    return ['1'=>'red','2'=>'orange','3'=>'yellow','4'=>'blue','5'=>'green','6'=>'purple'][$id_rol] ?? 'blue';
}
