<?php
require_once __DIR__ . '/includes/auth.php';
authRequireLogin(['profesor', 'alumno']);

$auth = authUser();
$isStudent = isset($auth['role']) && $auth['role'] === 'alumno';

$pageTitle = 'Promedia - Inicio';
$currentPage = 'home';
require __DIR__ . '/includes/header.php';
?>

<section class="hero hero-home">
    <p class="section-tag">Sistema academico</p>
    <h1>
        <?php if ($isStudent): ?>
            Promedia te muestra tus calificaciones y tu situacion academica.
        <?php else: ?>
            Promedia organiza estudiantes, materias, notas y analisis en un flujo simple.
        <?php endif; ?>
    </h1>
    <p class="subtitle">
        <?php if ($isStudent): ?>
            Ingresaste como alumno. Solo podes consultar tus notas, promedios y estado academico.
        <?php else: ?>
            Usa el menu superior para cargar informacion por seccion y revisar la situacion academica de cada estudiante sin mezclar tareas en una sola pantalla.
        <?php endif; ?>
    </p>
    <div class="hero-actions">
        <?php if ($isStudent): ?>
            <a class="button-link" href="analisis.php">Ver mis notas</a>
        <?php else: ?>
            <a class="button-link" href="notas.php">Cargar notas</a>
            <a class="button-link button-link--ghost" href="analisis.php">Ver analisis</a>
        <?php endif; ?>
    </div>
</section>

<?php if (!$isStudent): ?>
    <section class="page-grid">
        <article class="panel info-card">
            <p class="section-tag">Notas</p>
            <h2>Cargar calificaciones</h2>
            <p>Busca estudiante y materia, sube la nota y deja registrada la calificacion del periodo.</p>
            <a class="text-link" href="notas.php">Ir a notas</a>
        </article>

        <article class="panel info-card">
            <p class="section-tag">Analisis</p>
            <h2>Rendimiento academico</h2>
            <p>Revisa promedios, estado y evolucion de cada estudiante en una pantalla dedicada.</p>
            <a class="text-link" href="analisis.php">Ver analisis</a>
        </article>
    </section>
<?php endif; ?>

<div class="rules-strip">
    <p class="rules-strip__label">Criterios de aprobacion</p>
    <div id="rulesSummary" class="rules-strip__stats">Cargando...</div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
