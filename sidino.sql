-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-09-2026 a las 01:38:50
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sidino`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `acudiente_estudiante`
--

CREATE TABLE `acudiente_estudiante` (
  `id_acudiente` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignacion_academica`
--

CREATE TABLE `asignacion_academica` (
  `id_asignacion` int(11) NOT NULL,
  `id_docente` int(11) NOT NULL,
  `id_materia` int(11) NOT NULL,
  `id_periodo` int(11) NOT NULL,
  `id_salon` int(11) NOT NULL,
  `id_horario` int(11) NOT NULL,
  `id_curso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `asignacion_academica`
--

INSERT INTO `asignacion_academica` (`id_asignacion`, `id_docente`, `id_materia`, `id_periodo`, `id_salon`, `id_horario`, `id_curso`) VALUES
(1, 14, 1, 1, 1, 1, 2);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `atributo`
--

CREATE TABLE `atributo` (
  `id_atributo` int(11) NOT NULL,
  `nombre_atributo` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `boletin`
--

CREATE TABLE `boletin` (
  `id_boletin` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_periodo` int(11) NOT NULL,
  `fecha` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `boletin_detalle`
--

CREATE TABLE `boletin_detalle` (
  `id_detalle` int(11) NOT NULL,
  `id_boletin` int(11) NOT NULL,
  `id_nota` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citacion`
--

CREATE TABLE `citacion` (
  `id_citacion` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_asignacion` int(11) NOT NULL,
  `motivo` text NOT NULL,
  `fecha` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contenido`
--

CREATE TABLE `contenido` (
  `id_contenido` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `archivo` varchar(255) DEFAULT NULL,
  `id_asignacion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `curso`
--

CREATE TABLE `curso` (
  `id_curso` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `curso`
--

INSERT INTO `curso` (`id_curso`, `nombre`) VALUES
(2, 'Once');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_accion`
--

CREATE TABLE `historial_accion` (
  `id_historial` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `accion` varchar(255) NOT NULL,
  `tabla_afectada` varchar(100) NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `historial_accion`
--

INSERT INTO `historial_accion` (`id_historial`, `id_usuario`, `accion`, `tabla_afectada`, `fecha`) VALUES
(1, 1, 'Editó usuario: Rector Prueba de Cambio (ID 1)', 'usuario', '2026-08-23 14:52:48'),
(2, 1, 'Editó usuario: Paula Herrera (ID 9)', 'usuario', '2026-08-23 14:55:12'),
(3, 1, 'Creó usuario: Leonard Jahkio Parra Ballesteros (ID 11)', 'usuario', '2026-08-26 19:45:40'),
(4, 11, 'Eliminó usuario: Carlos Méndez (ID 3)', 'usuario', '2026-08-26 19:58:25'),
(5, 11, 'Creó usuario: Carol Dayana Trujillo Levasa (ID 12)', 'usuario', '2026-08-26 19:59:00'),
(6, 11, 'Creó usuario: Rafael Acero Narvaes (ID 13)', 'usuario', '2026-08-26 20:00:23'),
(7, 11, 'Creó usuario: Brayan Stiven Parra Fierro (ID 14)', 'usuario', '2026-08-26 20:00:53'),
(8, 11, 'Creó usuario: Kevin Izasa (ID 15)', 'usuario', '2026-08-26 20:01:38'),
(9, 11, 'Creó usuario: El calvo de OLMES (ID 16)', 'usuario', '2026-08-26 20:02:09'),
(10, 11, 'Creó la materia (ID 1) en materia', 'usuario', '2026-08-26 20:10:58'),
(11, 11, 'Creó el curso (ID 1) en curso', 'usuario', '2026-08-26 20:11:12'),
(12, 11, 'Eliminó registro (ID 1) de curso', 'usuario', '2026-08-26 20:11:33'),
(13, 11, 'Creó el curso (ID 2) en curso', 'usuario', '2026-08-26 20:11:43'),
(14, 11, 'Creó el salón (ID 1) en salon', 'usuario', '2026-08-26 20:12:15'),
(15, 11, 'Creó el horario (ID 1) en horario', 'usuario', '2026-08-26 20:23:48'),
(16, 11, 'Creó el periodo académico (ID 1) en periodo_academico', 'usuario', '2026-08-26 20:24:13'),
(17, 11, 'Creó asignación académica (ID 1)', 'usuario', '2026-08-26 20:24:26'),
(18, 11, 'Matriculó estudiante (matrícula ID 1)', 'usuario', '2026-08-26 20:24:36'),
(19, 11, 'Matriculó estudiante (matrícula ID 2)', 'usuario', '2026-08-26 20:24:38'),
(20, 11, 'Matriculó estudiante (matrícula ID 3)', 'usuario', '2026-08-26 20:24:40');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_chatbot`
--

CREATE TABLE `historial_chatbot` (
  `id_chat` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `respuesta` text NOT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horario`
--

CREATE TABLE `horario` (
  `id_horario` int(11) NOT NULL,
  `dia` varchar(20) NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `horario`
--

INSERT INTO `horario` (`id_horario`, `dia`, `hora_inicio`, `hora_fin`) VALUES
(1, 'Lunes', '06:00:00', '12:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `materia`
--

CREATE TABLE `materia` (
  `id_materia` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `materia`
--

INSERT INTO `materia` (`id_materia`, `nombre`) VALUES
(1, 'Matematicas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `matricula`
--

CREATE TABLE `matricula` (
  `id_matricula` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_asignacion` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `matricula`
--

INSERT INTO `matricula` (`id_matricula`, `id_estudiante`, `id_asignacion`) VALUES
(1, 15, 1),
(2, 7, 1),
(3, 10, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nota`
--

CREATE TABLE `nota` (
  `id_nota` int(11) NOT NULL,
  `id_matricula` int(11) NOT NULL,
  `valor` decimal(5,2) NOT NULL,
  `tipo` varchar(60) NOT NULL,
  `porcentaje` decimal(5,2) NOT NULL,
  `fecha` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `observador`
--

CREATE TABLE `observador` (
  `id_observacion` int(11) NOT NULL,
  `id_estudiante` int(11) NOT NULL,
  `id_asignacion` int(11) NOT NULL,
  `descripcion` text NOT NULL,
  `fecha` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `periodo_academico`
--

CREATE TABLE `periodo_academico` (
  `id_periodo` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `nivel` varchar(60) NOT NULL,
  `tipo` varchar(60) NOT NULL,
  `anio` year(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `periodo_academico`
--

INSERT INTO `periodo_academico` (`id_periodo`, `nombre`, `nivel`, `tipo`, `anio`) VALUES
(1, 'Primer trimestre', '1', '1', '2026');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permiso`
--

CREATE TABLE `permiso` (
  `id_permiso` int(11) NOT NULL,
  `nombre_permiso` varchar(100) NOT NULL,
  `tabla_objetivo` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol`
--

CREATE TABLE `rol` (
  `id_rol` int(11) NOT NULL,
  `nombre_rol` varchar(60) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `rol`
--

INSERT INTO `rol` (`id_rol`, `nombre_rol`) VALUES
(1, 'Rectoria'),
(2, 'Coordinacion'),
(3, 'Administrativo'),
(4, 'Docente'),
(5, 'Estudiante'),
(6, 'Acudiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permiso`
--

CREATE TABLE `rol_permiso` (
  `id_rol` int(11) NOT NULL,
  `id_permiso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `salon`
--

CREATE TABLE `salon` (
  `id_salon` int(11) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `capacidad` smallint(6) NOT NULL,
  `ubicacion` varchar(120) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `salon`
--

INSERT INTO `salon` (`id_salon`, `nombre`, `capacidad`, `ubicacion`) VALUES
(1, '1103', 20, '12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(11) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `correo` varchar(120) NOT NULL,
  `contrasena` varchar(255) NOT NULL,
  `id_rol` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nombre`, `correo`, `contrasena`, `id_rol`) VALUES
(1, 'Rector Prueba de Cambio', 'rector@sidino.com', '1234', 1),
(4, 'María López', '[coord@edunova.co](mailto:coord@edunova.co)', '123456', 2),
(5, 'Jorge Ramírez', '[admin@edunova.co](mailto:admin@edunova.co)', '123456', 3),
(6, 'Lucía Torres', '[docente@edunova.co](mailto:docente@edunova.co)', '123456', 4),
(7, 'Sofía Vargas', '[estudiante@edunova.co](mailto:estudiante@edunova.co)', '123456', 5),
(8, 'Andrés Castillo', '[acudiente@edunova.co](mailto:acudiente@edunova.co)', '123456', 6),
(9, 'Paula Herrera', 'paulaherrrera@sidino.com.co', '123456', 4),
(10, 'Tomás Ríos', '[est2@edunova.co](mailto:est2@edunova.co)', '123456', 5),
(11, 'Leonard Jahkio Parra Ballesteros', 'nosvemos71@gmail.com', '123456', 1),
(12, 'Carol Dayana Trujillo Levasa', 'nanot20@gmail.com', '123456', 2),
(13, 'Rafael Acero Narvaes', 'buenos1@gmail.com', '123456', 3),
(14, 'Brayan Stiven Parra Fierro', 'buenas2@gmail.com', '123456', 4),
(15, 'Kevin Izasa', 'buenas3@gmail.com', '123456', 5),
(16, 'El calvo de OLMES', 'buenas4@gmail.com', '123456', 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_atributo`
--

CREATE TABLE `usuario_atributo` (
  `id_usuario` int(11) NOT NULL,
  `id_atributo` int(11) NOT NULL,
  `valor` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `acudiente_estudiante`
--
ALTER TABLE `acudiente_estudiante`
  ADD PRIMARY KEY (`id_acudiente`,`id_estudiante`),
  ADD KEY `fk_acud_estudiante` (`id_estudiante`);

--
-- Indices de la tabla `asignacion_academica`
--
ALTER TABLE `asignacion_academica`
  ADD PRIMARY KEY (`id_asignacion`),
  ADD KEY `fk_asig_docente` (`id_docente`),
  ADD KEY `fk_asig_materia` (`id_materia`),
  ADD KEY `fk_asig_periodo` (`id_periodo`),
  ADD KEY `fk_asig_salon` (`id_salon`),
  ADD KEY `fk_asig_horario` (`id_horario`),
  ADD KEY `fk_asig_curso` (`id_curso`);

-- Reglas de unicidad para los catálogos académicos.
ALTER TABLE `materia`
  ADD UNIQUE KEY `uq_materia_nombre` (`nombre`);

ALTER TABLE `salon`
  ADD UNIQUE KEY `uq_salon_nombre_ubicacion` (`nombre`, `ubicacion`);

ALTER TABLE `horario`
  ADD UNIQUE KEY `uq_horario_horas` (`hora_inicio`, `hora_fin`);

ALTER TABLE `matricula`
  ADD UNIQUE KEY `uq_matricula_estudiante_asignacion` (`id_estudiante`, `id_asignacion`);

--
-- Indices de la tabla `atributo`
--
ALTER TABLE `atributo`
  ADD PRIMARY KEY (`id_atributo`);

--
-- Indices de la tabla `boletin`
--
ALTER TABLE `boletin`
  ADD PRIMARY KEY (`id_boletin`),
  ADD KEY `fk_boletin_estudiante` (`id_estudiante`),
  ADD KEY `fk_boletin_periodo` (`id_periodo`);

--
-- Indices de la tabla `boletin_detalle`
--
ALTER TABLE `boletin_detalle`
  ADD PRIMARY KEY (`id_detalle`),
  ADD KEY `fk_boletindet_boletin` (`id_boletin`),
  ADD KEY `fk_boletindet_nota` (`id_nota`);

--
-- Indices de la tabla `citacion`
--
ALTER TABLE `citacion`
  ADD PRIMARY KEY (`id_citacion`),
  ADD KEY `fk_cit_estudiante` (`id_estudiante`),
  ADD KEY `fk_cit_asignacion` (`id_asignacion`);

--
-- Indices de la tabla `contenido`
--
ALTER TABLE `contenido`
  ADD PRIMARY KEY (`id_contenido`),
  ADD KEY `fk_contenido_asignacion` (`id_asignacion`);

--
-- Indices de la tabla `curso`
--
ALTER TABLE `curso`
  ADD PRIMARY KEY (`id_curso`);

--
-- Indices de la tabla `historial_accion`
--
ALTER TABLE `historial_accion`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `fk_histaccion_usuario` (`id_usuario`);

--
-- Indices de la tabla `historial_chatbot`
--
ALTER TABLE `historial_chatbot`
  ADD PRIMARY KEY (`id_chat`),
  ADD KEY `fk_chatbot_usuario` (`id_usuario`);

--
-- Indices de la tabla `horario`
--
ALTER TABLE `horario`
  ADD PRIMARY KEY (`id_horario`);

--
-- Indices de la tabla `materia`
--
ALTER TABLE `materia`
  ADD PRIMARY KEY (`id_materia`);

--
-- Indices de la tabla `matricula`
--
ALTER TABLE `matricula`
  ADD PRIMARY KEY (`id_matricula`),
  ADD KEY `fk_matricula_estudiante` (`id_estudiante`),
  ADD KEY `fk_matricula_asignacion` (`id_asignacion`);

--
-- Indices de la tabla `nota`
--
ALTER TABLE `nota`
  ADD PRIMARY KEY (`id_nota`),
  ADD KEY `fk_nota_matricula` (`id_matricula`);

--
-- Indices de la tabla `observador`
--
ALTER TABLE `observador`
  ADD PRIMARY KEY (`id_observacion`),
  ADD KEY `fk_obs_estudiante` (`id_estudiante`),
  ADD KEY `fk_obs_asignacion` (`id_asignacion`);

--
-- Indices de la tabla `periodo_academico`
--
ALTER TABLE `periodo_academico`
  ADD PRIMARY KEY (`id_periodo`);

--
-- Indices de la tabla `permiso`
--
ALTER TABLE `permiso`
  ADD PRIMARY KEY (`id_permiso`);

--
-- Indices de la tabla `rol`
--
ALTER TABLE `rol`
  ADD PRIMARY KEY (`id_rol`);

--
-- Indices de la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  ADD PRIMARY KEY (`id_rol`,`id_permiso`),
  ADD KEY `fk_rolpermiso_permiso` (`id_permiso`);

--
-- Indices de la tabla `salon`
--
ALTER TABLE `salon`
  ADD PRIMARY KEY (`id_salon`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`),
  ADD KEY `fk_usuario_rol` (`id_rol`);

--
-- Indices de la tabla `usuario_atributo`
--
ALTER TABLE `usuario_atributo`
  ADD PRIMARY KEY (`id_usuario`,`id_atributo`),
  ADD KEY `fk_usuarioatrib_atributo` (`id_atributo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asignacion_academica`
--
ALTER TABLE `asignacion_academica`
  MODIFY `id_asignacion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `atributo`
--
ALTER TABLE `atributo`
  MODIFY `id_atributo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `boletin`
--
ALTER TABLE `boletin`
  MODIFY `id_boletin` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `boletin_detalle`
--
ALTER TABLE `boletin_detalle`
  MODIFY `id_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `citacion`
--
ALTER TABLE `citacion`
  MODIFY `id_citacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `contenido`
--
ALTER TABLE `contenido`
  MODIFY `id_contenido` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `curso`
--
ALTER TABLE `curso`
  MODIFY `id_curso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `historial_accion`
--
ALTER TABLE `historial_accion`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de la tabla `historial_chatbot`
--
ALTER TABLE `historial_chatbot`
  MODIFY `id_chat` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `horario`
--
ALTER TABLE `horario`
  MODIFY `id_horario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `materia`
--
ALTER TABLE `materia`
  MODIFY `id_materia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `matricula`
--
ALTER TABLE `matricula`
  MODIFY `id_matricula` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `nota`
--
ALTER TABLE `nota`
  MODIFY `id_nota` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `observador`
--
ALTER TABLE `observador`
  MODIFY `id_observacion` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `periodo_academico`
--
ALTER TABLE `periodo_academico`
  MODIFY `id_periodo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `permiso`
--
ALTER TABLE `permiso`
  MODIFY `id_permiso` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rol`
--
ALTER TABLE `rol`
  MODIFY `id_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `salon`
--
ALTER TABLE `salon`
  MODIFY `id_salon` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `acudiente_estudiante`
--
ALTER TABLE `acudiente_estudiante`
  ADD CONSTRAINT `fk_acud_acudiente` FOREIGN KEY (`id_acudiente`) REFERENCES `usuario` (`id_usuario`),
  ADD CONSTRAINT `fk_acud_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `asignacion_academica`
--
ALTER TABLE `asignacion_academica`
  ADD CONSTRAINT `fk_asig_curso` FOREIGN KEY (`id_curso`) REFERENCES `curso` (`id_curso`),
  ADD CONSTRAINT `fk_asig_docente` FOREIGN KEY (`id_docente`) REFERENCES `usuario` (`id_usuario`),
  ADD CONSTRAINT `fk_asig_horario` FOREIGN KEY (`id_horario`) REFERENCES `horario` (`id_horario`),
  ADD CONSTRAINT `fk_asig_materia` FOREIGN KEY (`id_materia`) REFERENCES `materia` (`id_materia`),
  ADD CONSTRAINT `fk_asig_periodo` FOREIGN KEY (`id_periodo`) REFERENCES `periodo_academico` (`id_periodo`),
  ADD CONSTRAINT `fk_asig_salon` FOREIGN KEY (`id_salon`) REFERENCES `salon` (`id_salon`);

--
-- Filtros para la tabla `boletin`
--
ALTER TABLE `boletin`
  ADD CONSTRAINT `fk_boletin_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `usuario` (`id_usuario`),
  ADD CONSTRAINT `fk_boletin_periodo` FOREIGN KEY (`id_periodo`) REFERENCES `periodo_academico` (`id_periodo`);

--
-- Filtros para la tabla `boletin_detalle`
--
ALTER TABLE `boletin_detalle`
  ADD CONSTRAINT `fk_boletindet_boletin` FOREIGN KEY (`id_boletin`) REFERENCES `boletin` (`id_boletin`),
  ADD CONSTRAINT `fk_boletindet_nota` FOREIGN KEY (`id_nota`) REFERENCES `nota` (`id_nota`);

--
-- Filtros para la tabla `citacion`
--
ALTER TABLE `citacion`
  ADD CONSTRAINT `fk_cit_asignacion` FOREIGN KEY (`id_asignacion`) REFERENCES `asignacion_academica` (`id_asignacion`),
  ADD CONSTRAINT `fk_cit_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `contenido`
--
ALTER TABLE `contenido`
  ADD CONSTRAINT `fk_contenido_asignacion` FOREIGN KEY (`id_asignacion`) REFERENCES `asignacion_academica` (`id_asignacion`);

--
-- Filtros para la tabla `historial_accion`
--
ALTER TABLE `historial_accion`
  ADD CONSTRAINT `fk_histaccion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `historial_chatbot`
--
ALTER TABLE `historial_chatbot`
  ADD CONSTRAINT `fk_chatbot_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `matricula`
--
ALTER TABLE `matricula`
  ADD CONSTRAINT `fk_matricula_asignacion` FOREIGN KEY (`id_asignacion`) REFERENCES `asignacion_academica` (`id_asignacion`),
  ADD CONSTRAINT `fk_matricula_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `nota`
--
ALTER TABLE `nota`
  ADD CONSTRAINT `fk_nota_matricula` FOREIGN KEY (`id_matricula`) REFERENCES `matricula` (`id_matricula`);

--
-- Filtros para la tabla `observador`
--
ALTER TABLE `observador`
  ADD CONSTRAINT `fk_obs_asignacion` FOREIGN KEY (`id_asignacion`) REFERENCES `asignacion_academica` (`id_asignacion`),
  ADD CONSTRAINT `fk_obs_estudiante` FOREIGN KEY (`id_estudiante`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  ADD CONSTRAINT `fk_rolpermiso_permiso` FOREIGN KEY (`id_permiso`) REFERENCES `permiso` (`id_permiso`),
  ADD CONSTRAINT `fk_rolpermiso_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`);

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol` (`id_rol`);

--
-- Filtros para la tabla `usuario_atributo`
--
ALTER TABLE `usuario_atributo`
  ADD CONSTRAINT `fk_usuarioatrib_atributo` FOREIGN KEY (`id_atributo`) REFERENCES `atributo` (`id_atributo`),
  ADD CONSTRAINT `fk_usuarioatrib_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
