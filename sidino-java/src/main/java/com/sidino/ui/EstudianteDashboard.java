package com.sidino.ui;

import com.sidino.core.DB;
import com.sidino.core.Sesion;
import com.sidino.ui.componentes.Estilos;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

public class EstudianteDashboard extends DashboardBase {

    public EstudianteDashboard() {
        super("Estudiante — Dashboard", "Estudiante — Mi Panel", Estilos.VERDE, List.of(
                new ItemNav("dashboard", "dashboard", "Dashboard"),
                new ItemNav("notas", "notas", "Mis notas"),
                new ItemNav("horario", "horario", "Horario"),
                new ItemNav("boletines", "boletines", "Boletines"),
                new ItemNav("contenido", "contenido", "Materiales"),
                new ItemNav("observador", "observador", "Observador")
        ));
    }

    private int idEst() { return Sesion.idUsuario; }

    @Override
    protected JPanel construirModulo(String id) {
        return switch (id) {
            case "dashboard" -> panelDashboard();
            case "notas" -> panelNotas();
            case "horario" -> panelHorario();
            case "boletines" -> panelBoletines();
            case "contenido" -> panelContenido();
            case "observador" -> panelObservador();
            default -> new JPanel();
        };
    }

    private List<Map<String, Object>> matriculas() {
        return DB.query("""
            SELECT mat.id_matricula, m.nombre AS materia, c.nombre AS curso,
                   u.nombre AS docente, h.dia, h.hora_inicio, h.hora_fin,
                   s.nombre AS salon, p.nombre AS periodo
            FROM matricula mat
            JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
            JOIN materia m ON aa.id_materia = m.id_materia
            JOIN curso c ON aa.id_curso = c.id_curso
            JOIN usuario u ON aa.id_docente = u.id_usuario
            JOIN horario h ON aa.id_horario = h.id_horario
            JOIN salon s ON aa.id_salon = s.id_salon
            JOIN periodo_academico p ON aa.id_periodo = p.id_periodo
            WHERE mat.id_estudiante = ?
            ORDER BY m.nombre
        """, idEst());
    }

    private List<Map<String, Object>> notas() {
        return DB.query("""
            SELECT n.valor, n.tipo, n.porcentaje, n.fecha, m.nombre AS materia, c.nombre AS curso
            FROM nota n
            JOIN matricula mat ON n.id_matricula = mat.id_matricula
            JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
            JOIN materia m ON aa.id_materia = m.id_materia
            JOIN curso c ON aa.id_curso = c.id_curso
            WHERE mat.id_estudiante = ?
            ORDER BY n.fecha DESC
        """, idEst());
    }

    private List<Map<String, Object>> promedios() {
        return DB.query("""
            SELECT m.nombre AS materia,
                   ROUND(AVG(n.valor * n.porcentaje / 100) / AVG(n.porcentaje / 100), 2) AS promedio,
                   COUNT(n.id_nota) AS total_notas
            FROM nota n
            JOIN matricula mat ON n.id_matricula = mat.id_matricula
            JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
            JOIN materia m ON aa.id_materia = m.id_materia
            WHERE mat.id_estudiante = ?
            GROUP BY m.id_materia
            ORDER BY m.nombre
        """, idEst());
    }

    private List<Map<String, Object>> boletines() {
        return DB.query("""
            SELECT b.id_boletin, b.fecha, p.nombre AS periodo
            FROM boletin b JOIN periodo_academico p ON b.id_periodo = p.id_periodo
            WHERE b.id_estudiante = ?
            ORDER BY b.fecha DESC
        """, idEst());
    }

    private List<Map<String, Object>> observaciones() {
        return DB.query("""
            SELECT o.descripcion, o.fecha, d.nombre AS docente, m.nombre AS materia
            FROM observador o
            JOIN asignacion_academica aa ON o.id_asignacion = aa.id_asignacion
            JOIN usuario d ON aa.id_docente = d.id_usuario
            JOIN materia m ON aa.id_materia = m.id_materia
            WHERE o.id_estudiante = ?
            ORDER BY o.fecha DESC
        """, idEst());
    }

    private List<Map<String, Object>> contenidos() {
        return DB.query("""
            SELECT co.titulo, co.descripcion, m.nombre AS materia, u.nombre AS docente
            FROM contenido co
            JOIN asignacion_academica aa ON co.id_asignacion = aa.id_asignacion
            JOIN materia m ON aa.id_materia = m.id_materia
            JOIN usuario u ON aa.id_docente = u.id_usuario
            WHERE aa.id_asignacion IN (SELECT id_asignacion FROM matricula WHERE id_estudiante = ?)
            ORDER BY co.id_contenido DESC
        """, idEst());
    }

    private JPanel columna() {
        JPanel p = new JPanel();
        p.setOpaque(false);
        p.setLayout(new BoxLayout(p, BoxLayout.Y_AXIS));
        return p;
    }

    private JPanel panelDashboard() {
        List<Map<String, Object>> proms = promedios();
        List<Map<String, Object>> contns = contenidos();
        double promedioGeneral = proms.stream().mapToDouble(p -> Estilos.numero(p, "promedio")).average().orElse(0);

        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("¡Hola, " + Sesion.nombre + "!"));

        JPanel stats = Estilos.crearGridStats(
                Estilos.crearStatCard(String.valueOf(matriculas().size()), "Materias matriculadas", Estilos.AZUL),
                Estilos.crearStatCard(promedioGeneral > 0 ? Estilos.formatearNumero(promedioGeneral, 1) : "—",
                        "Promedio general", Estilos.colorNota(promedioGeneral)),
                Estilos.crearStatCard(String.valueOf(boletines().size()), "Boletines", Estilos.NARANJA),
                Estilos.crearStatCard(String.valueOf(contns.size()), "Materiales disponibles", Estilos.MORADO)
        );
        raiz.add(stats);
        raiz.add(Box.createVerticalStrut(16));

        JPanel promContenido = new JPanel();
        promContenido.setOpaque(false);
        promContenido.setLayout(new BoxLayout(promContenido, BoxLayout.Y_AXIS));
        if (proms.isEmpty()) {
            promContenido.add(Estilos.crearEmptyState("Aún no tienes notas registradas"));
        } else {
            for (Map<String, Object> p : proms) {
                double val = Estilos.numero(p, "promedio");
                promContenido.add(Estilos.crearListItem(Estilos.texto(p, "materia"),
                        Estilos.numero(p, "total_notas") + " notas", Estilos.formatearNumero(val, 1), Estilos.colorNota(val)));
                promContenido.add(Box.createVerticalStrut(4));
            }
        }
        raiz.add(Estilos.crearTarjeta("Promedios por materia", promContenido));
        return raiz;
    }

    private JPanel panelNotas() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Mis Notas"));
        List<Map<String, Object>> notas = notas();
        if (notas.isEmpty()) {
            raiz.add(Estilos.crearEmptyState("No tienes notas registradas aún"));
            return raiz;
        }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("Materia", "materia"); cols.put("Curso", "curso"); cols.put("Tipo", "tipo");
        cols.put("Nota", "valor"); cols.put("%", "porcentaje"); cols.put("Fecha", "fecha");
        raiz.add(Estilos.crearTabla(cols, notas));
        return raiz;
    }

    private JPanel panelHorario() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Mi Horario"));
        List<Map<String, Object>> mats = matriculas();
        if (mats.isEmpty()) {
            raiz.add(Estilos.crearEmptyState("No tienes clases matriculadas"));
            return raiz;
        }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("Materia", "materia"); cols.put("Docente", "docente"); cols.put("Curso", "curso");
        cols.put("Salón", "salon"); cols.put("Día", "dia"); cols.put("Hora inicio", "hora_inicio"); cols.put("Hora fin", "hora_fin");
        raiz.add(Estilos.crearTabla(cols, mats));
        return raiz;
    }

    private JPanel panelBoletines() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Mis Boletines"));
        List<Map<String, Object>> b = boletines();
        if (b.isEmpty()) {
            raiz.add(Estilos.crearEmptyState("No hay boletines generados aún"));
            return raiz;
        }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("#", "id_boletin"); cols.put("Periodo", "periodo"); cols.put("Fecha de emisión", "fecha");
        raiz.add(Estilos.crearTabla(cols, b));
        return raiz;
    }

    private JPanel panelContenido() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Materiales y Contenido"));
        List<Map<String, Object>> c = contenidos();
        if (c.isEmpty()) {
            raiz.add(Estilos.crearEmptyState("No hay materiales disponibles aún"));
            return raiz;
        }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("Título", "titulo"); cols.put("Materia", "materia"); cols.put("Docente", "docente"); cols.put("Descripción", "descripcion");
        raiz.add(Estilos.crearTabla(cols, c));
        return raiz;
    }

    private JPanel panelObservador() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Mi Observador"));
        List<Map<String, Object>> o = observaciones();
        if (o.isEmpty()) {
            raiz.add(Estilos.crearEmptyState("No tienes observaciones registradas \uD83C\uDF89"));
            return raiz;
        }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("Materia", "materia"); cols.put("Docente", "docente"); cols.put("Descripción", "descripcion"); cols.put("Fecha", "fecha");
        raiz.add(Estilos.crearTabla(cols, o));
        return raiz;
    }
}
