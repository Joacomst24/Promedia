<?php
$pageTitle = 'Promedia - Analisis';
$currentPage = 'analysis';
require __DIR__ . '/includes/header.php';
?>

<section class="hero hero-section">
    <p class="section-tag">Analisis</p>
    <h1>Seguimiento academico</h1>
    <p class="subtitle">Busca un estudiante para ver su promedio general, materias aprobadas y situacion academica.</p>
</section>

<section class="panel page-panel">
    <div class="table-header">
        <h2>Analisis academico</h2>
        <button id="resetBtn" class="danger">Limpiar datos de demo</button>
    </div>
    <label class="search-block">
        Buscar alumno para analizar
        <input type="search" id="analysisStudentSearch" placeholder="Escribi nombre, curso o DNI">
    </label>
    <div id="reportsContainer" class="cards">
        <p>Escribi un alumno para ver su analisis academico.</p>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>