<?php
$pageTitle = 'Promedia - Inicio';
$currentPage = 'home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero hero-home">
    <p class="section-tag">Sistema academico</p>
    <h1>Promedia organiza estudiantes, materias, notas y analisis en un flujo simple.</h1>
    <p class="subtitle">Usa el menu superior para cargar informacion por seccion y revisar la situacion academica de cada estudiante sin mezclar tareas en una sola pantalla.</p>
    <div class="hero-actions">
        <a class="button-link" href="estudiantes.php">Registrar estudiantes</a>
        <a class="button-link button-link--ghost" href="notas.php">Cargar notas</a>
    </div>
</section>

<section class="page-grid">
    <article class="panel info-card">
        <p class="section-tag">Paso 1</p>
        <h2>Estudiantes</h2>
        <p>Carga los datos principales del alumno y agrega informacion opcional solo cuando la necesites.</p>
        <a class="text-link" href="estudiantes.php">Ir a estudiantes</a>
    </article>

    <article class="panel info-card">
        <p class="section-tag">Paso 2</p>
        <h2>Materias</h2>
        <p>Registra cada materia por año para dejar lista la estructura sobre la que luego cargas calificaciones.</p>
        <a class="text-link" href="materias.php">Ir a materias</a>
    </article>

    <article class="panel info-card">
        <p class="section-tag">Paso 3</p>
        <h2>Notas y analisis</h2>
        <p>Busca estudiante y materia, sube la nota y revisa el rendimiento academico en una pantalla dedicada.</p>
        <div class="info-card__links">
            <a class="text-link" href="notas.php">Cargar notas</a>
            <a class="text-link" href="analisis.php">Ver analisis</a>
        </div>
    </article>
</section>

<div class="rules-strip">
    <p class="rules-strip__label">Criterios de aprobacion</p>
    <div id="rulesSummary" class="rules-strip__stats">Cargando...</div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
