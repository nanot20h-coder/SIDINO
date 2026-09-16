package com.sidino.ui.componentes;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;

/**
 * Diálogos modales con estilo propio (tarjeta redondeada, oscura, con
 * icono y botones a juego con la app), para reemplazar los JOptionPane
 * genéricos de Swing —que se ven con el look por defecto del sistema
 * operativo y no combinan con el resto de la interfaz.
 */
public class Dialogos {

    private Dialogos() {}

    public enum Tipo {
        INFO("info", Estilos.AZUL),
        EXITO("exito", Estilos.VERDE),
        ERROR("error", Estilos.ROJO),
        ADVERTENCIA("advertencia", Estilos.AMBAR);

        /** Clave para Iconos.crear(...), no un emoji. */
        final String icono;
        final Color color;
        Tipo(String icono, Color color) { this.icono = icono; this.color = color; }
    }

    public static void info(Component parent, String mensaje) { mostrarMensaje(parent, "Información", mensaje, Tipo.INFO); }
    public static void exito(Component parent, String mensaje) { mostrarMensaje(parent, "Listo", mensaje, Tipo.EXITO); }
    public static void error(Component parent, String mensaje) { mostrarMensaje(parent, "Error", mensaje, Tipo.ERROR); }
    public static void advertencia(Component parent, String mensaje) { mostrarMensaje(parent, "Atención", mensaje, Tipo.ADVERTENCIA); }

    private static void mostrarMensaje(Component parent, String titulo, String mensaje, Tipo tipo) {
        JDialog dialogo = crearBase(parent, titulo);
        JPanel contenido = panelContenido(tipo, mensaje);

        BotonRedondeado ok = new BotonRedondeado("Aceptar", 10).colores(tipo.color, Estilos.aclarar(tipo.color, 0.15));
        ok.setPreferredSize(new Dimension(120, 38));
        ok.addActionListener(e -> dialogo.dispose());

        JPanel pieBotones = new JPanel(new FlowLayout(FlowLayout.CENTER));
        pieBotones.setOpaque(false);
        pieBotones.add(ok);
        contenido.add(pieBotones, BorderLayout.SOUTH);

        dialogo.getContentPane().add(contenido);
        dialogo.getRootPane().setDefaultButton(ok);
        dialogo.pack();
        dialogo.setLocationRelativeTo(parent);
        dialogo.setVisible(true);
    }

    /** Diálogo de confirmación Sí/No. Devuelve true si el usuario aceptó. */
    public static boolean confirmar(Component parent, String mensaje) {
        return confirmar(parent, mensaje, "Sí, continuar", Estilos.ACCENT);
    }

    public static boolean confirmar(Component parent, String mensaje, String textoConfirmar, Color colorConfirmar) {
        JDialog dialogo = crearBase(parent, "Confirmar");
        JPanel contenido = panelContenido(Tipo.ADVERTENCIA, mensaje);

        boolean[] resultado = {false};
        BotonRedondeado cancelar = new BotonRedondeado("Cancelar", 10).colores(Estilos.BORDE, Estilos.aclarar(Estilos.BORDE, 0.2));
        BotonRedondeado aceptar = new BotonRedondeado(textoConfirmar, 10).colores(colorConfirmar, Estilos.aclarar(colorConfirmar, 0.15));
        cancelar.setPreferredSize(new Dimension(120, 38));
        aceptar.setPreferredSize(new Dimension(150, 38));
        cancelar.addActionListener(e -> dialogo.dispose());
        aceptar.addActionListener(e -> { resultado[0] = true; dialogo.dispose(); });

        JPanel pieBotones = new JPanel(new FlowLayout(FlowLayout.CENTER, 12, 0));
        pieBotones.setOpaque(false);
        pieBotones.add(cancelar);
        pieBotones.add(aceptar);
        contenido.add(pieBotones, BorderLayout.SOUTH);

        dialogo.getContentPane().add(contenido);
        dialogo.getRootPane().setDefaultButton(aceptar);
        dialogo.pack();
        dialogo.setLocationRelativeTo(parent);
        dialogo.setVisible(true);
        return resultado[0];
    }

    /**
     * Diálogo de formulario genérico: envuelve cualquier JComponent (por
     * ejemplo un panel con campos de texto) en la misma tarjeta con botones
     * Cancelar/Guardar. Devuelve true si el usuario confirmó.
     */
    public static boolean formulario(Component parent, String titulo, JComponent formulario, String textoConfirmar) {
        JDialog dialogo = crearBase(parent, titulo);

        PanelRedondeadoPublico tarjeta = new PanelRedondeadoPublico(16);
        tarjeta.setBackground(Estilos.FONDO_TARJETA);
        tarjeta.setLayout(new BorderLayout(0, 16));
        tarjeta.setBorder(new EmptyBorder(22, 26, 20, 26));

        JLabel lblTitulo = new JLabel(titulo);
        lblTitulo.setFont(new Font("Arial", Font.BOLD, 17));
        lblTitulo.setForeground(Estilos.TEXTO);
        tarjeta.add(lblTitulo, BorderLayout.NORTH);
        tarjeta.add(formulario, BorderLayout.CENTER);

        boolean[] resultado = {false};
        BotonRedondeado cancelar = new BotonRedondeado("Cancelar", 10).colores(Estilos.BORDE, Estilos.aclarar(Estilos.BORDE, 0.2));
        BotonRedondeado aceptar = new BotonRedondeado(textoConfirmar, 10).colores(Estilos.ACCENT, Estilos.aclarar(Estilos.ACCENT, 0.15));
        cancelar.setPreferredSize(new Dimension(120, 38));
        aceptar.setPreferredSize(new Dimension(160, 38));
        cancelar.addActionListener(e -> dialogo.dispose());
        aceptar.addActionListener(e -> { resultado[0] = true; dialogo.dispose(); });

        JPanel pieBotones = new JPanel(new FlowLayout(FlowLayout.CENTER, 12, 0));
        pieBotones.setOpaque(false);
        pieBotones.add(cancelar);
        pieBotones.add(aceptar);
        tarjeta.add(pieBotones, BorderLayout.SOUTH);

        dialogo.getContentPane().add(tarjeta);
        dialogo.getRootPane().setDefaultButton(aceptar);
        dialogo.pack();
        dialogo.setLocationRelativeTo(parent);
        dialogo.setVisible(true);
        return resultado[0];
    }

    private static JDialog crearBase(Component parent, String titulo) {
        Window ventana = SwingUtilities.getWindowAncestor(parent);
        JDialog dialogo = new JDialog(ventana, titulo, Dialog.ModalityType.APPLICATION_MODAL);
        dialogo.setUndecorated(true);
        dialogo.setBackground(new Color(0, 0, 0, 0));
        dialogo.getContentPane().setBackground(new Color(0, 0, 0, 0));
        dialogo.setLayout(new BorderLayout());
        return dialogo;
    }

    private static JPanel panelContenido(Tipo tipo, String mensaje) {
        PanelRedondeadoPublico tarjeta = new PanelRedondeadoPublico(16);
        tarjeta.setBackground(Estilos.FONDO_TARJETA);
        tarjeta.setLayout(new BorderLayout(0, 18));
        tarjeta.setBorder(new EmptyBorder(28, 28, 22, 28));

        JPanel icono = new JPanel(new BorderLayout());
        icono.setOpaque(false);
        JLabel circulo = new JLabel(Iconos.crear(tipo.icono, tipo.color, 24), SwingConstants.CENTER) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setColor(new Color(tipo.color.getRed(), tipo.color.getGreen(), tipo.color.getBlue(), 40));
                g2.fillOval(0, 0, getWidth(), getHeight());
                g2.dispose();
                super.paintComponent(g);
            }
        };
        circulo.setVerticalAlignment(SwingConstants.CENTER);
        circulo.setPreferredSize(new Dimension(52, 52));
        icono.add(circulo, BorderLayout.CENTER);
        JPanel envoltorioIcono = new JPanel(new FlowLayout(FlowLayout.CENTER));
        envoltorioIcono.setOpaque(false);
        envoltorioIcono.add(icono);

        JLabel lblMensaje = new JLabel("<html><div style='text-align:center;width:280px'>" + mensaje.replace("\n", "<br>") + "</div></html>");
        lblMensaje.setForeground(Estilos.TEXTO);
        lblMensaje.setFont(Estilos.FUENTE_NORMAL);
        lblMensaje.setHorizontalAlignment(SwingConstants.CENTER);

        JPanel centro = new JPanel();
        centro.setOpaque(false);
        centro.setLayout(new BoxLayout(centro, BoxLayout.Y_AXIS));
        envoltorioIcono.setAlignmentX(Component.CENTER_ALIGNMENT);
        lblMensaje.setAlignmentX(Component.CENTER_ALIGNMENT);
        centro.add(envoltorioIcono);
        centro.add(Box.createVerticalStrut(6));
        centro.add(lblMensaje);

        tarjeta.add(centro, BorderLayout.CENTER);
        return tarjeta;
    }

    /** Variante pública (no-package-private) del panel redondeado, para usarlo desde Dialogos. */
    static class PanelRedondeadoPublico extends JPanel {
        private final int radio;
        PanelRedondeadoPublico(int radio) { this.radio = radio; setOpaque(false); }
        @Override
        protected void paintComponent(Graphics g) {
            Graphics2D g2 = (Graphics2D) g.create();
            g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
            g2.setColor(new Color(0, 0, 0, 60));
            g2.fillRoundRect(3, 4, getWidth() - 3, getHeight() - 3, radio, radio);
            g2.setColor(getBackground());
            g2.fillRoundRect(0, 0, getWidth() - 3, getHeight() - 3, radio, radio);
            g2.setColor(Estilos.BORDE);
            g2.setStroke(new BasicStroke(1));
            g2.drawRoundRect(0, 0, getWidth() - 4, getHeight() - 4, radio, radio);
            g2.dispose();
            super.paintComponent(g);
        }
    }
}