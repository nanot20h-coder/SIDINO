<?php
// ════════════════════════════════════════
// SIDINO 🐙 — Login PHP (Completo y Seguro)
// ════════════════════════════════════════
session_start();
require_once __DIR__ . '/rector/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo    = trim($_POST['correo'] ?? '');
    $password  = trim($_POST['password'] ?? '');

    if ($correo && $password) {
        try {
            $pdo  = getDB();
            
            // Buscamos al usuario únicamente por su correo electrónico
            $stmt = $pdo->prepare("SELECT u.*, r.nombre_rol 
                                   FROM usuario u 
                                   JOIN rol r ON u.id_rol = r.id_rol 
                                   WHERE u.correo = ?");
            $stmt->execute([$correo]);
            $user = $stmt->fetch();

            if ($user) {
                // Comprobación compatible: soporta hash real y texto plano temporal
                if (password_verify($password, $user['contrasena']) || $password === $user['contrasena']) {
                    
                    $_SESSION['user_id']   = $user['id_usuario'];
                    $_SESSION['nombre']    = $user['nombre'];
                    $_SESSION['correo']    = $user['correo'];
                    $_SESSION['id_rol']    = $user['id_rol'];
                    $_SESSION['rol']       = strtolower($user['nombre_rol']);

                    // Redirigir al dashboard del rol correspondiente
                    $dashboards = [
                      1 => 'rector/rector.php',
                      2 => '../sidino/coordinador.php',
                      3 => '../sidino/administrativo.php',
                      4 => 'docente/docente.php',
                      5 => '../sidino/estudiante.php',
                      6 => '../sidino/acudiente.php',
                    ];
                    
                    $dest = $dashboards[$user['id_rol']] ?? 'estudiante.php';
                    header("Location: $dest");
                    exit;
                } else {
                    $error = 'Correo o contraseña incorrectos.';
                }
            } else {
                $error = 'Correo o contraseña incorrectos.';
            }
        } catch (Exception $e) {
            $error = 'Error de conexión: ' . $e->getMessage();
        }
    } else {
        $error = 'Por favor completa todos los campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>SIDINO 🐙 — Iniciar Sesión</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
    :root {
      --bg: #ffffff; --surface: #1e3560; --border: rgba(56,189,248,0.15);
      --accent: #38bdf8; --accent2: #22d3ee; --text: #e0f2fe; --text2: #7dd3fc; --text3: #4b7fa8;
      --error: #f87171; --font: 'Poppins', sans-serif;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: var(--font); background: var(--bg); color: var(--text); min-height: 100vh;
           display: flex; align-items: center; justify-content: center; overflow: hidden;
           background: radial-gradient(ellipse at 30% 20%, #0c1f4a 0%, #0f172a 70%); }

    /* Burbujas animadas */
    .bubbles { position: fixed; inset: 0; pointer-events: none; overflow: hidden; z-index: 1; }
    .bubble { position: absolute; border-radius: 50%; background: rgba(56,189,248,0.07); animation: rise linear infinite; }
    .bubble:nth-child(1)  { width:12px;  height:12px;  left:10%;  animation-duration:8s;  animation-delay:0s; }
    .bubble:nth-child(2)  { width:20px;  height:20px;  left:25%;  animation-duration:12s; animation-delay:2s; }
    .bubble:nth-child(3)  { width:8px;   height:8px;   left:40%;  animation-duration:7s;  animation-delay:1s; }
    .bubble:nth-child(4)  { width:16px;  height:16px;  left:55%;  animation-duration:10s; animation-delay:3s; }
    .bubble:nth-child(5)  { width:24px;  height:24px;  left:70%;  animation-duration:14s; animation-delay:0.5s; }
    .bubble:nth-child(6)  { width:10px;  height:10px;  left:85%;  animation-duration:9s;  animation-delay:4s; }
    .bubble:nth-child(7)  { width:18px;  height:18px;  left:15%;  animation-duration:11s; animation-delay:1.5s; }
    .bubble:nth-child(8)  { width:6px;   height:6px;   left:60%;  animation-duration:6s;  animation-delay:2.5s; }
    .bubble:nth-child(9)  { width:14px;  height:14px;  left:45%;  animation-duration:13s; animation-delay:3.5s; }
    .bubble:nth-child(10) { width:22px;  height:22px;  left:78%;  animation-duration:15s; animation-delay:1s; }
    @keyframes rise { from { bottom: -50px; opacity: 0.6; } to { bottom: 110vh; opacity: 0; } }

    /* SIDINO título de fondo */
    .bg-title { position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%);
                font-size: clamp(80px, 15vw, 180px); font-weight: 800; letter-spacing: -4px;
                background: linear-gradient(90deg, rgba(56,189,248,0.06), rgba(34,211,238,0.1), rgba(56,189,248,0.06));
                -webkit-background-clip: text; -webkit-text-fill-color: transparent;
                pointer-events: none; white-space: nowrap; user-select: none; z-index: 2; }

    /* Contenedor Maestro Corredizo */
    .sliding-container { position: relative; z-index: 10; width: 100%; max-width: 850px; min-height: 500px; margin: 1rem;
                         background: rgba(30,53,96,0.45); border: 1px solid var(--border);
                         border-radius: 24px; backdrop-filter: blur(20px); overflow: hidden;
                         box-shadow: 0 14px 45px rgba(0,0,0,0.6), 0 0 80px rgba(56,189,248,0.03); }

    /* Capas internas contenedoras de formularios */
    .form-box { position: absolute; top: 0; height: 100%; transition: all 0.6s ease-in-out; width: 50%; padding: 2.5rem; display: flex; flex-direction: column; justify-content: center; }
    
    .sign-in-box { left: 0; z-index: 2; }
    .support-box { left: 0; opacity: 0; z-index: 1; }

    /* Animación del movimiento corredizo al activar panel secundario */
    .sliding-container.right-panel-active .sign-in-box { transform: translateX(100%); opacity: 0; z-index: 1; }
    .sliding-container.right-panel-active .support-box { transform: translateX(100%); opacity: 1; z-index: 5; }

    .brand { text-align: center; margin-bottom: 1.5rem; }
    .brand-octopus { font-size: 2.5rem; display: block; margin-bottom: .3rem; }
    .brand-title { font-size: 1.8rem; font-weight: 800; letter-spacing: 4px;
                   background: linear-gradient(90deg, var(--accent), var(--accent2));
                   -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .brand-sub { color: var(--text2); font-size: .8rem; margin-top: .2rem; }
    .brand-year { color: var(--text3); font-size: .7rem; margin-top: .1rem; }

    /* Formularios internos */
    form { display: flex; flex-direction: column; width: 100%; }
    .form-group { margin-bottom: 1rem; }
    .form-label { display: flex; align-items: center; gap: .5rem; font-size: .8rem; color: var(--text2); margin-bottom: .4rem; font-weight: 500; }
    .form-control { width: 100%; padding: .75rem 1rem; border-radius: 12px; background: rgba(15,23,42,0.6); border: 1px solid var(--border); color: var(--text); font-size: .9rem; transition: border-color .2s, box-shadow .2s; }
    .form-control:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(56,189,248,0.12); }
    .form-control::placeholder { color: var(--text3); }

    .input-wrap { position: relative; }
    .input-wrap .form-control { padding-right: 3rem; }
    .toggle-pass { position: absolute; right: .75rem; top: 50%; transform: translateY(-50%); color: var(--text3); background: none; border: none; cursor: pointer; font-size: .9rem; transition: color .2s; }
    .toggle-pass:hover { color: var(--accent); }

    /* Mensaje de Error */
    .error-msg { display: flex; align-items: center; gap: .5rem; color: var(--error); font-size: .8rem; padding: .6rem .9rem; background: rgba(248,113,113,0.1); border: 1px solid rgba(248,113,113,0.25); border-radius: 10px; margin-bottom: 1rem; }

    /* Botones */
    .btn-action { width: 100%; padding: .85rem; border-radius: 12px; font-size: .95rem; font-weight: 600; background: linear-gradient(135deg, var(--accent), var(--accent2)); color: #0f172a; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: .6rem; transition: transform .15s, box-shadow .15s; box-shadow: 0 4px 15px rgba(56,189,248,0.2); }
    .btn-action:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(56,189,248,0.35); }
    .btn-action:active { transform: translateY(0); }

    .btn-ghost { background: transparent; border: 2px solid var(--accent); color: var(--accent); width: auto; padding: .6rem 1.5rem; border-radius: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s; margin-top: 1rem; align-self: center; }
    .btn-ghost:hover { background: var(--accent); color: #0f172a; box-shadow: 0 0 15px rgba(56,189,248,0.3); }

    /* Estilos del Contenedor del Overlay (Panel corredizo de color) */
    .overlay-master { position: absolute; top: 0; left: 50%; width: 50%; height: 100%; overflow: hidden; transition: transform 0.6s ease-in-out; z-index: 100; border-left: 1px solid var(--border); }
    .sliding-container.right-panel-active .overlay-master { transform: translateX(-100%); border-left: none; border-right: 1px solid var(--border); }

    .overlay-slug { background: linear-gradient(135deg, #13274f 0%, #090d16 100%); color: var(--text); position: relative; left: -100%; height: 100%; width: 200%; transform: translateX(0); transition: transform 0.6s ease-in-out; }
    .sliding-container.right-panel-active .overlay-slug { transform: translateX(50%); }

    .overlay-panel { position: absolute; display: flex; align-items: center; justify-content: center; flex-direction: column; padding: 0 3rem; text-align: center; top: 0; height: 100%; width: 50%; transform: translateX(0); transition: transform 0.6s ease-in-out; }
    
    .overlay-left { transform: translateX(-200%); }
    .sliding-container.right-panel-active .overlay-left { transform: translateX(0); }

    .overlay-right { right: 0; transform: translateX(0); }
    .sliding-container.right-panel-active .overlay-right { transform: translateX(200%); }

    .overlay-panel h2 { font-size: 1.8rem; font-weight: 700; margin-bottom: 0.75rem; background: linear-gradient(90deg, #fff, var(--text2)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .overlay-panel p { font-size: .85rem; color: var(--text3); line-height: 1.5; }

    /* Ajuste Responsivo para móviles */
    @media (max-width: 768px) {
        .sliding-container { max-width: 400px; min-height: 550px; }
        .overlay-master { display: none; }
        .form-box { width: 100%; }
        .sliding-container.right-panel-active .sign-in-box { transform: none; opacity: 0; z-index: 1; }
        .sliding-container.right-panel-active .support-box { transform: none; opacity: 1; z-index: 5; }
        .btn-ghost-toggle { display: inline-block; background: none; border: none; color: var(--accent); font-size: 0.85rem; text-decoration: underline; margin-top: 1rem; cursor: pointer; text-align: center;}
    }
    @media (min-width: 769px) {
        .btn-ghost-toggle { display: none; }
    }
  </style>
</head>
<body>

<div class="bubbles">
  <div class="bubble"></div><div class="bubble"></div><div class="bubble"></div>
  <div class="bubble"></div><div class="bubble"></div><div class="bubble"></div>
  <div class="bubble"></div><div class="bubble"></div><div class="bubble"></div>
  <div class="bubble"></div>
</div>
<div class="bg-title">SIDINO</div>

<div class="sliding-container <?= $error ? 'right-panel-active' : '' ?>" id="slidingContainer">
    
    <div class="form-box sign-in-box">
      <div class="brand">
        <span class="brand-octopus">🐙</span>
        <div class="brand-title">SIDINO</div>
        <div class="brand-sub">Sistema de Gestión Académica</div>
        <div class="brand-year">Institución Educativa · 2026</div>
      </div>

      <?php if ($error): ?>
        <div class="error-msg">
          <i class="fa-solid fa-circle-xmark"></i>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-envelope"></i> Correo electrónico</label>
          <input type="email" name="correo" class="form-control" placeholder="tu@correo.com"
                 value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>" required/>
        </div>
        
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-lock"></i> Contraseña</label>
          <div class="input-wrap">
            <input type="password" name="password" id="passInput" class="form-control" placeholder="Ingresa tu contraseña" required/>
            <button type="button" class="toggle-pass" onclick="togglePass()">
              <i class="fa-solid fa-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>
        
        <button type="submit" class="btn-action">
          <i class="fa-solid fa-anchor"></i> Iniciar Sesión
        </button>
      </form>
      
      <button class="btn-ghost-toggle" onclick="activateRightPanel()">¿Necesitas ayuda o soporte técnico?</button>
    </div>

    <div class="form-box support-box">
      <div class="brand">
        <span class="brand-octopus">🛠️</span>
        <div class="brand-title">SOPORTE</div>
        <div class="brand-sub">¿Problemas para ingresar?</div>
      </div>
      
      <form action="#" method="POST" onsubmit="event.preventDefault(); alert('Solicitud enviada al administrador.');">
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-user"></i> Tu Nombre Completo</label>
          <input type="text" class="form-control" placeholder="Ej. Juan Pérez" required/>
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-id-card"></i> Documento de Identidad</label>
          <input type="text" class="form-control" placeholder="Número de identificación" required/>
        </div>
        <div class="form-group">
          <label class="form-label"><i class="fa-solid fa-comment-dots"></i> Describe el inconveniente</label>
          <input type="text" class="form-control" placeholder="Ej. Olvidé mi clave / Bloqueo de rol" required/>
        </div>
        <button type="submit" class="btn-action">
          <i class="fa-solid fa-paper-plane"></i> Solicitar Ayuda
        </button>
      </form>
      
      <button class="btn-ghost-toggle" onclick="deactivateRightPanel()">Volver al Login</button>
    </div>

    <div class="overlay-master">
        <div class="overlay-slug">
            <div class="overlay-panel overlay-left">
                <h2>¿Todo listo?</h2>
                <p>Si ya recuerdas tus credenciales o solucionaste tu inconveniente, regresa aquí.</p>
                <button class="btn-ghost" id="btnSignIn">Ir al Login</button>
            </div>
            <div class="overlay-panel overlay-right">
                <h2>¿Problemas de acceso?</h2>
                <p>Si eres estudiante, docente o acudiente y no logras ingresar, solicita asistencia al administrador del sistema.</p>
                <button class="btn-ghost" id="btnSupport">Soporte Técnico</button>
            </div>
        </div>
    </div>

</div>

<script>
// Alternar visibilidad de contraseña
function togglePass() {
  const i = document.getElementById('passInput');
  const e = document.getElementById('eyeIcon');
  if (i.type === 'password') { 
    i.type = 'text'; 
    e.className = 'fa-solid fa-eye-slash'; 
  } else { 
    i.type = 'password'; 
    e.className = 'fa-solid fa-eye'; 
  }
}

// Lógica de transición del Login Corredizo
const btnSupport = document.getElementById('btnSupport');
const btnSignIn = document.getElementById('btnSignIn');
const slidingContainer = document.getElementById('slidingContainer');

btnSupport.addEventListener('click', () => {
    activateRightPanel();
});

btnSignIn.addEventListener('click', () => {
    deactivateRightPanel();
});

function activateRightPanel(){
    slidingContainer.classList.add("right-panel-active");
}
function deactivateRightPanel(){
    slidingContainer.classList.remove("right-panel-active");
}
</script>
</body>
</html>