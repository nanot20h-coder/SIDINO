package com.sidino.ui.componentes;

import javax.swing.*;
import java.awt.*;

/**
 * Fábrica de íconos vectoriales dibujados con Graphics2D (líneas, círculos y
 * formas simples), en vez de caracteres emoji.
 *
 * Los emojis (📊 ⭐ 📅 🎓 ⚙ ⏻ etc.) dependen de que el sistema operativo
 * tenga instalada una fuente de emoji a color; Swing NO trae una integrada,
 * así que en muchos equipos (sobre todo Windows sin el paquete de emoji, o
 * Linux) esos caracteres se pintan como el típico recuadro vacío "tofu" que
 * se ve en las capturas. Dibujando el ícono nosotros mismos con formas
 * básicas, el resultado es idéntico en cualquier equipo, se puede colorear
 * dinámicamente (para que combine con el acento elegido en Personalizar) y
 * pesa prácticamente nada.
 */
public final class Iconos {

    private Iconos() {}

    /** Crea un ícono del tamaño y color indicados. Ver {@link #dibujar} para las claves disponibles. */
    public static Icon crear(String clave, Color color, int tamano) {
        return new IconoVectorial(clave, color, tamano);
    }

    private static final class IconoVectorial implements Icon {
        private final String clave;
        private final Color color;
        private final int tamano;

        IconoVectorial(String clave, Color color, int tamano) {
            this.clave = clave;
            this.color = color;
            this.tamano = tamano;
        }

        @Override public int getIconWidth() { return tamano; }
        @Override public int getIconHeight() { return tamano; }

        @Override
        public void paintIcon(Component c, Graphics g, int x, int y) {
            Graphics2D g2 = (Graphics2D) g.create();
            g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
            g2.translate(x, y);
            float escala = tamano / 24f;
            g2.scale(escala, escala);
            g2.setColor(color == null ? Color.WHITE : color);
            g2.setStroke(new BasicStroke(2f, BasicStroke.CAP_ROUND, BasicStroke.JOIN_ROUND));
            dibujar(g2, clave);
            g2.dispose();
        }
    }

    /** Todo se dibuja sobre una cuadrícula lógica de 24×24 (luego se escala al tamaño pedido). */
    private static void dibujar(Graphics2D g, String clave) {
        switch (clave) {
            case "dashboard" -> {
                g.fillRoundRect(2, 2, 9, 9, 3, 3);
                g.fillRoundRect(13, 2, 9, 6, 3, 3);
                g.fillRoundRect(13, 10, 9, 12, 3, 3);
                g.fillRoundRect(2, 13, 9, 9, 3, 3);
            }
            case "usuarios" -> {
                g.fillOval(3, 3, 9, 9);
                g.fillRoundRect(1, 13, 13, 9, 5, 5);
                g.setStroke(new BasicStroke(1.8f));
                g.drawOval(15, 5, 6, 6);
                g.drawRoundRect(14, 14, 9, 8, 5, 5);
            }
            case "agregar" -> {
                g.setStroke(new BasicStroke(3f, BasicStroke.CAP_ROUND, BasicStroke.JOIN_ROUND));
                g.drawLine(12, 3, 12, 21);
                g.drawLine(3, 12, 21, 12);
            }
            case "academico" -> {
                g.drawRoundRect(2, 4, 20, 17, 4, 4);
                g.drawLine(2, 10, 22, 10);
                g.drawLine(7, 2, 7, 6);
                g.drawLine(17, 2, 17, 6);
                g.fillRect(6, 14, 4, 3);
                g.fillRect(14, 14, 4, 3);
            }
            case "historial" -> {
                g.drawOval(2, 2, 20, 20);
                g.drawLine(12, 6, 12, 12);
                g.drawLine(12, 12, 17, 15);
            }
            case "reportes" -> {
                g.setStroke(new BasicStroke(2.4f, BasicStroke.CAP_ROUND, BasicStroke.JOIN_ROUND));
                g.drawPolyline(new int[]{2, 9, 13, 22}, new int[]{18, 11, 15, 4}, 4);
                g.fillPolygon(new int[]{22, 22, 15}, new int[]{4, 11, 4}, 3);
            }
            case "notas" -> {
                int[] xs = {12, 15, 22, 16, 18, 12, 6, 8, 2, 9};
                int[] ys = {2, 9, 9, 14, 21, 17, 21, 14, 9, 9};
                g.fillPolygon(xs, ys, xs.length);
            }
            case "horario" -> {
                g.drawRoundRect(2, 4, 20, 18, 4, 4);
                g.drawLine(2, 10, 22, 10);
                g.drawLine(7, 2, 7, 6);
                g.drawLine(17, 2, 17, 6);
                g.fillRect(6, 14, 4, 4);
            }
            case "boletines" -> {
                g.drawRoundRect(4, 2, 16, 20, 3, 3);
                g.drawLine(8, 8, 16, 8);
                g.drawLine(8, 12, 16, 12);
                g.drawLine(8, 16, 13, 16);
            }
            case "contenido" -> {
                g.fillRoundRect(2, 6, 20, 15, 3, 3);
                g.fillRoundRect(2, 3, 9, 5, 2, 2);
            }
            case "observador" -> {
                g.setStroke(new BasicStroke(1.8f));
                g.drawRoundRect(3, 4, 8, 17, 2, 2);
                g.drawRoundRect(13, 4, 8, 17, 2, 2);
                g.drawLine(12, 6, 12, 21);
            }
            case "citaciones" -> {
                g.fillOval(6, 2, 12, 12);
                g.fillPolygon(new int[]{8, 16, 12}, new int[]{12, 12, 22}, 3);
                Color base = g.getColor();
                g.setColor(new Color(255, 255, 255, 235));
                g.fillOval(10, 6, 4, 4);
                g.setColor(base);
            }
            case "estudiantes" -> {
                g.fillPolygon(new int[]{12, 22, 12, 2}, new int[]{3, 9, 15, 9}, 4);
                g.setStroke(new BasicStroke(1.8f));
                g.drawLine(6, 11, 6, 17);
                g.drawArc(3, 15, 6, 6, 190, 160);
            }
            case "docentes" -> {
                g.fillOval(7, 2, 10, 10);
                g.fillRoundRect(2, 13, 20, 9, 5, 5);
                g.setColor(new Color(255, 255, 255, 235));
                g.fillRect(15, 3, 6, 2);
            }
            case "ajustes" -> {
                g.setStroke(new BasicStroke(2.4f));
                g.drawOval(8, 8, 8, 8);
                for (int i = 0; i < 8; i++) {
                    double ang = Math.toRadians(i * 45);
                    int x1 = (int) (12 + Math.cos(ang) * 8);
                    int y1 = (int) (12 + Math.sin(ang) * 8);
                    int x2 = (int) (12 + Math.cos(ang) * 11);
                    int y2 = (int) (12 + Math.sin(ang) * 11);
                    g.drawLine(x1, y1, x2, y2);
                }
            }
            case "salir" -> {
                g.setStroke(new BasicStroke(2.2f, BasicStroke.CAP_ROUND, BasicStroke.JOIN_ROUND));
                g.drawArc(3, 3, 18, 18, -60, 300);
                g.drawLine(12, 2, 12, 12);
            }
            case "marca" -> {
                g.setStroke(new BasicStroke(2f, BasicStroke.CAP_ROUND, BasicStroke.JOIN_ROUND));
                g.drawPolygon(new int[]{12, 22, 12, 2}, new int[]{4, 9, 14, 9}, 4);
                g.fillOval(9, 8, 6, 6);
            }
            case "vacio" -> {
                g.drawRoundRect(2, 6, 20, 15, 3, 3);
                g.drawPolyline(new int[]{2, 12, 22}, new int[]{6, 16, 6}, 3);
            }
            case "info" -> {
                g.setStroke(new BasicStroke(2.2f));
                g.drawOval(2, 2, 20, 20);
                g.fillOval(11, 6, 2, 2);
                g.drawLine(12, 11, 12, 17);
            }
            case "exito" -> {
                g.setStroke(new BasicStroke(2.4f, BasicStroke.CAP_ROUND, BasicStroke.JOIN_ROUND));
                g.drawOval(2, 2, 20, 20);
                g.drawPolyline(new int[]{7, 11, 17}, new int[]{12, 16, 8}, 3);
            }
            case "error" -> {
                g.setStroke(new BasicStroke(2.2f, BasicStroke.CAP_ROUND, BasicStroke.JOIN_ROUND));
                g.drawOval(2, 2, 20, 20);
                g.drawLine(8, 8, 16, 16);
                g.drawLine(16, 8, 8, 16);
            }
            case "advertencia" -> {
                g.setStroke(new BasicStroke(2.2f, BasicStroke.CAP_ROUND, BasicStroke.JOIN_ROUND));
                g.drawPolygon(new int[]{12, 22, 2}, new int[]{3, 21, 21}, 3);
                g.drawLine(12, 9, 12, 15);
                g.fillOval(11, 17, 2, 2);
            }
            case "tema_oscuro" -> g.fillArc(2, 2, 20, 20, 60, 250);
            case "tema_claro" -> {
                g.setStroke(new BasicStroke(2f, BasicStroke.CAP_ROUND, BasicStroke.JOIN_ROUND));
                g.drawOval(7, 7, 10, 10);
                for (int i = 0; i < 8; i++) {
                    double ang = Math.toRadians(i * 45);
                    int x1 = (int) (12 + Math.cos(ang) * 9);
                    int y1 = (int) (12 + Math.sin(ang) * 9);
                    int x2 = (int) (12 + Math.cos(ang) * 11);
                    int y2 = (int) (12 + Math.sin(ang) * 11);
                    g.drawLine(x1, y1, x2, y2);
                }
            }
            default -> g.drawRoundRect(3, 3, 18, 18, 4, 4);
        }
    }
}