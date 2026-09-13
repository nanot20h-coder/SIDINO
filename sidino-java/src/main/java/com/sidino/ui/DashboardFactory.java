package com.sidino.ui;

import javax.swing.JPanel;

/** Crea el panel de dashboard correspondiente a un id_rol dado. */
public class DashboardFactory {
    private DashboardFactory() {}

    public static JPanel crear(int idRol) {
        return switch (idRol) {
            case 1 -> new RectorDashboard();
            case 2 -> new CoordinadorDashboard();
            case 3 -> new AdministrativoDashboard();
            case 4 -> new DocenteDashboard();
            case 5 -> new EstudianteDashboard();
            case 6 -> new AcudienteDashboard();
            default -> null;
        };
    }
}
