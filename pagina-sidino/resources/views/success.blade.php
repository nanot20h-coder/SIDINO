<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago realizado | SIDINO</title>

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
            font-family:'Segoe UI',sans-serif;
        }

        body{
            min-height:100vh;
            display:flex;
            justify-content:center;
            align-items:center;
            background:linear-gradient(
                135deg,
                #0f172a,
                #1e293b,
                #0f172a
            );
            color:white;
            padding:20px;
        }

        .success-card{
            width:100%;
            max-width:700px;
            background:rgba(255,255,255,.05);
            backdrop-filter:blur(10px);
            border:1px solid rgba(255,255,255,.1);
            border-radius:20px;
            padding:50px;
            text-align:center;
            box-shadow:0 20px 40px rgba(0,0,0,.35);
        }

        .success-icon{
            width:100px;
            height:100px;
            margin:0 auto 25px;
            border-radius:50%;
            background:rgba(34,197,94,.15);
            display:flex;
            justify-content:center;
            align-items:center;
        }

        .success-icon svg{
            width:55px;
            height:55px;
            stroke:#22c55e;
            stroke-width:3;
            fill:none;
        }

        h1{
            font-size:2.2rem;
            margin-bottom:15px;
        }

        p{
            font-size:1.1rem;
            line-height:1.8;
            color:#cbd5e1;
            margin-bottom:35px;
        }

        .btn-home{
            display:inline-block;
            text-decoration:none;
            padding:14px 28px;
            border-radius:12px;
            background:#2563eb;
            color:white;
            font-weight:600;
            transition:.3s;
        }

        .btn-home:hover{
            transform:translateY(-2px);
            background:#1d4ed8;
        }

        .logo{
            font-size:1.5rem;
            font-weight:bold;
            margin-bottom:30px;
            color:#60a5fa;
        }
    </style>
</head>
<body>

    <div class="success-card">

        <div class="logo">
            SIDINO
        </div>

        <div class="success-icon">
            <svg viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        <h1>Pago realizado correctamente</h1>

        <p>
            Gracias por contratar <strong>SIDINO</strong>.<br>
            Hemos recibido tu solicitud y tu pago fue procesado exitosamente.
            Nuestro equipo se pondrá en contacto contigo para iniciar la implementación del sistema.
        </p>

        <a href="{{ url('/') }}" class="btn-home">
            Volver al inicio
        </a>

    </div>

</body>
</html>