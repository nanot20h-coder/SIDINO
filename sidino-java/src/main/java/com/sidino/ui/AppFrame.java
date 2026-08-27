package com.sidino.ui;

import javax.swing.*;
import java.awt.*;

/**
 * Ventana única de la aplicación. En vez de cerrar el login y abrir una
 * ventana nueva para el dashboard (lo que causaba un parpadeo/salto visual),
 * esta clase mantiene un único JFrame y cambia su contenido con CardLayout:
 * "login" ↔ "dashboard".
 */
public class AppFrame extends JFrame {

    private static AppFrame instancia;

    private final CardLayout cardLayout = new CardLayout();
    private final JPanel raiz = new JPanel(cardLayout);

    /** null si estamos en el login; id_rol del dashboard activo en caso contrario. */
    private Integer idRolActual;

    private AppFrame() {
        super("SIDINO \uD83D\uDC19");
        setUndecorated(true);
        setExtendedState(JFrame.MAXIMIZED_BOTH);
        setDefaultCloseOperation(JFrame.EXIT_ON_CLOSE);
        setLayout(new BorderLayout());
        raiz.setBackground(com.sidino.ui.componentes.Estilos.FONDO);
        add(raiz, BorderLayout.CENTER);

        raiz.add(new LoginPanel(), "login");
        setVisible(true);
    }

    public static AppFrame obtener() {
        if (instancia == null) instancia = new AppFrame();
        return instancia;
    }

    /** Vuelve a mostrar el login (por ejemplo, tras cerrar sesión). */
    public void mostrarLogin() {
        idRolActual = null;
        raiz.add(new LoginPanel(), "login");
        cardLayout.show(raiz, "login");
        revalidate();
        repaint();
    }

    /** Reemplaza el contenido por el dashboard del rol indicado, sin abrir una ventana nueva. */
    public void mostrarDashboard(JPanel dashboard, int idRol) {
        idRolActual = idRol;
        raiz.add(dashboard, "dashboard");
        cardLayout.show(raiz, "dashboard");
        revalidate();
        repaint();
    }

    /**
     * Reconstruye la vista actual (login o dashboard) desde cero. Se usa
     * después de cambiar el tema/acento en Personalizar, ya que los
     * componentes Swing ya dibujados no cambian de color solos: hay que
     * volver a crearlos para que tomen la nueva paleta de Estilos.
     */
    public void refrescar() {
        SwingUtilities.updateComponentTreeUI(this);
        if (idRolActual == null) {
            mostrarLogin();
        } else {
            mostrarDashboard(DashboardFactory.crear(idRolActual), idRolActual);
        }
    }
}
