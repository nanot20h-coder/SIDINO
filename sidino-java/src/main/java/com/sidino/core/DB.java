package com.sidino.core;

import java.sql.*;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

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
