package com.sidino.ui.componentes;

import com.sidino.core.Preferencias;

import javax.swing.*;
import javax.swing.border.EmptyBorder;
import javax.swing.table.DefaultTableCellRenderer;
import javax.swing.table.DefaultTableModel;
import java.awt.*;
import java.sql.Date;
import java.text.SimpleDateFormat;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

/**
 * Panel con esquinas redondeadas y sombra suave opcional, usado como base
 * visual de tarjetas, stat-cards y demás bloques, para acercarse al look de
 * las tarjetas CSS del proyecto web original (evitando el aspecto plano de
 * un JPanel rectangular normal).
 */
class PanelRedondeado extends JPanel {
    private final int radio;
    private final boolean sombra;

    PanelRedondeado(int radio) { this(radio, false); }

    PanelRedondeado(int radio, boolean sombra) {
        this.radio = radio;
        this.sombra = sombra;
        setOpaque(false);
    }

    @Override
    protected void paintComponent(Graphics g) {
        Graphics2D g2 = (Graphics2D) g.create();
        g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);

        int ancho = getWidth() - (sombra ? 5 : 0);
        int alto = getHeight() - (sombra ? 5 : 0);

        if (sombra) {
            for (int i = 4; i >= 1; i--) {
                g2.setColor(new Color(0, 0, 0, 14));
                g2.fillRoundRect(i, i + 1, ancho, alto, radio, radio);
            }
        }
        g2.setColor(getBackground());
        g2.fillRoundRect(0, 0, ancho, alto, radio, radio);
        g2.dispose();
        super.paintComponent(g);
    }
}

/**
 * Paleta de colores/tipografía y fábricas de componentes reutilizables,
 * equivalentes a shared.css + los helpers de layout de auth.php pero en
 * Swing. A diferencia de una hoja CSS estática, esta paleta es DINÁMICA:
 * se recalcula desde Preferencias (tema claro/oscuro + color de acento
 * elegido por el usuario), igual que el panel "Personalizar" del proyecto
 * PHP original hacía sobre las variables CSS --accent / data-theme.
 */
public class Estilos {

    // ── Paleta base (se recalculan en aplicarPreferencias()) ──
    public static Color FONDO;
    public static Color FONDO_TARJETA;
    public static Color BORDE;
    public static Color TEXTO;
    public static Color TEXTO_SEC;
    /** Color de acento configurable por el usuario (equivalente a --accent en shared.css). */
    public static Color ACCENT;

    // ── Colores fijos de identidad por rol (no configurables, igual que color_map en auth.php) ──
    public static final Color VERDE  = new Color(52, 211, 153);
    public static final Color AMBAR  = new Color(251, 191, 36);
    public static final Color ROJO   = new Color(248, 113, 113);
    public static final Color AZUL   = new Color(56, 189, 248);
    public static final Color NARANJA = new Color(251, 146, 60);
    public static final Color MORADO = new Color(167, 139, 250);

    public static final Map<String, Color> COLOR_ROL = Map.of(
            "rectoria", ROJO,
            "coordinacion", NARANJA,
            "administrativo", AMBAR,
            "docente", AZUL,
            "estudiante", VERDE,
            "acudiente", MORADO
    );

    /** Paleta de acentos preestablecidos que se muestran como swatches en el panel de personalización. */
    public static final Color[] SWATCHES = {
            new Color(56, 189, 248),  // azul
            new Color(52, 211, 153),  // verde
            new Color(251, 146, 60),  // naranja
            new Color(248, 113, 113), // rojo
            new Color(251, 191, 36),  // amarillo
            new Color(167, 139, 250), // morado
            new Color(236, 72, 153),  // rosa
            new Color(0, 200, 190),   // turquesa (por defecto)
    };

    public static final Font FUENTE_TITULO   = new Font("Arial", Font.BOLD, 20);
    public static final Font FUENTE_SUBTITULO = new Font("Arial", Font.PLAIN, 13);
    public static final Font FUENTE_NORMAL   = new Font("Arial", Font.PLAIN, 13);
    public static final Font FUENTE_NEGRITA  = new Font("Arial", Font.BOLD, 13);

    static { aplicarPreferencias(); }

    /** Recalcula la paleta dinámica desde Preferencias. Llamar tras cambiar tema/acento. */
    public static void aplicarPreferencias() {
        ACCENT = Preferencias.getColorAcento();
        if (Preferencias.esTemaOscuro()) {
            FONDO         = new Color(10, 16, 30);
            FONDO_TARJETA = new Color(22, 32, 55);
            BORDE         = new Color(45, 60, 90);
            TEXTO         = new Color(226, 232, 240);
            TEXTO_SEC     = new Color(148, 163, 184);
        } else {
            FONDO         = new Color(241, 245, 249);
            FONDO_TARJETA = new Color(255, 255, 255);
            BORDE         = new Color(220, 226, 235);
            TEXTO         = new Color(15, 23, 42);
            TEXTO_SEC     = new Color(100, 116, 139);
        }
    }

    /** Color según valor de nota (escala 0-5), igual que las clases nota-alta/media/baja del CSS original. */
    public static Color colorNota(double valor) {
        if (valor >= 3.5) return VERDE;
        if (valor >= 3.0) return AMBAR;
        return ROJO;
    }

    public static Color aclarar(Color c, double cantidad) {
        int r = (int) Math.min(255, c.getRed() + (255 - c.getRed()) * cantidad);
        int g = (int) Math.min(255, c.getGreen() + (255 - c.getGreen()) * cantidad);
        int b = (int) Math.min(255, c.getBlue() + (255 - c.getBlue()) * cantidad);
        return new Color(r, g, b);
    }

    public static Color oscurecer(Color c, double cantidad) {
        int r = (int) Math.max(0, c.getRed() * (1 - cantidad));
        int g = (int) Math.max(0, c.getGreen() * (1 - cantidad));
        int b = (int) Math.max(0, c.getBlue() * (1 - cantidad));
        return new Color(r, g, b);
    }

    public static String formatearNumero(Object valor, int decimales) {
        if (valor == null) return "—";
        double d = ((Number) valor).doubleValue();
        return String.format("%,." + decimales + "f", d);
    }

    public static String formatearFecha(Object fecha) {
        if (fecha == null) return "—";
        try {
            if (fecha instanceof Date d) return new SimpleDateFormat("dd/MM/yyyy").format(d);
            if (fecha instanceof java.sql.Timestamp t) return new SimpleDateFormat("dd/MM/yyyy").format(t);
            return fecha.toString();
        } catch (Exception e) {
            return String.valueOf(fecha);
        }
    }

    public static String texto(Map<String, Object> fila, String clave) {
        Object v = fila.get(clave);
        return v == null ? "" : v.toString();
    }

    public static double numero(Map<String, Object> fila, String clave) {
        Object v = fila.get(clave);
        return v == null ? 0.0 : ((Number) v).doubleValue();
    }

    // ── Tarjeta de estadística (stat-card), con franja de color y sombra suave ──
    public static JPanel crearStatCard(String valor, String etiqueta, Color color) {
        PanelRedondeado card = new PanelRedondeado(14, true);
        card.setLayout(new BorderLayout(12, 4));
        card.setBackground(FONDO_TARJETA);
        card.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 4, 0, 0, color),
                new EmptyBorder(16, 16, 16, 14)));

        JPanel textos = new JPanel();
        textos.setOpaque(false);
        textos.setLayout(new BoxLayout(textos, BoxLayout.Y_AXIS));
        JLabel lblValor = new JLabel(valor);
        lblValor.setFont(new Font("Arial", Font.BOLD, 26));
        lblValor.setForeground(TEXTO);
        JLabel lblEtiqueta = new JLabel(etiqueta);
        lblEtiqueta.setFont(FUENTE_SUBTITULO);
        lblEtiqueta.setForeground(TEXTO_SEC);
        textos.add(lblValor);
        textos.add(lblEtiqueta);

        card.add(textos, BorderLayout.CENTER);
        return card;
    }

    /** Panel en fila (grid) para colocar varias stat-cards, con separación uniforme. */
    public static JPanel crearGridStats(JPanel... cards) {
        JPanel grid = new JPanel(new GridLayout(1, cards.length, 16, 16));
        grid.setOpaque(false);
        for (JPanel c : cards) grid.add(c);
        return grid;
    }

    // ── Elemento de lista (list-item) ──
    public static JPanel crearListItem(String titulo, String subtitulo, String meta, Color color) {
        PanelRedondeado item = new PanelRedondeado(10);
        item.setLayout(new BorderLayout(10, 2));
        item.setBackground(aclarar(FONDO_TARJETA, 0.04));
        item.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 3, 0, 0, color),
                new EmptyBorder(9, 10, 9, 12)));

        JPanel textos = new JPanel();
        textos.setOpaque(false);
        textos.setLayout(new BoxLayout(textos, BoxLayout.Y_AXIS));
        JLabel lblTitulo = new JLabel(titulo);
        lblTitulo.setFont(FUENTE_NEGRITA);
        lblTitulo.setForeground(TEXTO);
        JLabel lblSub = new JLabel(subtitulo);
        lblSub.setFont(FUENTE_SUBTITULO);
        lblSub.setForeground(TEXTO_SEC);
        textos.add(lblTitulo);
        textos.add(lblSub);

        item.add(textos, BorderLayout.CENTER);
        if (meta != null) {
            JLabel lblMeta = new JLabel(meta);
            lblMeta.setForeground(color);
            lblMeta.setFont(FUENTE_NEGRITA);
            item.add(lblMeta, BorderLayout.EAST);
        }
        return item;
    }

    /** Insignia tipo "pill" redondeada, equivalente a .badge del CSS original. */
    public static JComponent crearBadge(String texto, Color color) {
        PanelRedondeado badge = new PanelRedondeado(999);
        badge.setLayout(new FlowLayout(FlowLayout.CENTER, 0, 0));
        badge.setBackground(new Color(color.getRed(), color.getGreen(), color.getBlue(), 45));
        badge.setBorder(new EmptyBorder(3, 10, 3, 10));
        JLabel lbl = new JLabel(texto);
        lbl.setForeground(color);
        lbl.setFont(FUENTE_SUBTITULO);
        badge.add(lbl);
        return badge;
    }

    public static JLabel crearTituloSeccion(String texto) {
        JLabel lbl = new JLabel(texto);
        lbl.setFont(FUENTE_TITULO);
        lbl.setForeground(TEXTO);
        lbl.setBorder(new EmptyBorder(0, 0, 14, 0));
        return lbl;
    }

    public static JPanel crearEmptyState(String mensaje) {
        PanelRedondeado p = new PanelRedondeado(14);
        p.setLayout(new BorderLayout());
        p.setBackground(FONDO_TARJETA);
        p.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createLineBorder(BORDE, 1, true), new EmptyBorder(34, 20, 34, 20)));
        JLabel icono = new JLabel("\uD83D\uDCED", SwingConstants.CENTER);
        icono.setFont(new Font("Arial", Font.PLAIN, 26));
        JLabel lbl = new JLabel(mensaje, SwingConstants.CENTER);
        lbl.setForeground(TEXTO_SEC);
        lbl.setFont(FUENTE_NORMAL);
        JPanel centro = new JPanel();
        centro.setOpaque(false);
        centro.setLayout(new BoxLayout(centro, BoxLayout.Y_AXIS));
        icono.setAlignmentX(Component.CENTER_ALIGNMENT);
        lbl.setAlignmentX(Component.CENTER_ALIGNMENT);
        centro.add(icono);
        centro.add(Box.createVerticalStrut(6));
        centro.add(lbl);
        p.add(centro, BorderLayout.CENTER);
        return p;
    }

    public static JPanel crearAlertaInfo(String mensaje) {
        PanelRedondeado p = new PanelRedondeado(12);
        p.setLayout(new BorderLayout(10, 0));
        p.setBackground(new Color(AZUL.getRed(), AZUL.getGreen(), AZUL.getBlue(), 30));
        p.setBorder(BorderFactory.createCompoundBorder(
                BorderFactory.createMatteBorder(0, 3, 0, 0, AZUL), new EmptyBorder(12, 14, 12, 16)));
        JLabel icono = new JLabel("\u2139");
        icono.setForeground(AZUL);
        icono.setFont(new Font("Arial", Font.BOLD, 16));
        icono.setVerticalAlignment(SwingConstants.TOP);
        String html = "<html>" + mensaje.replace("\n", "<br>") + "</html>";
        JLabel lbl = new JLabel(html);
        lbl.setForeground(AZUL);
        p.add(icono, BorderLayout.WEST);
        p.add(lbl, BorderLayout.CENTER);
        return p;
    }

    public static JPanel crearTarjeta(String titulo, JComponent contenido) {
        PanelRedondeado card = new PanelRedondeado(14, true);
        card.setLayout(new BorderLayout(0, 12));
        card.setBackground(FONDO_TARJETA);
        card.setBorder(new EmptyBorder(18, 20, 18, 20));
        if (titulo != null) {
            JLabel lbl = new JLabel(titulo);
            lbl.setFont(new Font("Arial", Font.BOLD, 15));
            lbl.setForeground(TEXTO);
            card.add(lbl, BorderLayout.NORTH);
        }
        card.add(contenido, BorderLayout.CENTER);
        return card;
    }

    /**
     * Construye una tabla de solo lectura a partir de una lista de filas
     * (Map columna→valor, tal como las devuelve DB.query) y un mapa
     * encabezado→clave que define el orden y los nombres de columnas visibles.
     */
    public static JScrollPane crearTabla(LinkedHashMap<String, String> columnas, List<Map<String, Object>> filas) {
        String[] encabezados = columnas.keySet().toArray(new String[0]);
        String[] claves = columnas.values().toArray(new String[0]);

        DefaultTableModel modelo = new DefaultTableModel(encabezados, 0) {
            @Override public boolean isCellEditable(int row, int col) { return false; }
        };
        for (Map<String, Object> fila : filas) {
            Object[] valores = new Object[claves.length];
            for (int i = 0; i < claves.length; i++) valores[i] = fila.get(claves[i]);
            modelo.addRow(valores);
        }

        JTable tabla = new JTable(modelo);
        aplicarEstiloTabla(tabla);

        JScrollPane scroll = new JScrollPane(tabla);
        scroll.getViewport().setBackground(FONDO_TARJETA);
        scroll.setBorder(BorderFactory.createLineBorder(BORDE, 1, true));
        scroll.setPreferredSize(new Dimension(100, Math.min(420, 46 + filas.size() * 28)));
        return scroll;
    }

    /** Envuelve una tabla dentro de una tarjeta redondeada con su título, imitando .card + .table-wrap del CSS original. */
    public static JPanel crearTablaEnTarjeta(String titulo, LinkedHashMap<String, String> columnas, List<Map<String, Object>> filas, String mensajeVacio) {
        if (filas.isEmpty()) {
            return crearTarjeta(titulo, crearEmptyState(mensajeVacio));
        }
        return crearTarjeta(titulo, crearTabla(columnas, filas));
    }

    public static void aplicarEstiloTabla(JTable tabla) {
        tabla.setBackground(FONDO_TARJETA);
        tabla.setForeground(TEXTO);
        tabla.setGridColor(BORDE);
        tabla.setRowHeight(28);
        tabla.setIntercellSpacing(new Dimension(0, 0));
        tabla.setSelectionBackground(new Color(ACCENT.getRed(), ACCENT.getGreen(), ACCENT.getBlue(), 70));
        tabla.setSelectionForeground(TEXTO);
        tabla.getTableHeader().setBackground(oscurecer(FONDO_TARJETA, 0.15));
        tabla.getTableHeader().setForeground(TEXTO_SEC);
        tabla.getTableHeader().setFont(FUENTE_NEGRITA);
        tabla.getTableHeader().setPreferredSize(new Dimension(100, 32));
        tabla.setDefaultRenderer(Object.class, new DefaultTableCellRenderer() {
            @Override
            public Component getTableCellRendererComponent(JTable t, Object value, boolean selected, boolean focus, int row, int col) {
                Component c = super.getTableCellRendererComponent(t, value, selected, focus, row, col);
                setHorizontalAlignment(SwingConstants.LEFT);
                setBorder(new EmptyBorder(0, 8, 0, 8));
                if (!selected) {
                    setBackground(row % 2 == 0 ? FONDO_TARJETA : aclarar(FONDO_TARJETA, 0.05));
                    setForeground(TEXTO);
                }
                return c;
            }
        });
    }
}
