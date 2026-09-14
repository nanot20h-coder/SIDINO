package com.sidino.reportes.model;

import java.util.List;

public class Reporte {

    private String nombreTabla;
    private List<String> columnas;
    private List<List<Object>> datos;

    public Reporte() {
    }

    public Reporte(String nombreTabla, List<String> columnas, List<List<Object>> datos) {
        this.nombreTabla = nombreTabla;
        this.columnas = columnas;
        this.datos = datos;
    }

    public String getNombreTabla() {
        return nombreTabla;
    }

    public void setNombreTabla(String nombreTabla) {
        this.nombreTabla = nombreTabla;
    }

    public List<String> getColumnas() {
        return columnas;
    }

    public void setColumnas(List<String> columnas) {
        this.columnas = columnas;
    }

    public List<List<Object>> getDatos() {
        return datos;
    }

    public void setDatos(List<List<Object>> datos) {
        this.datos = datos;
    }
}