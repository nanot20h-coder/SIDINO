package com.sidino.ui;

import com.sidino.core.DB;
import com.sidino.core.Sesion;
import com.sidino.ui.componentes.Estilos;

import javax.swing.*;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

public class CoordinadorDashboard extends DashboardBase {

    public CoordinadorDashboard() {
        super("Coordinador — Dashboard", "Coordinación — Panel de Supervisión", Estilos.NARANJA, List.of(
                new ItemNav("dashboard", "dashboard", "Dashboard"),
                new ItemNav("observador", "observador", "Observador"),
                new ItemNav("citaciones", "citaciones", "Citaciones"),
                new ItemNav("docentes", "docentes", "Docentes"),
                new ItemNav("reportes", "reportes", "Reportes")
        ));
    }

    @Override
    protected JPanel construirModulo(String id) {
        return switch (id) {
            case "dashboard" -> panelDashboard();
            case "observador" -> panelObservador();
            case "citaciones" -> panelCitaciones();
            case "docentes" -> panelDocentes();
            case "reportes" -> panelReportes();
            default -> new JPanel();
        };
    }

    private List<Map<String, Object>> observaciones() {
        return DB.query("""
            SELECT o.descripcion, o.fecha, u.nombre AS estudiante, d.nombre AS docente, c.nombre AS curso
            FROM observador o
            JOIN usuario u ON o.id_estudiante = u.id_usuario
            JOIN asignacion_academica aa ON o.id_asignacion = aa.id_asignacion
            JOIN usuario d ON aa.id_docente = d.id_usuario
            JOIN curso c ON aa.id_curso = c.id_curso
            ORDER BY o.fecha DESC LIMIT 30
        """);
    }

    private List<Map<String, Object>> citaciones() {
        return DB.query("""
            SELECT ci.motivo, ci.fecha, u.nombre AS estudiante
            FROM citacion ci JOIN usuario u ON ci.id_estudiante = u.id_usuario
            ORDER BY ci.fecha DESC LIMIT 30
        """);
    }

    private List<Map<String, Object>> docentes() {
        return DB.query("""
            SELECT u.nombre, u.correo, COUNT(aa.id_asignacion) AS clases
            FROM usuario u
            LEFT JOIN asignacion_academica aa ON u.id_usuario = aa.id_docente
            WHERE u.id_rol = 4
            GROUP BY u.id_usuario ORDER BY u.nombre
        """);
    }

    private JPanel columna() {
        JPanel p = new JPanel();
        p.setOpaque(false);
        p.setLayout(new BoxLayout(p, BoxLayout.Y_AXIS));
        return p;
    }

    private JPanel panelDashboard() {
        long totalObs = DB.contar("SELECT COUNT(*) FROM observador");
        long totalCit = DB.contar("SELECT COUNT(*) FROM citacion");
        long totalEst = DB.contar("SELECT COUNT(*) FROM usuario WHERE id_rol=5");
        long totalDoc = DB.contar("SELECT COUNT(*) FROM usuario WHERE id_rol=4");

        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("¡Hola, " + Sesion.nombre + "!"));
        raiz.add(Estilos.crearGridStats(
                Estilos.crearStatCard(String.valueOf(totalEst), "Estudiantes", Estilos.VERDE),
                Estilos.crearStatCard(String.valueOf(totalDoc), "Docentes", Estilos.AZUL),
                Estilos.crearStatCard(String.valueOf(totalObs), "Observaciones", Estilos.NARANJA),
                Estilos.crearStatCard(String.valueOf(totalCit), "Citaciones", Estilos.ROJO)
        ));
        raiz.add(Box.createVerticalStrut(16));

        LinkedHashMap<String, String> colsObs = new LinkedHashMap<>();
        colsObs.put("Estudiante", "estudiante"); colsObs.put("Curso", "curso"); colsObs.put("Descripción", "descripcion"); colsObs.put("Fecha", "fecha");
        raiz.add(Estilos.crearTarjeta("Observaciones recientes", Estilos.crearTabla(colsObs, observaciones().stream().limit(8).toList())));
        raiz.add(Box.createVerticalStrut(10));

        LinkedHashMap<String, String> colsCit = new LinkedHashMap<>();
        colsCit.put("Estudiante", "estudiante"); colsCit.put("Motivo", "motivo"); colsCit.put("Fecha", "fecha");
        raiz.add(Estilos.crearTarjeta("Citaciones recientes", Estilos.crearTabla(colsCit, citaciones().stream().limit(8).toList())));
        return raiz;
    }

    private JPanel panelObservador() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Observador Estudiantil"));
        List<Map<String, Object>> o = observaciones();
        if (o.isEmpty()) { raiz.add(Estilos.crearEmptyState("No hay observaciones registradas")); return raiz; }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("Estudiante", "estudiante"); cols.put("Curso", "curso"); cols.put("Docente", "docente");
        cols.put("Descripción", "descripcion"); cols.put("Fecha", "fecha");
        raiz.add(Estilos.crearTabla(cols, o));
        return raiz;
    }

    private JPanel panelCitaciones() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Citaciones"));
        List<Map<String, Object>> c = citaciones();
        if (c.isEmpty()) { raiz.add(Estilos.crearEmptyState("No hay citaciones registradas")); return raiz; }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("Estudiante", "estudiante"); cols.put("Motivo", "motivo"); cols.put("Fecha", "fecha");
        raiz.add(Estilos.crearTabla(cols, c));
        return raiz;
    }

    private JPanel panelDocentes() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Docentes"));
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("Nombre", "nombre"); cols.put("Correo", "correo"); cols.put("Clases asignadas", "clases");
        raiz.add(Estilos.crearTabla(cols, docentes()));
        return raiz;
    }

    private JPanel panelReportes() {
        long totalObs = DB.contar("SELECT COUNT(*) FROM observador");
        long totalCit = DB.contar("SELECT COUNT(*) FROM citacion");
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Reportes de Coordinación"));
        raiz.add(Estilos.crearGridStats(
                Estilos.crearStatCard(String.valueOf(totalObs), "Total observaciones", Estilos.NARANJA),
                Estilos.crearStatCard(String.valueOf(totalCit), "Total citaciones", Estilos.ROJO)
        ));
        raiz.add(Box.createVerticalStrut(16));
        raiz.add(Estilos.crearAlertaInfo("Los reportes detallados estarán disponibles cuando se registren más datos en el sistema."));
        return raiz;
    }
}
