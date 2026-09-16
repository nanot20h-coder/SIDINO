package com.sidino.ui;

import com.sidino.core.Preferencias;
import com.sidino.ui.componentes.BotonRedondeado;
import com.sidino.ui.componentes.Estilos;
import com.sidino.ui.componentes.Iconos;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;

/**
 * Panel de "Personalizar" — equivalente Swing del panel deslizante de
 * auth.php (setTheme() / setAccent() / resetColors()), pero persistido a
 * disco en vez de localStorage. Al aplicar cambios, reconstruye la vista
 * actual para que los nuevos colores tomen efecto en todos los componentes.
 */
public class PanelAjustes {

    public static void mostrar(Component parent) {
        JDialog dialogo = new JDialog(SwingUtilities.getWindowAncestor(parent), "Personalizar", Dialog.ModalityType.APPLICATION_MODAL);
        dialogo.setUndecorated(true);
        dialogo.setBackground(new Color(0, 0, 0, 0));
        dialogo.setLayout(new BorderLayout());

        JPanel tarjeta = new JPanel();
        tarjeta.setLayout(new BoxLayout(tarjeta, BoxLayout.Y_AXIS));
        tarjeta.setBackground(Estilos.FONDO_TARJETA);
        tarjeta.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(Estilos.BORDE, 1, true), new EmptyBorder(22, 26, 20, 26)));

        JLabel titulo = new JLabel("Personalizar", Iconos.crear("ajustes", Estilos.ACCENT, 20), SwingConstants.LEFT);
        titulo.setIconTextGap(10);
        titulo.setFont(new Font("Arial", Font.BOLD, 18));
        titulo.setForeground(Estilos.TEXTO);
        titulo.setAlignmentX(Component.LEFT_ALIGNMENT);
        tarjeta.add(titulo);
        tarjeta.add(Box.createVerticalStrut(18));

        // ── Apariencia (claro/oscuro) ──
        tarjeta.add(subtitulo("Apariencia"));
        JPanel filaTema = new JPanel(new GridLayout(1, 2, 10, 0));
        filaTema.setOpaque(false);
        filaTema.setAlignmentX(Component.LEFT_ALIGNMENT);
        filaTema.setMaximumSize(new Dimension(400, 42));

        boolean[] oscuroSeleccionado = {Preferencias.esTemaOscuro()};
        BotonRedondeado btnOscuro = new BotonRedondeado("Oscuro", 10);
        btnOscuro.setIcon(Iconos.crear("tema_oscuro", Color.WHITE, 15));
        btnOscuro.setIconTextGap(8);
        BotonRedondeado btnClaro = new BotonRedondeado("Claro", 10);
        btnClaro.setIcon(Iconos.crear("tema_claro", Color.WHITE, 15));
        btnClaro.setIconTextGap(8);
        Runnable actualizarBotonesTema = () -> {
            btnOscuro.colores(oscuroSeleccionado[0] ? Estilos.ACCENT : Estilos.BORDE,
                    oscuroSeleccionado[0] ? Estilos.aclarar(Estilos.ACCENT, 0.15) : Estilos.aclarar(Estilos.BORDE, 0.2));
            btnClaro.colores(!oscuroSeleccionado[0] ? Estilos.ACCENT : Estilos.BORDE,
                    !oscuroSeleccionado[0] ? Estilos.aclarar(Estilos.ACCENT, 0.15) : Estilos.aclarar(Estilos.BORDE, 0.2));
            btnOscuro.repaint();
            btnClaro.repaint();
        };
        btnOscuro.addActionListener(e -> { oscuroSeleccionado[0] = true; actualizarBotonesTema.run(); });
        btnClaro.addActionListener(e -> { oscuroSeleccionado[0] = false; actualizarBotonesTema.run(); });
        actualizarBotonesTema.run();
        filaTema.add(btnOscuro);
        filaTema.add(btnClaro);
        tarjeta.add(filaTema);
        tarjeta.add(Box.createVerticalStrut(18));

        // ── Color de acento (swatches) ──
        tarjeta.add(subtitulo("Color de acento"));
        JPanel swatches = new JPanel(new FlowLayout(FlowLayout.LEFT, 8, 8));
        swatches.setOpaque(false);
        swatches.setAlignmentX(Component.LEFT_ALIGNMENT);
        Color[] colorElegido = {Preferencias.getColorAcento()};

        for (Color c : Estilos.SWATCHES) {
            JPanel swatch = new JPanel();
            swatch.setPreferredSize(new Dimension(30, 30));
            swatch.setBackground(c);
            swatch.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
            swatch.setBorder(BorderFactory.createLineBorder(Estilos.TEXTO, colorElegido[0].equals(c) ? 2 : 0));
            swatch.addMouseListener(new MouseAdapter() {
                @Override public void mouseClicked(MouseEvent e) {
                    colorElegido[0] = c;
                    for (Component comp : swatches.getComponents()) {
                        ((JPanel) comp).setBorder(BorderFactory.createLineBorder(Estilos.TEXTO, comp.getBackground().equals(c) ? 2 : 0));
                    }
                }
            });
            swatches.add(swatch);
        }
        tarjeta.add(swatches);
        tarjeta.add(Box.createVerticalStrut(6));

        // ── Selector de color personalizado ──
        JPanel filaPersonalizado = new JPanel(new FlowLayout(FlowLayout.LEFT, 8, 0));
        filaPersonalizado.setOpaque(false);
        filaPersonalizado.setAlignmentX(Component.LEFT_ALIGNMENT);
        JButton btnPersonalizado = new JButton("Elegir color personalizado…");
        btnPersonalizado.addActionListener(e -> {
            Color elegido = JColorChooser.showDialog(dialogo, "Elige un color", colorElegido[0]);
            if (elegido != null) colorElegido[0] = elegido;
        });
        filaPersonalizado.add(btnPersonalizado);
        tarjeta.add(filaPersonalizado);
        tarjeta.add(Box.createVerticalStrut(20));

        // ── Botones de acción ──
        JPanel pie = new JPanel(new FlowLayout(FlowLayout.RIGHT, 10, 0));
        pie.setOpaque(false);
        pie.setAlignmentX(Component.LEFT_ALIGNMENT);
        BotonRedondeado restablecer = new BotonRedondeado("Restablecer", 10).colores(Estilos.BORDE, Estilos.aclarar(Estilos.BORDE, 0.2));
        BotonRedondeado aplicar = new BotonRedondeado("Aplicar cambios", 10).colores(Estilos.ACCENT, Estilos.aclarar(Estilos.ACCENT, 0.15));
        restablecer.addActionListener(e -> {
            Preferencias.restablecer();
            Estilos.aplicarPreferencias();
            dialogo.dispose();
            AppFrame.obtener().refrescar();
        });
        aplicar.addActionListener(e -> {
            Preferencias.setTemaOscuro(oscuroSeleccionado[0]);
            Preferencias.setColorAcento(colorElegido[0]);
            Estilos.aplicarPreferencias();
            dialogo.dispose();
            AppFrame.obtener().refrescar();
        });
        pie.add(restablecer);
        pie.add(aplicar);
        tarjeta.add(pie);

        dialogo.add(tarjeta, BorderLayout.CENTER);
        dialogo.pack();
        dialogo.setLocationRelativeTo(parent);
        dialogo.setVisible(true);
    }

    private static JLabel subtitulo(String texto) {
        JLabel lbl = new JLabel(texto);
        lbl.setForeground(Estilos.TEXTO_SEC);
        lbl.setFont(Estilos.FUENTE_NEGRITA);
        lbl.setAlignmentX(Component.LEFT_ALIGNMENT);
        lbl.setBorder(new EmptyBorder(0, 0, 8, 0));
        return lbl;
    }
}