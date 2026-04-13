# Promedia - Sistema Académico (PHP + JS + HTML + CSS + MySQL)

Sistema base para registrar estudiantes, materias y calificaciones, con cálculo automático de promedios y situación académica.
La persistencia se realiza en MySQL (base `escuela`) mediante tablas propias de la app.

## Funcionalidades incluidas

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

## Sincronización manual de datos legacy

Además de la sincronización inicial automática, podés forzar una sincronización manual:

```text
POST /api.php?action=sync_legacy
```
