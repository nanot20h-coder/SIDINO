package com.sidino.ui;

import com.sidino.core.DB;
import com.sidino.core.Sesion;
import com.sidino.ui.componentes.BotonRedondeado;
import com.sidino.ui.componentes.Dialogos;
import com.sidino.ui.componentes.Estilos;

import javax.swing.*;
import java.awt.FlowLayout;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

public class AdministrativoDashboard extends DashboardBase {

    public AdministrativoDashboard() {
        super("Administrativo — Dashboard", "Administrativo — Gestión", Estilos.AMBAR, List.of(
                new ItemNav("dashboard", "dashboard", "Dashboard"),
                new ItemNav("estudiantes", "estudiantes", "Estudiantes"),
                new ItemNav("boletines", "boletines", "Boletines"),
                new ItemNav("historial", "historial", "Historial")
        ));
    }

    @Override
    protected JPanel construirModulo(String id) {
        return switch (id) {
            case "dashboard" -> panelDashboard();
            case "estudiantes" -> panelEstudiantes();
            case "boletines" -> panelBoletines();
            case "historial" -> panelHistorial();
            default -> new JPanel();
        };
    }

    private List<Map<String, Object>> estudiantes() {
        return DB.query("""
            SELECT u.id_usuario, u.nombre, u.correo, COUNT(m.id_matricula) AS materias_matriculadas
            FROM usuario u
            LEFT JOIN matricula m ON u.id_usuario = m.id_estudiante
            WHERE u.id_rol = 5
            GROUP BY u.id_usuario ORDER BY u.nombre
        """);
    }

    private List<Map<String, Object>> boletines() {
        return DB.query("""
            SELECT b.id_boletin, b.fecha, u.nombre AS estudiante, p.nombre AS periodo
            FROM boletin b
            JOIN usuario u ON b.id_estudiante = u.id_usuario
            JOIN periodo_academico p ON b.id_periodo = p.id_periodo
            ORDER BY b.fecha DESC LIMIT 50
        """);
    }

    private List<Map<String, Object>> historial() {
        return DB.query("""
            SELECT h.accion, h.tabla_afectada, h.fecha, u.nombre
            FROM historial_accion h JOIN usuario u ON h.id_usuario = u.id_usuario
            ORDER BY h.fecha DESC LIMIT 30
        """);
    }

    private List<Map<String, Object>> periodos() {
        return DB.query("SELECT id_periodo, nombre, anio FROM periodo_academico ORDER BY id_periodo DESC");
    }

    private List<Map<String, Object>> estudiantesParaBoletin() {
        return DB.query("SELECT id_usuario, nombre FROM usuario WHERE id_rol = 5 ORDER BY nombre");
    }

    private List<Map<String, Object>> resumenAcademico() {
        return DB.query("""
                SELECT u.nombre AS estudiante, u.correo,
                       COALESCE(GROUP_CONCAT(DISTINCT CONCAT(m.nombre, ' - ', c.nombre)
                           ORDER BY m.nombre SEPARATOR ', '), 'Sin clase asignada') AS clases,
                       COALESCE(GROUP_CONCAT(DISTINCT d.nombre ORDER BY d.nombre SEPARATOR ', '),
                           'Sin profesor asignado') AS profesores
                FROM usuario u
                LEFT JOIN matricula mat ON mat.id_estudiante = u.id_usuario
                LEFT JOIN asignacion_academica aa ON aa.id_asignacion = mat.id_asignacion
                LEFT JOIN materia m ON m.id_materia = aa.id_materia
                LEFT JOIN curso c ON c.id_curso = aa.id_curso
                LEFT JOIN usuario d ON d.id_usuario = aa.id_docente
                WHERE u.id_rol = 5
                GROUP BY u.id_usuario, u.nombre, u.correo
                ORDER BY u.nombre
                """);
    }

    private void registrarHistorial(String accion) {
        DB.ejecutar("INSERT INTO historial_accion (id_usuario, accion, tabla_afectada) VALUES (?, ?, 'boletin')",
                Sesion.idUsuario, accion);
    }

    private JPanel columna() {
        JPanel p = new JPanel();
        p.setOpaque(false);
        p.setLayout(new BoxLayout(p, BoxLayout.Y_AXIS));
        return p;
    }

    private JPanel panelDashboard() {
        long totalEst = DB.contar("SELECT COUNT(*) FROM usuario WHERE id_rol=5");
        long totalBol = DB.contar("SELECT COUNT(*) FROM boletin");
        long totalMat = DB.contar("SELECT COUNT(*) FROM matricula");
        long totalHist = DB.contar("SELECT COUNT(*) FROM historial_accion");

        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("¡Bienvenido, " + Sesion.nombre + "!"));
        raiz.add(Estilos.crearGridStats(
                Estilos.crearStatCard(String.valueOf(totalEst), "Estudiantes registrados", Estilos.VERDE),
                Estilos.crearStatCard(String.valueOf(totalBol), "Boletines generados", Estilos.AZUL),
                Estilos.crearStatCard(String.valueOf(totalMat), "Matrículas", Estilos.NARANJA),
                Estilos.crearStatCard(String.valueOf(totalHist), "Acciones registradas", Estilos.AMBAR)
        ));
        raiz.add(Box.createVerticalStrut(16));

        LinkedHashMap<String, String> colsEst = new LinkedHashMap<>();
        colsEst.put("Nombre", "nombre"); colsEst.put("Correo", "correo"); colsEst.put("Materias", "materias_matriculadas");
        raiz.add(Estilos.crearTarjeta("Estudiantes (primeros 10)", Estilos.crearTabla(colsEst, estudiantes().stream().limit(10).toList())));
        raiz.add(Box.createVerticalStrut(10));

        LinkedHashMap<String, String> colsBol = new LinkedHashMap<>();
        colsBol.put("Estudiante", "estudiante"); colsBol.put("Periodo", "periodo"); colsBol.put("Fecha", "fecha");
        raiz.add(Estilos.crearTarjeta("Boletines recientes", Estilos.crearTabla(colsBol, boletines().stream().limit(10).toList())));
        return raiz;
    }

    private JPanel panelEstudiantes() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Listado de Estudiantes"));
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("#", "id_usuario"); cols.put("Nombre", "nombre"); cols.put("Correo", "correo"); cols.put("Materias matriculadas", "materias_matriculadas");
        raiz.add(Estilos.crearTabla(cols, estudiantes()));
        return raiz;
    }

    private JPanel panelBoletines() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Boletines Académicos"));

        List<Map<String, Object>> periodos = periodos();
        if (periodos.isEmpty()) {
            raiz.add(Estilos.crearAlertaInfo("Crea primero un periodo académico para poder generar boletines."));
            return raiz;
        }

        JComboBox<String> comboPeriodo = new JComboBox<>();
        for (Map<String, Object> periodo : periodos) {
            comboPeriodo.addItem(Estilos.texto(periodo, "nombre") + " · " + Estilos.texto(periodo, "anio"));
        }
        JPanel generador = new JPanel(new FlowLayout(FlowLayout.LEFT, 10, 0));
        generador.setOpaque(false);
        generador.add(etiqueta("Periodo:"));
        generador.add(comboPeriodo);
        BotonRedondeado generar = new BotonRedondeado("Generar boletines de todos", 10)
                .colores(Estilos.AMBAR, Estilos.aclarar(Estilos.AMBAR, 0.15));
        generar.addActionListener(e -> {
            int indice = comboPeriodo.getSelectedIndex();
            int idPeriodo = ((Number) periodos.get(indice).get("id_periodo")).intValue();
            List<Map<String, Object>> estudiantes = estudiantesParaBoletin();
            if (estudiantes.isEmpty()) {
                Dialogos.advertencia(this, "No hay estudiantes registrados para generar boletines.");
                return;
            }
            int creados = 0;
            try {
                for (Map<String, Object> estudiante : estudiantes) {
                    int idEstudiante = ((Number) estudiante.get("id_usuario")).intValue();
                    if (DB.generarBoletin(idEstudiante, idPeriodo)) creados++;
                }
                registrarHistorial("Generó " + creados + " boletines del periodo " + Estilos.texto(periodos.get(indice), "nombre"));
                Dialogos.exito(this, creados == 0
                        ? "Todos los boletines de este periodo ya estaban generados."
                        : "Se generaron " + creados + " boletines correctamente.");
                refrescarModuloActual();
            } catch (RuntimeException ex) {
                Dialogos.error(this, "No se pudieron generar los boletines:\n" + ex.getMessage());
            }
        });
        generador.add(generar);
        raiz.add(Estilos.crearTarjeta("Generación masiva", generador));
        raiz.add(Box.createVerticalStrut(12));

        LinkedHashMap<String, String> colsAcademico = new LinkedHashMap<>();
        colsAcademico.put("Estudiante", "estudiante");
        colsAcademico.put("Correo", "correo");
        colsAcademico.put("Clases asignadas", "clases");
        colsAcademico.put("Profesores", "profesores");
        raiz.add(Estilos.crearTarjeta("Asignación académica de estudiantes", Estilos.crearTabla(colsAcademico, resumenAcademico())));
        raiz.add(Box.createVerticalStrut(12));

        List<Map<String, Object>> b = boletines();
        if (b.isEmpty()) {
            raiz.add(Estilos.crearEmptyState("No hay boletines generados aún"));
            return raiz;
        }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("#", "id_boletin"); cols.put("Estudiante", "estudiante"); cols.put("Periodo", "periodo"); cols.put("Fecha de emisión", "fecha");
        raiz.add(Estilos.crearTarjeta("Boletines emitidos", Estilos.crearTabla(cols, b)));
        return raiz;
    }

    private JLabel etiqueta(String texto) {
        JLabel label = new JLabel(texto);
        label.setForeground(Estilos.TEXTO_SEC);
        label.setFont(Estilos.FUENTE_NEGRITA);
        return label;
    }

    private JPanel panelHistorial() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Historial del Sistema"));
        List<Map<String, Object>> h = historial();
        if (h.isEmpty()) { raiz.add(Estilos.crearEmptyState("Sin actividad registrada")); return raiz; }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("Usuario", "nombre"); cols.put("Acción", "accion"); cols.put("Tabla", "tabla_afectada"); cols.put("Fecha", "fecha");
        raiz.add(Estilos.crearTabla(cols, h));
        return raiz;
    }
}
