<?php
require_once __DIR__ . '/includes/auth.php';
authRequireLogin(['profesor', 'alumno']);

$pageTitle = 'Promedia - Analisis';
$currentPage = 'analysis';
require __DIR__ . '/includes/header.php';

$auth = authUser();
$isStudent = isset($auth['role']) && $auth['role'] === 'alumno';
?>

<section class="hero hero-section">
    <p class="section-tag">Analisis</p>
    <h1>Seguimiento academico</h1>
    <p class="subtitle">
        <?php if ($isStudent): ?>
            Visualiza tu promedio general, materias aprobadas y situacion academica.
        <?php else: ?>
            Busca un estudiante para ver su promedio general, materias aprobadas y situacion academica.
        <?php endif; ?>
    </p>
</section>

<section class="panel page-panel">
    <div class="table-header">
        <h2>Analisis academico</h2>
        <?php if (!$isStudent): ?>
            <button id="resetBtn" class="danger">Limpiar datos de demo</button>
        <?php endif; ?>
    </div>
    <?php if (!$isStudent): ?>
        <label class="search-block">
            Buscar alumno para analizar
            <input type="search" id="analysisStudentSearch" placeholder="Escribi nombre, curso o DNI">
        </label>
    <?php endif; ?>
    <div id="reportsContainer" class="cards">
        <?php if ($isStudent): ?>
            <p>Cargando tu analisis academico...</p>
        <?php else: ?>
            <p>Escribi un alumno para ver su analisis academico.</p>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>