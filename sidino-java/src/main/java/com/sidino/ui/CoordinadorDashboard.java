package com.sidino.ui;

import com.sidino.core.DB;
import com.sidino.core.Sesion;
import com.sidino.ui.componentes.Estilos;

import javax.swing.*;
import java.awt.*;
import java.time.LocalDate;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.awt.Desktop;
import java.net.URI;

public class CoordinadorDashboard extends DashboardBase {

    public CoordinadorDashboard() {
        super("Coordinador — Dashboard", "Coordinación — Panel de Supervisión", Estilos.NARANJA, List.of(
                new ItemNav("dashboard", "📊", "Dashboard"),
                new ItemNav("observador", "📖", "Observador"),
                new ItemNav("citaciones", "📌", "Citaciones"),
                new ItemNav("docentes", "🧑‍🏫", "Docentes"),
                new ItemNav("reportes", "📈", "Reportes")
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

    // ══════════════════════════ CONSULTAS ══════════════════════════

    /** Versión resumida para el dashboard (sin ids, no se usa para editar/eliminar). */
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

    /** Versión con ids para el módulo Observador (permite editar/eliminar). */
    private List<Map<String, Object>> observacionesCrud() {
        return DB.query("""
            SELECT o.id_observacion, o.id_estudiante, o.id_asignacion, o.descripcion, o.fecha,
                   u.nombre AS estudiante, d.nombre AS docente, c.nombre AS curso
            FROM observador o
            JOIN usuario u ON o.id_estudiante = u.id_usuario
            JOIN asignacion_academica aa ON o.id_asignacion = aa.id_asignacion
            JOIN usuario d ON aa.id_docente = d.id_usuario
            JOIN curso c ON aa.id_curso = c.id_curso
            ORDER BY o.fecha DESC
        """);
    }

    private List<Map<String, Object>> citaciones() {
        return DB.query("""
            SELECT ci.motivo, ci.fecha, u.nombre AS estudiante
            FROM citacion ci JOIN usuario u ON ci.id_estudiante = u.id_usuario
            ORDER BY ci.fecha DESC LIMIT 30
        """);
    }

    private List<Map<String, Object>> citacionesCrud() {
        return DB.query("""
            SELECT ci.id_citacion, ci.id_estudiante, ci.id_asignacion, ci.motivo, ci.fecha,
                   u.nombre AS estudiante
            FROM citacion ci JOIN usuario u ON ci.id_estudiante = u.id_usuario
            ORDER BY ci.fecha DESC
        """);
    }

    private List<Map<String, Object>> docentes() {
        return DB.query("""
            SELECT u.id_usuario, u.nombre, u.correo, COUNT(aa.id_asignacion) AS clases
            FROM usuario u
            LEFT JOIN asignacion_academica aa ON u.id_usuario = aa.id_docente
            WHERE u.id_rol = 4
            GROUP BY u.id_usuario ORDER BY u.nombre
        """);
    }

    private List<Map<String, Object>> estudiantesParaCombo() {
        return DB.query("SELECT id_usuario, nombre FROM usuario WHERE id_rol = 5 ORDER BY nombre");
    }

    private List<Map<String, Object>> asignacionesParaCombo() {
        return DB.query("""
            SELECT aa.id_asignacion, c.nombre AS curso, m.nombre AS materia, d.nombre AS docente
            FROM asignacion_academica aa
            JOIN curso c ON aa.id_curso = c.id_curso
            JOIN materia m ON aa.id_materia = m.id_materia
            JOIN usuario d ON aa.id_docente = d.id_usuario
            ORDER BY c.nombre, m.nombre
        """);
    }

    // ══════════════════════════ LAYOUT BASE ══════════════════════════

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

    // ══════════════════════════ UTILIDADES CRUD (COMPARTIDAS) ══════════════════════════

    /** Ítem simple id+etiqueta para JComboBox, usado en los formularios. */
    private static class ComboItem {
        final Object id;
        final String etiqueta;
        ComboItem(Object id, String etiqueta) { this.id = id; this.etiqueta = etiqueta; }
        @Override public String toString() { return etiqueta; }
    }

    private JComboBox<ComboItem> comboEstudiantes() {
        JComboBox<ComboItem> combo = new JComboBox<>();
        for (Map<String, Object> f : estudiantesParaCombo()) {
            combo.addItem(new ComboItem(f.get("id_usuario"), Estilos.texto(f, "nombre")));
        }
        return combo;
    }

    private JComboBox<ComboItem> comboAsignaciones() {
        JComboBox<ComboItem> combo = new JComboBox<>();
        for (Map<String, Object> f : asignacionesParaCombo()) {
            String etiqueta = Estilos.texto(f, "curso") + " · " + Estilos.texto(f, "materia") + " (" + Estilos.texto(f, "docente") + ")";
            combo.addItem(new ComboItem(f.get("id_asignacion"), etiqueta));
        }
        return combo;
    }

    /** Selecciona en el combo el ítem cuyo id coincida (comparación por texto para evitar problemas Integer/Long). */
    private void seleccionarPorId(JComboBox<ComboItem> combo, Object id) {
        String buscado = String.valueOf(id);
        for (int i = 0; i < combo.getItemCount(); i++) {
            if (String.valueOf(combo.getItemAt(i).id).equals(buscado)) {
                combo.setSelectedIndex(i);
                return;
            }
        }
    }

    private JButton crearBoton(String texto, Color color) {
        JButton b = new JButton(texto);
        b.setBackground(color);
        b.setForeground(Color.WHITE);
        b.setFocusPainted(false);
        b.setFont(Estilos.FUENTE_NEGRITA);
        b.setBorder(BorderFactory.createEmptyBorder(8, 16, 8, 16));
        b.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        return b;
    }

    private JPanel crearBarraAcciones(JButton... botones) {
        JPanel barra = new JPanel(new FlowLayout(FlowLayout.LEFT, 8, 0));
        barra.setOpaque(false);
        for (JButton b : botones) barra.add(b);
        return barra;
    }

    /**
     * Muestra un formulario modal simple con los campos dados (etiqueta -> componente).
     * Devuelve 0 si el usuario pulsó "Guardar", cualquier otro valor si canceló/cerró.
     */
    private int mostrarFormulario(Component padre, String titulo, LinkedHashMap<String, JComponent> campos) {
        JPanel form = new JPanel();
        form.setLayout(new BoxLayout(form, BoxLayout.Y_AXIS));
        form.setBackground(Estilos.FONDO_TARJETA);
        form.setBorder(BorderFactory.createEmptyBorder(4, 4, 4, 4));
        for (Map.Entry<String, JComponent> e : campos.entrySet()) {
            JLabel lbl = new JLabel(e.getKey());
            lbl.setFont(Estilos.FUENTE_NEGRITA);
            lbl.setForeground(Estilos.TEXTO);
            lbl.setAlignmentX(Component.LEFT_ALIGNMENT);
            JComponent campo = e.getValue();
            campo.setAlignmentX(Component.LEFT_ALIGNMENT);
            int alto = (campo instanceof JScrollPane) ? 90 : 30;
            campo.setMaximumSize(new Dimension(360, alto));
            campo.setPreferredSize(new Dimension(360, alto));
            form.add(lbl);
            form.add(Box.createVerticalStrut(4));
            form.add(campo);
            form.add(Box.createVerticalStrut(12));
        }
        Object[] opciones = {"Guardar", "Cancelar"};
        return JOptionPane.showOptionDialog(padre, form, titulo, JOptionPane.YES_NO_OPTION,
                JOptionPane.PLAIN_MESSAGE, null, opciones, opciones[0]);
    }

    private boolean confirmarEliminacion(Component padre, String mensaje) {
        return JOptionPane.showConfirmDialog(padre, mensaje, "Confirmar eliminación",
                JOptionPane.YES_NO_OPTION, JOptionPane.WARNING_MESSAGE) == JOptionPane.YES_OPTION;
    }

    private void mostrarError(Component padre, String mensaje) {
        JOptionPane.showMessageDialog(padre, mensaje, "Error", JOptionPane.ERROR_MESSAGE);
    }

    private void mostrarAviso(Component padre, String mensaje) {
        JOptionPane.showMessageDialog(padre, mensaje, "Aviso", JOptionPane.WARNING_MESSAGE);
    }

    // ══════════════════════════ MÓDULO OBSERVADOR (CRUD) ══════════════════════════

    private JPanel panelObservador() {
        JPanel raiz = columna();
        construirPanelObservador(raiz);
        return raiz;
    }

    private void construirPanelObservador(JPanel raiz) {
        raiz.removeAll();
        raiz.add(Estilos.crearTituloSeccion("Observador Estudiantil"));

        JButton btnNuevo = crearBoton("+ Nueva observación", Estilos.NARANJA);
        JButton btnEditar = crearBoton("Editar", Estilos.AZUL);
        JButton btnEliminar = crearBoton("Eliminar", Estilos.ROJO);
        btnEditar.setEnabled(false);
        btnEliminar.setEnabled(false);
        raiz.add(crearBarraAcciones(btnNuevo, btnEditar, btnEliminar));
        raiz.add(Box.createVerticalStrut(10));

        btnNuevo.addActionListener(e -> nuevaObservacion(raiz));

        List<Map<String, Object>> datos = observacionesCrud();
        if (datos.isEmpty()) {
            raiz.add(Estilos.crearEmptyState("No hay observaciones registradas"));
        } else {
            LinkedHashMap<String, String> cols = new LinkedHashMap<>();
            cols.put("Estudiante", "estudiante"); cols.put("Curso", "curso"); cols.put("Docente", "docente");
            cols.put("Descripción", "descripcion"); cols.put("Fecha", "fecha");
            JScrollPane scroll = Estilos.crearTabla(cols, datos);
            JTable tabla = (JTable) scroll.getViewport().getView();
            tabla.getSelectionModel().addListSelectionListener(ev -> {
                boolean sel = tabla.getSelectedRow() >= 0;
                btnEditar.setEnabled(sel);
                btnEliminar.setEnabled(sel);
            });
            btnEditar.addActionListener(e -> {
                int fila = tabla.getSelectedRow();
                if (fila >= 0) editarObservacion(raiz, datos.get(fila));
            });
            btnEliminar.addActionListener(e -> {
                int fila = tabla.getSelectedRow();
                if (fila >= 0 && confirmarEliminacion(raiz, "¿Eliminar esta observación de forma permanente?")) {
                    try {
                        DB.ejecutar("DELETE FROM observador WHERE id_observacion=?", datos.get(fila).get("id_observacion"));
                        construirPanelObservador(raiz);
                    } catch (RuntimeException ex) {
                        mostrarError(raiz, "No se pudo eliminar: " + ex.getMessage());
                    }
                }
            });
            raiz.add(scroll);
        }
        raiz.revalidate();
        raiz.repaint();
    }

    private void nuevaObservacion(JPanel raiz) {
        JComboBox<ComboItem> comboEst = comboEstudiantes();
        JComboBox<ComboItem> comboAsig = comboAsignaciones();
        if (comboEst.getItemCount() == 0 || comboAsig.getItemCount() == 0) {
            mostrarAviso(raiz, "Debe existir al menos un estudiante y una asignación académica registrada.");
            return;
        }
        JTextArea txtDesc = new JTextArea(4, 20);
        txtDesc.setLineWrap(true); txtDesc.setWrapStyleWord(true);
        JTextField txtFecha = new JTextField(LocalDate.now().toString());

        LinkedHashMap<String, JComponent> campos = new LinkedHashMap<>();
        campos.put("Estudiante", comboEst);
        campos.put("Asignación (curso · materia · docente)", comboAsig);
        campos.put("Descripción", new JScrollPane(txtDesc));
        campos.put("Fecha (aaaa-mm-dd)", txtFecha);

        if (mostrarFormulario(raiz, "Nueva observación", campos) == 0) {
            String descripcion = txtDesc.getText().trim();
            if (descripcion.isEmpty()) { mostrarAviso(raiz, "La descripción no puede estar vacía."); return; }
            java.sql.Date fecha;
            try {
                fecha = java.sql.Date.valueOf(txtFecha.getText().trim());
            } catch (IllegalArgumentException ex) {
                mostrarError(raiz, "Fecha inválida, use el formato aaaa-mm-dd.");
                return;
            }
            ComboItem est = (ComboItem) comboEst.getSelectedItem();
            ComboItem asig = (ComboItem) comboAsig.getSelectedItem();
            try {
                DB.ejecutar("INSERT INTO observador (id_estudiante, id_asignacion, descripcion, fecha) VALUES (?,?,?,?)",
                        est.id, asig.id, descripcion, fecha);
                construirPanelObservador(raiz);
            } catch (RuntimeException ex) {
                mostrarError(raiz, "No se pudo guardar: " + ex.getMessage());
            }
        }
    }

    private void editarObservacion(JPanel raiz, Map<String, Object> fila) {
        JComboBox<ComboItem> comboEst = comboEstudiantes();
        JComboBox<ComboItem> comboAsig = comboAsignaciones();
        seleccionarPorId(comboEst, fila.get("id_estudiante"));
        seleccionarPorId(comboAsig, fila.get("id_asignacion"));
        JTextArea txtDesc = new JTextArea(Estilos.texto(fila, "descripcion"), 4, 20);
        txtDesc.setLineWrap(true); txtDesc.setWrapStyleWord(true);
        JTextField txtFecha = new JTextField(String.valueOf(fila.get("fecha")));

        LinkedHashMap<String, JComponent> campos = new LinkedHashMap<>();
        campos.put("Estudiante", comboEst);
        campos.put("Asignación (curso · materia · docente)", comboAsig);
        campos.put("Descripción", new JScrollPane(txtDesc));
        campos.put("Fecha (aaaa-mm-dd)", txtFecha);

        if (mostrarFormulario(raiz, "Editar observación", campos) == 0) {
            String descripcion = txtDesc.getText().trim();
            if (descripcion.isEmpty()) { mostrarAviso(raiz, "La descripción no puede estar vacía."); return; }
            java.sql.Date fecha;
            try {
                fecha = java.sql.Date.valueOf(txtFecha.getText().trim());
            } catch (IllegalArgumentException ex) {
                mostrarError(raiz, "Fecha inválida, use el formato aaaa-mm-dd.");
                return;
            }
            ComboItem est = (ComboItem) comboEst.getSelectedItem();
            ComboItem asig = (ComboItem) comboAsig.getSelectedItem();
            try {
                DB.ejecutar("UPDATE observador SET id_estudiante=?, id_asignacion=?, descripcion=?, fecha=? WHERE id_observacion=?",
                        est.id, asig.id, descripcion, fecha, fila.get("id_observacion"));
                construirPanelObservador(raiz);
            } catch (RuntimeException ex) {
                mostrarError(raiz, "No se pudo actualizar: " + ex.getMessage());
            }
        }
    }

    // ══════════════════════════ MÓDULO CITACIONES (CRUD) ══════════════════════════

    private JPanel panelCitaciones() {
        JPanel raiz = columna();
        construirPanelCitaciones(raiz);
        return raiz;
    }

    private void construirPanelCitaciones(JPanel raiz) {
        raiz.removeAll();
        raiz.add(Estilos.crearTituloSeccion("Citaciones"));

        JButton btnNuevo = crearBoton("+ Nueva citación", Estilos.NARANJA);
        JButton btnEditar = crearBoton("Editar", Estilos.AZUL);
        JButton btnEliminar = crearBoton("Eliminar", Estilos.ROJO);
        btnEditar.setEnabled(false);
        btnEliminar.setEnabled(false);
        raiz.add(crearBarraAcciones(btnNuevo, btnEditar, btnEliminar));
        raiz.add(Box.createVerticalStrut(10));

        btnNuevo.addActionListener(e -> nuevaCitacion(raiz));

        List<Map<String, Object>> datos = citacionesCrud();
        if (datos.isEmpty()) {
            raiz.add(Estilos.crearEmptyState("No hay citaciones registradas"));
        } else {
            LinkedHashMap<String, String> cols = new LinkedHashMap<>();
            cols.put("Estudiante", "estudiante"); cols.put("Motivo", "motivo"); cols.put("Fecha", "fecha");
            JScrollPane scroll = Estilos.crearTabla(cols, datos);
            JTable tabla = (JTable) scroll.getViewport().getView();
            tabla.getSelectionModel().addListSelectionListener(ev -> {
                boolean sel = tabla.getSelectedRow() >= 0;
                btnEditar.setEnabled(sel);
                btnEliminar.setEnabled(sel);
            });
            btnEditar.addActionListener(e -> {
                int fila = tabla.getSelectedRow();
                if (fila >= 0) editarCitacion(raiz, datos.get(fila));
            });
            btnEliminar.addActionListener(e -> {
                int fila = tabla.getSelectedRow();
                if (fila >= 0 && confirmarEliminacion(raiz, "¿Eliminar esta citación de forma permanente?")) {
                    try {
                        DB.ejecutar("DELETE FROM citacion WHERE id_citacion=?", datos.get(fila).get("id_citacion"));
                        construirPanelCitaciones(raiz);
                    } catch (RuntimeException ex) {
                        mostrarError(raiz, "No se pudo eliminar: " + ex.getMessage());
                    }
                }
            });
            raiz.add(scroll);
        }
        raiz.revalidate();
        raiz.repaint();
    }

    private void nuevaCitacion(JPanel raiz) {
        JComboBox<ComboItem> comboEst = comboEstudiantes();
        JComboBox<ComboItem> comboAsig = comboAsignaciones();
        if (comboEst.getItemCount() == 0 || comboAsig.getItemCount() == 0) {
            mostrarAviso(raiz, "Debe existir al menos un estudiante y una asignación académica registrada.");
            return;
        }
        JTextArea txtMotivo = new JTextArea(4, 20);
        txtMotivo.setLineWrap(true); txtMotivo.setWrapStyleWord(true);
        JTextField txtFecha = new JTextField(LocalDate.now().toString());

        LinkedHashMap<String, JComponent> campos = new LinkedHashMap<>();
        campos.put("Estudiante", comboEst);
        campos.put("Asignación (curso · materia · docente)", comboAsig);
        campos.put("Motivo", new JScrollPane(txtMotivo));
        campos.put("Fecha (aaaa-mm-dd)", txtFecha);

        if (mostrarFormulario(raiz, "Nueva citación", campos) == 0) {
            String motivo = txtMotivo.getText().trim();
            if (motivo.isEmpty()) { mostrarAviso(raiz, "El motivo no puede estar vacío."); return; }
            java.sql.Date fecha;
            try {
                fecha = java.sql.Date.valueOf(txtFecha.getText().trim());
            } catch (IllegalArgumentException ex) {
                mostrarError(raiz, "Fecha inválida, use el formato aaaa-mm-dd.");
                return;
            }
            ComboItem est = (ComboItem) comboEst.getSelectedItem();
            ComboItem asig = (ComboItem) comboAsig.getSelectedItem();
            try {
                DB.ejecutar("INSERT INTO citacion (id_estudiante, id_asignacion, motivo, fecha) VALUES (?,?,?,?)",
                        est.id, asig.id, motivo, fecha);
                construirPanelCitaciones(raiz);
            } catch (RuntimeException ex) {
                mostrarError(raiz, "No se pudo guardar: " + ex.getMessage());
            }
        }
    }

    private void editarCitacion(JPanel raiz, Map<String, Object> fila) {
        JComboBox<ComboItem> comboEst = comboEstudiantes();
        JComboBox<ComboItem> comboAsig = comboAsignaciones();
        seleccionarPorId(comboEst, fila.get("id_estudiante"));
        seleccionarPorId(comboAsig, fila.get("id_asignacion"));
        JTextArea txtMotivo = new JTextArea(Estilos.texto(fila, "motivo"), 4, 20);
        txtMotivo.setLineWrap(true); txtMotivo.setWrapStyleWord(true);
        JTextField txtFecha = new JTextField(String.valueOf(fila.get("fecha")));

        LinkedHashMap<String, JComponent> campos = new LinkedHashMap<>();
        campos.put("Estudiante", comboEst);
        campos.put("Asignación (curso · materia · docente)", comboAsig);
        campos.put("Motivo", new JScrollPane(txtMotivo));
        campos.put("Fecha (aaaa-mm-dd)", txtFecha);

        if (mostrarFormulario(raiz, "Editar citación", campos) == 0) {
            String motivo = txtMotivo.getText().trim();
            if (motivo.isEmpty()) { mostrarAviso(raiz, "El motivo no puede estar vacío."); return; }
            java.sql.Date fecha;
            try {
                fecha = java.sql.Date.valueOf(txtFecha.getText().trim());
            } catch (IllegalArgumentException ex) {
                mostrarError(raiz, "Fecha inválida, use el formato aaaa-mm-dd.");
                return;
            }
            ComboItem est = (ComboItem) comboEst.getSelectedItem();
            ComboItem asig = (ComboItem) comboAsig.getSelectedItem();
            try {
                DB.ejecutar("UPDATE citacion SET id_estudiante=?, id_asignacion=?, motivo=?, fecha=? WHERE id_citacion=?",
                        est.id, asig.id, motivo, fecha, fila.get("id_citacion"));
                construirPanelCitaciones(raiz);
            } catch (RuntimeException ex) {
                mostrarError(raiz, "No se pudo actualizar: " + ex.getMessage());
            }
        }
    }

    // ══════════════════════════ MÓDULO DOCENTES (CRUD) ══════════════════════════

    private JPanel panelDocentes() {
        JPanel raiz = columna();
        construirPanelDocentes(raiz);
        return raiz;
    }

    private void construirPanelDocentes(JPanel raiz) {
        raiz.removeAll();
        raiz.add(Estilos.crearTituloSeccion("Docentes"));

        JButton btnNuevo = crearBoton("+ Nuevo docente", Estilos.NARANJA);
        JButton btnEditar = crearBoton("Editar", Estilos.AZUL);
        JButton btnEliminar = crearBoton("Eliminar", Estilos.ROJO);
        btnEditar.setEnabled(false);
        btnEliminar.setEnabled(false);
        raiz.add(crearBarraAcciones(btnNuevo, btnEditar, btnEliminar));
        raiz.add(Box.createVerticalStrut(10));

        btnNuevo.addActionListener(e -> nuevoDocente(raiz));

        List<Map<String, Object>> datos = docentes();
        if (datos.isEmpty()) {
            raiz.add(Estilos.crearEmptyState("No hay docentes registrados"));
        } else {
            LinkedHashMap<String, String> cols = new LinkedHashMap<>();
            cols.put("Nombre", "nombre"); cols.put("Correo", "correo"); cols.put("Clases asignadas", "clases");
            JScrollPane scroll = Estilos.crearTabla(cols, datos);
            JTable tabla = (JTable) scroll.getViewport().getView();
            tabla.getSelectionModel().addListSelectionListener(ev -> {
                boolean sel = tabla.getSelectedRow() >= 0;
                btnEditar.setEnabled(sel);
                btnEliminar.setEnabled(sel);
            });
            btnEditar.addActionListener(e -> {
                int fila = tabla.getSelectedRow();
                if (fila >= 0) editarDocente(raiz, datos.get(fila));
            });
            btnEliminar.addActionListener(e -> {
                int fila = tabla.getSelectedRow();
                if (fila >= 0 && confirmarEliminacion(raiz, "¿Eliminar este docente de forma permanente?")) {
                    try {
                        DB.ejecutar("DELETE FROM usuario WHERE id_usuario=?", datos.get(fila).get("id_usuario"));
                        construirPanelDocentes(raiz);
                    } catch (RuntimeException ex) {
                        mostrarError(raiz, "No se puede eliminar: el docente tiene asignaciones académicas u otros registros vinculados.");
                    }
                }
            });
            raiz.add(scroll);
        }
        raiz.revalidate();
        raiz.repaint();
    }

    private void nuevoDocente(JPanel raiz) {
        JTextField txtNombre = new JTextField();
        JTextField txtCorreo = new JTextField();
        JPasswordField txtPass = new JPasswordField();

        LinkedHashMap<String, JComponent> campos = new LinkedHashMap<>();
        campos.put("Nombre completo", txtNombre);
        campos.put("Correo", txtCorreo);
        campos.put("Contraseña", txtPass);

        if (mostrarFormulario(raiz, "Nuevo docente", campos) == 0) {
            String nombre = txtNombre.getText().trim();
            String correo = txtCorreo.getText().trim();
            String pass = new String(txtPass.getPassword());
            if (nombre.isEmpty() || correo.isEmpty() || pass.isEmpty()) {
                mostrarAviso(raiz, "Todos los campos son obligatorios.");
                return;
            }
            try {
                DB.ejecutar("INSERT INTO usuario (nombre, correo, contrasena, id_rol) VALUES (?,?,?,4)", nombre, correo, pass);
                construirPanelDocentes(raiz);
            } catch (RuntimeException ex) {
                mostrarError(raiz, "No se pudo crear el docente (verifique que el correo no esté ya registrado): " + ex.getMessage());
            }
        }
    }

    private void editarDocente(JPanel raiz, Map<String, Object> fila) {
        JTextField txtNombre = new JTextField(Estilos.texto(fila, "nombre"));
        JTextField txtCorreo = new JTextField(Estilos.texto(fila, "correo"));
        JPasswordField txtPass = new JPasswordField();

        LinkedHashMap<String, JComponent> campos = new LinkedHashMap<>();
        campos.put("Nombre completo", txtNombre);
        campos.put("Correo", txtCorreo);
        campos.put("Nueva contraseña (dejar en blanco para no cambiarla)", txtPass);

        if (mostrarFormulario(raiz, "Editar docente", campos) == 0) {
            String nombre = txtNombre.getText().trim();
            String correo = txtCorreo.getText().trim();
            String pass = new String(txtPass.getPassword());
            if (nombre.isEmpty() || correo.isEmpty()) {
                mostrarAviso(raiz, "El nombre y el correo son obligatorios.");
                return;
            }
            try {
                if (pass.isEmpty()) {
                    DB.ejecutar("UPDATE usuario SET nombre=?, correo=? WHERE id_usuario=?", nombre, correo, fila.get("id_usuario"));
                } else {
                    DB.ejecutar("UPDATE usuario SET nombre=?, correo=?, contrasena=? WHERE id_usuario=?", nombre, correo, pass, fila.get("id_usuario"));
                }
                construirPanelDocentes(raiz);
            } catch (RuntimeException ex) {
                mostrarError(raiz, "No se pudo actualizar (verifique que el correo no esté ya registrado): " + ex.getMessage());
            }
        }
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