package com.sidino.core;

/**
 * Sustituto de $_SESSION del proyecto PHP. Como la app ahora es un único
 * proceso de escritorio (no hay peticiones HTTP independientes), basta con
 * un contenedor estático con los datos del usuario autenticado.
 */
public class Sesion {
    public static int idUsuario;
    public static String nombre;
    public static String correo;
    public static int idRol;
    /** nombre del rol en minúsculas: rectoria, coordinacion, administrativo, docente, estudiante, acudiente */
    public static String rol;

    public static void iniciar(int idUsuario, String nombre, String correo, int idRol, String rol) {
        Sesion.idUsuario = idUsuario;
        Sesion.nombre = nombre;
        Sesion.correo = correo;
        Sesion.idRol = idRol;
        Sesion.rol = rol;
    }

    public static void cerrar() {
        idUsuario = 0;
        nombre = null;
        correo = null;
        idRol = 0;
        rol = null;
    }

    public static boolean activa() {
        return idUsuario > 0;
    }
}
