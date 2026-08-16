<?php
// ════════════════════════════════════════
// SIDINO 🐙 — Auth guard + layout helper
// Incluir al inicio de cada dashboard
// ════════════════════════════════════════
session_start();
require_once __DIR__ . '/db.php';

// Roles permitidos se pasan como argumento
// Uso: require_auth([1]) — solo rector
//       require_auth([1,2]) — rector y coordinador
function require_auth(array $roles_permitidos = []) {
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
    if (!empty($roles_permitidos) && !in_array($_SESSION['id_rol'], $roles_permitidos)) {
        header('Location: index.php');
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
    $color  = $color_map[$rolKey] ?? '#38bdf8';

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
        <a href='logout.php' class='btn-logout'>
          <i class='fa-solid fa-right-from-bracket'></i>
          <span>Cerrar Sesión</span>
        </a>
      </div>
    </aside>";
}

function topbar_html(string $titulo = 'Dashboard', string $rol_color = '#38bdf8'): string {
    $nombre = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario');
    $avatar = strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1));
    return "
    <header class='topbar'>
      <div class='topbar-left'>
        <button class='mobile-toggle' onclick='toggleSidebar()'><i class='fa-solid fa-bars'></i></button>
        <div class='breadcrumb'><i class='fa-solid fa-house'></i> {$titulo}</div>
      </div>
      <div class='topbar-right'>
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
  <link rel='stylesheet' href='shared.css'/>
  {$extra_css}
</head>
<body>";
}

function layout_close(): void {
    echo "
<div class='settings-overlay' id='settingsOverlay' onclick='closeSettings()'></div>
<div class='settings-panel' id='settingsPanel'>
  <div class='settings-panel-header'>
    <div class='settings-panel-title'><i class='fa-solid fa-palette'></i> Personalizar</div>
    <button class='settings-close' onclick='closeSettings()'><i class='fa-solid fa-xmark'></i></button>
  </div>

  <div class='settings-section'>
    <span class='settings-label'>Apariencia</span>
    <div class='theme-switch'>
      <button type='button' class='theme-opt' id='optDark' onclick=\"setTheme('dark')\">
        <i class='fa-solid fa-moon'></i> Oscuro
      </button>
      <button type='button' class='theme-opt' id='optLight' onclick=\"setTheme('light')\">
        <i class='fa-solid fa-sun'></i> Claro
      </button>
    </div>
  </div>

  <div class='settings-section'>
    <span class='settings-label'>Color de acento</span>
    <div class='swatch-grid' id='swatchGrid'>
      <button type='button' class='swatch' data-color='56,189,248' style='background:#38bdf8' onclick=\"setAccent('56,189,248')\" title='Azul'></button>
      <button type='button' class='swatch' data-color='52,211,153' style='background:#34d399' onclick=\"setAccent('52,211,153')\" title='Verde'></button>
      <button type='button' class='swatch' data-color='251,146,60'  style='background:#fb923c' onclick=\"setAccent('251,146,60')\" title='Naranja'></button>
      <button type='button' class='swatch' data-color='248,113,113' style='background:#f87171' onclick=\"setAccent('248,113,113')\" title='Rojo'></button>
      <button type='button' class='swatch' data-color='251,191,36'  style='background:#fbbf24' onclick=\"setAccent('251,191,36')\" title='Amarillo'></button>
      <button type='button' class='swatch' data-color='167,139,250' style='background:#a78bfa' onclick=\"setAccent('167,139,250')\" title='Morado'></button>
      <button type='button' class='swatch' data-color='236,72,153'  style='background:#ec4899' onclick=\"setAccent('236,72,153')\" title='Rosa'></button>
    </div>
  </div>

  <div class='settings-section'>
    <span class='settings-label'>Color personalizado</span>
    <div class='custom-color-row'>
      <input type='color' id='customColorInput' class='custom-color-input' value='#38bdf8' oninput='onCustomColor(this.value)'>
      <span class='custom-color-label'>Elige cualquier color</span>
    </div>
  </div>

  <button type='button' class='btn btn-ghost btn-sm settings-reset' onclick='resetColors()'>
    <i class='fa-solid fa-arrow-rotate-left'></i> Restablecer colores
  </button>
</div>

<button class='settings-fab' id='settingsFab' onclick='toggleSettings()' title='Personalizar colores'>
  <i class='fa-solid fa-gear'></i>
</button>

<script>
function toggleSidebar() {
  const s = document.getElementById('sidebar');
  const m = document.getElementById('mainContent');
  const collapsed = s.classList.toggle('collapsed');
  if (m) m.classList.toggle('expanded');
  if (window.innerWidth > 1024) {
    localStorage.setItem('sidebarCollapsed', collapsed ? 'true' : 'false');
  } else {
    s.classList.toggle('mobile-open');
  }
}

// ── Tema claro / oscuro ──────────────────────────────
function setTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem('theme', theme);
  updateThemeButtons(theme);
}
function toggleTheme() {
  const current = document.documentElement.getAttribute('data-theme') || 'dark';
  setTheme(current === 'dark' ? 'light' : 'dark');
}
function updateThemeButtons(theme) {
  const optDark = document.getElementById('optDark');
  const optLight = document.getElementById('optLight');
  if (!optDark || !optLight) return;
  optDark.classList.toggle('active', theme !== 'light');
  optLight.classList.toggle('active', theme === 'light');
}

// ── Color de acento personalizado ────────────────────
function hexToRgbString(hex) {
  hex = hex.replace('#', '');
  if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
  const r = parseInt(hex.substring(0,2), 16);
  const g = parseInt(hex.substring(2,4), 16);
  const b = parseInt(hex.substring(4,6), 16);
  return r + ',' + g + ',' + b;
}
function rgbStringToHex(rgb) {
  const [r,g,b] = rgb.split(',').map(n => parseInt(n,10));
  return '#' + [r,g,b].map(n => n.toString(16).padStart(2,'0')).join('');
}
function lightenRgb(rgb, amount) {
  const [r,g,b] = rgb.split(',').map(n => parseInt(n,10));
  const l = n => Math.min(255, Math.round(n + (255 - n) * amount));
  return l(r) + ',' + l(g) + ',' + l(b);
}
function applyAccent(rgb) {
  const root = document.documentElement.style;
  root.setProperty('--accent-rgb', rgb);
  root.setProperty('--accent', 'rgb(' + rgb + ')');
  root.setProperty('--accent2', 'rgb(' + lightenRgb(rgb, .25) + ')');
  document.querySelectorAll('.user-avatar, .topbar-avatar').forEach(function(el){
    el.style.background = 'linear-gradient(135deg, rgb(' + rgb + '), rgba(15,23,42,0.5))';
  });
  document.querySelectorAll('.user-role-badge').forEach(function(el){ el.style.color = 'rgb(' + rgb + ')'; });
  const input = document.getElementById('customColorInput');
  if (input) input.value = rgbStringToHex(rgb);
  document.querySelectorAll('.swatch').forEach(function(sw){
    sw.classList.toggle('active', sw.getAttribute('data-color') === rgb);
  });
}
function setAccent(rgb) {
  applyAccent(rgb);
  localStorage.setItem('customAccent', rgb);
}
function onCustomColor(hex) {
  setAccent(hexToRgbString(hex));
}
function resetColors() {
  const root = document.documentElement.style;
  root.removeProperty('--accent-rgb');
  root.removeProperty('--accent');
  root.removeProperty('--accent2');
  localStorage.removeItem('customAccent');
  document.querySelectorAll('.user-avatar, .topbar-avatar').forEach(function(el){ el.style.background = ''; });
  document.querySelectorAll('.user-role-badge').forEach(function(el){ el.style.color = ''; });
  document.querySelectorAll('.swatch').forEach(function(sw){ sw.classList.remove('active'); });
  const input = document.getElementById('customColorInput');
  if (input) input.value = '#38bdf8';
  location.reload();
}

// ── Panel de personalización ─────────────────────────
function toggleSettings() {
  document.getElementById('settingsPanel').classList.toggle('open');
  document.getElementById('settingsOverlay').classList.toggle('open');
}
function closeSettings() {
  document.getElementById('settingsPanel').classList.remove('open');
  document.getElementById('settingsOverlay').classList.remove('open');
}

// ── Restaurar preferencias guardadas ─────────────────
document.addEventListener('DOMContentLoaded', function() {
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.getElementById('mainContent');
  const isMobile = window.innerWidth <= 1024;

  const savedTheme = localStorage.getItem('theme') || 'dark';
  setTheme(savedTheme);

  const savedAccent = localStorage.getItem('customAccent');
  if (savedAccent) applyAccent(savedAccent);

  if (!isMobile) {
    const savedState = localStorage.getItem('sidebarCollapsed');
    if (savedState === 'true' && sidebar) {
      sidebar.classList.add('collapsed');
      if (mainContent) mainContent.classList.add('expanded');
    }
  }
});
</script>
</body></html>";
}