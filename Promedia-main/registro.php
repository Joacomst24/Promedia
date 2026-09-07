<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/lib/mysql_storage.php';

appSessionStart();

if (authIsLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error   = '';
$dniValue = '';
$emailValue = '';
$firstNameValue = '';
$lastNameValue = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $dniValue  = trim((string)($_POST['dni'] ?? ''));
    $emailValue = trim((string)($_POST['email'] ?? ''));
    $firstNameValue = trim((string)($_POST['first_name'] ?? ''));
    $lastNameValue = trim((string)($_POST['last_name'] ?? ''));
    $password  = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');

    try {
        $pdo = escuelaDbConnection();
    } catch (Throwable $e) {
        $error = 'No se pudo conectar a la base de datos.';
        $pdo = null;
    }

    if ($error === '') {
        if ($dniValue === '' || !ctype_digit($dniValue)) {
            $error = 'Ingresá un DNI válido.';
        } elseif ($emailValue === '' || !filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
            $error = 'Ingresá un email válido.';
        } elseif ($firstNameValue === '' || $lastNameValue === '') {
            $error = 'Completá nombre y apellido.';
        } elseif (strlen($password) < 4) {
            $error = 'La clave debe tener al menos 4 caracteres.';
        } elseif ($password !== $password2) {
            $error = 'Las claves no coinciden.';
        } elseif (!$pdo instanceof PDO) {
            $error = 'No se pudo validar el DNI.';
        } else {
            dbRegisterTeacher($pdo, $dniValue, $emailValue, $firstNameValue, $lastNameValue, $password);

            $student = dbFindStudentByDni($pdo, $dniValue);
            if ($student !== null) {
                dbSetStudentPassword($pdo, (int)$student['id'], $password);
            }

            header('Location: login.php?pending=1');
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promedia - Registro</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="bg-shape bg-shape-a"></div>
    <div class="bg-shape bg-shape-b"></div>

    <main class="container login-container">
        <section class="hero hero-section">
            <p class="section-tag">Registro</p>
            <h1>Registro por DNI</h1>
            <p class="subtitle">Registrá tu cuenta. Un superior debe autorizarla antes de ingresar.</p>
        </section>

        <section class="panel page-panel login-panel">
            <?php if ($error !== ''): ?>
                <p class="login-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <form method="post" class="form form-compact">
                <label>
                    DNI
                    <input type="text" name="dni" inputmode="numeric" pattern="[0-9]+" required
                           placeholder="Ej: 40123456"
                           value="<?= htmlspecialchars($dniValue, ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label>
                    Email
                    <input type="email" name="email" required placeholder="Ej: usuario@gmail.com"
                           value="<?= htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label>
                    Nombre
                    <input type="text" name="first_name" placeholder="Ej: Ana"
                           value="<?= htmlspecialchars($firstNameValue, ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label>
                    Apellido
                    <input type="text" name="last_name" placeholder="Ej: Perez"
                           value="<?= htmlspecialchars($lastNameValue, ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label>
                    Nueva clave
                    <input type="password" name="password" required minlength="4" placeholder="Mínimo 4 caracteres">
                </label>
                <label>
                    Repetir clave
                    <input type="password" name="password2" required minlength="4" placeholder="Repetí la clave">
                </label>
                <button type="submit">Registrar clave</button>
            </form>
            <p class="login-footer-link">¿Ya tenés clave? <a href="login.php">Ingresar</a></p>
        </section>
    </main>
</body>
</html>
