Para iniciar el programa sidino-java.

1. Abrir la carpeta en el cmd (Esto se puede hacer en la parte superir del gestor de archivos, donde esta la ruta ahi se escribre cmd)
2. entrar a la carpeta sidino-java (cd sidino-java)
3. Escribir "java -jar target/SidinoApp-1.0-jar-with-dependencies.jar"
4. Listoo

Para iniciar el spring-boot reportes.

1.Abrir la carpeta reportes en el cmd (cd reportes)
2.Escribe mvn spring-boot:run y ejecutalo
3.Listo

SIDINO(sistema digital de notas)

DESCRIPCION DEL PROYECTO
SIDINO es un entorno digital académico que busca fusionar todo lo necesario para que los usuarios 
(docentes, administrativos, padres de familia y estudiantes) interactúen en un ecosistema unificado, eficiente y 
seguro. La plataforma elimina la dispersión de la información escolar al integrar en un solo aplicativo 
el control de calificaciones, el seguimiento disciplinario, la comunicación institucional y el análisis de 
datos de rendimiento.A través de herramientas de automatización como la sincronización con hojas de cálculo y 
la incorporación de un asistente virtual inteligente, SIDINO reduce drásticamente la carga 
administrativa de los educadores, permitiéndoles concentrarse en el desarrollo pedagógico y el acompañamiento personalizado del alumnado.

OBJETIVO GENERAL

Desarrollar un Sistema Integral de Gestión de Actividades Académicas que incorpore
herramientas de asistencia virtual, análisis estadístico y módulos de
seguimiento disciplinario para optimizar los procesos administrativos y pedagógicos
de la institución.

OBJETIVO ESPECIFICO
• Identificar las necesidades de los distintos perfiles (docentes, administrativos,
padres y alumnos) para definir el sistema de roles y los niveles de acceso.

• Definir los parámetros de integración con Microsoft Excel y los estándares de
seguridad para el escudo de privacidad de los datos sensibles.

• Modelar la interfaz del asistente virtual y los tableros de control (dashboards) que
generarán las gráficas y boletines automáticos.

• Diseñar la arquitectura de la base de datos que albergará los contenidos
educativos, el observador virtual y los registros de citaciones.

• Programar los módulos funcionales, incluyendo el motor de generación de
formularios de citaciones y la lógica de sincronización con hojas de cálculo.

• Implementar el asistente virtual mediante algoritmos de procesamiento de
lenguaje natural para la atención de consultas frecuentes.

PARTE TECNICA

1. Explicación del Diagrama de Despliegue (Infraestructura y Red)

Para iniciar, hablemos de la infraestructura física y cómo garantizamos que SIDINO sea un sistema rápido, seguro y disponible en todo momento. La arquitectura está diseñada en capas:
El Cliente y la DMZ: Los usuarios (ya sea desde un computador o un celular) ingresan a través del navegador. La primera barrera con la que choca el tráfico de internet es la Zona Perimetral (DMZ), donde un 
balanceador de carga se encarga de recibir todas las peticiones de manera segura mediante HTTPS. Esto funciona como un escudo para proteger los servidores internos.
El Clúster de Aplicación: El balanceador distribuye inteligentemente el tráfico hacia un clúster interno compuesto por múltiples instancias de la aplicación SIDINO (Instancia 1 e Instancia 2). Si una instancia 
sufre una caída o recibe demasiada carga, la otra sigue respondiendo, logrando alta disponibilidad.
Capa de Caché y Base de Datos: Para evitar que los usuarios pierdan su sesión al cambiar de servidor, utilizamos un servidor Redis dedicado exclusivamente a la gestión de sesiones en memoria. Finalmente, todas las 
peticiones de datos se conectan con el Servidor de Base de Datos relacional para procesar la información de forma persistente.

link diagrama de despliegue:https://drive.google.com/file/d/1ecTU8XXq27sKac4CO-9Ywlim0R9cWTKr/view?usp=sharing

---

2. Explicación del Diagrama de Paquetes 

A nivel de diseño de software y organización del código, SIDINO no es un sistema monolítico desordenado, sino que aplica una arquitectura modular basada en paquetes independientes:
Seguridad y Usuarios: Contiene los módulos de control de roles, autenticación y un componente vital llamado Historial de Acciones, el cual registra la trazabilidad y los logs de auditoría cada vez que un usuario 
modifica datos en el sistema.
El Núcleo Académico: Representa el corazón de la plataforma. Está subdividido en la Gestión Curricular (cursos, materias y horarios), el Registro de Notas y Periodos, y el Observador Virtual enfocado en el control 
disciplinario y citaciones.
Módulos de Apoyo y Analítica: El sistema se complementa con paquetes especializados para la Gestión de Matrículas, la Comunicación y Soporte (que integra el ChatBot institucional), y el módulo de Reportes y 
Analítica para la exportación de boletines en PDF y estadísticas.

link diagrama de paquetes:https://drive.google.com/file/d/12K-jis6neE_sozFt6jzR_GrMmgjdhMgC/view?usp=sharing

---
3. Explicación del Diagrama de Componentes 

Si nos adentramos en el backend y en cómo interactúan los componentes lógicos (pensando en una implementación con Spring Boot), el sistema se organiza mediante servicios especializados y patrones de diseño:
UsuarioService: Funciona como el componente centralizador para validar la autenticación, los permisos y los registros de auditoría de los usuarios.
Gestion academica: Para evitar que el código se vuelva ingobernable por tantas conexiones cruzadas, implementamos una fachada que agrupa y simplifica los servicios de Periodos, Cursos, 
Materias, Salones y Horarios. Todo esto se consolida a través del Asignación Académica Service.
Servicios de Evaluación y Matrículas: Contamos con servicios dedicados que conectan la lógica de matrículas, notas, boletines y el observador, asegurando que cada componente cumpla con una única responsabilidad 
dentro del sistema.

link diagrama de componentes:https://drive.google.com/file/d/19QL5uaV0dXAEw7dFkv1tCn5jnokfeurn/view?usp=sharing

---

4. Explicación del Diagrama de Casos de Uso 

SIDINO es una plataforma multirol, lo que significa que las acciones están estrictamente limitadas según el perfil del usuario que inicie sesión:
Directivos (Rector y Coordinador): Tienen el nivel más alto de control. Pueden supervisar el rendimiento académico general, gestionar usuarios, auditar el historial de acciones, generar reportes masivos y 
administrar los casos disciplinarios en el observador.
Docentes: Su enfoque principal es el registro de calificaciones, la subida de evidencias, la consulta de sus horarios y asignaciones académicas, además de interactuar con el asistente virtual.
Estudiantes y Acudientes: Son perfiles orientados a la consulta y seguimiento. Permiten visualizar boletines, revisar notas detalladas, consultar el historial académico, recibir alertas y mantenerse informados 
sobre el rendimiento escolar.

link diagrama de casos de uso:https://drive.google.com/file/d/1Vdy0pc5rj2_TDO9O7COkU_m7Dyn9ddks/view?usp=sharing

---

5. Explicación del Diagrama de Clases y Modelo Entidad-Relación 

Finalmente, para la persistencia de los datos, diseñamos un modelo relacional que asegura la integridad de toda la información institucional:
Control de Identidad: La tabla usuario se vincula directamente con la tabla rol (que soporta los 6 perfiles definidos en el sistema: Rectoría, Coordinación, Administrativo, Docente, Estudiante y Acudiente). Además, 
la tabla historial_accion almacena de forma permanente todas las trazas de modificaciones realizadas.
Estructura Académica: Tenemos tablas maestras como curso, materia, salon, horario y periodo_academico. Todas ellas convergen en una tabla intermedia fundamental llamada asignacion_academica, que define exactamente 
qué docente imparte qué materia, en qué salón, horario, curso y periodo.
Proceso Evaluativo y Resultados: A partir de la asignación se generan las matriculas de los alumnos, las cuales almacenan sus respectivas notas. Estos registros se consolidan mediante las tablas de boletin y 
boletin_detalle para generar los informes finales que consultan los estudiantes y acudientes.

Link diagrama de clases:https://drive.google.com/file/d/1swu6Kec2w_S8CrwY4BBdu7zSItw9f6N1/view?usp=sharing
Link diagrama entidad-relacion:https://drive.google.com/file/d/1UIC3YN36vyZJr0dRywNZpWQuisPnfYm0/view?usp=sharing


link carpeta diagramas:https://drive.google.com/drive/folders/1A1zDm2MmCkZje5FMZ8CaErTeAwR4bfGN