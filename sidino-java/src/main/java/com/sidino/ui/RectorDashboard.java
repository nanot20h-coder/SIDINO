package com.sidino.ui;

import com.sidino.core.DB;
import com.sidino.core.Sesion;
import com.sidino.ui.componentes.BotonRedondeado;
import com.sidino.ui.componentes.Dialogos;
import com.sidino.ui.componentes.Estilos;

import javax.swing.*;
import javax.swing.table.DefaultTableModel;
import java.awt.*;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.awt.Desktop;
import java.net.URI;

public class RectorDashboard extends DashboardBase {

    /** Recuerda cuál sub-pestaña de Gestión Académica estaba activa, para no perderla cada vez que se refresca el módulo tras guardar algo. */
    private String pestaniaAcademicaActiva = "Materias";

    public RectorDashboard() {
        super("Rector — Dashboard", "Rector — Panel de Control", Estilos.ROJO, List.of(
                new ItemNav("dashboard", "📊", "Dashboard"),
                new ItemNav("usuarios", "👥", "Usuarios"),
                new ItemNav("crear_usuario", "➕", "Crear Usuario"),
                new ItemNav("asignaciones", "🗓", "Gestión Académica"),
                new ItemNav("historial", "🕑", "Historial"),
                new ItemNav("reportes", "📈", "Reportes")
        ));
    }

    @Override
    protected JPanel construirModulo(String id) {
        return switch (id) {
            case "dashboard" -> panelDashboard();
            case "usuarios" -> panelUsuarios();
            case "crear_usuario" -> panelCrearUsuario();
            case "asignaciones" -> panelAsignaciones();
            case "historial" -> panelHistorial();
            case "reportes" -> panelReportes();
            default -> new JPanel();
        };
    }

    private List<Map<String, Object>> usuarios() {
        return DB.query("""
            SELECT u.id_usuario, u.nombre, u.correo, r.nombre_rol, u.id_rol
            FROM usuario u JOIN rol r ON u.id_rol = r.id_rol
            ORDER BY u.id_rol, u.nombre
        """);
    }

    private List<Map<String, Object>> roles() {
        return DB.query("SELECT * FROM rol ORDER BY id_rol");
    }

    private List<Map<String, Object>> asignaciones() {
        return DB.query("""
            SELECT aa.id_asignacion, u.nombre AS docente, m.nombre AS materia,
                   c.nombre AS curso, s.nombre AS salon,
                   h.dia, h.hora_inicio, h.hora_fin, p.nombre AS periodo
            FROM asignacion_academica aa
            JOIN usuario u ON aa.id_docente = u.id_usuario
            JOIN materia m ON aa.id_materia = m.id_materia
            JOIN curso c ON aa.id_curso = c.id_curso
            JOIN salon s ON aa.id_salon = s.id_salon
            JOIN horario h ON aa.id_horario = h.id_horario
            JOIN periodo_academico p ON aa.id_periodo = p.id_periodo
            ORDER BY aa.id_asignacion DESC LIMIT 50
        """);
    }

    private List<Map<String, Object>> historial() {
        return DB.query("""
            SELECT h.accion, h.tabla_afectada, h.fecha, u.nombre
            FROM historial_accion h JOIN usuario u ON h.id_usuario = u.id_usuario
            ORDER BY h.fecha DESC LIMIT 30
        """);
    }

    private void registrarHistorial(String accion) {
        DB.ejecutar("INSERT INTO historial_accion (id_usuario, accion, tabla_afectada) VALUES (?, ?, 'usuario')",
                Sesion.idUsuario, accion);
    }

    private JPanel columna() {
        JPanel p = new JPanel();
        p.setOpaque(false);
        p.setLayout(new BoxLayout(p, BoxLayout.Y_AXIS));
        return p;
    }

    private JPanel panelDashboard() {
        long totalUsuarios = DB.contar("SELECT COUNT(*) FROM usuario");
        long totalDocentes = DB.contar("SELECT COUNT(*) FROM usuario WHERE id_rol=4");
        long totalEstudiantes = DB.contar("SELECT COUNT(*) FROM usuario WHERE id_rol=5");
        long totalCursos = DB.contar("SELECT COUNT(*) FROM curso");
        long totalMaterias = DB.contar("SELECT COUNT(*) FROM materia");
        long totalPeriodos = DB.contar("SELECT COUNT(*) FROM periodo_academico");

        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("¡Bienvenido, " + Sesion.nombre + "!"));
        raiz.add(Estilos.crearGridStats(
                Estilos.crearStatCard(String.valueOf(totalUsuarios), "Usuarios", Estilos.ROJO),
                Estilos.crearStatCard(String.valueOf(totalDocentes), "Docentes", Estilos.AZUL),
                Estilos.crearStatCard(String.valueOf(totalEstudiantes), "Estudiantes", Estilos.VERDE),
                Estilos.crearStatCard(String.valueOf(totalCursos), "Cursos", Estilos.NARANJA)
        ));
        raiz.add(Box.createVerticalStrut(10));
        raiz.add(Estilos.crearGridStats(
                Estilos.crearStatCard(String.valueOf(totalMaterias), "Materias", Estilos.MORADO),
                Estilos.crearStatCard(String.valueOf(totalPeriodos), "Periodos académicos", Estilos.AMBAR)
        ));
        return raiz;
    }

    private JPanel panelUsuarios() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Gestión de Usuarios"));

        List<Map<String, Object>> usuarios = usuarios();
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("#", "id_usuario"); cols.put("Nombre", "nombre"); cols.put("Correo", "correo"); cols.put("Rol", "nombre_rol");
        JScrollPane scroll = Estilos.crearTabla(cols, usuarios);
        JTable tabla = (JTable) scroll.getViewport().getView();

        JPanel acciones = new JPanel(new FlowLayout(FlowLayout.LEFT));
        acciones.setOpaque(false);
        BotonRedondeado btnEditar = new BotonRedondeado("Editar seleccionado", 10).colores(Estilos.AZUL, Estilos.AZUL.brighter());
        BotonRedondeado btnEliminar = new BotonRedondeado("Eliminar seleccionado", 10).colores(Estilos.ROJO, Estilos.ROJO.darker());
        btnEditar.addActionListener(e -> {
            int fila = tabla.getSelectedRow();
            if (fila < 0) { Dialogos.advertencia(this, "Selecciona un usuario de la tabla."); return; }
            editarUsuario(usuarios.get(fila));
        });
        btnEliminar.addActionListener(e -> {
            int fila = tabla.getSelectedRow();
            if (fila < 0) { Dialogos.advertencia(this, "Selecciona un usuario de la tabla."); return; }
            eliminarUsuario(usuarios.get(fila));
        });
        acciones.add(btnEditar);
        acciones.add(btnEliminar);

        raiz.add(scroll);
        raiz.add(acciones);
        return raiz;
    }

    private void editarUsuario(Map<String, Object> usuario) {
        JTextField nombre = campoEstilizado(Estilos.texto(usuario, "nombre"));
        JTextField correo = campoEstilizado(Estilos.texto(usuario, "correo"));
        List<Map<String, Object>> roles = roles();
        JComboBox<String> rolCombo = new JComboBox<>();
        int idxSeleccionado = 0;
        for (int i = 0; i < roles.size(); i++) {
            rolCombo.addItem(Estilos.texto(roles.get(i), "nombre_rol"));
            if (((Number) roles.get(i).get("id_rol")).intValue() == ((Number) usuario.get("id_rol")).intValue()) idxSeleccionado = i;
        }
        rolCombo.setSelectedIndex(idxSeleccionado);
        JPasswordField nuevaClave = new JPasswordField();

        JPanel panel = new JPanel(new GridLayout(0, 1, 4, 8));
        panel.setOpaque(false);
        panel.add(etiqueta("Nombre:")); panel.add(nombre);
        panel.add(etiqueta("Correo:")); panel.add(correo);
        panel.add(etiqueta("Rol:")); panel.add(rolCombo);
        panel.add(etiqueta("Nueva contraseña (vacío = no cambiar):")); panel.add(nuevaClave);

        boolean confirmado = Dialogos.formulario(this, "Editar usuario", panel, "Guardar cambios");
        if (!confirmado) return;

        if (nombre.getText().isBlank() || correo.getText().isBlank()) {
            Dialogos.error(this, "Nombre y correo son obligatorios.");
            return;
        }
        int idUsuario = ((Number) usuario.get("id_usuario")).intValue();
        long duplicados = DB.contar("SELECT COUNT(*) FROM usuario WHERE correo = ? AND id_usuario != ?", correo.getText().trim(), idUsuario);
        if (duplicados > 0) {
            Dialogos.error(this, "Ese correo ya está en uso por otro usuario.");
            return;
        }
        int idRol = ((Number) roles.get(rolCombo.getSelectedIndex()).get("id_rol")).intValue();
        String clave = new String(nuevaClave.getPassword());
        if (!clave.isBlank()) {
            DB.ejecutar("UPDATE usuario SET nombre=?, correo=?, id_rol=?, contrasena=? WHERE id_usuario=?",
                    nombre.getText().trim(), correo.getText().trim(), idRol, clave, idUsuario);
        } else {
            DB.ejecutar("UPDATE usuario SET nombre=?, correo=?, id_rol=? WHERE id_usuario=?",
                    nombre.getText().trim(), correo.getText().trim(), idRol, idUsuario);
        }
        registrarHistorial("Editó usuario: " + nombre.getText().trim() + " (ID " + idUsuario + ")");
        Dialogos.exito(this, "Usuario actualizado correctamente.");
        mostrarModulo("usuarios");
    }

    private void eliminarUsuario(Map<String, Object> usuario) {
        int idUsuario = ((Number) usuario.get("id_usuario")).intValue();
        if (idUsuario == Sesion.idUsuario) {
            Dialogos.advertencia(this, "No puedes eliminar tu propio usuario.");
            return;
        }
        String nombre = Estilos.texto(usuario, "nombre");
        boolean confirmar = Dialogos.confirmar(this, "¿Seguro que quieres eliminar a " + nombre + "?\nEsta acción no se puede deshacer.",
                "Sí, eliminar", Estilos.ROJO);
        if (!confirmar) return;
        DB.ejecutar("DELETE FROM usuario WHERE id_usuario = ?", idUsuario);
        registrarHistorial("Eliminó usuario: " + nombre + " (ID " + idUsuario + ")");
        Dialogos.exito(this, "Usuario «" + nombre + "» eliminado correctamente.");
        mostrarModulo("usuarios");
    }

    private JTextField campoEstilizado(String texto) {
        JTextField campo = new JTextField(texto);
        campo.setBackground(Estilos.aclarar(Estilos.FONDO_TARJETA, 0.06));
        campo.setForeground(Estilos.TEXTO);
        campo.setCaretColor(Estilos.TEXTO);
        campo.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(Estilos.BORDE), BorderFactory.createEmptyBorder(4, 6, 4, 6)));
        return campo;
    }

    private JPanel panelCrearUsuario() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Crear Usuario"));

        JTextField nombre = new JTextField(20);
        JTextField correo = new JTextField(20);
        JPasswordField clave = new JPasswordField(20);
        List<Map<String, Object>> roles = roles();
        JComboBox<String> rolCombo = new JComboBox<>();
        for (Map<String, Object> r : roles) rolCombo.addItem(Estilos.texto(r, "nombre_rol"));

        JPanel form = new JPanel(new GridBagLayout());
        form.setOpaque(false);
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 6, 6, 6);
        gc.anchor = GridBagConstraints.WEST;
        gc.gridx = 0; gc.gridy = 0; form.add(etiqueta("Nombre:"), gc);
        gc.gridx = 1; form.add(nombre, gc);
        gc.gridx = 0; gc.gridy = 1; form.add(etiqueta("Correo:"), gc);
        gc.gridx = 1; form.add(correo, gc);
        gc.gridx = 0; gc.gridy = 2; form.add(etiqueta("Rol:"), gc);
        gc.gridx = 1; form.add(rolCombo, gc);
        gc.gridx = 0; gc.gridy = 3; form.add(etiqueta("Contraseña:"), gc);
        gc.gridx = 1; form.add(clave, gc);

        BotonRedondeado guardar = new BotonRedondeado("Crear usuario", 10).colores(Estilos.ROJO, Estilos.ROJO.brighter());
        guardar.addActionListener(e -> {
            String n = nombre.getText().trim();
            String c = correo.getText().trim();
            String p = new String(clave.getPassword()).trim();
            if (n.isEmpty() || c.isEmpty() || p.isEmpty() || roles.isEmpty()) {
                Dialogos.advertencia(this, "Todos los campos son obligatorios.");
                return;
            }
            if (!c.matches("^[^@\\s]+@[^@\\s]+\\.[^@\\s]+$")) {
                Dialogos.error(this, "El correo ingresado no es válido.");
                return;
            }
            long existentes = DB.contar("SELECT COUNT(*) FROM usuario WHERE correo = ?", c);
            if (existentes > 0) {
                Dialogos.error(this, "Ya existe un usuario con ese correo.");
                return;
            }
            int idRol = ((Number) roles.get(rolCombo.getSelectedIndex()).get("id_rol")).intValue();
            long nuevoId = DB.ejecutarYObtenerId(
                    "INSERT INTO usuario (nombre, correo, contrasena, id_rol) VALUES (?, ?, ?, ?)", n, c, p, idRol);
            registrarHistorial("Creó usuario: " + n + " (ID " + nuevoId + ")");
            Dialogos.exito(this, "Usuario «" + n + "» creado exitosamente (ID: " + nuevoId + ").");
            nombre.setText(""); correo.setText(""); clave.setText("");
        });
        gc.gridx = 1; gc.gridy = 4; form.add(guardar, gc);

        raiz.add(Estilos.crearTarjeta(null, form));
        return raiz;
    }

    private JPanel panelAsignaciones() {
        JPanel raiz = columna();
        raiz.add(Estilos.crearTituloSeccion("Gestión Académica"));

        LinkedHashMap<String, JPanel> pestanias = new LinkedHashMap<>();
        pestanias.put("Materias", panelCrudSimple("materia", "id_materia", "materia", "la materia", List.of(
                new CampoForm("Nombre", "nombre", "texto"))));
        pestanias.put("Cursos", panelCrudSimple("curso", "id_curso", "curso", "el curso", List.of(
                new CampoForm("Nombre", "nombre", "texto"))));
        pestanias.put("Salones", panelCrudSimple("salon", "id_salon", "salón", "el salón", List.of(
                new CampoForm("Nombre", "nombre", "texto"),
                new CampoForm("Capacidad", "capacidad", "numero"),
                new CampoForm("Ubicación", "ubicacion", "texto"))));
        pestanias.put("Horarios", panelCrudSimple("horario", "id_horario", "horario", "el horario", List.of(
                new CampoForm("Día", "dia", "texto"),
                new CampoForm("Hora inicio (HH:MM)", "hora_inicio", "texto"),
                new CampoForm("Hora fin (HH:MM)", "hora_fin", "texto"))));
        pestanias.put("Periodos", panelCrudSimple("periodo_academico", "id_periodo", "periodo", "el periodo académico", List.of(
                new CampoForm("Nombre", "nombre", "texto"),
                new CampoForm("Nivel", "nivel", "texto"),
                new CampoForm("Tipo", "tipo", "texto"),
                new CampoForm("Año", "anio", "numero"))));
        pestanias.put("Asignaciones", panelAsignacionAcademica());
        pestanias.put("Matrículas", panelMatriculas());
        pestanias.put("Acudientes", panelAcudientes());

        raiz.add(construirPestanias(pestanias));
        return raiz;
    }

    /** Matricula estudiantes en una asignación académica (materia+curso+docente ya armados). */
    private JPanel panelMatriculas() {
        JPanel raiz = columna();

        List<Map<String, Object>> estudiantes = DB.query("SELECT id_usuario, nombre FROM usuario WHERE id_rol=5 ORDER BY nombre");
        List<Map<String, Object>> asigDisponibles = asignaciones();

        if (estudiantes.isEmpty() || asigDisponibles.isEmpty()) {
            StringBuilder faltan = new StringBuilder("Antes de matricular necesitas:\n");
            if (estudiantes.isEmpty()) faltan.append("• Al menos un usuario con rol Estudiante (créalo en «Crear Usuario»).\n");
            if (asigDisponibles.isEmpty()) faltan.append("• Al menos una asignación académica creada (pestaña «Asignaciones»).\n");
            raiz.add(Estilos.crearAlertaInfo(faltan.toString()));
            return raiz;
        }

        JComboBox<String> comboEstudiante = new JComboBox<>();
        for (Map<String, Object> e : estudiantes) comboEstudiante.addItem(Estilos.texto(e, "nombre"));
        JComboBox<String> comboAsignacion = new JComboBox<>();
        for (Map<String, Object> a : asigDisponibles) {
            comboAsignacion.addItem(Estilos.texto(a, "materia") + " — " + Estilos.texto(a, "curso") + " (" + Estilos.texto(a, "docente") + ")");
        }

        JPanel form = new JPanel(new GridBagLayout());
        form.setOpaque(false);
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 6, 6, 6);
        gc.anchor = GridBagConstraints.WEST;
        gc.fill = GridBagConstraints.HORIZONTAL;

        gc.gridx = 0; gc.gridy = 0; form.add(etiqueta("Estudiante:"), gc);
        gc.gridx = 1; form.add(comboEstudiante, gc);
        gc.gridx = 0; gc.gridy = 1; form.add(etiqueta("Clase (materia/curso):"), gc);
        gc.gridx = 1; form.add(comboAsignacion, gc);

        BotonRedondeado guardar = new BotonRedondeado("Matricular", 10).colores(Estilos.ROJO, Estilos.aclarar(Estilos.ROJO, 0.15));
        guardar.addActionListener(e -> {
            int idEstudiante = ((Number) estudiantes.get(comboEstudiante.getSelectedIndex()).get("id_usuario")).intValue();
            int idAsignacion = ((Number) asigDisponibles.get(comboAsignacion.getSelectedIndex()).get("id_asignacion")).intValue();

            long yaExiste = DB.contar("SELECT COUNT(*) FROM matricula WHERE id_estudiante=? AND id_asignacion=?", idEstudiante, idAsignacion);
            if (yaExiste > 0) {
                Dialogos.advertencia(this, "Ese estudiante ya está matriculado en esa clase.");
                return;
            }
            try {
                long nuevoId = DB.ejecutarYObtenerId(
                        "INSERT INTO matricula (id_estudiante, id_asignacion) VALUES (?, ?)", idEstudiante, idAsignacion);
                registrarHistorial("Matriculó estudiante (matrícula ID " + nuevoId + ")");
                Dialogos.exito(this, "Estudiante matriculado correctamente.");
                refrescarModuloActual();
            } catch (RuntimeException ex) {
                Dialogos.error(this, "No se pudo matricular:\n" + ex.getMessage());
            }
        });
        gc.gridx = 1; gc.gridy = 2; form.add(guardar, gc);

        raiz.add(Estilos.crearTarjeta("Nueva matrícula", form));
        raiz.add(Box.createVerticalStrut(12));

        List<Map<String, Object>> matriculados = DB.query("""
            SELECT mat.id_matricula, u.nombre AS estudiante, m.nombre AS materia, c.nombre AS curso, d.nombre AS docente
            FROM matricula mat
            JOIN usuario u ON mat.id_estudiante = u.id_usuario
            JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
            JOIN materia m ON aa.id_materia = m.id_materia
            JOIN curso c ON aa.id_curso = c.id_curso
            JOIN usuario d ON aa.id_docente = d.id_usuario
            ORDER BY mat.id_matricula DESC
        """);
        if (matriculados.isEmpty()) {
            raiz.add(Estilos.crearEmptyState("Aún no hay estudiantes matriculados"));
            return raiz;
        }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("#", "id_matricula"); cols.put("Estudiante", "estudiante"); cols.put("Materia", "materia");
        cols.put("Curso", "curso"); cols.put("Docente", "docente");
        JScrollPane scroll = Estilos.crearTabla(cols, matriculados);
        JTable jTabla = (JTable) scroll.getViewport().getView();

        BotonRedondeado eliminar = new BotonRedondeado("Eliminar matrícula seleccionada", 10).colores(Estilos.ROJO, Estilos.aclarar(Estilos.ROJO, 0.15));
        eliminar.addActionListener(e -> {
            int seleccion = jTabla.getSelectedRow();
            if (seleccion < 0) { Dialogos.advertencia(this, "Selecciona una matrícula de la tabla."); return; }
            Object id = matriculados.get(seleccion).get("id_matricula");
            boolean confirmar = Dialogos.confirmar(this,
                    "¿Retirar esta matrícula?\nSi el estudiante ya tiene notas en esa clase, no se podrá borrar.",
                    "Sí, retirar", Estilos.ROJO);
            if (!confirmar) return;
            try {
                DB.ejecutar("DELETE FROM matricula WHERE id_matricula = ?", id);
                registrarHistorial("Retiró matrícula (ID " + id + ")");
                Dialogos.exito(this, "Matrícula retirada correctamente.");
                refrescarModuloActual();
            } catch (RuntimeException ex) {
                Dialogos.error(this, "No se pudo retirar: el estudiante ya tiene notas registradas en esa clase.");
            }
        });
        JPanel acciones = new JPanel(new FlowLayout(FlowLayout.LEFT));
        acciones.setOpaque(false);
        acciones.add(eliminar);

        raiz.add(scroll);
        raiz.add(acciones);
        return raiz;
    }

    /** Vincula un acudiente (rol id 6) con un estudiante (rol id 5) en acudiente_estudiante. */
    private JPanel panelAcudientes() {
        JPanel raiz = columna();

        List<Map<String, Object>> acudientes = DB.query("SELECT id_usuario, nombre FROM usuario WHERE id_rol=6 ORDER BY nombre");
        List<Map<String, Object>> estudiantes = DB.query("SELECT id_usuario, nombre FROM usuario WHERE id_rol=5 ORDER BY nombre");

        if (acudientes.isEmpty() || estudiantes.isEmpty()) {
            StringBuilder faltan = new StringBuilder("Antes de vincular necesitas:\n");
            if (acudientes.isEmpty()) faltan.append("• Al menos un usuario con rol Acudiente (créalo en «Crear Usuario»).\n");
            if (estudiantes.isEmpty()) faltan.append("• Al menos un usuario con rol Estudiante (créalo en «Crear Usuario»).\n");
            raiz.add(Estilos.crearAlertaInfo(faltan.toString()));
            return raiz;
        }

        JComboBox<String> comboAcudiente = new JComboBox<>();
        for (Map<String, Object> a : acudientes) comboAcudiente.addItem(Estilos.texto(a, "nombre"));
        JComboBox<String> comboEstudiante = new JComboBox<>();
        for (Map<String, Object> e : estudiantes) comboEstudiante.addItem(Estilos.texto(e, "nombre"));

        JPanel form = new JPanel(new GridBagLayout());
        form.setOpaque(false);
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 6, 6, 6);
        gc.anchor = GridBagConstraints.WEST;
        gc.fill = GridBagConstraints.HORIZONTAL;

        gc.gridx = 0; gc.gridy = 0; form.add(etiqueta("Acudiente:"), gc);
        gc.gridx = 1; form.add(comboAcudiente, gc);
        gc.gridx = 0; gc.gridy = 1; form.add(etiqueta("Estudiante:"), gc);
        gc.gridx = 1; form.add(comboEstudiante, gc);

        BotonRedondeado guardar = new BotonRedondeado("Vincular", 10).colores(Estilos.ROJO, Estilos.aclarar(Estilos.ROJO, 0.15));
        guardar.addActionListener(e -> {
            int idAcudiente = ((Number) acudientes.get(comboAcudiente.getSelectedIndex()).get("id_usuario")).intValue();
            int idEstudiante = ((Number) estudiantes.get(comboEstudiante.getSelectedIndex()).get("id_usuario")).intValue();

            long yaExiste = DB.contar("SELECT COUNT(*) FROM acudiente_estudiante WHERE id_acudiente=? AND id_estudiante=?", idAcudiente, idEstudiante);
            if (yaExiste > 0) {
                Dialogos.advertencia(this, "Ese acudiente ya está vinculado a ese estudiante.");
                return;
            }
            try {
                DB.ejecutar("INSERT INTO acudiente_estudiante (id_acudiente, id_estudiante) VALUES (?, ?)", idAcudiente, idEstudiante);
                registrarHistorial("Vinculó acudiente (ID " + idAcudiente + ") con estudiante (ID " + idEstudiante + ")");
                Dialogos.exito(this, "Acudiente vinculado correctamente al estudiante.");
                refrescarModuloActual();
            } catch (RuntimeException ex) {
                Dialogos.error(this, "No se pudo vincular:\n" + ex.getMessage());
            }
        });
        gc.gridx = 1; gc.gridy = 2; form.add(guardar, gc);

        raiz.add(Estilos.crearTarjeta("Nuevo vínculo acudiente — estudiante", form));
        raiz.add(Box.createVerticalStrut(12));

        List<Map<String, Object>> vinculos = DB.query("""
            SELECT a.nombre AS acudiente, e.nombre AS estudiante, ae.id_acudiente, ae.id_estudiante
            FROM acudiente_estudiante ae
            JOIN usuario a ON ae.id_acudiente = a.id_usuario
            JOIN usuario e ON ae.id_estudiante = e.id_usuario
            ORDER BY a.nombre, e.nombre
        """);
        if (vinculos.isEmpty()) {
            raiz.add(Estilos.crearEmptyState("Aún no hay acudientes vinculados a estudiantes"));
            return raiz;
        }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("Acudiente", "acudiente"); cols.put("Estudiante", "estudiante");
        JScrollPane scrollVinculos = Estilos.crearTabla(cols, vinculos);
        JTable tablaVinculos = (JTable) scrollVinculos.getViewport().getView();

        BotonRedondeado eliminarVinculo = new BotonRedondeado("Eliminar vínculo seleccionado", 10).colores(Estilos.ROJO, Estilos.aclarar(Estilos.ROJO, 0.15));
        eliminarVinculo.addActionListener(e -> {
            int seleccion = tablaVinculos.getSelectedRow();
            if (seleccion < 0) { Dialogos.advertencia(this, "Selecciona un vínculo de la tabla."); return; }
            Object idAcud = vinculos.get(seleccion).get("id_acudiente");
            Object idEst = vinculos.get(seleccion).get("id_estudiante");
            boolean confirmar = Dialogos.confirmar(this, "¿Eliminar este vínculo acudiente-estudiante?", "Sí, eliminar", Estilos.ROJO);
            if (!confirmar) return;
            try {
                DB.ejecutar("DELETE FROM acudiente_estudiante WHERE id_acudiente = ? AND id_estudiante = ?", idAcud, idEst);
                registrarHistorial("Eliminó vínculo acudiente (ID " + idAcud + ") - estudiante (ID " + idEst + ")");
                Dialogos.exito(this, "Vínculo eliminado correctamente.");
                refrescarModuloActual();
            } catch (RuntimeException ex) {
                Dialogos.error(this, "No se pudo eliminar el vínculo:\n" + ex.getMessage());
            }
        });
        JPanel accionesVinculos = new JPanel(new FlowLayout(FlowLayout.LEFT));
        accionesVinculos.setOpaque(false);
        accionesVinculos.add(eliminarVinculo);

        raiz.add(scrollVinculos);
        raiz.add(accionesVinculos);
        return raiz;
    }

    /**
     * Barra de pestañas propia (en vez de JTabbedPane) para que se vea con
     * nuestros colores en cualquier sistema operativo, en lugar del gris o
     * blanco por defecto que Windows le pinta a las pestañas nativas de Swing.
     */
    private JPanel construirPestanias(LinkedHashMap<String, JPanel> secciones) {
        JPanel contenedor = new JPanel(new BorderLayout(0, 12));
        contenedor.setOpaque(false);

        JPanel barra = new JPanel(new FlowLayout(FlowLayout.LEFT, 6, 0));
        barra.setOpaque(false);

        CardLayout cards = new CardLayout();
        JPanel cuerpo = new JPanel(cards);
        cuerpo.setOpaque(false);

        if (!secciones.containsKey(pestaniaAcademicaActiva)) {
            pestaniaAcademicaActiva = secciones.keySet().iterator().next();
        }

        Map<String, JButton> botones = new LinkedHashMap<>();
        for (String nombre : secciones.keySet()) {
            cuerpo.add(secciones.get(nombre), nombre);
            JButton boton = new JButton(nombre);
            boton.setFocusPainted(false);
            boton.setBorderPainted(false);
            boton.setFont(Estilos.FUENTE_NEGRITA);
            boton.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
            boton.setBorder(BorderFactory.createEmptyBorder(8, 16, 8, 16));
            boton.addActionListener(e -> {
                pestaniaAcademicaActiva = nombre;
                cards.show(cuerpo, nombre);
                botones.forEach((n, b) -> marcarPestania(b, n.equals(nombre)));
            });
            botones.put(nombre, boton);
            barra.add(boton);
        }
        botones.forEach((n, b) -> marcarPestania(b, n.equals(pestaniaAcademicaActiva)));
        cards.show(cuerpo, pestaniaAcademicaActiva);

        contenedor.add(barra, BorderLayout.NORTH);
        contenedor.add(cuerpo, BorderLayout.CENTER);
        return contenedor;
    }

    private void marcarPestania(JButton boton, boolean activa) {
        boton.setOpaque(true);
        boton.setContentAreaFilled(true);
        boton.setBackground(activa ? Estilos.ACCENT : Estilos.aclarar(Estilos.FONDO_TARJETA, 0.05));
        boton.setForeground(activa ? Color.WHITE : Estilos.TEXTO_SEC);
    }

    /** Un campo de un formulario CRUD genérico: etiqueta visible, columna SQL, y tipo ("texto" | "numero"). */
    private record CampoForm(String etiqueta, String columna, String tipo) {}

    /**
     * Construye un panel CRUD simple y genérico para tablas de catálogo con
     * pocos campos (materia, curso, salón, horario, periodo): tabla con lo
     * existente + formulario para agregar + botón para eliminar lo
     * seleccionado. Evita repetir el mismo código 5 veces.
     */
    private JPanel panelCrudSimple(String tabla, String columnaId, String nombreSingular, String articulo, List<CampoForm> campos) {
        JPanel raiz = columna();

        List<Map<String, Object>> filas = DB.query("SELECT * FROM " + tabla + " ORDER BY " + columnaId + " DESC");

        // ── Formulario para agregar ──
        JPanel form = new JPanel(new GridBagLayout());
        form.setOpaque(false);
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 6, 6, 6);
        gc.anchor = GridBagConstraints.WEST;
        gc.fill = GridBagConstraints.HORIZONTAL;

        Map<String, JTextField> entradas = new LinkedHashMap<>();
        int fila = 0;
        for (CampoForm campo : campos) {
            JTextField input = campoEstilizado("");
            entradas.put(campo.columna(), input);
            gc.gridx = 0; gc.gridy = fila; gc.weightx = 0;
            form.add(etiqueta(campo.etiqueta() + ":"), gc);
            gc.gridx = 1; gc.weightx = 1;
            form.add(input, gc);
            fila++;
        }

        BotonRedondeado guardar = new BotonRedondeado("Agregar " + nombreSingular, 10).colores(Estilos.ROJO, Estilos.aclarar(Estilos.ROJO, 0.15));
        guardar.addActionListener(e -> {
            Object[] valores = new Object[campos.size()];
            for (int i = 0; i < campos.size(); i++) {
                CampoForm c = campos.get(i);
                String texto = entradas.get(c.columna()).getText().trim();
                if (texto.isEmpty()) {
                    Dialogos.advertencia(this, "Completa todos los campos.");
                    return;
                }
                if (c.tipo().equals("numero")) {
                    try {
                        valores[i] = Integer.parseInt(texto);
                    } catch (NumberFormatException ex) {
                        Dialogos.error(this, "«" + c.etiqueta() + "» debe ser un número.");
                        return;
                    }
                } else {
                    valores[i] = texto;
                }
            }
            String columnasSql = String.join(", ", campos.stream().map(CampoForm::columna).toList());
            String signos = String.join(", ", campos.stream().map(c -> "?").toList());
            try {
                long nuevoId = DB.ejecutarYObtenerId("INSERT INTO " + tabla + " (" + columnasSql + ") VALUES (" + signos + ")", valores);
                registrarHistorial("Creó " + articulo + " (ID " + nuevoId + ") en " + tabla);
                Dialogos.exito(this, "Se agregó " + articulo + " correctamente.");
                refrescarModuloActual();
            } catch (RuntimeException ex) {
                Dialogos.error(this, "No se pudo guardar:\n" + ex.getMessage());
            }
        });
        gc.gridx = 1; gc.gridy = fila; gc.weightx = 0;
        form.add(guardar, gc);

        raiz.add(Estilos.crearTarjeta("Agregar " + nombreSingular, form));
        raiz.add(Box.createVerticalStrut(12));

        // ── Listado + eliminar ──
        if (filas.isEmpty()) {
            raiz.add(Estilos.crearEmptyState("Aún no hay " + tabla + " registrados"));
            return raiz;
        }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("#", columnaId);
        for (CampoForm c : campos) cols.put(c.etiqueta(), c.columna());
        JScrollPane scroll = Estilos.crearTabla(cols, filas);
        JTable jTabla = (JTable) scroll.getViewport().getView();

        BotonRedondeado eliminar = new BotonRedondeado("Eliminar seleccionado", 10).colores(Estilos.ROJO, Estilos.aclarar(Estilos.ROJO, 0.15));
        eliminar.addActionListener(e -> {
            int seleccion = jTabla.getSelectedRow();
            if (seleccion < 0) { Dialogos.advertencia(this, "Selecciona una fila de la tabla."); return; }
            Object id = filas.get(seleccion).get(columnaId);
            boolean confirmar = Dialogos.confirmar(this, "¿Eliminar " + articulo + " seleccionado?\nSi está siendo usado en alguna asignación, no se podrá borrar.",
                    "Sí, eliminar", Estilos.ROJO);
            if (!confirmar) return;
            try {
                DB.ejecutar("DELETE FROM " + tabla + " WHERE " + columnaId + " = ?", id);
                registrarHistorial("Eliminó registro (ID " + id + ") de " + tabla);
                Dialogos.exito(this, "Eliminado correctamente.");
                refrescarModuloActual();
            } catch (RuntimeException ex) {
                Dialogos.error(this, "No se pudo eliminar: está siendo usado por otra asignación académica.\nElimina primero esa asignación.");
            }
        });

        JPanel acciones = new JPanel(new FlowLayout(FlowLayout.LEFT));
        acciones.setOpaque(false);
        acciones.add(eliminar);

        raiz.add(scroll);
        raiz.add(acciones);
        return raiz;
    }

    /** Panel especial para asignar un docente a una materia/curso/salón/horario/periodo (asignacion_academica). */
    private JPanel panelAsignacionAcademica() {
        JPanel raiz = columna();

        List<Map<String, Object>> docentes = DB.query("SELECT id_usuario, nombre FROM usuario WHERE id_rol=4 ORDER BY nombre");
        List<Map<String, Object>> materias = DB.query("SELECT id_materia, nombre FROM materia ORDER BY nombre");
        List<Map<String, Object>> cursos = DB.query("SELECT id_curso, nombre FROM curso ORDER BY nombre");
        List<Map<String, Object>> salones = DB.query("SELECT id_salon, nombre FROM salon ORDER BY nombre");
        List<Map<String, Object>> horarios = DB.query("SELECT id_horario, dia, hora_inicio, hora_fin FROM horario ORDER BY dia, hora_inicio");
        List<Map<String, Object>> periodos = DB.query("SELECT id_periodo, nombre FROM periodo_academico ORDER BY id_periodo DESC");

        if (docentes.isEmpty() || materias.isEmpty() || cursos.isEmpty() || salones.isEmpty() || horarios.isEmpty() || periodos.isEmpty()) {
            raiz.add(Estilos.crearAlertaInfo(
                    "Antes de crear una asignación necesitas al menos un docente, una materia, un curso, un salón, un horario y un periodo académico. Créalos en las otras pestañas primero."));
            return raiz;
        }

        JComboBox<String> comboDocente = new JComboBox<>();
        for (Map<String, Object> d : docentes) comboDocente.addItem(Estilos.texto(d, "nombre"));
        JComboBox<String> comboMateria = new JComboBox<>();
        for (Map<String, Object> m : materias) comboMateria.addItem(Estilos.texto(m, "nombre"));
        JComboBox<String> comboCurso = new JComboBox<>();
        for (Map<String, Object> c : cursos) comboCurso.addItem(Estilos.texto(c, "nombre"));
        JComboBox<String> comboSalon = new JComboBox<>();
        for (Map<String, Object> s : salones) comboSalon.addItem(Estilos.texto(s, "nombre"));
        JComboBox<String> comboHorario = new JComboBox<>();
        for (Map<String, Object> h : horarios) comboHorario.addItem(Estilos.texto(h, "dia") + " " + Estilos.texto(h, "hora_inicio") + "–" + Estilos.texto(h, "hora_fin"));
        JComboBox<String> comboPeriodo = new JComboBox<>();
        for (Map<String, Object> p : periodos) comboPeriodo.addItem(Estilos.texto(p, "nombre"));

        JPanel form = new JPanel(new GridBagLayout());
        form.setOpaque(false);
        GridBagConstraints gc = new GridBagConstraints();
        gc.insets = new Insets(6, 6, 6, 6);
        gc.anchor = GridBagConstraints.WEST;
        gc.fill = GridBagConstraints.HORIZONTAL;

        gc.gridx = 0; gc.gridy = 0; form.add(etiqueta("Docente:"), gc);
        gc.gridx = 1; form.add(comboDocente, gc);
        gc.gridx = 0; gc.gridy = 1; form.add(etiqueta("Materia:"), gc);
        gc.gridx = 1; form.add(comboMateria, gc);
        gc.gridx = 0; gc.gridy = 2; form.add(etiqueta("Curso:"), gc);
        gc.gridx = 1; form.add(comboCurso, gc);
        gc.gridx = 0; gc.gridy = 3; form.add(etiqueta("Salón:"), gc);
        gc.gridx = 1; form.add(comboSalon, gc);
        gc.gridx = 0; gc.gridy = 4; form.add(etiqueta("Horario:"), gc);
        gc.gridx = 1; form.add(comboHorario, gc);
        gc.gridx = 0; gc.gridy = 5; form.add(etiqueta("Periodo:"), gc);
        gc.gridx = 1; form.add(comboPeriodo, gc);

        BotonRedondeado guardar = new BotonRedondeado("Crear asignación", 10).colores(Estilos.ROJO, Estilos.aclarar(Estilos.ROJO, 0.15));
        guardar.addActionListener(e -> {
            int idDocente = ((Number) docentes.get(comboDocente.getSelectedIndex()).get("id_usuario")).intValue();
            int idMateria = ((Number) materias.get(comboMateria.getSelectedIndex()).get("id_materia")).intValue();
            int idCurso = ((Number) cursos.get(comboCurso.getSelectedIndex()).get("id_curso")).intValue();
            int idSalon = ((Number) salones.get(comboSalon.getSelectedIndex()).get("id_salon")).intValue();
            int idHorario = ((Number) horarios.get(comboHorario.getSelectedIndex()).get("id_horario")).intValue();
            int idPeriodo = ((Number) periodos.get(comboPeriodo.getSelectedIndex()).get("id_periodo")).intValue();
            try {
                long nuevoId = DB.ejecutarYObtenerId(
                        "INSERT INTO asignacion_academica (id_docente, id_materia, id_curso, id_salon, id_horario, id_periodo) VALUES (?,?,?,?,?,?)",
                        idDocente, idMateria, idCurso, idSalon, idHorario, idPeriodo);
                registrarHistorial("Creó asignación académica (ID " + nuevoId + ")");
                Dialogos.exito(this, "Asignación creada correctamente. Ya puedes matricular estudiantes en esa clase.");
                refrescarModuloActual();
            } catch (RuntimeException ex) {
                Dialogos.error(this, "No se pudo crear la asignación:\n" + ex.getMessage());
            }
        });
        gc.gridx = 1; gc.gridy = 6; form.add(guardar, gc);

        raiz.add(Estilos.crearTarjeta("Nueva asignación (profesor → clase)", form));
        raiz.add(Box.createVerticalStrut(12));

        List<Map<String, Object>> existentes = asignaciones();
        if (existentes.isEmpty()) {
            raiz.add(Estilos.crearEmptyState("Aún no hay asignaciones académicas registradas"));
            return raiz;
        }
        LinkedHashMap<String, String> cols = new LinkedHashMap<>();
        cols.put("#", "id_asignacion"); cols.put("Docente", "docente"); cols.put("Materia", "materia"); cols.put("Curso", "curso");
        cols.put("Salón", "salon"); cols.put("Día", "dia"); cols.put("Hora inicio", "hora_inicio");
        cols.put("Hora fin", "hora_fin"); cols.put("Periodo", "periodo");
        JScrollPane scroll = Estilos.crearTabla(cols, existentes);
        JTable jTabla = (JTable) scroll.getViewport().getView();

        BotonRedondeado eliminar = new BotonRedondeado("Eliminar seleccionada", 10).colores(Estilos.ROJO, Estilos.aclarar(Estilos.ROJO, 0.15));
        eliminar.addActionListener(e -> {
            int seleccion = jTabla.getSelectedRow();
            if (seleccion < 0) { Dialogos.advertencia(this, "Selecciona una asignación de la tabla."); return; }
            Object id = existentes.get(seleccion).get("id_asignacion");
            boolean confirmar = Dialogos.confirmar(this,
                    "¿Eliminar esta asignación?\nSi ya tiene estudiantes matriculados o notas registradas, no se podrá borrar.",
                    "Sí, eliminar", Estilos.ROJO);
            if (!confirmar) return;
            try {
                DB.ejecutar("DELETE FROM asignacion_academica WHERE id_asignacion = ?", id);
                registrarHistorial("Eliminó asignación académica (ID " + id + ")");
                Dialogos.exito(this, "Asignación eliminada correctamente.");
                refrescarModuloActual();
            } catch (RuntimeException ex) {
                Dialogos.error(this, "No se pudo eliminar: ya tiene estudiantes matriculados, notas u observaciones asociadas.");
            }
        });
        JPanel acciones = new JPanel(new FlowLayout(FlowLayout.LEFT));
        acciones.setOpaque(false);
        acciones.add(eliminar);

        raiz.add(scroll);
        raiz.add(acciones);
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
private JPanel panelReportes() {
    long totalUsuarios = DB.contar("SELECT COUNT(*) FROM usuario");
    long totalDocentes = DB.contar("SELECT COUNT(*) FROM usuario WHERE id_rol=4");
    long totalEstudiantes = DB.contar("SELECT COUNT(*) FROM usuario WHERE id_rol=5");

    JPanel raiz = columna();

    raiz.add(Estilos.crearTituloSeccion("Reportes Generales"));

    raiz.add(Estilos.crearGridStats(
            Estilos.crearStatCard(String.valueOf(totalUsuarios), "Usuarios totales", Estilos.ROJO),
            Estilos.crearStatCard(String.valueOf(totalDocentes), "Docentes", Estilos.AZUL),
            Estilos.crearStatCard(String.valueOf(totalEstudiantes), "Estudiantes", Estilos.VERDE)
    ));

    raiz.add(Box.createVerticalStrut(20));

    BotonRedondeado btnReportes =
            new BotonRedondeado("Generar reportes", 10)
                    .colores(Estilos.ROJO, Estilos.ROJO.brighter());

    btnReportes.addActionListener(e -> abrirAplicacionReportes());

    JPanel acciones = new JPanel(new FlowLayout(FlowLayout.LEFT));
    acciones.setOpaque(false);
    acciones.add(btnReportes);

    raiz.add(acciones);

    return raiz;
}
private void abrirAplicacionReportes() {
    try {
        Desktop.getDesktop().browse(
                new URI("http://localhost:8080/")
        );
    } catch (Exception ex) {
        Dialogos.error(
                this,
                "No se pudo abrir la aplicación de reportes:\n" + ex.getMessage()
        );
    }
}
    private JLabel etiqueta(String texto) {
        JLabel l = new JLabel(texto);
        l.setForeground(Estilos.TEXTO_SEC);
        return l;
    }
}