package com.sidino.core;

import java.awt.Color;
import java.io.*;
import java.nio.file.*;
import java.util.Properties;

/**
 * Preferencias de apariencia del usuario, persistidas en disco (equivalente
 * al localStorage que usaba el panel de personalización de auth.php:
 * setTheme() / setAccent() / resetColors()).
 *
 * Se guardan en {user.home}/.sidino/preferencias.properties para que
 * sobrevivan entre ejecuciones de la aplicación de escritorio.
 */
public class Preferencias {

    private static final Path ARCHIVO = Paths.get(System.getProperty("user.home"), ".sidino", "preferencias.properties");
    public static final Color ACENTO_DEFECTO = new Color(0, 200, 190);

    private static boolean temaOscuro = true;
    private static Color colorAcento = ACENTO_DEFECTO;

    static { cargar(); }

    public static boolean esTemaOscuro() { return temaOscuro; }
    public static Color getColorAcento() { return colorAcento; }

    public static void setTemaOscuro(boolean oscuro) { temaOscuro = oscuro; guardar(); }
    public static void setColorAcento(Color color) { colorAcento = color; guardar(); }

    public static void restablecer() {
        temaOscuro = true;
        colorAcento = ACENTO_DEFECTO;
        guardar();
    }

    private static void cargar() {
        try {
            if (!Files.exists(ARCHIVO)) return;
            Properties p = new Properties();
            try (InputStream in = Files.newInputStream(ARCHIVO)) {
                p.load(in);
            }
            temaOscuro = Boolean.parseBoolean(p.getProperty("temaOscuro", "true"));
            String rgb = p.getProperty("colorAcento");
            if (rgb != null) {
                String[] partes = rgb.split(",");
                colorAcento = new Color(Integer.parseInt(partes[0]), Integer.parseInt(partes[1]), Integer.parseInt(partes[2]));
            }
        } catch (Exception ignored) {
            // Si el archivo está corrupto o no se puede leer, seguimos con los valores por defecto.
        }
    }

    private static void guardar() {
        try {
            Files.createDirectories(ARCHIVO.getParent());
            Properties p = new Properties();
            p.setProperty("temaOscuro", String.valueOf(temaOscuro));
            p.setProperty("colorAcento", colorAcento.getRed() + "," + colorAcento.getGreen() + "," + colorAcento.getBlue());
            try (OutputStream out = Files.newOutputStream(ARCHIVO)) {
                p.store(out, "Preferencias de apariencia SIDINO");
            }
        } catch (Exception ignored) {
            // Si no se puede escribir a disco, la preferencia sigue activa solo en esta sesión.
        }
    }
}
