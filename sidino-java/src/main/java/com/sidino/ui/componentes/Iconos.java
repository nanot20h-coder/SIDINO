package com.sidino.ui.componentes;

import javax.swing.Icon;
import java.awt.BasicStroke;
import java.awt.Component;
import java.awt.Graphics;
import java.awt.Graphics2D;
import java.awt.RenderingHints;

/** Iconos vectoriales pequeños para no depender de emojis ni fuentes externas. */
public final class Iconos {

    private Iconos() {}

    public static Icon navegacion(String clave) {
        return new IconoNavegacion(clave);
    }

    private static final class IconoNavegacion implements Icon {
        private final String clave;

        private IconoNavegacion(String clave) {
            this.clave = clave;
        }

        @Override public int getIconWidth() { return 18; }
        @Override public int getIconHeight() { return 18; }

        @Override
        public void paintIcon(Component componente, Graphics g, int x, int y) {
            Graphics2D g2 = (Graphics2D) g.create();
            g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
            g2.setColor(componente.getForeground());
            g2.setStroke(new BasicStroke(1.7f, BasicStroke.CAP_ROUND, BasicStroke.JOIN_ROUND));
            int left = x + 2;
            int top = y + 2;
            int right = x + 16;

            switch (clave) {
                case "dashboard" -> {
                    g2.drawRoundRect(left, top, 5, 5, 1, 1);
                    g2.drawRoundRect(x + 10, top, 5, 5, 1, 1);
                    g2.drawRoundRect(left, y + 10, 5, 5, 1, 1);
                    g2.drawRoundRect(x + 10, y + 10, 5, 5, 1, 1);
                }
                case "usuarios", "estudiantes", "docentes" -> {
                    g2.drawOval(x + 6, top, 6, 6);
                    g2.drawArc(x + 3, y + 9, 12, 8, 200, 140);
                }
                case "crear_usuario" -> {
                    g2.drawOval(x + 3, top, 6, 6);
                    g2.drawArc(left, y + 9, 10, 7, 190, 150);
                    g2.drawLine(x + 13, y + 8, x + 13, y + 16);
                    g2.drawLine(x + 9, y + 12, x + 17, y + 12);
                }
                case "horario" -> {
                    g2.drawRoundRect(left, top + 1, 14, 13, 2, 2);
                    g2.drawLine(left, y + 6, right, y + 6);
                    g2.drawLine(x + 6, top, x + 6, y + 5);
                    g2.drawLine(x + 12, top, x + 12, y + 5);
                }
                case "notas" -> {
                    g2.drawLine(left, y + 14, x + 13, top + 1);
                    g2.drawLine(x + 13, top + 1, right, y + 4);
                    g2.drawLine(left, y + 14, x + 5, y + 15);
                }
                case "observador", "historial" -> {
                    g2.drawRoundRect(left + 1, top, 12, 15, 2, 2);
                    g2.drawLine(x + 6, y + 6, x + 13, y + 6);
                    g2.drawLine(x + 6, y + 10, x + 13, y + 10);
                }
                case "citaciones" -> {
                    g2.drawLine(x + 9, top, x + 9, y + 11);
                    g2.drawOval(x + 8, y + 14, 2, 2);
                }
                case "boletines" -> {
                    g2.drawRoundRect(left + 1, top, 12, 15, 2, 2);
                    g2.drawLine(x + 6, y + 6, x + 13, y + 6);
                    g2.drawLine(x + 6, y + 10, x + 13, y + 10);
                }
                case "contenido" -> {
                    g2.drawRoundRect(left, y + 5, 14, 10, 2, 2);
                    g2.drawLine(left, y + 5, x + 6, y + 3);
                    g2.drawLine(x + 6, y + 3, x + 10, y + 3);
                    g2.drawLine(x + 10, y + 3, x + 12, y + 5);
                }
                case "asignaciones" -> {
                    g2.drawRoundRect(left, top, 14, 14, 2, 2);
                    g2.drawLine(x + 6, top, x + 6, y + 16);
                    g2.drawLine(x + 11, top, x + 11, y + 16);
                    g2.drawLine(left, y + 7, right, y + 7);
                }
                case "reportes" -> {
                    g2.drawLine(left, y + 15, left, y + 11);
                    g2.drawLine(x + 7, y + 15, x + 7, y + 7);
                    g2.drawLine(x + 12, y + 15, x + 12, top + 1);
                    g2.drawLine(left, y + 4, x + 7, y + 7);
                    g2.drawLine(x + 7, y + 7, right, top + 1);
                }
                default -> g2.drawRoundRect(left, top, 14, 14, 3, 3);
            }
            g2.dispose();
        }
    }
}
