package com.sidino.ui;

import com.sidino.core.Sesion;
import com.sidino.ui.componentes.BotonRedondeado;
import com.sidino.ui.componentes.Estilos;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import java.awt.*;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

/**
 * Panel base para todos los dashboards de rol (ya NO es una ventana propia:
 * se embebe dentro del único AppFrame). Equivalente Swing de sidebar_html()
 * + topbar_html() + layout_head()/layout_close() de auth.php.
 *
 * Cada subclase declara sus ítems de menú y construye el panel de cada
 * módulo (equivalente a los "elseif($modulo === ...)" del PHP original).
 */
public abstract class DashboardBase extends JPanel {

    public record ItemNav(String id, String icono, String etiqueta) {}

    private final CardLayout cardLayout = new CardLayout();
    private final JPanel panelContenido = new JPanel(cardLayout);
    private final Map<String, BotonNav> botonesNav = new LinkedHashMap<>();
    private final Color colorRol;
    private JScrollPane scrollContenido;
    private String moduloActual;

    protected DashboardBase(String tituloVentana, String tituloTopbar, Color colorRol, List<ItemNav> nav) {
        this.colorRol = colorRol;

        setLayout(new BorderLayout());
        setBackground(Estilos.FONDO);

        add(construirSidebar(nav), BorderLayout.WEST);

        JPanel principal = new JPanel(new BorderLayout());
        principal.setBackground(Estilos.FONDO);
        principal.add(construirTopbar(tituloTopbar), BorderLayout.NORTH);

        panelContenido.setBackground(Estilos.FONDO);
        panelContenido.setBorder(new EmptyBorder(22, 26, 22, 26));
        scrollContenido = new JScrollPane(panelContenido);
        scrollContenido.setBorder(null);
        scrollContenido.getViewport().setBackground(Estilos.FONDO);
        scrollContenido.getVerticalScrollBar().setUnitIncrement(16);
        principal.add(scrollContenido, BorderLayout.CENTER);

        add(principal, BorderLayout.CENTER);

        if (!nav.isEmpty()) mostrarModulo(nav.get(0).id());
    }

    /** Construye el contenido de un módulo (equivalente al bloque PHP de cada $modulo). */
    protected abstract JPanel construirModulo(String idModulo);

    /**
     * Muestra un módulo, RECONSTRUYÉNDOLO desde cero (nueva consulta a la
     * base de datos incluida) cada vez que se invoca. Esto imita el
     * comportamiento del PHP original: cada navegación es como una recarga
     * de página, así que los datos siempre están al día (por ejemplo, tras
     * crear/editar/eliminar un usuario y volver a "Usuarios").
     */
    protected void mostrarModulo(String id) {
        moduloActual = id;
        panelContenido.removeAll();
        JPanel modulo = construirModulo(id);
        JPanel envoltorio = new JPanel(new BorderLayout());
        envoltorio.setOpaque(false);
        envoltorio.add(modulo, BorderLayout.NORTH);
        panelContenido.add(envoltorio, id);
        cardLayout.show(panelContenido, id);
        panelContenido.revalidate();
        panelContenido.repaint();
        SwingUtilities.invokeLater(() -> scrollContenido.getViewport().setViewPosition(new Point(0, 0)));
        botonesNav.forEach((clave, boton) -> boton.setActivo(clave.equals(id)));
    }

    /** Refresca el módulo que esté visible en este momento (por ejemplo tras guardar un cambio). */
    protected void refrescarModuloActual() {
        if (moduloActual != null) mostrarModulo(moduloActual);
    }

    // ── Botón de navegación con estado activo/hover pintado a mano (rounded + degradado) ──
    private class BotonNav extends JButton {
        private boolean activo;
        private boolean hover;

        BotonNav(String texto) {
            super(texto);
            setOpaque(false);
            setContentAreaFilled(false);
            setBorderPainted(false);
            setFocusPainted(false);
            setHorizontalAlignment(SwingConstants.LEFT);
            setFont(Estilos.FUENTE_NORMAL);
            setForeground(Estilos.TEXTO_SEC);
            setBorder(new EmptyBorder(9, 14, 9, 10));
            setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
            addMouseListener(new MouseAdapter() {
                @Override public void mouseEntered(MouseEvent e) { hover = true; repaint(); }
                @Override public void mouseExited(MouseEvent e) { hover = false; repaint(); }
            });
        }

        void setActivo(boolean activo) {
            this.activo = activo;
            setForeground(activo ? Color.WHITE : Estilos.TEXTO_SEC);
            repaint();
        }

        @Override
        protected void paintComponent(Graphics g) {
            Graphics2D g2 = (Graphics2D) g.create();
            g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
            if (activo) {
                GradientPaint grad = new GradientPaint(0, 0, Estilos.ACCENT, getWidth(), 0, Estilos.oscurecer(Estilos.ACCENT, 0.25));
                g2.setPaint(grad);
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 10, 10);
            } else if (hover) {
                g2.setColor(Estilos.aclarar(Estilos.FONDO_TARJETA, 0.06));
                g2.fillRoundRect(0, 0, getWidth(), getHeight(), 10, 10);
            }
            g2.dispose();
            super.paintComponent(g);
        }
    }

    private JPanel construirSidebar(List<ItemNav> nav) {
        JPanel sidebar = new JPanel() {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setPaint(new GradientPaint(0, 0, new Color(6, 10, 20), 0, getHeight(), new Color(3, 6, 14)));
                g2.fillRect(0, 0, getWidth(), getHeight());
                g2.dispose();
                super.paintComponent(g);
            }
        };
        sidebar.setLayout(new BoxLayout(sidebar, BoxLayout.Y_AXIS));
        sidebar.setOpaque(false);
        sidebar.setPreferredSize(new Dimension(232, 0));
        sidebar.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 0, 0, 1, new Color(0, 0, 0, 80)),
                new EmptyBorder(18, 14, 18, 14)));

        JLabel marca = new JLabel("\uD83D\uDC19 SIDINO 2026");
        marca.setFont(new Font("Arial", Font.BOLD, 18));
        marca.setForeground(Estilos.TEXTO);
        marca.setAlignmentX(Component.LEFT_ALIGNMENT);
        sidebar.add(marca);
        sidebar.add(Box.createVerticalStrut(20));

        String inicial = Sesion.nombre != null && !Sesion.nombre.isBlank()
                ? Sesion.nombre.substring(0, 1).toUpperCase() : "U";
        JPanel tarjetaUsuario = new JPanel(new BorderLayout(10, 0));
        tarjetaUsuario.setOpaque(false);
        tarjetaUsuario.setAlignmentX(Component.LEFT_ALIGNMENT);
        tarjetaUsuario.setMaximumSize(new Dimension(Integer.MAX_VALUE, 46));
        JLabel avatar = new JLabel(inicial, SwingConstants.CENTER) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
                g2.setPaint(new GradientPaint(0, 0, colorRol, getWidth(), getHeight(), Estilos.oscurecer(colorRol, 0.3)));
                g2.fillOval(0, 0, getWidth(), getHeight());
                g2.dispose();
                super.paintComponent(g);
            }
        };
        avatar.setForeground(Color.WHITE);
        avatar.setFont(new Font("Arial", Font.BOLD, 16));
        avatar.setPreferredSize(new Dimension(38, 38));

        JPanel textosUsuario = new JPanel();
        textosUsuario.setOpaque(false);
        textosUsuario.setLayout(new BoxLayout(textosUsuario, BoxLayout.Y_AXIS));
        JLabel nombreLbl = new JLabel(Sesion.nombre == null ? "Usuario" : Sesion.nombre);
        nombreLbl.setFont(Estilos.FUENTE_NEGRITA);
        nombreLbl.setForeground(Estilos.TEXTO);
        JLabel rolLbl = new JLabel(Sesion.rol == null ? "" : Character.toUpperCase(Sesion.rol.charAt(0)) + Sesion.rol.substring(1));
        rolLbl.setFont(Estilos.FUENTE_SUBTITULO);
        rolLbl.setForeground(colorRol);
        textosUsuario.add(nombreLbl);
        textosUsuario.add(rolLbl);

        tarjetaUsuario.add(avatar, BorderLayout.WEST);
        tarjetaUsuario.add(textosUsuario, BorderLayout.CENTER);
        sidebar.add(tarjetaUsuario);
        sidebar.add(Box.createVerticalStrut(20));

        JPanel sepPanel = new JPanel();
        sepPanel.setBackground(new Color(255, 255, 255, 18));
        sepPanel.setMaximumSize(new Dimension(Integer.MAX_VALUE, 1));
        sepPanel.setPreferredSize(new Dimension(10, 1));
        sepPanel.setAlignmentX(Component.LEFT_ALIGNMENT);
        sidebar.add(sepPanel);
        sidebar.add(Box.createVerticalStrut(14));

        for (ItemNav item : nav) {
            BotonNav boton = new BotonNav(item.icono() + "   " + item.etiqueta());
            boton.setAlignmentX(Component.LEFT_ALIGNMENT);
            boton.setMaximumSize(new Dimension(Integer.MAX_VALUE, 40));
            boton.addActionListener(e -> mostrarModulo(item.id()));
            botonesNav.put(item.id(), boton);
            sidebar.add(boton);
            sidebar.add(Box.createVerticalStrut(4));
        }

        sidebar.add(Box.createVerticalGlue());
        JButton salir = new BotonRedondeado("\u23FB  Cerrar sesión", 10).colores(Estilos.ROJO, Estilos.aclarar(Estilos.ROJO, 0.15));
        salir.setAlignmentX(Component.LEFT_ALIGNMENT);
        salir.setMaximumSize(new Dimension(Integer.MAX_VALUE, 40));
        salir.addActionListener(e -> cerrarSesion());
        sidebar.add(salir);

        return sidebar;
    }

    private JPanel construirTopbar(String titulo) {
        JPanel topbar = new JPanel(new BorderLayout()) {
            @Override protected void paintComponent(Graphics g) {
                Graphics2D g2 = (Graphics2D) g.create();
                g2.setPaint(new GradientPaint(0, 0, Estilos.aclarar(Estilos.FONDO, 0.03), 0, getHeight(), Estilos.FONDO));
                g2.fillRect(0, 0, getWidth(), getHeight());
                g2.dispose();
                super.paintComponent(g);
            }
        };
        topbar.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 0, 1, 0, Estilos.BORDE),
                new EmptyBorder(14, 22, 14, 22)));
        JLabel lbl = new JLabel(titulo);
        lbl.setFont(new Font("Arial", Font.BOLD, 17));
        lbl.setForeground(Estilos.TEXTO);
        topbar.add(lbl, BorderLayout.WEST);

        JPanel derecha = new JPanel(new FlowLayout(FlowLayout.RIGHT, 14, 0));
        derecha.setOpaque(false);

        JLabel engranaje = new JLabel("\u2699");
        engranaje.setFont(new Font("Arial", Font.PLAIN, 20));
        engranaje.setForeground(Estilos.TEXTO_SEC);
        engranaje.setCursor(Cursor.getPredefinedCursor(Cursor.HAND_CURSOR));
        engranaje.setToolTipText("Personalizar apariencia");
        engranaje.addMouseListener(new MouseAdapter() {
            @Override public void mouseClicked(MouseEvent e) { PanelAjustes.mostrar(DashboardBase.this); }
            @Override public void mouseEntered(MouseEvent e) { engranaje.setForeground(Estilos.ACCENT); }
            @Override public void mouseExited(MouseEvent e) { engranaje.setForeground(Estilos.TEXTO_SEC); }
        });

        JLabel usuario = new JLabel(Sesion.nombre == null ? "Usuario" : Sesion.nombre);
        usuario.setForeground(Estilos.TEXTO_SEC);

        derecha.add(engranaje);
        derecha.add(usuario);
        topbar.add(derecha, BorderLayout.EAST);
        return topbar;
    }

    private void cerrarSesion() {
        Sesion.cerrar();
        AppFrame.obtener().mostrarLogin();
    }
}
