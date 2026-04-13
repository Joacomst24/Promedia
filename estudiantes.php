<?php
$pageTitle = 'Promedia - Estudiantes';
$currentPage = 'students';
require __DIR__ . '/includes/header.php';
?>

<section class="hero hero-section">
    <p class="section-tag">Estudiantes</p>
    <h1>Registrar estudiante</h1>
    <p class="subtitle">Carga todos los datos del estudiante en una sola vista, sin desplegables intermedios.</p>
</section>

<section class="panel page-panel">
    <form id="studentForm" class="form form-compact two-column-form">
        <label>
            Apellido
            <input type="text" name="last_name" required placeholder="Ej: Perez">
        </label>
        <label>
            Nombre
            <input type="text" name="first_name" required placeholder="Ej: Ana">
        </label>
        <label>
            DNI
            <input type="text" name="dni" placeholder="Ej: 40123456">
        </label>
        <label>
            Curso / Año
            <input type="text" name="course" required placeholder="Ej: 3 B">
        </label>
        <label>
            Nombre completo
            <input type="text" name="name" placeholder="Ej: Ana Perez">
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
            Telefono
            <input type="text" name="phone" placeholder="Ej: 2257-123456">
        </label>

        <button type="submit" class="form-span-full">Guardar estudiante</button>
    </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>