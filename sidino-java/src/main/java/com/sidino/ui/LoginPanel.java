package com.sidino.ui;

import com.sidino.core.Conexion;
import com.sidino.core.Sesion;
import com.sidino.ui.componentes.BotonRedondeado;
import com.sidino.ui.componentes.Dialogos;
import com.sidino.ui.componentes.RPF;
import com.sidino.ui.componentes.RTF;

import javax.swing.*;
import java.awt.*;
import java.awt.event.FocusAdapter;
import java.awt.event.FocusEvent;
import java.sql.Connection;
import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.util.ArrayList;
import java.util.List;

/**
 * Panel de inicio de sesión (ya no es una ventana propia). Mismo diseño
 * visual que el Login original (fondo con burbujas, título "SIDINO"
 * degradado, campos redondeados), pero:
 *  - corregido el bug donde el placeholder de la contraseña se superponía
 *    con el texto escrito (ahora se oculta al enfocar/escribir, igual que
 *    hace el campo de usuario).
 *  - al autenticar, no cierra ninguna ventana ni abre una nueva: le pide a
 *    AppFrame que cambie el contenido al dashboard correspondiente.
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

    private final List<Burbuja> burbujas = new ArrayList<>();
    private final JLabel bienvenidos = new JLabel("Bienvenidos");
    private final JLabel subBienvenidos = new JLabel("Inicia sesión para empezar tu día");
    private final JTextField campoUsuario = new RTF(20);
    private final JLabel errUsu = new JLabel("El usuario no puede estar vacío");
    private final JPasswordField campoPassword = new RPF(20);
    private final JLabel errPass = new JLabel("La contraseña no puede estar vacía");
    private final BotonRedondeado botonLogin = new BotonRedondeado("Iniciar Sesión", 15);

    private static final String PLACEHOLDER_USUARIO = "Escribe tu usuario (correo):";
    private static final String PLACEHOLDER_PASSWORD = "Introduce tu contraseña";
    private char echoCharReal;
    private boolean passwordEsPlaceholder = true;
    private boolean inicializacion = false;

    public LoginPanel() {
        setLayout(null);
        Timer timer = new Timer(30, e -> {
            for (Burbuja b : burbujas) {
                b.y -= b.velocidad;
                if (b.y < -20) { b.y = getHeight() + 20; b.x = (float) (Math.random() * getWidth()); }
            }
            repaint();
        });
        for (int i = 0; i < 40; i++) burbujas.add(new Burbuja());
        timer.start();

        botonLogin.addActionListener(e -> intentarLogin());
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

        if (hayError) return;

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
        if (!inicializacion) {
            inicializar();
            inicializacion = true;
        }

        Graphics2D g2d = (Graphics2D) g;
        g2d.setRenderingHint(RenderingHints.KEY_ANTIALIASING, RenderingHints.VALUE_ANTIALIAS_ON);
        GradientPaint gradiante = new GradientPaint(0, 0, new Color(35, 60, 100), 0, getHeight(), new Color(5, 10, 30));
        g2d.setPaint(gradiante);
        g2d.fillRect(0, 0, getWidth(), getHeight());
        g2d.setColor(new Color(255, 255, 255, 50));
        for (Burbuja b : burbujas) g2d.fillOval((int) b.x, (int) b.y, (int) b.tamanio, (int) b.tamanio);

        Font fuenteBase = new Font("Arial Black", Font.BOLD, 120);
        g2d.setFont(fuenteBase);
        FontMetrics fm = g2d.getFontMetrics();
        float anchoObjetivo = getWidth() * 0.80f;
        float escala = anchoObjetivo / fm.stringWidth("SIDINO");
        int textoX = (int) ((getWidth() / escala - fm.stringWidth("SIDINO")) / 2);
        int textoY = (getHeight() - 1300 / 2);
        Graphics2D g2Titulo = (Graphics2D) g2d.create();
        g2Titulo.scale(escala, 1.0);
        LinearGradientPaint gradiente1 = new LinearGradientPaint(
                0, 0, (int) (getWidth() / escala), 0,
                new float[]{0.0f, 0.5f, 1.0f},
                new Color[]{new Color(0, 180, 195), new Color(80, 210, 220), new Color(0, 180, 195)});
        g2Titulo.setPaint(gradiente1);
        g2Titulo.drawString("SIDINO", textoX, textoY);
        g2Titulo.dispose();

        g2d.setColor(new Color(15, 35, 70, 220));
        g2d.fillRoundRect((getWidth() - 400) / 2, (getHeight() - 400) / 2, 400, 400, 30, 30);
    }

    private void inicializar() {
        bienvenidos.setBounds((getWidth() - 300) / 2, (getHeight() - 500) / 2 + 100, 300, 30);
        bienvenidos.setForeground(new Color(220, 235, 255));
        bienvenidos.setFont(new Font("Arial", Font.BOLD, 24));
        bienvenidos.setHorizontalAlignment(JLabel.CENTER);

        subBienvenidos.setBounds((getWidth() - 300) / 2, (getHeight() - 500) / 2 + 138, 300, 20);
        subBienvenidos.setForeground(new Color(140, 160, 190));
        subBienvenidos.setFont(new Font("Arial", Font.PLAIN, 16));
        subBienvenidos.setHorizontalAlignment(JLabel.CENTER);

        // ── Campo de usuario (placeholder simulado con texto que se limpia al enfocar) ──
        campoUsuario.setBounds((getWidth() - 300) / 2, (getHeight() - 500) / 2 + 180, 300, 35);
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
        errUsu.setFont(new Font("Arial", Font.PLAIN, 14));
        errUsu.setVisible(false);
        errUsu.setBounds((getWidth() - 275) / 2, (getHeight() - 500) / 2 + 150, 300, 35);

        // ── Campo de contraseña: el placeholder ahora es TEXTO REAL dentro
        //    del propio campo (igual que el de usuario), no una etiqueta
        //    superpuesta. Para eso se desactiva temporalmente el "echo char"
        //    (el caracter que oculta la contraseña) mientras se muestra el
        //    placeholder, y se reactiva apenas el usuario empieza a escribir.
        //    Así no hay dos textos pisándose nunca. ──
        campoPassword.setBounds((getWidth() - 300) / 2, (getHeight() - 375) / 2 + 180, 300, 35);
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
        errPass.setFont(new Font("Arial", Font.PLAIN, 14));
        errPass.setVisible(false);
        errPass.setBounds((getWidth() - 275) / 2, (getHeight() - 375) / 2 + 150, 300, 35);

        botonLogin.setBounds((getWidth() - 350) / 2, (getHeight() - 250) / 2 + 180, 350, 45);

        add(bienvenidos);
        add(subBienvenidos);
        add(errUsu);
        add(campoUsuario);
        add(errPass);
        add(campoPassword);
        add(botonLogin);
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
