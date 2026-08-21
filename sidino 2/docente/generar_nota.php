<?php
// generar_nota.php
// Página para generar notas y enviar la información a docente.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Nota</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
        .container { max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,.1); }
        label { display: block; margin-bottom: 10px; }
        input, select { width: 100%; padding: 8px; margin-top: 4px; box-sizing: border-box; }
        button { padding: 10px 16px; background: #007bff; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .info { margin-top: 16px; font-size: 0.95rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Generar Nota</h1>
        <form method="post" action="docente.php">
            <label>
                Nombre del estudiante
                <input type="text" name="estudiante" required>
            </label>
            <label>
                Materia
                <input type="text" name="materia" required>
            </label>
            <label>
                Docente
                <input type="text" name="docente" required>
            </label>
            <label>
                Nota
                <input type="number" name="nota" min="0" max="100" step="0.1" required>
            </label>
            <input type="hidden" name="origen" value="generar_nota">
            <button type="submit">Enviar nota a docente.php</button>
        </form>
        <div class="info">
            <p>Esta página envía los datos de la nota a <strong>docente.php</strong> para su procesamiento.</p>
            <p>Si deseas ver los docentes, haz clic en <a href="docente.php">docente.php</a>.</p>
        </div>
    </div>
</body>
</html>
