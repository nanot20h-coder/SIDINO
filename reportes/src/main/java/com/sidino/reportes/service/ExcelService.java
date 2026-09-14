package com.sidino.reportes.service;

import com.sidino.reportes.model.Reporte;
import org.apache.poi.ss.usermodel.Row;
import org.apache.poi.ss.usermodel.Sheet;
import org.apache.poi.ss.usermodel.Workbook;
import org.apache.poi.xssf.usermodel.XSSFWorkbook;
import org.springframework.stereotype.Service;

import java.io.ByteArrayOutputStream;
import java.io.IOException;

@Service
public class ExcelService {

    public byte[] generarExcel(Reporte reporte) {

        try (Workbook workbook = new XSSFWorkbook();
             ByteArrayOutputStream outputStream = new ByteArrayOutputStream()) {

            Sheet hoja = workbook.createSheet(reporte.getNombreTabla());

            // Encabezados
            Row encabezado = hoja.createRow(0);

            for (int i = 0; i < reporte.getColumnas().size(); i++) {
                encabezado.createCell(i)
                        .setCellValue(reporte.getColumnas().get(i));
            }

            // Datos
            for (int fila = 0; fila < reporte.getDatos().size(); fila++) {

                Row row = hoja.createRow(fila + 1);

                for (int columna = 0;
                     columna < reporte.getDatos().get(fila).size();
                     columna++) {

                    Object valor = reporte.getDatos()
                            .get(fila)
                            .get(columna);

                    if (valor != null) {
                        row.createCell(columna)
                                .setCellValue(valor.toString());
                    }
                }
            }

            // Ajustar ancho
            for (int i = 0; i < reporte.getColumnas().size(); i++) {
                hoja.autoSizeColumn(i);
            }

            workbook.write(outputStream);

            return outputStream.toByteArray();

        } catch (IOException e) {
            throw new RuntimeException(
                    "Error al generar el archivo Excel",
                    e
            );
        }
    }
}