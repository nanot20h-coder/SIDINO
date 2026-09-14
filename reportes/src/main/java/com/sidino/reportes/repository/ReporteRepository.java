package com.sidino.reportes.repository;

import com.sidino.reportes.model.Reporte;
import org.springframework.stereotype.Repository;

import javax.sql.DataSource;
import java.sql.Connection;
import java.sql.DatabaseMetaData;
import java.sql.ResultSet;
import java.sql.ResultSetMetaData;
import java.sql.SQLException;
import java.sql.PreparedStatement;
import java.util.ArrayList;
import java.util.List;

@Repository
public class ReporteRepository {

    private final DataSource dataSource;

    public ReporteRepository(DataSource dataSource) {
        this.dataSource = dataSource;
    }

    public List<String> obtenerTablas() {

        List<String> tablas = new ArrayList<>();

        try (Connection connection = dataSource.getConnection()) {

            DatabaseMetaData metaData = connection.getMetaData();

            try (ResultSet resultSet = metaData.getTables(
                    "sidino",
                    null,
                    "%",
                    new String[]{"TABLE"})) {

                while (resultSet.next()) {

                    String nombreTabla =
                            resultSet.getString("TABLE_NAME");

                    tablas.add(nombreTabla);
                }
            }

        } catch (SQLException e) {

            throw new RuntimeException(
                    "Error al obtener las tablas de la base de datos",
                    e
            );
        }

        return tablas;
    }

    public List<String> obtenerColumnas(String nombreTabla) {

        validarNombreTabla(nombreTabla);

        List<String> columnas = new ArrayList<>();

        String sql =
                "SELECT * FROM `" + nombreTabla + "` LIMIT 0";

        try (
                Connection connection =
                        dataSource.getConnection();

                PreparedStatement statement =
                        connection.prepareStatement(sql);

                ResultSet resultSet =
                        statement.executeQuery()
        ) {

            ResultSetMetaData metaData =
                    resultSet.getMetaData();

            int cantidadColumnas =
                    metaData.getColumnCount();

            for (int i = 1;
                 i <= cantidadColumnas;
                 i++) {

                columnas.add(
                        metaData.getColumnName(i)
                );
            }

        } catch (SQLException e) {

            throw new RuntimeException(
                    "Error al obtener las columnas de la tabla: "
                            + nombreTabla,
                    e
            );
        }

        return columnas;
    }

    public Reporte obtenerDatosTabla(String nombreTabla) {

        validarNombreTabla(nombreTabla);

        String sql =
                "SELECT * FROM `" + nombreTabla + "`";

        try (
                Connection connection =
                        dataSource.getConnection();

                PreparedStatement statement =
                        connection.prepareStatement(sql);

                ResultSet resultSet =
                        statement.executeQuery()
        ) {

            ResultSetMetaData metaData =
                    resultSet.getMetaData();

            int cantidadColumnas =
                    metaData.getColumnCount();

            List<String> columnas =
                    new ArrayList<>();

            for (int i = 1;
                 i <= cantidadColumnas;
                 i++) {

                columnas.add(
                        metaData.getColumnName(i)
                );
            }

            List<List<Object>> datos =
                    new ArrayList<>();

            while (resultSet.next()) {

                List<Object> fila =
                        new ArrayList<>();

                for (int i = 1;
                     i <= cantidadColumnas;
                     i++) {

                    fila.add(
                            resultSet.getObject(i)
                    );
                }

                datos.add(fila);
            }

            return new Reporte(
                    nombreTabla,
                    columnas,
                    datos
            );

        } catch (SQLException e) {

            throw new RuntimeException(
                    "Error al obtener los datos de la tabla: "
                            + nombreTabla,
                    e
            );
        }
    }

    public Reporte obtenerDatosTablaFiltrados(
            String nombreTabla,
            List<String> columnas,
            List<String> valores
    ) {

        validarNombreTabla(nombreTabla);

        if (columnas == null || valores == null) {

            return obtenerDatosTabla(nombreTabla);
        }

        if (columnas.size() != valores.size()) {

            throw new IllegalArgumentException(
                    "La cantidad de columnas y valores no coincide"
            );
        }

        List<String> columnasDisponibles =
                obtenerColumnas(nombreTabla);

        for (String columna : columnas) {

            if (!columnasDisponibles.contains(columna)) {

                throw new IllegalArgumentException(
                        "La columna indicada no existe: "
                                + columna
                );
            }
        }

        StringBuilder sql =
                new StringBuilder();

        sql.append("SELECT * FROM `")
                .append(nombreTabla)
                .append("`");

        if (!columnas.isEmpty()) {

            sql.append(" WHERE ");

            for (int i = 0;
                 i < columnas.size();
                 i++) {

                if (i > 0) {
                    sql.append(" AND ");
                }

                sql.append("`")
                        .append(columnas.get(i))
                        .append("` LIKE ?");
            }
        }

        try (
                Connection connection =
                        dataSource.getConnection();

                PreparedStatement statement =
                        connection.prepareStatement(
                                sql.toString()
                        )
        ) {

            for (int i = 0;
                 i < valores.size();
                 i++) {

                statement.setString(
                        i + 1,
                        "%" + valores.get(i) + "%"
                );
            }

            try (
                    ResultSet resultSet =
                            statement.executeQuery()
            ) {

                ResultSetMetaData metaData =
                        resultSet.getMetaData();

                int cantidadColumnas =
                        metaData.getColumnCount();

                List<String> nombresColumnas =
                        new ArrayList<>();

                for (int i = 1;
                     i <= cantidadColumnas;
                     i++) {

                    nombresColumnas.add(
                            metaData.getColumnName(i)
                    );
                }

                List<List<Object>> datos =
                        new ArrayList<>();

                while (resultSet.next()) {

                    List<Object> fila =
                            new ArrayList<>();

                    for (int i = 1;
                         i <= cantidadColumnas;
                         i++) {

                        fila.add(
                                resultSet.getObject(i)
                        );
                    }

                    datos.add(fila);
                }

                return new Reporte(
                        nombreTabla,
                        nombresColumnas,
                        datos
                );
            }

        } catch (SQLException e) {

            throw new RuntimeException(
                    "Error al obtener los datos filtrados de la tabla: "
                            + nombreTabla,
                    e
            );
        }
    }

    private void validarNombreTabla(
            String nombreTabla
    ) {

        List<String> tablas =
                obtenerTablas();

        if (!tablas.contains(nombreTabla)) {

            throw new IllegalArgumentException(
                    "La tabla indicada no existe en la base de datos"
            );
        }
    }
}