<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Académico - Promedia</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="bg-shape bg-shape-a"></div>
    <div class="bg-shape bg-shape-b"></div>

    <main class="container">
        <header class="hero">
            <p class="eyebrow">Gestión escolar</p>
            <h1>Promedia</h1>
            <p class="subtitle">Registro y análisis automático de calificaciones para nivel secundario.</p>
        </header>

        <section class="panel rules-panel">
            <h2>Criterios académicos actuales</h2>
            <div id="rulesSummary" class="rules-summary">Cargando criterios...</div>
        </section>

        <section class="grid panels-forms">
            <article class="panel">
                <h2>Registrar estudiante</h2>
                <form id="studentForm" class="form">
                    <label>
                        DNI
                        <input type="text" name="dni" placeholder="Ej: 40123456">
                    </label>
                    <label>
                        Apellido
                        <input type="text" name="last_name" required placeholder="Ej: Pérez">
                    </label>
                    <label>
                        Nombre
                        <input type="text" name="first_name" required placeholder="Ej: Ana">
                    </label>
                    <label>
                        Nombre completo (opcional)
                        <input type="text" name="name" placeholder="Ej: Ana Pérez">
                    </label>
                    <label>
                        Curso / Año
                        <input type="text" name="course" required placeholder="Ej: 3° B">
                    </label>
                    <label>
                        Fecha de nacimiento
                        <input type="date" name="birth_date">
                    </label>
                    <label>
                        Sexo
                        <select name="sex">
                            <option value="">Sin especificar</option>
                            <option value="F">F</option>
                            <option value="M">M</option>
                            <option value="X">X</option>
                        </select>
                    </label>
                    <label>
                        Domicilio
                        <input type="text" name="address" placeholder="Ej: Calle 10 Nro. 1234">
                    </label>
                    <label>
                        Email
                        <input type="email" name="email" placeholder="ejemplo@correo.com">
                    </label>
                    <label>
                        Teléfono
                        <input type="text" name="phone" placeholder="Ej: 2257-123456">
                    </label>
                    <button type="submit">Guardar estudiante</button>
                </form>
            </article>

            <article class="panel">
                <h2>Registrar materia</h2>
                <form id="subjectForm" class="form">
                    <label>
                        Materia
                        <input type="text" name="name" required placeholder="Ej: Matemática">
                    </label>
                    <label>
                        Año
                        <input type="text" name="year" required placeholder="Ej: 3°">
                    </label>
                    <label>
                        Abreviatura
                        <input type="text" name="abbreviation" placeholder="Ej: MAT">
                    </label>
                    <label>
                        Resumen
                        <input type="text" name="summary" placeholder="Ej: Matemática anual">
                    </label>
                    <label>
                        Departamento
                        <input type="text" name="department" placeholder="Ej: Ciencias Exactas">
                    </label>
                    <label>
                        Docente
                        <input type="text" name="teacher" placeholder="Ej: Prof. López">
                    </label>
                    <label>
                        Estado
                        <select name="status">
                            <option value="">Sin definir</option>
                            <option value="Activa">Activa</option>
                            <option value="Inactiva">Inactiva</option>
                        </select>
                    </label>
                    <button type="submit">Guardar materia</button>
                </form>
            </article>
        </section>

        <section class="panel">
            <h2>Cargar calificación</h2>
            <form id="gradeForm" class="form grade-form">
                <label>
                    Buscar estudiante
                    <input type="search" id="studentSearch" placeholder="Escribí nombre, curso o DNI">
                </label>
                <label>
                    Estudiante
                    <select name="student_id" id="studentSelect" required></select>
                </label>
                <label>
                    Buscar materia
                    <input type="search" id="subjectSearch" placeholder="Escribí materia, año o abreviatura">
                </label>
                <label>
                    Materia
                    <select name="subject_id" id="subjectSelect" required></select>
                </label>
                <label>
                    Período
                    <input type="text" name="term" required placeholder="Ej: 1° trimestre">
                </label>
                <label>
                    Nota (1 a 10)
                    <input type="number" name="score" required min="1" max="10" step="0.01">
                </label>
                <label>
                    Fecha
                    <input type="date" name="date">
                </label>
                <button type="submit">Guardar calificación</button>
            </form>
        </section>

        <section class="panel">
            <div class="table-header">
                <h2>Análisis académico</h2>
                <button id="resetBtn" class="danger">Limpiar datos de demo</button>
            </div>
            <label>
                Buscar alumno para analizar
                <input type="search" id="analysisStudentSearch" placeholder="Escribí nombre, curso o DNI">
            </label>
            <div id="reportsContainer" class="cards">
                <p>Escribí un alumno para ver su análisis académico.</p>
            </div>
        </section>
    </main>

    <div id="toast" class="toast" aria-live="polite"></div>

    <script src="assets/js/app.js"></script>
</body>
</html>
