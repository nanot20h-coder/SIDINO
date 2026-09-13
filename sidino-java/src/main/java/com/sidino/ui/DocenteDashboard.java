package com.sidino.ui;

import com.sidino.core.DB;
import com.sidino.core.Sesion;
import com.sidino.ui.componentes.BotonRedondeado;
import com.sidino.ui.componentes.Dialogos;
import com.sidino.ui.componentes.Estilos;

import javax.swing.*;
import java.awt.*;
import java.time.LocalDate;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

public class DocenteDashboard extends DashboardBase {

    public DocenteDashboard() {
        super("Docente — Dashboard", "Docente — Mi Panel", Estilos.AZUL, List.of(
                new ItemNav("dashboard", "dashboard", "Dashboard"),
                new ItemNav("horario", "horario", "Mis clases"),
                new ItemNav("notas", "notas", "Registrar notas"),
                new ItemNav("observador", "observador", "Observador"),
                new ItemNav("contenido", "contenido", "Contenido")
        ));
    }

    private int idDocente() { return Sesion.idUsuario; }

    @Override
    protected JPanel construirModulo(String id) {
        return switch (id) {
            case "dashboard" -> panelDashboard();
            case "horario" -> panelHorario();
            case "notas" -> panelNotas();
            case "observador" -> panelObservador();
            case "contenido" -> panelContenido();
            default -> new JPanel();
        };
    }

    private List<Map<String, Object>> asignaciones() {
        return DB.query("""
            SELECT aa.id_asignacion, m.nombre AS materia, c.nombre AS curso,
                   s.nombre AS salon, h.dia, h.hora_inicio, h.hora_fin, p.nombre AS periodo
            FROM asignacion_academica aa
            JOIN materia m ON aa.id_materia = m.id_materia
            JOIN curso c ON aa.id_curso = c.id_curso
            JOIN salon s ON aa.id_salon = s.id_salon
            JOIN horario h ON aa.id_horario = h.id_horario
            JOIN periodo_academico p ON aa.id_periodo = p.id_periodo
            WHERE aa.id_docente = ?
            ORDER BY h.dia, h.hora_inicio
        """, idDocente());
    }

    private List<Integer> idsAsig() {
        return asignaciones().stream().map(a -> ((Number) a.get("id_asignacion")).intValue()).toList();
    }

    private String marcadores(int n) { return String.join(",", java.util.Collections.nCopies(n, "?")); }

    private List<Map<String, Object>> notas() {
        List<Integer> ids = idsAsig();
        if (ids.isEmpty()) return List.of();
        return DB.query("""
            SELECT n.id_nota, n.valor, n.tipo, n.porcentaje, n.fecha, u.nombre AS estudiante, m.nombre AS materia, c.nombre AS curso
            FROM nota n
            JOIN matricula mat ON n.id_matricula = mat.id_matricula
            JOIN usuario u ON mat.id_estudiante = u.id_usuario
            JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
            JOIN materia m ON aa.id_materia = m.id_materia
            JOIN curso c ON aa.id_curso = c.id_curso
            WHERE aa.id_docente = ?
            ORDER BY n.fecha DESC LIMIT 50
        """, idDocente());
    }

    private List<Map<String, Object>> observaciones() {
        List<Integer> ids = idsAsig();
        if (ids.isEmpty()) return List.of();
        return DB.query("""
            SELECT o.descripcion, o.fecha, u.nombre AS estudiante, c.nombre AS curso
            FROM observador o
            JOIN usuario u ON o.id_estudiante = u.id_usuario
            JOIN asignacion_academica aa ON o.id_asignacion = aa.id_asignacion
            JOIN curso c ON aa.id_curso = c.id_curso
            WHERE o.id_asignacion IN (""" + marcadores(ids.size()) + ")\nORDER BY o.fecha DESC LIMIT 30",
            ids.toArray());
    }

    private List<Map<String, Object>> contenidos() {
        List<Integer> ids = idsAsig();
        if (ids.isEmpty()) return List.of();
        return DB.query("""
            SELECT co.titulo, co.descripcion, m.nombre AS materia
            FROM contenido co
            JOIN asignacion_academica aa ON co.id_asignacion = aa.id_asignacion
            JOIN materia m ON aa.id_materia = m.id_materia
            WHERE co.id_asignacion IN (""" + marcadores(ids.size()) + ")\nORDER BY co.id_contenido DESC LIMIT 30",
            ids.toArray());
    }

    private List<Map<String, Object>> matriculasParaFormulario() {
        List<Integer> ids = idsAsig();
        if (ids.isEmpty()) return List.of();
        return DB.query("""
            SELECT mat.id_matricula, u.nombre AS estudiante, m.nombre AS materia, c.nombre AS curso
            FROM matricula mat
            JOIN usuario u ON mat.id_estudiante = u.id_usuario
            JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
            JOIN materia m ON aa.id_materia = m.id_materia
            JOIN curso c ON aa.id_curso = c.id_curso
            WHERE mat.id_asignacion IN (""" + marcadores(ids.size()) + ")\nORDER BY c.nombre, u.nombre",
            ids.toArray());
    }

    private List<Map<String, Object>> estudiantesDeAsignacion(int idAsignacion) {
        return DB.query("""
            SELECT u.id_usuario, u.nombre
            FROM matricula mat JOIN usuario u ON mat.id_estudiante = u.id_usuario
            WHERE mat.id_asignacion = ? ORDER BY u.nombre
        """, idAsignacion);
    }

    private JPanel columna() {
        JPanel p = new JPanel();
        p.setOpaque(false);
        p.setLayout(new BoxLayout(p, BoxLayout.Y_AXIS));
        return p;
    }

    private JPanel panelDashboard() {
        List<Map<String, Object>> asig = asignaciones();
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("¡Hola, " + Sesion.nombre + "!"));
        raiz.add(Estilos.crearGridStats(
                Estilos.crearStatCard(String.valueOf(asig.size()), "Clases asignadas", Estilos.AZUL),
                Estilos.crearStatCard(String.valueOf(notas().size()), "Notas registradas", Estilos.VERDE),
                Estilos.crearStatCard(String.valueOf(observaciones().size()), "Observaciones", Estilos.NARANJA),
                Estilos.crearStatCard(String.valueOf(contenidos().size()), "Materiales subidos", Estilos.MORADO)
        ));
        return raiz;
    }

    private JPanel panelHorario() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Mis Clases"));
        List<Map<String, Object>> a = asignaciones();
        if (a.isEmpty()) { raiz.add(Estilos.crearEmptyState("No tienes clases asignadas")); return raiz; }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("Materia", "materia"); cols.put("Curso", "curso"); cols.put("Salón", "salon");
        cols.put("Día", "dia"); cols.put("Hora inicio", "hora_inicio"); cols.put("Hora fin", "hora_fin"); cols.put("Periodo", "periodo");
        raiz.add(Estilos.crearTabla(cols, a));
        return raiz;
    }

    private JPanel panelNotas() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Registrar Notas"));

        List<Map<String, Object>> matriculas = matriculasParaFormulario();
        if (!matriculas.isEmpty()) {
            JPanel form = new JPanel(new GridBagLayout());
            form.setOpaque(false);
            GridBagConstraints gc = new GridBagConstraints();
            gc.insets = new Insets(4, 4, 4, 4);
            gc.gridy = 0; gc.gridx = 0; gc.anchor = GridBagConstraints.WEST;

            JComboBox<String> comboMatricula = new JComboBox<>();
            comboMatricula.addItem("— Selecciona —");
            for (Map<String, Object> m : matriculas) {
                comboMatricula.addItem(Estilos.texto(m, "estudiante") + " · " + Estilos.texto(m, "materia") + " (" + Estilos.texto(m, "curso") + ")");
            }
            JTextField campoValor = new JTextField(5);
            JTextField campoTipo = new JTextField(14);
            JTextField campoPorcentaje = new JTextField(5);
            JTextField campoFecha = new JTextField(LocalDate.now().toString(), 10);

            form.add(etiqueta("Estudiante / Materia:"), gc);
            gc.gridx = 1; form.add(comboMatricula, gc);
            gc.gridx = 2; form.add(etiqueta("Nota (0-5):"), gc);
            gc.gridx = 3; form.add(campoValor, gc);

            gc.gridx = 0; gc.gridy = 1; form.add(etiqueta("Tipo:"), gc);
            gc.gridx = 1; form.add(campoTipo, gc);
            gc.gridx = 2; form.add(etiqueta("Porcentaje:"), gc);
            gc.gridx = 3; form.add(campoPorcentaje, gc);

            gc.gridx = 0; gc.gridy = 2; form.add(etiqueta("Fecha (AAAA-MM-DD):"), gc);
            gc.gridx = 1; form.add(campoFecha, gc);

            BotonRedondeado guardar = new BotonRedondeado("Guardar nota", 10).colores(Estilos.AZUL, Estilos.AZUL.brighter());
            guardar.addActionListener(e -> {
                int idx = comboMatricula.getSelectedIndex();
                if (idx <= 0) { Dialogos.advertencia(this, "Selecciona un estudiante/materia."); return; }
                try {
                    double valor = Double.parseDouble(campoValor.getText().trim().replace(',', '.'));
                    double porcentaje = Double.parseDouble(campoPorcentaje.getText().trim());
                    String tipo = campoTipo.getText().trim();
                    String fecha = campoFecha.getText().trim();
                    if (valor < 0 || valor > 5 || tipo.isEmpty() || porcentaje <= 0 || fecha.isEmpty()) {
                        Dialogos.advertencia(this, "Verifica los datos. La nota debe estar entre 0 y 5.");
                        return;
                    }
                    long idMatricula = ((Number) matriculas.get(idx - 1).get("id_matricula")).longValue();
                    DB.ejecutar("INSERT INTO nota (id_matricula, valor, tipo, porcentaje, fecha) VALUES (?,?,?,?,?)",
                            idMatricula, valor, tipo, porcentaje, fecha);
                    Dialogos.exito(this, "Nota registrada correctamente.");
                    mostrarModulo("notas");
                } catch (NumberFormatException ex) {
                    Dialogos.error(this, "Nota y porcentaje deben ser numéricos.");
                }
            });
            gc.gridx = 0; gc.gridy = 3; gc.gridwidth = 2;
            form.add(guardar, gc);

            raiz.add(Estilos.crearTarjeta("Nueva nota académica", form));
            raiz.add(Box.createVerticalStrut(12));
        }

        List<Map<String, Object>> notas = notas();
        if (notas.isEmpty()) {
            raiz.add(Estilos.crearEmptyState("No has registrado notas aún"));
        } else {
            LinkedHashMap<String, String> cols = new LinkedHashMap<>();
            cols.put("Estudiante", "estudiante"); cols.put("Materia", "materia"); cols.put("Curso", "curso");
            cols.put("Tipo", "tipo"); cols.put("Nota", "valor"); cols.put("%", "porcentaje"); cols.put("Fecha", "fecha");
            raiz.add(Estilos.crearTarjeta("Historial de notas calificadas", Estilos.crearTabla(cols, notas)));
        }
        return raiz;
    }

    private JPanel panelObservador() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Observador Estudiantil"));

        List<Map<String, Object>> asig = asignaciones();
        if (!asig.isEmpty()) {
            JPanel form = new JPanel(new GridBagLayout());
            form.setOpaque(false);
            GridBagConstraints gc = new GridBagConstraints();
            gc.insets = new Insets(4, 4, 4, 4);
            gc.anchor = GridBagConstraints.WEST;

            JComboBox<String> comboClase = new JComboBox<>();
            comboClase.addItem("— Selecciona clase —");
            for (Map<String, Object> a : asig) {
                comboClase.addItem(Estilos.texto(a, "materia") + " — " + Estilos.texto(a, "curso"));
            }
            JComboBox<String> comboEstudiante = new JComboBox<>();
            comboEstudiante.addItem("— Selecciona clase primero —");
            JTextField campoFecha = new JTextField(LocalDate.now().toString(), 10);
            JTextArea campoDescripcion = new JTextArea(3, 30);
            campoDescripcion.setLineWrap(true);
            campoDescripcion.setWrapStyleWord(true);

            comboClase.addActionListener(e -> {
                comboEstudiante.removeAllItems();
                comboEstudiante.addItem("— Selecciona —");
                int idx = comboClase.getSelectedIndex();
                if (idx > 0) {
                    int idAsignacion = ((Number) asig.get(idx - 1).get("id_asignacion")).intValue();
                    for (Map<String, Object> est : estudiantesDeAsignacion(idAsignacion)) {
                        comboEstudiante.addItem(Estilos.texto(est, "nombre"));
                    }
                }
            });

            gc.gridx = 0; gc.gridy = 0; form.add(etiqueta("Clase:"), gc);
            gc.gridx = 1; form.add(comboClase, gc);
            gc.gridx = 2; form.add(etiqueta("Fecha:"), gc);
            gc.gridx = 3; form.add(campoFecha, gc);

            gc.gridx = 0; gc.gridy = 1; form.add(etiqueta("Estudiante:"), gc);
            gc.gridx = 1; gc.gridwidth = 3; form.add(comboEstudiante, gc); gc.gridwidth = 1;

            gc.gridx = 0; gc.gridy = 2; form.add(etiqueta("Descripción:"), gc);
            gc.gridx = 1; gc.gridy = 2; gc.gridwidth = 3; form.add(new JScrollPane(campoDescripcion), gc); gc.gridwidth = 1;

            BotonRedondeado guardar = new BotonRedondeado("Guardar observación", 10).colores(Estilos.AZUL, Estilos.AZUL.brighter());
            guardar.addActionListener(e -> {
                int idxClase = comboClase.getSelectedIndex();
                int idxEst = comboEstudiante.getSelectedIndex();
                String descripcion = campoDescripcion.getText().trim();
                String fecha = campoFecha.getText().trim();
                if (idxClase <= 0 || idxEst <= 0 || descripcion.isEmpty() || fecha.isEmpty()) {
                    Dialogos.advertencia(this, "Completa todos los campos obligatorios del observador.");
                    return;
                }
                int idAsignacion = ((Number) asig.get(idxClase - 1).get("id_asignacion")).intValue();
                List<Map<String, Object>> estudiantes = estudiantesDeAsignacion(idAsignacion);
                int idEstudiante = ((Number) estudiantes.get(idxEst - 1).get("id_usuario")).intValue();
                DB.ejecutar("INSERT INTO observador (id_estudiante, id_asignacion, descripcion, fecha) VALUES (?,?,?,?)",
                        idEstudiante, idAsignacion, descripcion, fecha);
                Dialogos.exito(this, "Observación registrada con éxito.");
                mostrarModulo("observador");
            });
            gc.gridx = 0; gc.gridy = 3; gc.gridwidth = 2;
            form.add(guardar, gc);

            raiz.add(Estilos.crearTarjeta("Nueva observación convivencial", form));
            raiz.add(Box.createVerticalStrut(12));
        }

        List<Map<String, Object>> obs = observaciones();
        if (obs.isEmpty()) {
            raiz.add(Estilos.crearEmptyState("No has registrado observaciones aún"));
        } else {
            LinkedHashMap<String, String> cols = new LinkedHashMap<>();
            cols.put("Estudiante", "estudiante"); cols.put("Curso", "curso"); cols.put("Descripción", "descripcion"); cols.put("Fecha", "fecha");
            raiz.add(Estilos.crearTarjeta("Observaciones registradas", Estilos.crearTabla(cols, obs)));
        }
        return raiz;
    }

    private JPanel panelContenido() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Contenido Académico"));
        List<Map<String, Object>> c = contenidos();
        if (c.isEmpty()) { raiz.add(Estilos.crearEmptyState("No has subido contenidos aún")); return raiz; }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("Título", "titulo"); cols.put("Materia", "materia"); cols.put("Descripción", "descripcion");
        raiz.add(Estilos.crearTabla(cols, c));
        return raiz;
    }

    private JLabel etiqueta(String texto) {
        JLabel l = new JLabel(texto);
        l.setForeground(Estilos.TEXTO_SEC);
        return l;
    }
}
