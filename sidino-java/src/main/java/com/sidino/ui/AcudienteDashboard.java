package com.sidino.ui;

import com.sidino.core.DB;
import com.sidino.core.Sesion;
import com.sidino.ui.componentes.Estilos;

import javax.swing.*;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

public class AcudienteDashboard extends DashboardBase {

    public AcudienteDashboard() {
        super("Acudiente — Dashboard", "Acudiente — Seguimiento", Estilos.MORADO, List.of(
                new ItemNav("dashboard", "dashboard", "Dashboard"),
                new ItemNav("notas", "notas", "Notas del hijo/a"),
                new ItemNav("observador", "observador", "Observador"),
                new ItemNav("citaciones", "citaciones", "Citaciones"),
                new ItemNav("boletines", "boletines", "Boletines")
        ));
    }

    private int idAcud() { return Sesion.idUsuario; }

    @Override
    protected JPanel construirModulo(String id) {
        return switch (id) {
            case "dashboard" -> panelDashboard();
            case "notas" -> panelNotas();
            case "observador" -> panelObservador();
            case "citaciones" -> panelCitaciones();
            case "boletines" -> panelBoletines();
            default -> new JPanel();
        };
    }

    private List<Map<String, Object>> hijos() {
        return DB.query("""
            SELECT u.id_usuario, u.nombre, u.correo
            FROM acudiente_estudiante ae
            JOIN usuario u ON ae.id_estudiante = u.id_usuario
            WHERE ae.id_acudiente = ?
        """, idAcud());
    }

    private List<Integer> idsHijos(List<Map<String, Object>> hijos) {
        return hijos.stream().map(h -> ((Number) h.get("id_usuario")).intValue()).collect(Collectors.toList());
    }

    private String marcadores(int n) { return String.join(",", java.util.Collections.nCopies(n, "?")); }

    private List<Map<String, Object>> notasHijos(List<Integer> ids) {
        if (ids.isEmpty()) return List.of();
        return DB.query("""
            SELECT n.valor, n.tipo, n.porcentaje, n.fecha, m.nombre AS materia, c.nombre AS curso, u.nombre AS estudiante
            FROM nota n
            JOIN matricula mat ON n.id_matricula = mat.id_matricula
            JOIN usuario u ON mat.id_estudiante = u.id_usuario
            JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
            JOIN materia m ON aa.id_materia = m.id_materia
            JOIN curso c ON aa.id_curso = c.id_curso
            WHERE mat.id_estudiante IN (""" + marcadores(ids.size()) + ")\nORDER BY u.nombre, n.fecha DESC",
            ids.toArray());
    }

    private List<Map<String, Object>> observacionesHijos(List<Integer> ids) {
        if (ids.isEmpty()) return List.of();
        return DB.query("""
            SELECT o.descripcion, o.fecha, u.nombre AS estudiante, d.nombre AS docente, m.nombre AS materia
            FROM observador o
            JOIN usuario u ON o.id_estudiante = u.id_usuario
            JOIN asignacion_academica aa ON o.id_asignacion = aa.id_asignacion
            JOIN usuario d ON aa.id_docente = d.id_usuario
            JOIN materia m ON aa.id_materia = m.id_materia
            WHERE o.id_estudiante IN (""" + marcadores(ids.size()) + ")\nORDER BY o.fecha DESC",
            ids.toArray());
    }

    private List<Map<String, Object>> citacionesHijos(List<Integer> ids) {
        if (ids.isEmpty()) return List.of();
        return DB.query("""
            SELECT ci.motivo, ci.fecha, u.nombre AS estudiante
            FROM citacion ci JOIN usuario u ON ci.id_estudiante = u.id_usuario
            WHERE ci.id_estudiante IN (""" + marcadores(ids.size()) + ")\nORDER BY ci.fecha DESC",
            ids.toArray());
    }

    private List<Map<String, Object>> boletinesHijos(List<Integer> ids) {
        if (ids.isEmpty()) return List.of();
        return DB.query("""
            SELECT b.id_boletin, b.fecha, u.nombre AS estudiante, p.nombre AS periodo
            FROM boletin b
            JOIN usuario u ON b.id_estudiante = u.id_usuario
            JOIN periodo_academico p ON b.id_periodo = p.id_periodo
            WHERE b.id_estudiante IN (""" + marcadores(ids.size()) + ")\nORDER BY b.fecha DESC",
            ids.toArray());
    }

    private JPanel columna() {
        JPanel p = new JPanel();
        p.setOpaque(false);
        p.setLayout(new BoxLayout(p, BoxLayout.Y_AXIS));
        return p;
    }

    private JPanel panelDashboard() {
        List<Map<String, Object>> hijos = hijos();
        List<Integer> ids = idsHijos(hijos);
        List<Map<String, Object>> notas = notasHijos(ids);
        List<Map<String, Object>> citas = citacionesHijos(ids);
        List<Map<String, Object>> obs = observacionesHijos(ids);

        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("¡Hola, " + Sesion.nombre + "!"));

        if (hijos.isEmpty()) {
            raiz.add(Estilos.crearAlertaInfo("No tienes estudiantes vinculados. Comunícate con administración."));
            return raiz;
        }

        raiz.add(Estilos.crearGridStats(
                Estilos.crearStatCard(String.valueOf(hijos.size()), "Estudiante(s) a cargo", Estilos.MORADO),
                Estilos.crearStatCard(String.valueOf(notas.size()), "Notas registradas", Estilos.NARANJA),
                Estilos.crearStatCard(String.valueOf(citas.size()), "Citaciones", Estilos.ROJO),
                Estilos.crearStatCard(String.valueOf(obs.size()), "Observaciones", Estilos.AZUL)
        ));
        raiz.add(Box.createVerticalStrut(16));

        for (Map<String, Object> hijo : hijos) {
            String nombreHijo = Estilos.texto(hijo, "nombre");
            List<Map<String, Object>> promHijo = DB.query("""
                SELECT m.nombre AS materia,
                       ROUND(AVG(n.valor * n.porcentaje / 100) / AVG(n.porcentaje / 100), 2) AS promedio
                FROM nota n
                JOIN matricula mat ON n.id_matricula = mat.id_matricula
                JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
                JOIN materia m ON aa.id_materia = m.id_materia
                WHERE mat.id_estudiante = ?
                GROUP BY m.id_materia ORDER BY m.nombre
            """, hijo.get("id_usuario"));

            JPanel cuerpo = new JPanel();
            cuerpo.setOpaque(false);
            cuerpo.setLayout(new BoxLayout(cuerpo, BoxLayout.Y_AXIS));
            if (promHijo.isEmpty()) {
                cuerpo.add(Estilos.crearEmptyState("Sin notas aún"));
            } else {
                for (Map<String, Object> p : promHijo) {
                    double val = Estilos.numero(p, "promedio");
                    cuerpo.add(Estilos.crearListItem(Estilos.texto(p, "materia"), "Promedio", Estilos.formatearNumero(val, 1), Estilos.colorNota(val)));
                    cuerpo.add(Box.createVerticalStrut(4));
                }
            }
            raiz.add(Estilos.crearTarjeta(nombreHijo, cuerpo));
            raiz.add(Box.createVerticalStrut(10));
        }
        return raiz;
    }

    private JPanel panelNotas() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Notas de mi hijo/a"));
        List<Map<String, Object>> notas = notasHijos(idsHijos(hijos()));
        if (notas.isEmpty()) { raiz.add(Estilos.crearEmptyState("No hay notas registradas aún")); return raiz; }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("Estudiante", "estudiante"); cols.put("Materia", "materia"); cols.put("Curso", "curso");
        cols.put("Tipo", "tipo"); cols.put("Nota", "valor"); cols.put("%", "porcentaje"); cols.put("Fecha", "fecha");
        raiz.add(Estilos.crearTabla(cols, notas));
        return raiz;
    }

    private JPanel panelObservador() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Observador"));
        List<Map<String, Object>> obs = observacionesHijos(idsHijos(hijos()));
        if (obs.isEmpty()) { raiz.add(Estilos.crearEmptyState("Sin observaciones registradas \uD83C\uDF89")); return raiz; }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("Estudiante", "estudiante"); cols.put("Materia", "materia"); cols.put("Docente", "docente");
        cols.put("Descripción", "descripcion"); cols.put("Fecha", "fecha");
        raiz.add(Estilos.crearTabla(cols, obs));
        return raiz;
    }

    private JPanel panelCitaciones() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Citaciones"));
        List<Map<String, Object>> c = citacionesHijos(idsHijos(hijos()));
        if (c.isEmpty()) { raiz.add(Estilos.crearEmptyState("No hay citaciones registradas")); return raiz; }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("Estudiante", "estudiante"); cols.put("Motivo", "motivo"); cols.put("Fecha", "fecha");
        raiz.add(Estilos.crearTabla(cols, c));
        return raiz;
    }

    private JPanel panelBoletines() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Boletines"));
        List<Map<String, Object>> b = boletinesHijos(idsHijos(hijos()));
        if (b.isEmpty()) { raiz.add(Estilos.crearEmptyState("No hay boletines generados aún")); return raiz; }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("Estudiante", "estudiante"); cols.put("Periodo", "periodo"); cols.put("Fecha", "fecha");
        raiz.add(Estilos.crearTabla(cols, b));
        return raiz;
    }
}
