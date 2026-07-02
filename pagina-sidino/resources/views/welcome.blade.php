<?php
// Página principal de Sidino — convertida a PHP
declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');

// Año dinámico para el pie de página (antes estaba fijo en "2025")
$anioActual = date('Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sidino - Gestión Escolar Inteligente</title>
  <style>
    *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
    :root {
      --blue-900: #042C53;
      --blue-800: #0C447C;
      --blue-600: #185FA5;
      --blue-400: #378ADD;
      --blue-200: #85B7EB;
      --blue-100: #B5D4F4;
      --gray-900: #2C2C2A;
      --gray-700: #444441;
      --gray-500: #5F5E5A;
      --gray-300: #B4B2A9;
      --surface: #0a2e58;
      --card: #0D3A6E;
      --text-main: #E8F2FC;
      --text-muted: #93BAE0;
      --border: rgba(255,255,255,.12);
    }
    html { scroll-behavior: smooth; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: var(--surface);
      color: var(--text-main);
      overflow-x: hidden;
      line-height: 1.7;
    }
    a { text-decoration: none; color: inherit; }

    nav {
      position: sticky; top: 0; z-index: 200;
      background: var(--blue-900);
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 2.5rem; height: 64px;
      border-bottom: 1px solid var(--blue-800);
    }
    .nav-logo { font-size: 22px; font-weight: 700; color: #E8F2FC; letter-spacing: 1.5px; }
    .nav-links { display: flex; gap: 1.5rem; align-items: center; }
    .nav-links a { color: var(--blue-100); font-size: 14px; transition: color .2s; cursor: pointer; }
    .nav-links a:hover { color: #E8F2FC; }
    .nav-cta {
      background: var(--blue-400); color: #E8F2FC !important;
      border: none; padding: 9px 20px; border-radius: 6px;
      font-size: 14px; font-weight: 500; cursor: pointer; transition: background .2s;
    }
    .nav-cta:hover { background: var(--blue-600) !important; }
    @media (max-width: 700px) {
      nav { padding: 0 1rem; }
      .nav-links a:not(.nav-cta) { display: none; }
    }

    .hero { position: relative; width: 100%; height: 500px; overflow: hidden; }
    .slide { position: absolute; inset: 0; display: flex; align-items: center; opacity: 0; transition: opacity .9s ease; }
    .slide.active { opacity: 1; }
    .slide-bg { position: absolute; inset: 0; background-size: cover; background-position: center; }
    .slide-overlay { position: absolute; inset: 0; background: linear-gradient(to right, rgba(4,44,83,.82) 45%, rgba(4,44,83,.25)); }
    .slide-content { position: relative; z-index: 2; max-width: 600px; padding: 0 4rem; }
    .slide-content h1 { font-size: 36px; font-weight: 600; line-height: 1.2; margin-bottom: 14px; color: #E8F2FC; }
    .slide-content p { font-size: 16px; color: var(--blue-100); line-height: 1.75; }
    .hero-btn {
      display: inline-block; margin-top: 1.5rem;
      background: var(--blue-400); color: #E8F2FC;
      padding: 12px 28px; border-radius: 8px;
      font-size: 15px; font-weight: 500; cursor: pointer; border: none; transition: background .2s;
    }
    .hero-btn:hover { background: var(--blue-600); }
    .carousel-dots { position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); display: flex; gap: 8px; z-index: 5; }
    .dot { width: 9px; height: 9px; border-radius: 50%; background: rgba(255,255,255,.35); cursor: pointer; transition: background .3s; }
    .dot.active { background: #E8F2FC; }
    .arrow {
      position: absolute; top: 50%; transform: translateY(-50%);
      z-index: 5; background: rgba(255,255,255,.15); border: none; color: #E8F2FC;
      width: 44px; height: 44px; border-radius: 50%; font-size: 20px; cursor: pointer;
      display: flex; align-items: center; justify-content: center; transition: background .2s;
    }
    .arrow:hover { background: rgba(255,255,255,.28); }
    .arrow-l { left: 20px; } .arrow-r { right: 20px; }

    section { padding: 5rem 2.5rem; }
    .container { max-width: 1000px; margin: 0 auto; }
    .section-tag { font-size: 11px; font-weight: 700; letter-spacing: 2.5px; text-transform: uppercase; color: var(--blue-400); margin-bottom: .4rem; }
    .section-title { font-size: 28px; font-weight: 600; color: #E8F2FC; margin-bottom: .75rem; }
    .section-sub { font-size: 16px; color: var(--text-muted); line-height: 1.75; max-width: 620px; }

    #empresa { background: var(--surface); }
    .empresa-text p { font-size: 15px; color: var(--text-muted); line-height: 1.85; margin-bottom: 1rem; max-width: 700px; }

    #servicios { background: var(--card); }
    .servicios-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-top: 2.5rem; }
    .servicio-card { border-radius: 14px; overflow: hidden; border: 1px solid var(--border); transition: box-shadow .25s, transform .25s; }
    .servicio-card:hover { box-shadow: 0 8px 28px rgba(24,95,165,.2); transform: translateY(-3px); }
    .srv-img { height: 200px; position: relative; display: flex; align-items: center; justify-content: center; }
    .srv-badge { position: absolute; top: 12px; left: 12px; background: var(--blue-900); color: #E8F2FC; font-size: 11px; padding: 4px 12px; border-radius: 20px; font-weight: 500; }
    .srv-body { padding: 1.2rem 1.4rem; background: var(--surface); }
    .srv-body h3 { font-size: 15px; font-weight: 600; margin-bottom: 7px; color: #E8F2FC; }
    .srv-body p { font-size: 13px; color: var(--text-muted); line-height: 1.65; }
    @media (max-width: 700px) { .servicios-grid { grid-template-columns: 1fr; } }

    #funciones { background: var(--surface); }
    .funciones-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-top: 2.5rem; }
    .func-card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.4rem; transition: box-shadow .2s; }
    .func-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.3); }
    .func-icon { width: 40px; height: 40px; border-radius: 10px; background: rgba(55,138,221,.25); display: flex; align-items: center; justify-content: center; margin-bottom: 14px; }
    .func-icon svg { width: 20px; height: 20px; stroke: var(--blue-400); fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
    .func-card h3 { font-size: 14px; font-weight: 600; margin-bottom: 6px; color: #E8F2FC; }
    .func-card p { font-size: 13px; color: var(--text-muted); line-height: 1.65; }
    @media (max-width: 700px) { .funciones-grid { grid-template-columns: 1fr; } }

    /* ===== GALERÍA VISTA PREVIA ===== */
    #vista-sidino { background: var(--card); }
    .galeria-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.75rem; margin-top: 2.5rem; }
    .galeria-card { border-radius: 14px; overflow: hidden; border: 1px solid var(--border); background: var(--surface); transition: box-shadow .25s, transform .25s; }
    .galeria-card:hover { box-shadow: 0 8px 28px rgba(24,95,165,.2); transform: translateY(-3px); }
    .galeria-img { position: relative; width: 100%; height: 220px; background: var(--blue-900); overflow: hidden; }
    .galeria-img img { width: 100%; height: 100%; object-fit: cover; display: block; position: relative; z-index: 2; }
    .galeria-placeholder {
      position: absolute; inset: 0; z-index: 1;
      display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 10px;
      border: 2px dashed rgba(133,183,235,.3);
    }
    .galeria-placeholder svg { width: 44px; height: 44px; stroke: var(--blue-400); fill: none; stroke-width: 1.5; opacity: .6; }
    .galeria-placeholder span { font-size: 12px; color: var(--text-muted); letter-spacing: .3px; }
    .galeria-body { padding: 1.2rem 1.4rem 1.5rem; }
    .galeria-body h3 { font-size: 15px; font-weight: 600; color: #E8F2FC; margin-bottom: 7px; }
    .galeria-body p { font-size: 13px; color: var(--text-muted); line-height: 1.7; }
    @media (max-width: 900px) { .galeria-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .galeria-grid { grid-template-columns: 1fr; } }

    footer { background: var(--gray-900); color: var(--gray-300); padding: 3.5rem 2.5rem 1.5rem; }
    .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 2rem; max-width: 1000px; margin: 0 auto; }
    .footer-brand h3 { color: #E8F2FC; font-size: 19px; margin-bottom: 8px; }
    .footer-brand p { font-size: 13px; line-height: 1.75; }
    .footer-col h4 { color: #E8F2FC; font-size: 13px; font-weight: 600; margin-bottom: 14px; }
    .footer-col a { display: block; font-size: 13px; color: var(--gray-300); margin-bottom: 8px; cursor: pointer; transition: color .2s; }
    .footer-col a:hover { color: #E8F2FC; }
    .footer-bottom { border-top: 1px solid var(--gray-700); margin-top: 2.5rem; padding-top: 1.2rem; text-align: center; font-size: 12px; color: var(--gray-500); max-width: 1000px; margin-left: auto; margin-right: auto; }
    @media (max-width: 700px) { .footer-grid { grid-template-columns: 1fr 1fr; } }

    /* ===== PÁGINA CONTRÁTANOS ===== */
    #page-contratanos { display: none; min-height: 100vh; background: var(--surface); }
    .ct-header { background: var(--blue-900); padding: 4rem 2rem 3rem; text-align: center; border-bottom: 1px solid var(--blue-800); }
    .ct-header h2 { font-size: 32px; font-weight: 700; color: #E8F2FC; margin-bottom: 10px; }
    .ct-header p { font-size: 16px; color: var(--blue-100); }
    .ct-body { max-width: 480px; margin: 3rem auto 5rem; padding: 0 1.5rem; }
    .back-link { display: inline-flex; align-items: center; gap: 6px; color: var(--blue-400); font-size: 14px; cursor: pointer; margin-bottom: 2rem; font-weight: 500; transition: color .2s; }
    .back-link:hover { color: var(--blue-200); }
    .ct-card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 2.5rem 2rem; }
    .ct-card label { display: block; font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 7px; margin-top: 1.25rem; }
    .ct-card label:first-of-type { margin-top: 0; }
    .ct-card input {
      width: 100%; padding: 12px 15px;
      border: 1.5px solid rgba(255,255,255,.15);
      border-radius: 9px; font-size: 15px;
      background: var(--surface); color: #E8F2FC;
      transition: border-color .2s; outline: none;
    }
    .ct-card input::placeholder { color: #4A7AAA; }
    .ct-card input:focus { border-color: var(--blue-400); }
    .send-btn {
      width: 100%; background: var(--blue-400); color: #fff;
      border: none; padding: 15px; border-radius: 9px;
      font-size: 16px; font-weight: 600; cursor: pointer;
      margin-top: 2rem; transition: background .2s, opacity .2s;
    }
    .send-btn:hover:not(:disabled) { background: var(--blue-600); }
    .send-btn:disabled { opacity: .6; cursor: not-allowed; }
    .field-error { font-size: 12px; color: #F09595; margin-top: 5px; display: none; }
    .server-error {
      background: rgba(240,100,100,.12); border: 1px solid rgba(240,100,100,.35);
      color: #F09595; font-size: 13px; padding: 10px 14px;
      border-radius: 8px; margin-top: 1rem; display: none;
    }
    .success-state { display: none; text-align: center; padding: 1rem 0; }
    .success-icon { width: 72px; height: 72px; border-radius: 50%; background: rgba(29,158,117,.15); border: 2px solid rgba(29,158,117,.4); display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; }
    .success-icon svg { width: 34px; height: 34px; stroke: #2ECC9A; fill: none; stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; }
    .success-state h3 { font-size: 22px; font-weight: 700; color: #E8F2FC; margin-bottom: .75rem; }
    .success-state p { font-size: 15px; color: var(--text-muted); line-height: 1.8; }
    .email-chip { display: inline-block; margin-top: 1.2rem; background: rgba(55,138,221,.15); border: 1px solid rgba(55,138,221,.3); color: var(--blue-200); font-size: 13px; padding: 8px 18px; border-radius: 8px; font-weight: 500; word-break: break-all; }
    .back-home-btn { display: inline-block; margin-top: 2rem; background: none; border: 1.5px solid var(--border); color: var(--text-muted); padding: 11px 28px; border-radius: 9px; font-size: 14px; cursor: pointer; transition: border-color .2s, color .2s; }
    .back-home-btn:hover { border-color: var(--blue-400); color: #E8F2FC; }
  </style>
</head>
<body>

<!-- ===== PÁGINA PRINCIPAL ===== -->
<div id="page-main">
  <nav>
    <div class="nav-logo">Sidino</div>
    <div class="nav-links">
      <a href="#empresa">Empresa</a>
      <a href="#servicios">Servicios</a>
      <a href="#funciones">Funciones</a>
      <a href="#footer-contacto">Contacto</a>
      <a class="nav-cta" onclick="mostrarPagina('contratanos')">Contrátanos</a>
    </div>
  </nav>

  <div class="hero">
    <div class="slide active">
      <div class="slide-bg" style="background: linear-gradient(135deg, #042C53 0%, #185FA5 55%, #378ADD 100%);"></div>
      <div class="slide-overlay"></div>
      <div class="slide-content">
        <h1>Gestión escolar inteligente para tu institución</h1>
        <p>Sidino centraliza todo lo que necesita tu colegio en una sola plataforma moderna y fácil de usar.</p>
        <button class="hero-btn" onclick="mostrarPagina('contratanos')">Comenzar ahora</button>
      </div>
    </div>
    <div class="slide">
      <div class="slide-bg" style="background: linear-gradient(135deg, #0C447C 0%, #042C53 45%, #2C2C2A 100%);"></div>
      <div class="slide-overlay"></div>
      <div class="slide-content">
        <h1>Comunicación efectiva entre docentes, padres y alumnos</h1>
        <p>Mantén a toda la comunidad educativa conectada en tiempo real con herramientas pensadas para el aprendizaje.</p>
        <button class="hero-btn" onclick="mostrarPagina('contratanos')">Solicitar demo</button>
      </div>
    </div>
    <div class="slide">
      <div class="slide-bg" style="background: linear-gradient(135deg, #185FA5 0%, #378ADD 60%, #1D9E75 100%);"></div>
      <div class="slide-overlay"></div>
      <div class="slide-content">
        <h1>Reportes y analíticas en tiempo real</h1>
        <p>Toma decisiones informadas con dashboards claros sobre el rendimiento académico y la asistencia.</p>
        <button class="hero-btn" onclick="mostrarPagina('contratanos')">Ver más</button>
      </div>
    </div>
    <button class="arrow arrow-l" onclick="cambiarSlide(-1)">&#8592;</button>
    <button class="arrow arrow-r" onclick="cambiarSlide(1)">&#8594;</button>
    <div class="carousel-dots">
      <div class="dot active" onclick="irSlide(0)"></div>
      <div class="dot" onclick="irSlide(1)"></div>
      <div class="dot" onclick="irSlide(2)"></div>
    </div>
  </div>

  <section id="empresa">
    <div class="container">
      <div class="empresa-text">
        <p class="section-tag">Quiénes somos</p>
        <h2 class="section-title">Transformamos la educación con tecnología</h2>
        <p>Sidino es una plataforma de gestión educativa diseñada para colegios que quieren modernizar sus procesos administrativos y académicos sin complicaciones.</p>
        <p>Desde la matrícula hasta los reportes de calificaciones, lo centralizamos todo para que directivos, docentes y familias trabajen en sintonía.</p>
        <p>Nacimos con la misión de que ningún colegio se quede atrás en la transformación digital educativa de Colombia y Latinoamérica.</p>
      </div>
    </div>
  </section>

  <section id="servicios">
    <div class="container">
      <p class="section-tag">Nuestros servicios</p>
      <h2 class="section-title">Así se ve Sidino en acción</h2>
      <p class="section-sub">Tres pilares que transforman la forma en que tu institución opera cada día.</p>
      <div class="servicios-grid">
        <div class="servicio-card">
          <div class="srv-img" style="background: linear-gradient(135deg, #185FA5, #378ADD);">
            <svg width="70" height="70" viewBox="0 0 70 70" fill="none">
              <rect x="10" y="12" width="50" height="46" rx="5" fill="rgba(255,255,255,.15)"/>
              <rect x="18" y="22" width="24" height="3" rx="1.5" fill="rgba(255,255,255,.75)"/>
              <rect x="18" y="29" width="18" height="3" rx="1.5" fill="rgba(255,255,255,.5)"/>
              <rect x="18" y="36" width="22" height="3" rx="1.5" fill="rgba(255,255,255,.5)"/>
              <circle cx="50" cy="46" r="9" fill="rgba(255,255,255,.2)"/>
              <text x="50" y="51" text-anchor="middle" fill="white" font-size="12" font-weight="600">A+</text>
            </svg>
            <div class="srv-badge">Académico</div>
          </div>
          <div class="srv-body">
            <h3>Gestión de calificaciones</h3>
            <p>Registro, seguimiento y reporte de notas por período con vista para docentes, padres y estudiantes en tiempo real.</p>
          </div>
        </div>
        <div class="servicio-card">
          <div class="srv-img" style="background: linear-gradient(135deg, #042C53, #185FA5);">
            <svg width="70" height="70" viewBox="0 0 70 70" fill="none">
              <circle cx="26" cy="26" r="12" fill="rgba(255,255,255,.2)"/>
              <circle cx="44" cy="26" r="12" fill="rgba(255,255,255,.15)"/>
              <rect x="8" y="42" width="34" height="16" rx="5" fill="rgba(255,255,255,.2)"/>
              <rect x="34" y="42" width="24" height="16" rx="5" fill="rgba(255,255,255,.15)"/>
            </svg>
            <div class="srv-badge">Comunidad</div>
          </div>
          <div class="srv-body">
            <h3>Portal de familias</h3>
            <p>Los padres acceden desde su celular a las notas, asistencia, circulares y comunicados del colegio sin necesidad de llamar.</p>
          </div>
        </div>
        <div class="servicio-card">
          <div class="srv-img" style="background: linear-gradient(135deg, #0C447C, #1D9E75);">
            <svg width="70" height="70" viewBox="0 0 70 70" fill="none">
              <rect x="10" y="12" width="50" height="34" rx="5" fill="rgba(255,255,255,.15)"/>
              <rect x="18" y="20" width="34" height="4" rx="2" fill="rgba(255,255,255,.6)"/>
              <rect x="18" y="28" width="22" height="4" rx="2" fill="rgba(255,255,255,.4)"/>
              <rect x="18" y="54" width="34" height="8" rx="3" fill="rgba(255,255,255,.2)"/>
              <rect x="31" y="46" width="8" height="10" fill="rgba(255,255,255,.3)"/>
            </svg>
            <div class="srv-badge">Analítica</div>
          </div>
          <div class="srv-body">
            <h3>Dashboard administrativo</h3>
            <p>Panel completo con métricas de asistencia, rendimiento académico y estadísticas del colegio actualizadas al instante.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section id="funciones">
    <div class="container">
      <p class="section-tag">Funcionalidades</p>
      <h2 class="section-title">Todo lo que Sidino hace por tu colegio</h2>
      <p class="section-sub">Una plataforma completa con todas las herramientas que necesitas para digitalizar tu institución.</p>
      <div class="funciones-grid">
        <div class="func-card">
          <div class="func-icon"><svg viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></div>
          <h3>Control de asistencia</h3>
          <p>Registro digital de asistencia por clase con notificaciones automáticas a los padres ante ausencias.</p>
        </div>
        <div class="func-card">
          <div class="func-icon"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
          <h3>Agenda escolar digital</h3>
          <p>Calendario compartido con eventos, reuniones, evaluaciones y fechas importantes del colegio.</p>
        </div>
        <div class="func-card">
          <div class="func-icon"><svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg></div>
          <h3>Mensajería interna</h3>
          <p>Comunicación directa entre docentes, coordinadores y familias dentro de la plataforma.</p>
        </div>
        <div class="func-card">
          <div class="func-icon"><svg viewBox="0 0 24 24"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
          <h3>Seguimiento académico</h3>
          <p>Historial completo del desempeño del estudiante con gráficos de evolución por período y asignatura.</p>
        </div>
        <div class="func-card">
          <div class="func-icon"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg></div>
          <h3>Reportes y boletines</h3>
          <p>Generación automática de boletines, informes académicos y certificados con un clic.</p>
        </div>
        <div class="func-card">
          <div class="func-icon"><svg viewBox="0 0 24 24"><path d="M18 8h1a4 4 0 010 8h-1"/><path d="M2 8h16v9a4 4 0 01-4 4H6a4 4 0 01-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg></div>
          <h3>Soporte prioritario 24/7</h3>
          <p>Equipo de soporte disponible todo el día para resolver dudas, capacitar al personal y acompañar la implementación.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== SECCIÓN: ASÍ SE VE SIDINO ===== -->
  <section id="vista-sidino">
    <div class="container">
      <p class="section-tag">Vista previa</p>
      <h2 class="section-title">Así se ve Sidino en tu colegio</h2>
      <p class="section-sub">Conoce cada módulo de la plataforma antes de empezar.</p>
      <div class="galeria-grid">

        <div class="galeria-card">
          <div class="galeria-img">
            <img src="cordi.png" alt="Panel de cordinacion"
              onload="this.nextElementSibling.style.display='none'"
              onerror="this.style.display='none'" />
            <div class="galeria-placeholder">
              <svg viewBox="0 0 48 48"><rect x="6" y="6" width="36" height="36" rx="4"/><line x1="14" y1="20" x2="34" y2="20"/><line x1="14" y1="28" x2="26" y2="28"/></svg>
              <span>Agrega tu imagen aquí</span>
            </div>
          </div>
          <div class="galeria-body">
            <h3>Panel de cordinacion</h3>
            <p>Panel de cordinacion vista previa de las acciones para cordinacion.</p>
          </div>
        </div>

        <div class="galeria-card">
          <div class="galeria-img">
            <img src="estu.png" alt="Panel de estudiantes"
              onload="this.nextElementSibling.style.display='none'"
              onerror="this.style.display='none'" />
            <div class="galeria-placeholder">
              <svg viewBox="0 0 48 48"><rect x="6" y="6" width="36" height="36" rx="4"/><line x1="14" y1="20" x2="34" y2="20"/><line x1="14" y1="28" x2="26" y2="28"/></svg>
              <span>Agrega tu imagen aquí</span>
            </div>
          </div>
          <div class="galeria-body">
            <h3>Panel de estudiantes</h3>
            <p>Panel de estudiantes vista previa de las acciones que pueden realizar y mirar procedimientos.</p>
          </div>
        </div>

        <div class="galeria-card">
          <div class="galeria-img">
            <img src="acud.png" alt="Panel de acudientes"
              onload="this.nextElementSibling.style.display='none'"
              onerror="this.style.display='none'" />
            <div class="galeria-placeholder">
              <svg viewBox="0 0 48 48"><rect x="6" y="6" width="36" height="36" rx="4"/><line x1="14" y1="20" x2="34" y2="20"/><line x1="14" y1="28" x2="26" y2="28"/></svg>
              <span>Agrega tu imagen aquí</span>
            </div>
          </div>
          <div class="galeria-body">
            <h3>Panel de acudientes </h3>
            <p>Panel de acudinetes vista previa de como van sus hijos.</p>
          </div>
        </div>

        <div class="galeria-card">
          <div class="galeria-img">
            <img src="almini.png" alt="Panel de alministradores "
              onload="this.nextElementSibling.style.display='none'"
              onerror="this.style.display='none'" />
            <div class="galeria-placeholder">
              <svg viewBox="0 0 48 48"><rect x="6" y="6" width="36" height="36" rx="4"/><line x1="14" y1="20" x2="34" y2="20"/><line x1="14" y1="28" x2="26" y2="28"/></svg>
              <span>Agrega tu imagen aquí</span>
            </div>
          </div>
          <div class="galeria-body">
            <h3>Panel de alministradores</h3>
            <p>Panel de alminitradores visualizacion previa de sus acciones.</p>
          </div>
        </div>

        <div class="galeria-card">
          <div class="galeria-img">
            <img src="recto.png" alt="Panel de rector"
              onload="this.nextElementSibling.style.display='none'"
              onerror="this.style.display='none'" />
            <div class="galeria-placeholder">
              <svg viewBox="0 0 48 48"><rect x="6" y="6" width="36" height="36" rx="4"/><line x1="14" y1="20" x2="34" y2="20"/><line x1="14" y1="28" x2="26" y2="28"/></svg>
              <span>Agrega tu imagen aquí</span>
            </div>
          </div>
          <div class="galeria-body">
            <h3>Panel de rectores </h3>
            <p>Panel de rectores vista previa de las acciones que puede hacer.</p>
          </div>
        </div>

        <div class="galeria-card">
          <div class="galeria-img">
            <img src="docente.png" alt="Boletines y reportes"
              onload="this.nextElementSibling.style.display='none'"
              onerror="this.style.display='none'" />
            <div class="galeria-placeholder">
              <svg viewBox="0 0 48 48"><rect x="6" y="6" width="36" height="36" rx="4"/><line x1="14" y1="20" x2="34" y2="20"/><line x1="14" y1="28" x2="26" y2="28"/></svg>
              <span>Agrega tu imagen aquí</span>
            </div>
          </div>
          <div class="galeria-body">
            <h3>Panel de docentes</h3>
            <p>Panel de docentes previa vista de las acciones y organizacion que se maneja.</p>
          </div>
        </div>

      </div>
    </div>
  </section>

  <footer id="footer-contacto">
    <div class="footer-grid">
      <div class="footer-brand">
        <h3>Sidino</h3>
        <p>Plataforma de gestión educativa diseñada para transformar colegios de toda Colombia y Latinoamérica.</p>
        <p style="margin-top:1rem;color:#5F5E5A;font-size:12px">contacto@sidino.co &nbsp;|&nbsp; +57 (1) 234-5678</p>
      </div>
      <div class="footer-col">
        <h4>Legal</h4>
        <a>Términos y condiciones</a>
        <a>Política de privacidad</a>
        <a>Política de cookies</a>
        <a>Aviso legal</a>
      </div>
      <div class="footer-col">
        <h4>Empresa</h4>
        <a href="#empresa">Quiénes somos</a>
        <a href="#servicios">Servicios</a>
        <a href="#funciones">Funciones</a>
      </div>
      <div class="footer-col">
        <h4>Contacto</h4>
        <a>contacto@sidino.co</a>
        <a>+57 (1) 234-5678</a>
        <a>Bogotá, Colombia</a>
        <a onclick="mostrarPagina('contratanos')" style="color:#85B7EB;margin-top:6px;display:block;font-weight:600">Contrátanos →</a>
      </div>
    </div>
    <div class="footer-bottom">
      © <?php echo $anioActual; ?> Sidino. Todos los derechos reservados. &nbsp;|&nbsp; Términos y condiciones &nbsp;|&nbsp; Política de privacidad
    </div>
  </footer>
</div>


<!-- ===== PÁGINA CONTRÁTANOS ===== -->

<div id="page-contratanos">
  <nav>
    <div class="nav-logo">Sidino</div>
    <div class="nav-links">
      <a class="nav-cta" onclick="mostrarPagina('main')">← Volver al inicio</a>
    </div>
  </nav>

  <div class="ct-header">
    <h2>Contrátanos</h2>
    <p>Cuéntanos sobre tu institución y nos pondremos en contacto contigo</p>
  </div>

<div class="ct-card">

  <!-- FORMULARIO -->
  <div id="form-view">

    <label>Nombre del colegio</label>
    <input
      type="text"
      id="f-colegio"
      placeholder="Nombre de la institución educativa"
    />

    <label>Dirección</label>
    <input
      type="text"
      id="f-dir"
      placeholder="Ciudad y dirección del colegio"
    />

    <label>Correo electrónico</label>
    <input
      type="email"
      id="f-email"
      placeholder="correo@tucolegio.edu.co"
    />

    <div class="field-error" id="err-email">
      Ingresa un correo electrónico válido.
    </div>

    <label>Número de teléfono</label>
    <input
      type="tel"
      id="f-tel"
      placeholder="+57 300 000 0000"
    />

    <div class="server-error" id="server-error"></div>

    <button
      class="send-btn"
      id="send-btn"
      onclick="enviarSolicitud()">
      Mandar solicitud
    </button>

  </div>

  <!-- PLANES -->
  <div id="planes-view" style="display:none;">

    <h3 style="margin-bottom:25px;">
      Selecciona tu plan SIDINO
    </h3>

    <!-- PLAN MENSUAL -->
    <div style="
      border:1px solid rgba(255,255,255,.15);
      border-radius:12px;
      padding:20px;
      margin-bottom:20px;
    ">

      <h4>Plan Mensual</h4>

      <p style="
        font-size:28px;
        font-weight:bold;
        margin:10px 0;
      ">
        $300.000 COP
      </p>

      <p>
        Acceso completo a la plataforma SIDINO
        con facturación mensual.
      </p>

      <form action="{{ route('checkout') }}" method="POST">
        @csrf

        <input
          type="hidden"
          name="plan"
          value="mensual">

        <button class="send-btn">
          Elegir Plan Mensual
        </button>
      </form>

    </div>

    <!-- PLAN ANUAL -->
    <div style="
      border:1px solid rgba(255,255,255,.15);
      border-radius:12px;
      padding:20px;
    ">

      <h4>Plan Anual</h4>

      <p style="
        font-size:28px;
        font-weight:bold;
        margin:10px 0;
      ">
        $3.000.000 COP
      </p>

      <p>
        Acceso completo a la plataforma SIDINO
        durante todo el año.
      </p>

      <form action="{{ route('checkout') }}" method="POST">
        @csrf

        <input
          type="hidden"
          name="plan"
          value="anual">

        <button class="send-btn">
          Elegir Plan Anual
        </button>
      </form>

    </div>

  </div>

  <!-- ÉXITO -->
  <div
    class="success-state"
    id="success-view"
    style="display:none;"
  >

    <div class="success-icon">
      <svg viewBox="0 0 24 24">
        <polyline points="20 6 9 17 4 12"/>
      </svg>
    </div>

    <h3>¡Pago realizado correctamente!</h3>

    <p>
      Gracias por contratar SIDINO.<br>
      Hemos recibido tu pago y pronto
      activaremos tu servicio.
    </p>

    <button
      class="back-home-btn"
      onclick="mostrarPagina('main')">

      Volver al inicio

    </button>

  </div>

</div>
```

  </div>
</div>



<script>
  /* CAROUSEL */
  let slideActual = 0;
  const slides = document.querySelectorAll('.slide');
  const dots   = document.querySelectorAll('.dot');
  let autoplay = setInterval(() => cambiarSlide(1), 5500);

  function irSlide(n) {
    slides[slideActual].classList.remove('active');
    dots[slideActual].classList.remove('active');
    slideActual = n;
    slides[slideActual].classList.add('active');
    dots[slideActual].classList.add('active');
  }
  function cambiarSlide(d) {
    clearInterval(autoplay);
    irSlide((slideActual + d + slides.length) % slides.length);
    autoplay = setInterval(() => cambiarSlide(1), 5500);
  }

  /* NAVEGACIÓN */
  function mostrarPagina(pagina) {
    document.getElementById('page-main').style.display        = pagina === 'main'        ? 'block' : 'none';
    document.getElementById('page-contratanos').style.display = pagina === 'contratanos' ? 'block' : 'none';
    window.scrollTo(0, 0);
    if (pagina === 'contratanos') resetForm();
  }

  function resetForm() {
    document.getElementById('form-view').style.display    = 'block';
    document.getElementById('success-view').style.display = 'none';
    ['f-colegio','f-dir','f-email','f-tel'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('err-email').style.display    = 'none';
    document.getElementById('server-error').style.display = 'none';
    const btn = document.getElementById('send-btn');
    btn.disabled = false;
    btn.textContent = 'Mandar solicitud';
  }

  /* ENVÍO */
  async function enviarSolicitud() {
    const colegio   = document.getElementById('f-colegio').value.trim();
    const direccion = document.getElementById('f-dir').value.trim();
    const email     = document.getElementById('f-email').value.trim();
    const telefono  = document.getElementById('f-tel').value.trim();
    const emailOk   = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

    document.getElementById('err-email').style.display    = (!email || !emailOk) ? 'block' : 'none';
    document.getElementById('server-error').style.display = 'none';

    if (!colegio || !direccion || !email || !emailOk || !telefono) {
      if (!colegio || !direccion || !telefono) {
        const se = document.getElementById('server-error');
        se.textContent = 'Por favor completa todos los campos.';
        se.style.display = 'block';
      }
      return;
    }

    const btn = document.getElementById('send-btn');
    btn.disabled = true;
    btn.textContent = 'Enviando…';

    try {
      const res = await fetch('/enviar-solicitud', {
        method: 'POST',
        headers: {
'Content-Type': 'application/json',
'X-CSRF-TOKEN': document
.querySelector('meta[name="csrf-token"]')
.content
},
        body: JSON.stringify({ colegio, direccion, email, telefono })
      });

      const data = await res.json();

     if (data.ok) {

    document.getElementById('form-view').style.display = 'none';

    document.getElementById('planes-view').style.display = 'block';

}else {
        const se = document.getElementById('server-error');
        se.textContent = data.error || (data.errores ? data.errores.join(' ') : 'Error al enviar.');
        se.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Mandar solicitud';
      }

    } catch (err) {
      const se = document.getElementById('server-error');
      se.textContent = 'No se pudo conectar con el servidor. Intenta de nuevo.';
      se.style.display = 'block';
      btn.disabled = false;
      btn.textContent = 'Mandar solicitud';
    }
  }
</script>
</body>
</html>