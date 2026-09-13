package com.sidino.core;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;

/**
 * Conexión JDBC a la base de datos "sidino" (MySQL/MariaDB).
 * Misma configuración que el proyecto original.
 */
public class Conexion {
    private static final String URL      = "jdbc:mysql://127.0.0.1:3306/sidino";
    private static final String USUARIO  = "root";
    private static final String PASSWORD = "";

    public static Connection obtener() throws SQLException {
        return DriverManager.getConnection(URL, USUARIO, PASSWORD);
    }
}
