package com.sidino.core;

import java.sql.Connection;
import java.sql.Date;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.ResultSetMetaData;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.function.Consumer;

/**
 * Capa de acceso a datos genérica.
 *
 * Reemplaza el patrón "$pdo->query(...)->fetchAll()" / "->fetchColumn()" /
 * "$pdo->prepare(...)->execute([...])" del proyecto PHP original, para poder
 * portar el SQL casi tal cual a Java sin tener que escribir una clase DAO
 * distinta por cada tabla.
 *
 * Cada fila se representa como un Map<String,Object> (clave = nombre de
 * columna, tal como en los arreglos asociativos de PHP).
 */
public class DB {

    /** Equivalente a $pdo->query($sql)->fetchAll() o $pdo->prepare($sql)->execute($params) + fetchAll() */
    public static List<Map<String, Object>> query(String sql, Object... params) {
        List<Map<String, Object>> filas = new ArrayList<>();
        try (Connection con = Conexion.obtener();
             PreparedStatement ps = con.prepareStatement(sql)) {
            bind(ps, params);
            try (ResultSet rs = ps.executeQuery()) {
                ResultSetMetaData md = rs.getMetaData();
                int columnas = md.getColumnCount();
                while (rs.next()) {
                    Map<String, Object> fila = new LinkedHashMap<>();
                    for (int i = 1; i <= columnas; i++) {
                        fila.put(md.getColumnLabel(i), rs.getObject(i));
                    }
                    filas.add(fila);
                }
            }
        } catch (SQLException e) {
            throw new RuntimeException("Error de base de datos: " + e.getMessage(), e);
        }
        return filas;
    }

    /** Equivalente a $pdo->query($sql)->fetchColumn() */
    public static Object escalar(String sql, Object... params) {
        List<Map<String, Object>> filas = query(sql, params);
        if (filas.isEmpty()) return null;
        return filas.get(0).values().iterator().next();
    }

    /** Escalar convertido a long, útil para SELECT COUNT(*) */
    public static long contar(String sql, Object... params) {
        Object v = escalar(sql, params);
        return v == null ? 0L : ((Number) v).longValue();
    }

    /** Equivalente a $pdo->prepare($sql)->execute($params) para INSERT/UPDATE/DELETE */
    public static int ejecutar(String sql, Object... params) {
        try (Connection con = Conexion.obtener();
             PreparedStatement ps = con.prepareStatement(sql)) {
            bind(ps, params);
            return ps.executeUpdate();
        } catch (SQLException e) {
            throw new RuntimeException("Error de base de datos: " + e.getMessage(), e);
        }
    }

    /** Ejecuta varias operaciones en una única transacción. */
    public static void transaccion(Consumer<Connection> operaciones) {
        try (Connection con = Conexion.obtener()) {
            boolean autoCommitOriginal = con.getAutoCommit();
            con.setAutoCommit(false);
            try {
                operaciones.accept(con);
                con.commit();
            } catch (RuntimeException | SQLException e) {
                con.rollback();
                if (e instanceof RuntimeException runtimeException) throw runtimeException;
                throw new RuntimeException("Error de base de datos: " + e.getMessage(), e);
            } finally {
                con.setAutoCommit(autoCommitOriginal);
            }
        } catch (SQLException e) {
            throw new RuntimeException("Error de base de datos: " + e.getMessage(), e);
        }
    }

    /** Elimina un usuario y todos sus registros dependientes respetando las FK. */
    public static void eliminarUsuario(int idUsuario) {
        transaccion(con -> {
            ejecutar(con, "DELETE FROM usuario_atributo WHERE id_usuario = ?", idUsuario);
            ejecutar(con, "DELETE FROM historial_chatbot WHERE id_usuario = ?", idUsuario);
            ejecutar(con, "DELETE FROM acudiente_estudiante WHERE id_acudiente = ? OR id_estudiante = ?", idUsuario, idUsuario);
            ejecutar(con, "DELETE FROM historial_accion WHERE id_usuario = ?", idUsuario);

            ejecutar(con, "DELETE bd FROM boletin_detalle bd JOIN boletin b ON b.id_boletin = bd.id_boletin WHERE b.id_estudiante = ?", idUsuario);
            ejecutar(con, "DELETE FROM boletin WHERE id_estudiante = ?", idUsuario);
            ejecutar(con, "DELETE n FROM nota n JOIN matricula m ON m.id_matricula = n.id_matricula WHERE m.id_estudiante = ?", idUsuario);
            ejecutar(con, "DELETE FROM matricula WHERE id_estudiante = ?", idUsuario);
            ejecutar(con, "DELETE FROM citacion WHERE id_estudiante = ?", idUsuario);
            ejecutar(con, "DELETE FROM observador WHERE id_estudiante = ?", idUsuario);

            ejecutar(con, "DELETE bd FROM boletin_detalle bd JOIN nota n ON n.id_nota = bd.id_nota JOIN matricula m ON m.id_matricula = n.id_matricula JOIN asignacion_academica a ON a.id_asignacion = m.id_asignacion WHERE a.id_docente = ?", idUsuario);
            ejecutar(con, "DELETE n FROM nota n JOIN matricula m ON m.id_matricula = n.id_matricula JOIN asignacion_academica a ON a.id_asignacion = m.id_asignacion WHERE a.id_docente = ?", idUsuario);
            ejecutar(con, "DELETE FROM matricula WHERE id_asignacion IN (SELECT id_asignacion FROM asignacion_academica WHERE id_docente = ?)", idUsuario);
            ejecutar(con, "DELETE FROM citacion WHERE id_asignacion IN (SELECT id_asignacion FROM asignacion_academica WHERE id_docente = ?)", idUsuario);
            ejecutar(con, "DELETE FROM observador WHERE id_asignacion IN (SELECT id_asignacion FROM asignacion_academica WHERE id_docente = ?)", idUsuario);
            ejecutar(con, "DELETE FROM contenido WHERE id_asignacion IN (SELECT id_asignacion FROM asignacion_academica WHERE id_docente = ?)", idUsuario);
            ejecutar(con, "DELETE FROM asignacion_academica WHERE id_docente = ?", idUsuario);

            ejecutar(con, "DELETE FROM usuario WHERE id_usuario = ?", idUsuario);
        });
    }

    /** Genera un boletín único para un estudiante y periodo, incluyendo sus notas. */
    public static boolean generarBoletin(int idEstudiante, int idPeriodo) {
        final boolean[] creado = {false};
        transaccion(con -> {
            try {
                if (contar("SELECT COUNT(*) FROM boletin WHERE id_estudiante = ? AND id_periodo = ?", idEstudiante, idPeriodo) > 0) {
                    return;
                }
                long idBoletin = insertar(con,
                        "INSERT INTO boletin (id_estudiante, id_periodo, fecha) VALUES (?, ?, ?)",
                        idEstudiante, idPeriodo, new Date(System.currentTimeMillis()));
                ejecutar(con, """
                        INSERT INTO boletin_detalle (id_boletin, id_nota)
                        SELECT ?, n.id_nota
                        FROM nota n
                        JOIN matricula mat ON n.id_matricula = mat.id_matricula
                        JOIN asignacion_academica aa ON mat.id_asignacion = aa.id_asignacion
                        WHERE mat.id_estudiante = ? AND aa.id_periodo = ?
                        """, idBoletin, idEstudiante, idPeriodo);
                creado[0] = true;
            } catch (RuntimeException ex) {
                throw ex;
            }
        });
        return creado[0];
    }

    /** Crea una asignación académica. Las reglas de disponibilidad las gestiona la base de datos. */
    public static long crearAsignacionAcademica(int idDocente, int idMateria, int idCurso,
                                                int idSalon, int idHorario, int idPeriodo) {
        final long[] id = {0};
        transaccion(con -> {
            long duplicada = contar(con, """
                SELECT COUNT(*)
                FROM asignacion_academica
                WHERE id_docente = ? AND id_materia = ? AND id_curso = ?
                  AND id_salon = ? AND id_horario = ? AND id_periodo = ?
                """, idDocente, idMateria, idCurso, idSalon, idHorario, idPeriodo);
            if (duplicada > 0) {
            throw new IllegalStateException("Esa asignación ya existe.");
            }
            id[0] = insertar(con,
                    "INSERT INTO asignacion_academica (id_docente, id_materia, id_curso, id_salon, id_horario, id_periodo) VALUES (?,?,?,?,?,?)",
                    idDocente, idMateria, idCurso, idSalon, idHorario, idPeriodo);
        });
        return id[0];
    }

    public static long crearMateria(String nombre) {
        return crearCatalogo("materia", "nombre", nombre,
                "Ya existe una materia con ese nombre.");
    }

    public static long crearSalon(String nombre, int capacidad, String ubicacion) {
        final long[] id = {0};
        transaccion(con -> {
            if (contar(con, "SELECT COUNT(*) FROM salon WHERE nombre = ? AND ubicacion = ?",
                    nombre, ubicacion) > 0) {
                throw new IllegalStateException("Ya existe un salón con ese nombre y ubicación.");
            }
            id[0] = insertar(con,
                    "INSERT INTO salon (nombre, capacidad, ubicacion) VALUES (?, ?, ?)",
                    nombre, capacidad, ubicacion);
        });
        return id[0];
    }

    public static long crearHorario(String dia, String horaInicio, String horaFin) {
        final long[] id = {0};
        transaccion(con -> {
            if (contar(con, "SELECT COUNT(*) FROM horario WHERE hora_inicio = ? AND hora_fin = ?",
                    horaInicio, horaFin) > 0) {
                throw new IllegalStateException("Ya existe un horario con esa hora de inicio y fin.");
            }
            id[0] = insertar(con,
                    "INSERT INTO horario (dia, hora_inicio, hora_fin) VALUES (?, ?, ?)",
                    dia, horaInicio, horaFin);
        });
        return id[0];
    }

    private static long crearCatalogo(String tabla, String columna, String valor, String mensajeDuplicado) {
        final long[] id = {0};
        transaccion(con -> {
            if (contar(con, "SELECT COUNT(*) FROM " + tabla + " WHERE " + columna + " = ?", valor) > 0) {
                throw new IllegalStateException(mensajeDuplicado);
            }
            id[0] = insertar(con, "INSERT INTO " + tabla + " (" + columna + ") VALUES (?)", valor);
        });
        return id[0];
    }

    /** Crea una matrícula y evita duplicar al estudiante o asignarlo a otro docente. */
    public static long crearMatricula(int idEstudiante, int idAsignacion) {
        final long[] id = {0};
        transaccion(con -> {
            if (contar(con, """
                    SELECT COUNT(*)
                    FROM matricula mat
                    JOIN asignacion_academica aa ON aa.id_asignacion = mat.id_asignacion
                    WHERE mat.id_estudiante = ? AND mat.id_asignacion = ?
                    """, idEstudiante, idAsignacion) > 0) {
                throw new IllegalStateException("El estudiante ya está matriculado en esa clase.");
            }
            if (contar(con, """
                    SELECT COUNT(*)
                    FROM matricula mat
                    JOIN asignacion_academica existente ON existente.id_asignacion = mat.id_asignacion
                    JOIN asignacion_academica nueva ON nueva.id_asignacion = ?
                    WHERE mat.id_estudiante = ? AND existente.id_docente <> nueva.id_docente
                    """, idAsignacion, idEstudiante) > 0) {
                throw new IllegalStateException("El estudiante ya fue matriculado con otro docente.");
            }
            id[0] = insertar(con,
                    "INSERT INTO matricula (id_estudiante, id_asignacion) VALUES (?, ?)",
                    idEstudiante, idAsignacion);
        });
        return id[0];
    }

    private static void ejecutar(Connection con, String sql, Object... params) {
        try (PreparedStatement ps = con.prepareStatement(sql)) {
            bind(ps, params);
            ps.executeUpdate();
        } catch (SQLException e) {
            throw new RuntimeException("Error de base de datos: " + e.getMessage(), e);
        }
    }

    private static long insertar(Connection con, String sql, Object... params) {
        try (PreparedStatement ps = con.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS)) {
            bind(ps, params);
            ps.executeUpdate();
            try (ResultSet keys = ps.getGeneratedKeys()) {
                if (keys.next()) return keys.getLong(1);
            }
            throw new RuntimeException("La base de datos no devolvió el ID generado.");
        } catch (SQLException e) {
            throw new RuntimeException("Error de base de datos: " + e.getMessage(), e);
        }
    }

    private static long contar(Connection con, String sql, Object... params) {
        try (PreparedStatement ps = con.prepareStatement(sql)) {
            bind(ps, params);
            try (ResultSet rs = ps.executeQuery()) {
                return rs.next() ? rs.getLong(1) : 0L;
            }
        } catch (SQLException e) {
            throw new RuntimeException("Error de base de datos: " + e.getMessage(), e);
        }
    }

    /** Igual que ejecutar(), pero retorna el ID autogenerado (equivalente a $pdo->lastInsertId()) */
    public static long ejecutarYObtenerId(String sql, Object... params) {
        try (Connection con = Conexion.obtener();
             PreparedStatement ps = con.prepareStatement(sql, Statement.RETURN_GENERATED_KEYS)) {
            bind(ps, params);
            ps.executeUpdate();
            try (ResultSet keys = ps.getGeneratedKeys()) {
                if (keys.next()) return keys.getLong(1);
            }
        } catch (SQLException e) {
            throw new RuntimeException("Error de base de datos: " + e.getMessage(), e);
        }
        return 0;
    }

    private static void bind(PreparedStatement ps, Object... params) throws SQLException {
        for (int i = 0; i < params.length; i++) {
            ps.setObject(i + 1, params[i]);
        }
    }
}
