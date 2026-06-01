# Promedia - Sistema Académico (PHP + JS + HTML + CSS + MySQL)

Sistema base para registrar estudiantes, materias y calificaciones, con cálculo automático de promedios y situación académica.
La persistencia se realiza en MySQL (base `escuela`) mediante tablas propias de la app.

## Funcionalidades incluidas

- Login por DNI y clave con rol definido en base de datos
- Rol configurable por cuenta (`profesor` o `alumno`) desde MySQL/phpMyAdmin en `promedia_teachers.role`
- Registro con email y aprobación previa por superior antes del primer acceso
- Sitio adicional para superior (`superior_login.php`) para aprobar/rechazar cuentas y asignar rol
- Acceso de alumno por DNI (solo puede ver sus propias notas)
- Registro de profesores con DNI, nombre, apellido y contraseña
- Registrar estudiantes
- Registrar materias
- Cargar calificaciones
- Calcular promedio por materia
- Calcular promedio general por estudiante
- Determinar situación académica
  - Promociona
  - Debe intensificar contenidos
  - Debe recursar materias
- Importación inicial automática de estudiantes y materias desde tablas legacy (`alumnos`, `materias`)
- Integración de notas históricas (`nota` + `notastrimestrales`) en el dashboard

## Reglas académicas actuales

Estas reglas están definidas en `lib/academic.php` y se pueden ajustar fácilmente:

- Nota mínima para aprobar materia: `7`
- Promoción directa: `0` materias desaprobadas
- Intensificación: hasta `2` materias desaprobadas
- Recursa: más de `2` materias desaprobadas
- Asistencia mínima por materia: `80%` (si no alcanza, la materia queda en intensificación)

## Ejecutar el proyecto

Requisitos:

- PHP 8 o superior
- MySQL/MariaDB activo con base `escuela`

Si necesitás importar la base, usá el dump `data/escuela.sql`.

Desde la carpeta del proyecto:

```bash
php -S localhost:8000
```

Abrir en navegador:

```text
http://localhost:8000/index.php
```

## Acceso al sistema

- Ruta de ingreso: `login.php`
- Ruta de superior: `superior_login.php`
- Ingreso único: DNI + clave (sin selector de perfil).
- La app toma el rol de la cuenta desde la base de datos.
- Si existe cuenta en `promedia_teachers`, se usa su campo `role` (`profesor` o `alumno`).
- Si no existe cuenta docente, el acceso se valida como alumno por DNI en `promedia_students`.
- Cuentas registradas desde `registro.php` quedan en estado pendiente hasta revisión del superior.
- Al aprobar una cuenta, se intenta enviar un email de aviso de habilitación.

## Superior inicial por defecto

Si no existe ningún superior cargado, la app crea uno automáticamente al iniciar:

- DNI: `10000000`
- Clave: `admin1234`

Podés cambiar estos valores por variables de entorno:

- `SUPERIOR_DNI`
- `SUPERIOR_NAME`
- `SUPERIOR_EMAIL`
- `SUPERIOR_PASS`
- Alumno: ingresa con su DNI + clave.
  - Para alumnos nuevos cargados desde la app: la clave se define al crear el estudiante (si no se completa, queda `1234`).
  - Para alumnos heredados de la tabla `alumnos`: se usa la clave legacy (`alumnos.clave`) y se migra a hash local en el primer acceso exitoso.
- El rol final se define por aprobación del superior (`1` profesor, `0` alumno).

La portada ahora funciona como presentacion del sistema y acceso a las secciones principales:

- `index.php`: inicio y explicacion de como se aprueba
- `estudiantes.php`: alta de estudiantes
- `materias.php`: alta de materias
- `notas.php`: carga de calificaciones
- `analisis.php`: consulta de rendimiento academico

## Estructura

- `index.php`: portada del sistema
- `estudiantes.php`: formulario de estudiantes
- `materias.php`: formulario de materias
- `notas.php`: formulario de calificaciones
- `analisis.php`: pantalla de analisis
- `api.php`: API de operaciones
- `includes/header.php`: layout compartido superior
- `includes/footer.php`: layout compartido inferior
- `lib/mysql_storage.php`: conexión y persistencia MySQL
- `lib/academic.php`: reglas y análisis académico
- `assets/js/app.js`: lógica del frontend
- `assets/css/styles.css`: estilos
- `data/escuela.sql`: dump de la base `escuela`

## Variables de conexión opcionales

Podés configurar la conexión por variables de entorno:

- `DB_HOST` (default: `127.0.0.1`)
- `DB_PORT` (default: `3306`)
- `DB_NAME` (default: `escuela`)
- `DB_USER` (default: `root`)
- `DB_PASS` (default: vacío)
- `APP_MAIL_FROM` (default: `no-reply@promedia.local`)
- `APP_MAIL_FROM_NAME` (default: `Promedia`)
- `SMTP_HOST` (ej: `sandbox.smtp.mailtrap.io` o `smtp.gmail.com`)
- `SMTP_PORT` (ej: `2525`, `587`)
- `SMTP_ENCRYPTION` (`tls`, `ssl`, `none`)
- `SMTP_USER` (usuario SMTP)
- `SMTP_PASS` (clave SMTP)
- `SMTP_HELO` (opcional, default: `localhost`)
- `SMTP_TIMEOUT` (opcional, segundos, default: `15`)

## Sincronización manual de datos legacy

Además de la sincronización inicial automática, podés forzar una sincronización manual:

```text
POST /api.php?action=sync_legacy
```
