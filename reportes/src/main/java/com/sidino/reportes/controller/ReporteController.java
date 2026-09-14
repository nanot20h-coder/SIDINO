package com.sidino.reportes.controller;

import java.util.List;
import com.sidino.reportes.model.Reporte;
import com.sidino.reportes.service.ExcelService;
import com.sidino.reportes.service.PdfService;
import com.sidino.reportes.service.ReporteService;
import com.sidino.reportes.service.WordService;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestParam;
import java.util.ArrayList;

@Controller
public class ReporteController {

    private final ReporteService reporteService;
    private final ExcelService excelService;
    private final PdfService pdfService;
    private final WordService wordService;

    public ReporteController(
            ReporteService reporteService,
            ExcelService excelService,
            PdfService pdfService,
            WordService wordService
    ) {
        this.reporteService = reporteService;
        this.excelService = excelService;
        this.pdfService = pdfService;
        this.wordService = wordService;
    }

    @GetMapping("/")
    public String mostrarReportes(Model model) {

        model.addAttribute(
                "tablas",
                reporteService.obtenerTablas()
        );

        return "reportes";
    }

@GetMapping("/reportes/generar")
public ResponseEntity<byte[]> generarReporte(
        @RequestParam String tabla,
        @RequestParam String formato,
        @RequestParam(required = false) List<String> columnas,
        @RequestParam(required = false) List<String> valores
) {

    Reporte reporte;

    if (columnas != null && valores != null
            && !columnas.isEmpty()
            && !valores.isEmpty()) {

        reporte =
                reporteService.generarReporteFiltrado(
                        tabla,
                        columnas,
                        valores
                );

    } else {

        reporte =
                reporteService.generarReporte(tabla);
    }

        byte[] archivo;

        String nombreArchivo;

        switch (formato.toLowerCase()) {

            case "excel":

                archivo = excelService.generarExcel(reporte);

                nombreArchivo =
                        "reporte_" + tabla + ".xlsx";

                return ResponseEntity.ok()
                        .header(
                                HttpHeaders.CONTENT_DISPOSITION,
                                "attachment; filename=\"" +
                                        nombreArchivo +
                                        "\""
                        )
                        .contentType(
                                MediaType.parseMediaType(
                                        "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                                )
                        )
                        .body(archivo);

            case "word":

                archivo = wordService.generarWord(reporte);

                nombreArchivo =
                        "reporte_" + tabla + ".docx";

                return ResponseEntity.ok()
                        .header(
                                HttpHeaders.CONTENT_DISPOSITION,
                                "attachment; filename=\"" +
                                        nombreArchivo +
                                        "\""
                        )
                        .contentType(
                                MediaType.parseMediaType(
                                        "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                )
                        )
                        .body(archivo);

            case "pdf":

                archivo = pdfService.generarPdf(reporte);

                nombreArchivo =
                        "reporte_" + tabla + ".pdf";

                return ResponseEntity.ok()
                        .header(
                                HttpHeaders.CONTENT_DISPOSITION,
                                "attachment; filename=\"" +
                                        nombreArchivo +
                                        "\""
                        )
                        .contentType(MediaType.APPLICATION_PDF)
                        .body(archivo);

            default:

                throw new IllegalArgumentException(
                        "Formato de reporte no válido"
                );
        }
    }
    @GetMapping("/reportes/columnas")
public String obtenerColumnas(
        @RequestParam String tabla,
        Model model
) {

    model.addAttribute(
            "tablas",
            reporteService.obtenerTablas()
    );

    model.addAttribute(
            "tablaSeleccionada",
            tabla
    );

    model.addAttribute(
            "columnas",
            reporteService.obtenerColumnas(tabla)
    );

    return "reportes";
}
@GetMapping("/reportes/buscar")
public String buscarReporte(
        @RequestParam String tabla,
        @RequestParam(required = false) List<String> columnas,
        @RequestParam(required = false) List<String> valores,
        Model model
) {

    List<String> columnasValidas = new ArrayList<>();
    List<String> valoresValidos = new ArrayList<>();

    /*
     * Validamos que ambas listas existan
     * y recorremos únicamente hasta donde
     * ambas tengan elementos.
     */
    if (columnas != null && valores != null) {

        int cantidad = Math.min(
                columnas.size(),
                valores.size()
        );

        for (int i = 0; i < cantidad; i++) {

            String columna = columnas.get(i);
            String valor = valores.get(i);

            if (columna != null
                    && !columna.isBlank()
                    && valor != null
                    && !valor.isBlank()) {

                columnasValidas.add(columna);
                valoresValidos.add(valor);
            }
        }
    }

    Reporte reporte;

    /*
     * Si existen filtros válidos,
     * hacemos una búsqueda filtrada.
     *
     * Si no existen filtros válidos,
     * mostramos todos los registros.
     */
    if (!columnasValidas.isEmpty()) {

        reporte =
                reporteService.generarReporteFiltrado(
                        tabla,
                        columnasValidas,
                        valoresValidos
                );

    } else {

        reporte =
                reporteService.generarReporte(tabla);
    }

    model.addAttribute(
            "tablas",
            reporteService.obtenerTablas()
    );

    model.addAttribute(
            "tablaSeleccionada",
            tabla
    );

    model.addAttribute(
            "columnas",
            reporteService.obtenerColumnas(tabla)
    );

    model.addAttribute(
            "reporte",
            reporte
    );

    model.addAttribute(
            "columnasFiltro",
            columnasValidas
    );

    model.addAttribute(
            "valoresFiltro",
            valoresValidos
    );

    return "reportes";
}
}