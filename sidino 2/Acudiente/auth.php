<?php
session_start();
require_once __DIR__ . '/db.php';

function require_auth(array $roles_permitidos = []) {
    if (empty($_SESSION['user_id'])) {
        header('Location: ../index.php');
        exit;
    }
    if (!empty($roles_permitidos) && !in_array($_SESSION['id_rol'], $roles_permitidos)) {
        header('Location: ../index.php');
        exit;
    }
}

function sidebar_html(string $activo = 'dashboard', array $nav = []): string {
    $nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario');
    $rol    = htmlspecialchars(ucfirst($_SESSION['rol'] ?? ''));
    $avatar = strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1));
    $color_map = [
        'rectoria'       => '#f87171',
        'coordinacion'   => '#fb923c',
        'administrativo' => '#fbbf24',
        'docente'        => '#38bdf8',
        'estudiante'     => '#34d399',
        'acudiente'      => '#a78bfa',
    ];
    $rolKey = strtolower($_SESSION['rol'] ?? '');
    $color  = $color_map[$rolKey] ?? '#a78bfa';

    $nav_html = '';
    foreach ($nav as $item) {
        $activo_cls = ($item['id'] === $activo) ? 'active' : '';
        $nav_html .= "
        <a href=\"{$item['href']}\" class=\"nav-item {$activo_cls}\">
            <i class=\"fa-solid {$item['icon']}\"></i>
            <span>{$item['label']}</span>
        </a>";
    }

    return "
    <aside class='sidebar' id='sidebar'>
      <div class='sidebar-brand'>
        <div class='brand-octopus'>🐙</div>
        <div class='brand-text'>
          <span class='brand-name'>SIDINO</span>
          <span class='brand-year'>2026</span>
        </div>
        <button class='sidebar-toggle' onclick='toggleSidebar()'>
          <i class='fa-solid fa-bars'></i>
        </button>
      </div>
      <div class='sidebar-user'>
        <div class='user-avatar' style='background:linear-gradient(135deg,{$color},rgba(15,23,42,0.5))'>{$avatar}</div>
        <div class='user-info'>
          <span class='user-name'>{$nombre}</span>
          <span class='user-role-badge' style='color:{$color}'>{$rol}</span>
        </div>
      </div>
      <nav class='sidebar-nav'>{$nav_html}</nav>
      <div class='sidebar-footer'>
        <a href='../logout.php' class='btn-logout'>
          <i class='fa-solid fa-right-from-bracket'></i>
          <span>Cerrar Sesión</span>
        </a>
      </div>
    </aside>";
}

function topbar_html(string $titulo = 'Dashboard', string $rol_color = '#a78bfa'): string {
    $nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario');
    $avatar = strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1));
    return "
    <header class='topbar'>
      <div class='topbar-left'>
        <button class='mobile-toggle' onclick='toggleSidebar()'><i class='fa-solid fa-bars'></i></button>
        <div class='breadcrumb'><i class='fa-solid fa-house'></i> {$titulo}</div>
      </div>
      <div class='topbar-right'>
        <button class='theme-toggle' type='button' onclick='toggleTheme()' aria-label='Cambiar tema' title='Cambiar tema'>
          <i id='themeIcon' class='fa-solid fa-moon'></i><span id='themeText'>Modo oscuro</span>
        </button>
        <div class='topbar-user'>
          <span class='topbar-name'>{$nombre}</span>
          <div class='topbar-avatar' style='background:linear-gradient(135deg,{$rol_color},rgba(15,23,42,0.6))'>{$avatar}</div>
        </div>
      </div>
    </header>";
}

function layout_head(string $titulo, string $extra_css = ''): void {
    echo "<!DOCTYPE html>
<html lang='es'>
<head>
  <meta charset='UTF-8'/>
  <meta name='viewport' content='width=device-width,initial-scale=1.0'/>
  <title>SIDINO 🐙 — {$titulo}</title>
  <link rel='preconnect' href='https://fonts.googleapis.com'/>
  <link href='https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap' rel='stylesheet'/>
  <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'/>
  <link rel='stylesheet' href='../shared.css'/>
  {$extra_css}
</head>
<body>";
}

function layout_close(): void {
    echo "
<script>
function toggleSidebar() {
  const s = document.getElementById('sidebar');
  const m = document.getElementById('mainContent');

  if (window.innerWidth <= 768) {
    const isOpen = s.classList.toggle('mobile-open');
    s.style.transform = isOpen ? 'translateX(0)' : 'translateX(-100%)';
    return;
  }

  s.classList.toggle('collapsed');
  if (m) m.classList.toggle('expanded');
}

function toggleTheme() {
  const html = document.documentElement;
  const newTheme = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
  applyTheme(newTheme);
  localStorage.setItem('theme', newTheme);
}

function applyTheme(theme) {
  const themeIcon = document.getElementById('themeIcon');
  const themeText = document.getElementById('themeText');
  document.documentElement.setAttribute('data-theme', theme);
  themeIcon.className = theme === 'light' ? 'fa-solid fa-sun' : 'fa-solid fa-moon';
  themeText.textContent = theme === 'light' ? 'Modo claro' : 'Modo oscuro';
}

document.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.getElementById('mainContent');
  const isMobile = window.innerWidth <= 768;
  const savedTheme = localStorage.getItem('theme');
  if (savedTheme === 'light' || savedTheme === 'dark') applyTheme(savedTheme);

  if (!isMobile) {
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === 'true') {
      sidebar.classList.add('collapsed');
      if (mainContent) {
        mainContent.classList.add('expanded');
      }
    }
  } else {
    sidebar.classList.remove('collapsed');
    sidebar.style.transform = 'translateX(-100%)';
    sidebar.classList.remove('mobile-open');
  }
});
</script>
</body></html>";
}
