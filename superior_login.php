<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/lib/mysql_storage.php';

appSessionStart();

if (authHasRole('superior')) {
    header('Location: superior_panel.php');
    exit;
}

$error = '';
$dniValue = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $dniValue = trim((string)($_POST['dni'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    try {
        $pdo = escuelaDbConnection();
    } catch (Throwable $e) {
        $error = 'No se pudo conectar a la base de datos.';
        $pdo = null;
    }

    if ($error === '') {
        if ($dniValue === '' || !ctype_digit($dniValue)) {
            $error = 'Ingresá un DNI válido.';
        } elseif ($password === '') {
            $error = 'Ingresá tu clave.';
        } elseif (!$pdo instanceof PDO) {
            $error = 'No se pudo validar el acceso.';
        } else {
            $superior = dbFindSuperiorByDni($pdo, $dniValue);

            if ($superior === null) {
                $error = 'Superior no encontrado.';
            } elseif (!dbValidateSuperiorPassword($pdo, (int)$superior['id'], $password)) {
                $error = 'Clave inválida.';
            } else {
                authLoginSuperior($superior);
                header('Location: superior_panel.php');
                exit;
            }
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promedia - Ingreso Superior</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="bg-shape bg-shape-a"></div>
    <div class="bg-shape bg-shape-b"></div>

    <main class="container login-container">
        <section class="hero hero-section">
            <p class="section-tag">Control superior</p>
            <h1>Ingreso de supervisor</h1>
            <p class="subtitle">Desde este acceso podés autorizar cuentas registradas y asignar rol.</p>
        </section>

        <section class="panel page-panel login-panel">
            <?php if ($error !== ''): ?>
                <p class="login-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <form method="post" class="form form-compact">
                <label>
                    DNI superior
                    <input type="text" name="dni" inputmode="numeric" pattern="[0-9]+" placeholder="Ej: 10000000" value="<?= htmlspecialchars($dniValue, ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label>
                    Clave
                    <input type="password" name="password" placeholder="Tu clave">
                </label>

                <button type="submit">Ingresar como superior</button>
            </form>
            <p class="login-footer-link"><a href="login.php">Volver al ingreso general</a></p>
        </section>
    </main>
</body>
</html>
