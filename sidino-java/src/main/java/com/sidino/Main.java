package com.sidino;

import com.sidino.ui.AppFrame;

import javax.swing.SwingUtilities;

public class Main {
    public static void main(String[] args) {
        SwingUtilities.invokeLater(AppFrame::obtener);
    }
}
