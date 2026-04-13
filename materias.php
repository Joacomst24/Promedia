<?php
$pageTitle = 'Promedia - Materias';
$currentPage = 'subjects';
require __DIR__ . '/includes/header.php';
?>

<section class="hero hero-section">
    <p class="section-tag">Materias</p>
    <h1>Registrar materia</h1>
    <p class="subtitle">Define la materia con todos sus datos visibles en la misma pantalla.</p>
</section>

<section class="panel page-panel">
    <form id="subjectForm" class="form form-compact two-column-form">
        <label>
            Materia
            <input type="text" name="name" required placeholder="Ej: Matematica">
        </label>
        <label>
            Año
            <input type="text" name="year" required placeholder="Ej: 3">
        </label>
        <label>
            Abreviatura
            <input type="text" name="abbreviation" placeholder="Ej: MAT">
        </label>
        <label>
            Resumen
            <input type="text" name="summary" placeholder="Ej: Matematica anual">
        </label>
        <label>
            Departamento
            <input type="text" name="department" placeholder="Ej: Ciencias Exactas">
        </label>
        <label>
            Docente
            <input type="text" name="teacher" placeholder="Ej: Prof. Lopez">
        </label>
        <label>
            Estado
            <select name="status">
                <option value="">Sin definir</option>
                <option value="Activa">Activa</option>
                <option value="Inactiva">Inactiva</option>
            </select>
        </label>

        <button type="submit" class="form-span-full">Guardar materia</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>