<?php
$pageTitle = 'Promedia - Notas';
$currentPage = 'grades';
require __DIR__ . '/includes/header.php';
?>

<section class="hero hero-section">
    <p class="section-tag">Notas</p>
    <h1>Subir calificacion</h1>
    <p class="subtitle">Busca estudiante y materia desde el mismo campo y registra la nota en pocos pasos.</p>
</section>

<section class="panel page-panel">
    <form id="gradeForm" class="form form-compact two-column-form">
        <label>
            Estudiante
            <input type="search" id="gradeStudentInput" placeholder="Escribi nombre, curso o DNI" list="studentsList" required>
        </label>
        <label>
            Materia
            <input type="search" id="gradeSubjectInput" placeholder="Escribi materia, año o abreviatura" list="subjectsList" required>
        </label>
        <datalist id="studentsList"></datalist>
        <input type="hidden" name="student_id" id="gradeStudentId">
        <datalist id="subjectsList"></datalist>
        <input type="hidden" name="subject_id" id="gradeSubjectId">
        <label>
            Periodo
            <select name="term" required>
                <option value="" disabled selected>Seleccionar...</option>
                <option value="1° Informe">1° Informe</option>
                <option value="2° Informe">2° Informe</option>
                <option value="1° Cuatrimestre">1° Cuatrimestre</option>
                <option value="2° Cuatrimestre">2° Cuatrimestre</option>
            </select>
        </label>
        <label>
            Nota (1 a 10)
            <input type="number" name="score" required min="1" max="10" step="0.01">
        </label>
        <label>
            Asistencia (%)
            <input type="number" name="attendance" required min="0" max="100" step="0.01" placeholder="Ej: 85">
        </label>
        <label class="form-span-full form-field--narrow">
            Fecha
            <input type="date" name="date">
        </label>
        <button type="submit" class="form-span-full">Guardar calificacion</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>