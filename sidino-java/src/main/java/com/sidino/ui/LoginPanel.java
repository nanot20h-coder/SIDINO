package com.sidino.ui;

import com.sidino.core.Conexion;
import com.sidino.core.Sesion;
import com.sidino.ui.componentes.BotonRedondeado;
import com.sidino.ui.componentes.Dialogos;
import com.sidino.ui.componentes.RPF;
import com.sidino.ui.componentes.RTF;

import javax.swing.*;
import java.awt.*;
import java.awt.event.ComponentAdapter;
import java.awt.event.ComponentEvent;
import java.awt.event.FocusAdapter;
import java.awt.event.FocusEvent;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.ArrayList;
import java.util.List;

/**
 * Panel de inicio de sesión con diseño dinámico.
 * Los botones y campos se adaptan a la pantalla y la tarjeta contenedora
 * ajusta su tamaño exactamente al contenido, evitando espacios vacíos.
 */
public class LoginPanel extends JPanel {

    private static class Burbuja {
        float x, y, tamanio, velocidad;
        Burbuja() {
            tamanio = (float) (Math.random() * 16 + 4);
            velocidad = (float) (1.0 / tamanio * 30);
            x = (float) (Math.random() * 1920);
            y = (float) (Math.random() * 1080);
        }
    }

    private static final String PLACEHOLDER_USUARIO = "Escribe tu usuario (correo):";
    private static final String PLACEHOLDER_PASSWORD = "Introduce tu contraseña";

    private final List<Burbuja> burbujas = new ArrayList<>();
    private final JLabel bienvenidos = new JLabel("Bienvenidos");
    private final JLabel subBienvenidos = new JLabel("Inicia sesión para empezar tu día");
    private final JTextField campoUsuario = new RTF(20);
    private final JLabel errUsu = new JLabel("El usuario no puede estar vacío");
    private final JPasswordField campoPassword = new RPF(20);
    private final JLabel errPass = new JLabel("La contraseña no puede estar vacía");
    private final BotonRedondeado botonLogin = new BotonRedondeado("Iniciar Sesión", 15);

    private char echoCharReal;
    private boolean passwordEsPlaceholder = true;
    private boolean construido = false;

    public LoginPanel() {
        setLayout(null);

        // Animación de burbujas en el fondo
        Timer timer = new Timer(30, e -> {
            for (Burbuja b : burbujas) {
                b.y -= b.velocidad;
                if (b.y < -20) { 
                    b.y = getHeight() + 20; 
                    b.x = (float) (Math.random() * getWidth()); 
                }
            }
            repaint();
        });
        for (int i = 0; i < 40; i++) burbujas.add(new Burbuja());
        timer.start();

        // Listeners de autenticación
        botonLogin.addActionListener(e -> intentarLogin());
        campoUsuario.addActionListener(e -> intentarLogin());
        campoPassword.addActionListener(e -> intentarLogin());

        // Evento para recalcular al redimensionar la ventana
        addComponentListener(new ComponentAdapter() {
            @Override 
            public void componentResized(ComponentEvent e) {
                revalidate();
                repaint();
            }
        });
    }

    @Override
    public void doLayout() {
        super.doLayout();
        if (!construido) {
            construirComponentes();
            construido = true;
        }
        posicionarComponentes();
    }

    private void intentarLogin() {
        boolean hayError = false;

        String usuario = campoUsuario.getText();
        if (usuario.isBlank() || usuario.equals(PLACEHOLDER_USUARIO)) {
            errUsu.setVisible(true);
            hayError = true;
        } else {
            errUsu.setVisible(false);
        }

        if (passwordEsPlaceholder || campoPassword.getPassword().length == 0) {
            errPass.setVisible(true);
            hayError = true;
        } else {
            errPass.setVisible(false);
        }

        if (hayError) {
            revalidate();
            repaint();
            return;
        }

        String correo = campoUsuario.getText().trim();
        String password = new String(campoPassword.getPassword());

        try (Connection con = Conexion.obtener()) {
            String sql = "SELECT u.id_usuario, u.nombre, u.correo, u.id_rol, r.nombre_rol " +
                         "FROM usuario u JOIN rol r ON u.id_rol = r.id_rol " +
                         "WHERE u.correo = ? AND u.contrasena = ?";
            try (PreparedStatement ps = con.prepareStatement(sql)) {
                ps.setString(1, correo);
                ps.setString(2, password);
                try (ResultSet rs = ps.executeQuery()) {
                    if (rs.next()) {
                        int idUsuario = rs.getInt("id_usuario");
                        String nombre = rs.getString("nombre");
                        int idRol = rs.getInt("id_rol");
                        String rolNombre = rs.getString("nombre_rol");

                        Sesion.iniciar(idUsuario, nombre, correo, idRol, rolNombre == null ? "" : rolNombre.toLowerCase());
                        abrirDashboard(idRol);
                    } else {
                        Dialogos.error(this, "Usuario o contraseña incorrectos.");
                    }
                }
            }
        } catch (Exception ex) {
            Dialogos.error(this, "Error de conexión con la base de datos:\n" + ex.getMessage());
        }
    }

    private void abrirDashboard(int idRol) {
        JPanel dashboard = DashboardFactory.crear(idRol);
        if (dashboard == null) {
            Dialogos.error(this, "Rol no reconocido.");
            return;
        }
        AppFrame.obtener().mostrarDashboard(dashboard, idRol);
    }

    @Override
    protected void paintComponent(Graphics g) {
        super.paintComponent(g);

        Graphics2D g2d = (Graphics2D) g;
        g2d.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);

        // Fondo degradado
        GradientPaint gradiante = new GradientPaint(0, 0, new Color(35, 60, 100), 0, getHeight(), new Color(5, 10, 30));
        g2d.setPaint(gradiante);
        g2d.fillRect(0, 0, getWidth(), getHeight());

        // Dibujo de burbujas
        g2d.setColor(new Color(255, 255, 255, 50));
        for (Burbuja b : burbujas) {
            g2d.fillOval((int) b.x, (int) b.y, (int) b.tamanio, (int) b.tamanio);
        }

        // Obtener dimensiones de la tarjeta (calculadas en base al contenido)
        Rectangle t = obtenerDimensionesTarjeta();

        // Título "SIDINO" adaptado a la parte superior de la tarjeta
        Font fuenteBase = new Font("Arial Black", Font.BOLD, 120);
        g2d.setFont(fuenteBase);
        FontMetrics fm = g2d.getFontMetrics();

        float anchoObjetivo = getWidth() * 0.65f;
        float escala = anchoObjetivo / fm.stringWidth("SIDINO");
        int textoX = (int) ((getWidth() / escala - fm.stringWidth("SIDINO")) / 2);
        int textoY = (int) (t.y * 0.65f);

        Graphics2D g2Titulo = (Graphics2D) g2d.create();
        g2Titulo.scale(escala, 1.0);
        LinearGradientPaint gradiente1 = new LinearGradientPaint(
                0, 0, (int) (getWidth() / escala), 0,
                new float[]{0.0f, 0.5f, 1.0f},
                new Color[]{new Color(0, 180, 195), new Color(80, 210, 220), new Color(0, 180, 195)});
        g2Titulo.setPaint(gradiente1);
        g2Titulo.drawString("SIDINO", textoX, Math.max(30, textoY));
        g2Titulo.dispose();

        // Dibujo del panel contenedor (la tarjeta se ajusta exactamente a los elementos)
        g2d.setColor(new Color(15, 35, 70, 220));
        g2d.fillRoundRect(t.x, t.y, t.width, t.height, 30, 30);
    }

    private void construirComponentes() {
        bienvenidos.setForeground(new Color(220, 235, 255));
        bienvenidos.setHorizontalAlignment(JLabel.CENTER);

        subBienvenidos.setForeground(new Color(140, 160, 190));
        subBienvenidos.setHorizontalAlignment(JLabel.CENTER);

        // Campo Usuario
        campoUsuario.setText(PLACEHOLDER_USUARIO);
        campoUsuario.setForeground(new Color(140, 160, 190));
        campoUsuario.setBackground(new Color(40, 65, 110));
        campoUsuario.setCaretColor(new Color(0, 230, 210));
        campoUsuario.setMargin(new Insets(0, 15, 0, 0));
        campoUsuario.addFocusListener(new FocusAdapter() {
            @Override public void focusGained(FocusEvent e) {
                if (campoUsuario.getText().equals(PLACEHOLDER_USUARIO)) {
                    campoUsuario.setText("");
                    campoUsuario.setForeground(new Color(220, 235, 255));
                }
            }
            @Override public void focusLost(FocusEvent e) {
                if (campoUsuario.getText().isBlank()) {
                    campoUsuario.setText(PLACEHOLDER_USUARIO);
                    campoUsuario.setForeground(new Color(140, 160, 190));
                }
            }
        });

        errUsu.setForeground(new Color(255, 80, 80));
        errUsu.setHorizontalAlignment(JLabel.LEFT);
        errUsu.setVisible(false);

        // Campo Contraseña
        echoCharReal = campoPassword.getEchoChar();
        campoPassword.setCaretColor(new Color(0, 230, 210));
        campoPassword.setBackground(new Color(40, 65, 110));
        campoPassword.setMargin(new Insets(0, 15, 0, 0));
        mostrarPlaceholderPassword();

        campoPassword.addFocusListener(new FocusAdapter() {
            @Override public void focusGained(FocusEvent e) {
                if (passwordEsPlaceholder) ocultarPlaceholderPassword();
            }
            @Override public void focusLost(FocusEvent e) {
                if (campoPassword.getPassword().length == 0) mostrarPlaceholderPassword();
            }
        });

        errPass.setForeground(new Color(255, 80, 80));
        errPass.setHorizontalAlignment(JLabel.LEFT);
        errPass.setVisible(false);

        add(bienvenidos);
        add(subBienvenidos);
        add(campoUsuario);
        add(errUsu);
        add(campoPassword);
        add(errPass);
        add(botonLogin);
    }

    /**
     * Calcula las dimensiones del panel ajustándose a los componentes internos.
     */
    private Rectangle obtenerDimensionesTarjeta() {
        int screenW = getWidth();
        int screenH = getHeight();

        // 1. Los botones y campos se escalan con la pantalla
        int compAncho = Math.max(280, Math.min(450, (int) (screenW * 0.28f)));
        int campoAlto = Math.max(36, Math.min(48, (int) (screenH * 0.052f)));
        int botonAlto = Math.max(40, Math.min(52, (int) (screenH * 0.058f)));

        int fontTituloSize = Math.max(20, Math.min(26, (int) (compAncho * 0.07f)));
        int fontSubSize = Math.max(12, Math.min(15, (int) (compAncho * 0.045f)));

        int hBien = (int) (fontTituloSize * 1.3f);
        int hSub = (int) (fontSubSize * 1.3f);
        int hErrUsu = errUsu.isVisible() ? 16 : 0;
        int hErrPass = errPass.isVisible() ? 16 : 0;

        int gapPequeno = Math.max(4, (int) (screenH * 0.008f));
        int gapMedio = Math.max(8, (int) (screenH * 0.018f));
        int gapGrande = Math.max(14, (int) (screenH * 0.028f));

        // 2. Suma total del alto ocupado por los elementos
        int altoContenido = hBien + gapPequeno
                + hSub + gapGrande
                + campoAlto + (errUsu.isVisible() ? gapPequeno + hErrUsu : 0) + gapMedio
                + campoAlto + (errPass.isVisible() ? gapPequeno + hErrPass : 0) + gapGrande
                + botonAlto;

        // 3. Márgenes internos (padding) del panel alrededor de los elementos
        int padH = Math.max(24, (int) (compAncho * 0.10f));
        int padV = Math.max(24, (int) (screenH * 0.035f));

        int tarjetaAncho = compAncho + (padH * 2);
        int tarjetaAlto = altoContenido + (padV * 2);

        int tarjetaX = (screenW - tarjetaAncho) / 2;
        int tarjetaY = (screenH - tarjetaAlto) / 2 + (int) (screenH * 0.03f);

        return new Rectangle(tarjetaX, tarjetaY, tarjetaAncho, tarjetaAlto);
    }

    /**
     * Posiciona los componentes responsivamente dentro del panel envolvente.
     */
    private void posicionarComponentes() {
        int screenW = getWidth();
        int screenH = getHeight();

        int compAncho = Math.max(280, Math.min(450, (int) (screenW * 0.28f)));
        int campoAlto = Math.max(36, Math.min(48, (int) (screenH * 0.052f)));
        int botonAlto = Math.max(40, Math.min(52, (int) (screenH * 0.058f)));

        int fontTituloSize = Math.max(20, Math.min(26, (int) (compAncho * 0.07f)));
        int fontSubSize = Math.max(12, Math.min(15, (int) (compAncho * 0.045f)));
        int fontCampoSize = Math.max(13, Math.min(16, (int) (compAncho * 0.048f)));

        bienvenidos.setFont(new Font("Arial", Font.BOLD, fontTituloSize));
        subBienvenidos.setFont(new Font("Arial", Font.PLAIN, fontSubSize));
        campoUsuario.setFont(new Font("Arial", Font.PLAIN, fontCampoSize));
        campoPassword.setFont(new Font("Arial", Font.PLAIN, fontCampoSize));
        botonLogin.setFont(new Font("Arial", Font.BOLD, fontCampoSize + 1));

        errUsu.setFont(new Font("Arial", Font.PLAIN, fontCampoSize - 2));
        errPass.setFont(new Font("Arial", Font.PLAIN, fontCampoSize - 2));

        int gapPequeno = Math.max(4, (int) (screenH * 0.008f));
        int gapMedio = Math.max(8, (int) (screenH * 0.018f));
        int gapGrande = Math.max(14, (int) (screenH * 0.028f));

        int hBien = (int) (fontTituloSize * 1.3f);
        int hSub = (int) (fontSubSize * 1.3f);
        int hErrUsu = errUsu.isVisible() ? 16 : 0;
        int hErrPass = errPass.isVisible() ? 16 : 0;

        // Obtener el rectángulo exacto del panel
        Rectangle t = obtenerDimensionesTarjeta();

        int padH = Math.max(24, (int) (compAncho * 0.10f));
        int padV = Math.max(24, (int) (screenH * 0.035f));

        int compX = t.x + padH;
        int yCursor = t.y + padV;

        // 1. Título "Bienvenidos"
        bienvenidos.setBounds(compX, yCursor, compAncho, hBien);
        yCursor += hBien + gapPequeno;

        // 2. Subtítulo
        subBienvenidos.setBounds(compX, yCursor, compAncho, hSub);
        yCursor += hSub + gapGrande;

        // 3. Campo Usuario
        campoUsuario.setBounds(compX, yCursor, compAncho, campoAlto);
        yCursor += campoAlto;

        if (errUsu.isVisible()) {
            yCursor += gapPequeno;
            errUsu.setBounds(compX, yCursor, compAncho, hErrUsu);
            yCursor += hErrUsu;
        }
        yCursor += gapMedio;

        // 4. Campo Contraseña
        campoPassword.setBounds(compX, yCursor, compAncho, campoAlto);
        yCursor += campoAlto;

        if (errPass.isVisible()) {
            yCursor += gapPequeno;
            errPass.setBounds(compX, yCursor, compAncho, hErrPass);
            yCursor += hErrPass;
        }
        yCursor += gapGrande;

        // 5. Botón Iniciar Sesión
        botonLogin.setBounds(compX, yCursor, compAncho, botonAlto);
    }

    private void mostrarPlaceholderPassword() {
        passwordEsPlaceholder = true;
        campoPassword.setEchoChar((char) 0);
        campoPassword.setText(PLACEHOLDER_PASSWORD);
        campoPassword.setForeground(new Color(140, 160, 190));
    }

    private void ocultarPlaceholderPassword() {
        passwordEsPlaceholder = false;
        campoPassword.setText("");
        campoPassword.setEchoChar(echoCharReal);
        campoPassword.setForeground(new Color(220, 235, 255));
    }
}