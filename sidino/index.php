<?php
// ════════════════════════════════════════
// SIDINO 🐙 — Login PHP
// ════════════════════════════════════════
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo    = trim($_POST['correo'] ?? '');
    $password  = trim($_POST['password'] ?? '');

    if ($correo && $password) {
        try {
            $pdo  = getDB();
            $stmt = $pdo->prepare("SELECT u.*, r.nombre_rol FROM usuario u JOIN rol r ON u.id_rol = r.id_rol WHERE u.correo = ? AND u.contrasena = ?");
            $stmt->execute([$correo, $password]);
            $user = $stmt->fetch();

            if ($user) {
                $_SESSION['user_id']   = $user['id_usuario'];
                $_SESSION['nombre']    = $user['nombre'];
                $_SESSION['correo']    = $user['correo'];
                $_SESSION['id_rol']    = $user['id_rol'];
                $_SESSION['rol']       = strtolower($user['nombre_rol']);

                // Redirigir al dashboard del rol
                $dashboards = [
                    1 => 'rector.php',
                    2 => 'coordinador.php',
                    3 => 'administrativo.php',
                    4 => 'docente.php',
                    5 => 'estudiante.php',
                    6 => 'acudiente.php',
                ];
                $dest = $dashboards[$user['id_rol']] ?? 'estudiante.php';
                header("Location: $dest");
                exit;
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

    /* Burbujas */
    .bubbles { position: fixed; inset: 0; pointer-events: none; overflow: hidden; }
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
                pointer-events: none; white-space: nowrap; user-select: none; }

    /* Card */
    .login-card { position: relative; z-index: 10; width: 100%; max-width: 420px; margin: 1rem;
                  background: rgba(30,53,96,0.6); border: 1px solid var(--border);
                  border-radius: 24px; padding: 2.5rem; backdrop-filter: blur(16px);
                  box-shadow: 0 8px 40px rgba(0,0,0,0.5), 0 0 60px rgba(56,189,248,0.05); }

    .brand { text-align: center; margin-bottom: 2rem; }
    .brand-octopus { font-size: 3rem; display: block; margin-bottom: .5rem; }
    .brand-title { font-size: 2rem; font-weight: 800; letter-spacing: 4px;
                   background: linear-gradient(90deg, var(--accent), var(--accent2));
                   -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .brand-sub { color: var(--text2); font-size: .85rem; margin-top: .3rem; }
    .brand-year { color: var(--text3); font-size: .75rem; margin-top: .2rem; }

    /* Form */
    .form-group { margin-bottom: 1.2rem; }
    .form-label { display: flex; align-items: center; gap: .5rem; font-size: .8rem;
                  color: var(--text2); margin-bottom: .5rem; font-weight: 500; }
    .form-control { width: 100%; padding: .75rem 1rem; border-radius: 12px;
                    background: rgba(15,23,42,0.5); border: 1px solid var(--border);
                    color: var(--text); font-size: .9rem; transition: border-color .2s, box-shadow .2s; }
    .form-control:focus { outline: none; border-color: var(--accent); box-shadow: 0 0 0 3px rgba(56,189,248,0.12); }
    .form-control::placeholder { color: var(--text3); }

    .input-wrap { position: relative; }
    .input-wrap .form-control { padding-right: 3rem; }
    .toggle-pass { position: absolute; right: .75rem; top: 50%; transform: translateY(-50%);
                   color: var(--text3); background: none; border: none; cursor: pointer;
                   font-size: .9rem; transition: color .2s; }
    .toggle-pass:hover { color: var(--accent); }

    .error-msg { display: flex; align-items: center; gap: .5rem; color: var(--error);
                 font-size: .8rem; padding: .6rem .9rem; background: rgba(248,113,113,0.1);
                 border: 1px solid rgba(248,113,113,0.25); border-radius: 10px; margin-bottom: 1rem; }

    .btn-login { width: 100%; padding: .85rem; border-radius: 12px; font-size: .95rem; font-weight: 600;
                 background: linear-gradient(135deg, var(--accent), var(--accent2));
                 color: #0f172a; border: none; cursor: pointer; display: flex; align-items: center;
                 justify-content: center; gap: .6rem; transition: transform .15s, box-shadow .15s;
                 box-shadow: 0 4px 15px rgba(56,189,248,0.3); }
    .btn-login:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(56,189,248,0.45); }
    .btn-login:active { transform: translateY(0); }

    .hint { text-align: center; margin-top: 1.2rem; color: var(--text3); font-size: .78rem; }
    .hint code { background: rgba(56,189,248,0.12); color: var(--accent); padding: .1rem .4rem;
                 border-radius: 5px; font-size: .78rem; }
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

<div class="login-card">
  <div class="brand">
    <span class="brand-octopus">🐙</span>
    <div class="brand-title">SIDINO</div>
    <div class="brand-sub">Sistema de Gestión Académica</div>
    <div class="brand-year">Institución Educativa · 2026</div>
  </div>

  <?php if ($error): ?>
    <div class="error-msg"><i class="fa-solid fa-circle-xmark"></i><?= htmlspecialchars($error) ?></div>
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
        <button type="button" class="toggle-pass" onclick="togglePass()"><i class="fa-solid fa-eye" id="eyeIcon"></i></button>
      </div>
    </div>
    <button type="submit" class="btn-login">
      <i class="fa-solid fa-anchor"></i> Iniciar Sesión
    </button>
  </form>
</div>

<script>
function togglePass() {
  const i = document.getElementById('passInput');
  const e = document.getElementById('eyeIcon');
  if (i.type === 'password') { i.type = 'text'; e.className = 'fa-solid fa-eye-slash'; }
  else { i.type = 'password'; e.className = 'fa-solid fa-eye'; }
}
</script>
</body>
</html>
