package com.sidino.reportes.service;

import com.sidino.reportes.model.Reporte;
import org.apache.poi.xwpf.usermodel.XWPFDocument;
import org.apache.poi.xwpf.usermodel.XWPFParagraph;
import org.apache.poi.xwpf.usermodel.XWPFRun;
import org.apache.poi.xwpf.usermodel.XWPFTable;
import org.springframework.stereotype.Service;

import java.io.ByteArrayOutputStream;
import java.io.IOException;

@Service
public class WordService {

    public byte[] generarWord(Reporte reporte) {

        try (
                XWPFDocument documento = new XWPFDocument();
                ByteArrayOutputStream outputStream = new ByteArrayOutputStream()
        ) {

            XWPFParagraph titulo = documento.createParagraph();

            XWPFRun run = titulo.createRun();

            run.setText("Reporte: " + reporte.getNombreTabla());
            run.setBold(true);
            run.setFontSize(16);

            XWPFTable tabla = documento.createTable(
                    reporte.getDatos().size() + 1,
                    reporte.getColumnas().size()
            );

            // Encabezados
            for (int i = 0; i < reporte.getColumnas().size(); i++) {

                tabla.getRow(0)
                        .getCell(i)
                        .setText(reporte.getColumnas().get(i));
            }

            // Datos
            for (int fila = 0; fila < reporte.getDatos().size(); fila++) {

                for (int columna = 0;
                     columna < reporte.getDatos().get(fila).size();
                     columna++) {

                    Object valor = reporte.getDatos()
                            .get(fila)
                            .get(columna);

                    tabla.getRow(fila + 1)
                            .getCell(columna)
                            .setText(
                                    valor != null
                                            ? valor.toString()
                                            : ""
                            );
                }
            }

            documento.write(outputStream);

            return outputStream.toByteArray();

        } catch (IOException e) {
            throw new RuntimeException(
                    "Error al generar el archivo Word",
                    e
            );
        }
    }
}