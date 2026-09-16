package com.sidino.ui;

import com.sidino.core.DB;
import com.sidino.core.Sesion;
import com.sidino.ui.componentes.Estilos;

import javax.swing.*;
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
        List<Map<String, Object>> b = boletines();
        if (b.isEmpty()) { raiz.add(Estilos.crearEmptyState("No hay boletines generados aún")); return raiz; }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("#", "id_boletin"); cols.put("Estudiante", "estudiante"); cols.put("Periodo", "periodo"); cols.put("Fecha de emisión", "fecha");
        raiz.add(Estilos.crearTabla(cols, b));
        return raiz;
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