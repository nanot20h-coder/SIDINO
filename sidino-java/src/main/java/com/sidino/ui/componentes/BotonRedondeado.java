package com.sidino.ui.componentes;

import javax.swing.*;
import java.awt.*;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;

/** Botón con esquinas redondeadas, degradado sutil y color de acento dinámico (Estilos.ACCENT por defecto). */
public class BotonRedondeado extends JButton {
    private final int radio;
    private boolean hover;
    private boolean presionado;
    private Color colorBase;
    private Color colorHover;

    public BotonRedondeado(String texto, int radio) {
        this.radio = radio;
        this.colorBase = Estilos.ACCENT;
        this.colorHover = Estilos.aclarar(Estilos.ACCENT, 0.18);
        setText(texto);
        setOpaque(false);
        setFocusPainted(false);
        setBorderPainted(false);
        setContentAreaFilled(false);
        setForeground(Color.WHITE);
        setFont(new Font("Arial", Font.BOLD, 14));
        setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));

        addMouseListener(new MouseAdapter() {
            @Override public void mouseEntered(MouseEvent e) { hover = true; repaint(); }
            @Override public void mouseExited(MouseEvent e) { hover = false; presionado = false; repaint(); }
            @Override public void mousePressed(MouseEvent e) { presionado = true; repaint(); }
            @Override public void mouseReleased(MouseEvent e) { presionado = false; repaint(); }
        });
    }

    public BotonRedondeado colores(Color base, Color hoverColor) {
        this.colorBase = base;
        this.colorHover = hoverColor;
        return this;
    }

    @Override
    protected void paintComponent(Graphics g) {
        Graphics2D g2d = (Graphics2D) g.create();
        g2d.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);

        Color tope = presionado ? Estilos.oscurecer(colorBase, 0.12) : (hover ? colorHover : colorBase);
        Color base = presionado ? Estilos.oscurecer(colorBase, 0.22) : (hover ? colorBase : Estilos.oscurecer(colorBase, 0.12));

        // Sombra suave debajo del botón para que no se vea plano.
        if (!presionado) {
            g2d.setColor(new Color(0, 0, 0, 40));
            g2d.fillRoundRect(0, 3, getWidth(), getHeight() - 2, radio, radio);
        }

        GradientPaint degradado = new GradientPaint(0, 0, tope, 0, getHeight(), base);
        g2d.setPaint(degradado);
        g2d.fillRoundRect(0, presionado ? 2 : 0, getWidth(), getHeight() - (presionado ? 2 : 3), radio, radio);

        super.paintComponent(g);
        g2d.dispose();
    }

    @Override
    protected void paintBorder(Graphics g) {
        // sin borde
    }
}
