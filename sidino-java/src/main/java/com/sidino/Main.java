package com.sidino;

import com.formdev.flatlaf.FlatDarkLaf;
import com.formdev.flatlaf.FlatLightLaf;
import com.sidino.core.Preferencias;
import com.sidino.ui.AppFrame;

import javax.swing.SwingUtilities;
import javax.swing.UIManager;

public class Main {
    public static void main(String[] args) {
        try {
            if (Preferencias.esTemaOscuro()) {
                FlatDarkLaf.setup();
            } else {
                FlatLightLaf.setup();
            }
            UIManager.put("Component.arc", 12);
            UIManager.put("Button.arc", 10);
            UIManager.put("TextComponent.arc", 10);
            UIManager.put("ScrollBar.showButtons", false);
            UIManager.put("Table.showHorizontalLines", false);
        } catch (Exception ex) {
            System.err.println("No se pudo activar FlatLaf: " + ex.getMessage());
        }
        SwingUtilities.invokeLater(AppFrame::obtener);
    }
}
