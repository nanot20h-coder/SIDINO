package com.sidino.reportes.service;

import com.sidino.reportes.model.Reporte;
import com.lowagie.text.Document;
import com.lowagie.text.PageSize;
import com.lowagie.text.Paragraph;
import com.lowagie.text.pdf.PdfWriter;
import com.lowagie.text.pdf.PdfPTable;
import org.springframework.stereotype.Service;

import java.io.ByteArrayOutputStream;

@Service
public class PdfService {

    public byte[] generarPdf(Reporte reporte) {

        try {

            ByteArrayOutputStream outputStream =
                    new ByteArrayOutputStream();

            Document documento =
                    new Document(PageSize.A4.rotate());

            PdfWriter.getInstance(
                    documento,
                    outputStream
            );

            documento.open();

         Paragraph titulo =
        new Paragraph(
                "Reporte: " + reporte.getNombreTabla()
        );
            titulo.setSpacingAfter(15);
            documento.add(titulo);  

            PdfPTable tabla =
                    new PdfPTable(
                            reporte.getColumnas().size()
                    );

            // Encabezados
            for (String columna : reporte.getColumnas()) {
                tabla.addCell(columna);
            }

            // Datos
            for (var fila : reporte.getDatos()) {

                for (Object valor : fila) {

                    tabla.addCell(
                            valor != null
                                    ? valor.toString()
                                    : ""
                    );
                }
            }

            documento.add(tabla);

            documento.close();

            return outputStream.toByteArray();

        } catch (Exception e) {

            throw new RuntimeException(
                    "Error al generar el archivo PDF",
                    e
            );
        }
    }
}