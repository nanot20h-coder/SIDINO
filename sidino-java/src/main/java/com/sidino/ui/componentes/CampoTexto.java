package com.sidino.ui.componentes;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.awt.event.FocusAdapter;
import java.awt.event.FocusEvent;

/**
 * Campo de texto con esquinas redondeadas y fondo oscuro a juego con el
 * resto de la app (tarjetas, botones), en vez del JTextField blanco y
 * cuadrado que trae Swing por defecto. Al enfocarse, el borde cambia al
 * color de acento (Estilos.ACCENT) para dar retroalimentación visual clara.
 */
public class CampoTexto extends JTextField {

    private static final int RADIO = 10;
    private boolean enfocado;

    public CampoTexto() { this("", 0); }
    public CampoTexto(int columnas) { this("", columnas); }
    public CampoTexto(String texto) { this(texto, 0); }

    public CampoTexto(String texto, int columnas) {
        super(texto, columnas);
        setOpaque(false);
        setForeground(Estilos.TEXTO);
        setCaretColor(Estilos.ACCENT);
        setFont(Estilos.FUENTE_NORMAL);
        setSelectionColor(Estilos.ACCENT);
        setBorder(new EmptyBorder(8, 12, 8, 12));
        addFocusListener(new FocusAdapter() {
            @Override public void focusGained(FocusEvent e) { enfocado = true; repaint(); }
            @Override public void focusLost(FocusEvent e) { enfocado = false; repaint(); }
        });
    }

    @Override
    protected void paintComponent(Graphics g) {
        Graphics2D g2 = (Graphics2D) g.create();
        g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
        g2.setColor(Estilos.aclarar(Estilos.FONDO_TARJETA, 0.06));
        g2.fillRoundRect(0, 0, getWidth(), getHeight(), RADIO, RADIO);
        g2.setColor(enfocado ? Estilos.ACCENT : Estilos.BORDE);
        g2.setStroke(new BasicStroke(enfocado ? 1.6f : 1f));
        g2.drawRoundRect(0, 0, getWidth() - 1, getHeight() - 1, RADIO, RADIO);
        g2.dispose();
        super.paintComponent(g);
    }

    @Override
    protected void paintBorder(Graphics g) {
        // El borde ya se pinta (redondeado) dentro de paintComponent().
    }
}