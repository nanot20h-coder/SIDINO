# SIDINO 🐙 — App de escritorio en Java (Swing)

Migración completa del proyecto PHP a una aplicación de escritorio 100% Java
(Swing + JDBC), usando la misma base de datos MySQL/MariaDB `sidino`.

## Cómo ejecutar

1. Asegúrate de tener MySQL/MariaDB corriendo localmente con la base `sidino`
   importada (el mismo `sidino.sql` que ya usabas).
2. Revisa `src/main/java/com/sidino/core/Conexion.java` si tu host/usuario/clave
   de la base de datos son distintos a `127.0.0.1 / root / (vacío)`.
3. Compila y ejecuta con Maven:

   ```
   mvn clean package
   java -jar target/SidinoApp-1.0-jar-with-dependencies.jar
   ```

   O ábrelo directamente en tu IDE (IntelliJ/Eclipse/NetBeans) como proyecto
   Maven y ejecuta `com.sidino.Main`.

## Qué se migró

- **Login** (`ui/LoginPanel.java`): mismo diseño visual (fondo con burbujas, título
  "SIDINO" degradado, campos redondeados), embebido en la única ventana de
  la app (`AppFrame`) — ya no abre otra ventana ni cierra nada al autenticar.
- **6 dashboards por rol**, cada uno con su propio menú lateral y módulos,
  calcados de los `.php` originales:
  - `RectorDashboard` → usuarios (crear/editar/eliminar), asignaciones, historial, reportes
  - `CoordinadorDashboard` → observador, citaciones, docentes, reportes
  - `AdministrativoDashboard` → estudiantes, boletines, historial
  - `DocenteDashboard` → mis clases, registrar notas (formulario real →
    INSERT en BD), observador (formulario real → INSERT en BD), contenido
  - `EstudianteDashboard` → notas, horario, boletines, materiales, observador
  - `AcudienteDashboard` → notas/observador/citaciones/boletines de sus hijos
- **Capa de datos** (`core/DB.java`): un helper genérico que imita el patrón
  `$pdo->query(...)->fetchAll()` / `->prepare(...)->execute([...])` de PHP,
  para poder portar el SQL casi tal cual (con `?` en vez de interpolar
  variables directamente en el string, cerrando de paso el hueco de
  inyección SQL que tenía el PHP original).
- **Sesión** (`core/Sesion.java`): reemplaza `$_SESSION` con variables
  estáticas, ya que ahora todo corre en un solo proceso de escritorio.
- **Personalización de apariencia** (`core/Preferencias.java` +
  `ui/PanelAjustes.java`): equivalente al panel "Personalizar" de
  `auth.php` (tema claro/oscuro + color de acento con swatches y selector
  personalizado), pero persistido en
  `{tu carpeta de usuario}/.sidino/preferencias.properties` en vez de
  `localStorage` (aquí no hay navegador). Se abre con el ícono ⚙ de la
  barra superior en cualquier dashboard, y al aplicar cambios reconstruye
  la vista para que tomen efecto en todos los componentes.
- **Gestión académica del Rector** (dentro de "Gestión Académica"): crear y
  eliminar materias, cursos, salones, horarios y periodos académicos, y
  asignar un docente a una materia/curso/salón/horario/periodo concretos
  (`asignacion_academica`). Antes solo se podían ver; ahora el Rector puede
  configurar todo el semestre desde la app, sin tocar la base de datos a
  mano. Las eliminaciones respetan las llaves foráneas del esquema original:
  si un registro está en uso por una asignación (o una asignación tiene
  estudiantes/notas), la app avisa en vez de fallar en seco.
- **Matrículas** (también dentro de "Gestión Académica"): matricula
  estudiantes en una clase (asignación académica) ya creada, y permite
  retirarlos. Sin esto, Docente y Estudiante no tenían ningún dato con el
  que trabajar — ahora el flujo completo Rector → clases → matrículas →
  Docente/Estudiante queda operativo de punta a punta.
- **Diálogos con estilo propio** (`ui/componentes/Dialogos.java`): sustituyen
  todos los `JOptionPane` (que se veían con el estilo por defecto del
  sistema operativo) por ventanas modales a juego con el resto de la app —
  con ícono, color según el tipo (info/éxito/error/confirmación) y botones
  redondeados.
- **Componentes visuales reutilizables** (`ui/componentes/`): `RTF`/`RPF`
  (campos redondeados de tu Login), `BotonRedondeado` (con degradado y
  sombra), y `Estilos.java` (paleta dinámica + fábricas de tarjetas con
  sombra, tablas zebra, badges tipo "pill", estados vacíos) — el
  equivalente Swing de tu `shared.css` + los helpers de `auth.php`.

## Qué quedó pendiente / se puede pedir a continuación

- El módulo de **boletín en PDF** (generar_nota.php/generar_observador.php
  eran solo puentes hacia docente.php, ya no se necesitan y no se migraron).
- Swing nunca se va a ver pixel-por-pixel igual que HTML/CSS —son
  tecnologías distintas—, pero ya tiene sombras suaves, degradados, tarjetas
  redondeadas, iconos y diálogos propios en vez del look plano por defecto.
- Si quieres, puedo añadir hash de contraseñas (BCrypt) al crear/editar
  usuarios — hoy se guardan igual que en tu base de datos actual (texto
  plano), para no romper los usuarios de prueba existentes.

## Estructura

```
pom.xml
src/main/java/com/sidino/
  Main.java
  core/
    Conexion.java   (conexión JDBC, igual que el original)
    DB.java         (helper de consultas genérico)
    Sesion.java     (sustituto de $_SESSION)
  ui/
    Login.java
    DashboardBase.java   (sidebar + topbar + módulos, común a los 6 roles)
    RectorDashboard.java
    CoordinadorDashboard.java
    AdministrativoDashboard.java
    DocenteDashboard.java
    EstudianteDashboard.java
    AcudienteDashboard.java
    componentes/
      RTF.java, RPF.java, BotonRedondeado.java, Estilos.java
```
