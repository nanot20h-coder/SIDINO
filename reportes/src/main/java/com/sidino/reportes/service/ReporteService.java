package com.sidino.reportes.service;

import com.sidino.reportes.model.Reporte;
import com.sidino.reportes.repository.ReporteRepository;
import org.springframework.stereotype.Service;

import java.util.List;

@Service
public class ReporteService {

    private final ReporteRepository reporteRepository;

    public ReporteService(ReporteRepository reporteRepository) {
        this.reporteRepository = reporteRepository;
    }

    public List<String> obtenerTablas() {

        return reporteRepository.obtenerTablas();
    }

    public Reporte generarReporte(
            String nombreTabla
    ) {

        return reporteRepository.obtenerDatosTabla(
                nombreTabla
        );
    }

    public List<String> obtenerColumnas(
            String nombreTabla
    ) {

        return reporteRepository.obtenerColumnas(
                nombreTabla
        );
    }

    public Reporte generarReporteFiltrado(
            String nombreTabla,
            List<String> columnas,
            List<String> valores
    ) {

        return reporteRepository.obtenerDatosTablaFiltrados(
                nombreTabla,
                columnas,
                valores
        );
    }
}