package com.sidino.ui.componentes;

import javax.swing.*;
import java.awt.*;
import java.awt.event.FocusAdapter;
import java.awt.event.FocusEvent;

/** Campo de texto con bordes redondeados y línea inferior que se resalta al enfocar. */
public class RTF extends JTextField {
    private final int radio;
    private boolean enfocado;

    public RTF(int radio) {
        this.radio = radio;
        setOpaque(false);
        addFocusListener(new FocusAdapter() {
            public void focusGained(FocusEvent e) { enfocado = true; repaint(); }
            public void focusLost(FocusEvent e) { enfocado = false; repaint(); }
        });
    }

    @Override
    protected void paintComponent(Graphics g) {
        Graphics2D g2 = (Graphics2D) g.create();
        g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
        g2.setColor(getBackground());
        g2.fillRoundRect(0, 0, getWidth(), getHeight(), radio, radio);
        super.paintComponent(g);
        g2.dispose();
    }

    @Override
    protected void paintBorder(Graphics g) {
        Graphics2D g2 = (Graphics2D) g.create();
        g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
        g2.setColor(enfocado ? new Color(0, 230, 210) : new Color(64, 180, 200));
        g2.setStroke(new BasicStroke(enfocado ? 3 : 2));
        g2.drawLine(radio / 2, getHeight() - 1, getWidth() - radio / 2, getHeight() - 1);
        g2.dispose();
    }
}
