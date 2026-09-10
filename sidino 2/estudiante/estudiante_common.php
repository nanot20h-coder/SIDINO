<?php
require_once __DIR__ . '/auth.php';
require_auth([5]);

$pdo = getDB();
$color = '#34d399';

function estudiante_nav(string $activo): array {
    return [
        ['id'=>'dashboard', 'href'=>'estudiante.php', 'icon'=>'fa-gauge', 'label'=>'Dashboard'],
        ['id'=>'clases', 'href'=>'mis_clases.php', 'icon'=>'fa-calendar-days', 'label'=>'Mis clases'],
        ['id'=>'actividades', 'href'=>'contenido.php', 'icon'=>'fa-folder-open', 'label'=>'Actividades'],
    ];
}

function estudiante_layout_start(string $titulo, string $activo): void {
    global $color;
    layout_head('Estudiante — ' . $titulo);
    echo "<div class='app'>";
    echo sidebar_html($activo, estudiante_nav($activo));
    echo "<div class='main-content' id='mainContent'>";
    echo topbar_html('Estudiante — ' . $titulo, $color);
    echo "<div class='module-container'>";
}

function estudiante_layout_end(): void {
    echo "</div></div></div>";
    layout_close();
}

function estudiante_stats(PDO $pdo): array {
    return [
        'clases' => (int)$pdo->query("SELECT COUNT(*) FROM matricula WHERE id_estudiante = " . (int)($_SESSION['user_id'] ?? 0))->fetchColumn(),
        'actividades' => (int)$pdo->query("SELECT COUNT(*) FROM contenido co JOIN matricula mat ON co.id_asignacion = mat.id_asignacion WHERE mat.id_estudiante = " . (int)($_SESSION['user_id'] ?? 0) . " AND co.tipo = 'actividad'")->fetchColumn(),
    ];
}

function estudiante_badge(int $id_rol): string {
    return ['1'=>'red','2'=>'orange','3'=>'yellow','4'=>'blue','5'=>'green','6'=>'purple'][$id_rol] ?? 'green';
}
