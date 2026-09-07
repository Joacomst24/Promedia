<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/lib/mysql_storage.php';

appSessionStart();

if (authIsLoggedIn()) {
    $loggedUser = authUser();
    $target = ((string)($loggedUser['role'] ?? '') === 'superior')
        ? 'superior_panel.php'
        : 'index.php';
    header('Location: ' . $target);
    exit;
}

$error = '';
$dniValue = '';
$passwordValue = '';
$infoMessage = isset($_GET['pending']) && (string)$_GET['pending'] === '1'
    ? 'Cuenta registrada. Esperá la autorización del superior para ingresar.'
    : '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $dniValue = trim((string)($_POST['dni'] ?? ''));
    $passwordValue = (string)($_POST['password'] ?? '');

    try {
        $pdo = escuelaDbConnection();
    } catch (Throwable $e) {
        $error = 'No se pudo conectar a la base de datos.';
        $pdo = null;
    }

    if ($error === '') {
        if ($dniValue === '' || !ctype_digit($dniValue)) {
            $error = 'Ingresá un DNI válido.';
        } elseif ($passwordValue === '') {
            $error = 'Ingresá tu clave.';
        } elseif (!$pdo instanceof PDO) {
            $error = 'No se pudo validar el acceso.';
        } else {
            $superior = dbFindSuperiorByDni($pdo, $dniValue);
            if ($superior !== null) {
                if (!dbValidateSuperiorPassword($pdo, (int)$superior['id'], $passwordValue)) {
                    $error = 'Clave inválida.';
                } else {
                    authLoginSuperior($superior);
                    header('Location: superior_panel.php');
                    exit;
                }
            }

            $teacher = dbFindTeacherByDni($pdo, $dniValue);

            if ($teacher !== null) {
                if (!dbValidateTeacherPassword($pdo, (int)$teacher['id'], $passwordValue)) {
                    $error = 'Clave inválida.';
                } else {
                    $approvalStatus = strtolower((string)($teacher['approval_status'] ?? 'pending'));

                    if ($approvalStatus === 'pending') {
                        $error = 'Tu cuenta todavía no fue autorizada por el superior.';
                    } elseif ($approvalStatus === 'rejected') {
                        $error = 'Tu cuenta fue rechazada. Contactá al superior para revisarla.';
                    } elseif ($approvalStatus !== 'approved') {
                        $error = 'Estado de cuenta inválido. Revisalo con el superior.';
                    }

                    if ($error === '') {
                        $teacherRole = isset($teacher['role']) ? (int)$teacher['role'] : -1;

                        if ($teacherRole === 1) {
                            authLoginProfessor($teacher);
                            header('Location: index.php');
                            exit;
                        }

                        if ($teacherRole === 2) {
                            authLoginAdministrator($teacher);
                            header('Location: superior_panel.php');
                            exit;
                        }

                        if ($teacherRole === 0) {
                            $studentByRole = dbFindStudentByDni($pdo, $dniValue);
                            if ($studentByRole === null) {
                                $error = 'La cuenta tiene rol alumno, pero no existe un alumno con ese DNI. Contactá al administrador.';
                            } else {
                                authLoginStudent($studentByRole);
                                header('Location: analisis.php');
                                exit;
                            }
                        } else {
                            $error = 'La cuenta aún no tiene un rol asignado por el administrador.';
                        }
                    }
                }
            }

            if ($error !== '') {
                // Ya se resolvió con mensaje de cuenta docente.
            } else {
            $student = dbFindStudentByDni($pdo, $dniValue);
            if ($student === null) {
                $error = 'No se encontró un alumno con ese DNI.';
            } elseif (!dbValidateStudentPassword($pdo, (int)$student['id'], $passwordValue)) {
                $error = 'Clave de alumno inválida.';
            } else {
                authLoginStudent($student);
                header('Location: analisis.php');
                exit;
            }
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
    <title>Promedia - Ingreso</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="bg-shape bg-shape-a"></div>
    <div class="bg-shape bg-shape-b"></div>

    <main class="container login-container">
        <section class="hero hero-section">
            <p class="section-tag">Acceso</p>
            <h1>Ingresar al sistema</h1>
            <p class="subtitle">Ingresá con DNI y clave. Primero necesitás autorización del superior.</p>
        </section>

        <section class="panel page-panel login-panel">
            <?php if ($error !== ''): ?>
                <p class="login-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <?php if ($infoMessage !== ''): ?>
                <p class="login-success"><?= htmlspecialchars($infoMessage, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <form method="post" class="form form-compact">
                <label>
                    DNI
                    <input type="text" name="dni" inputmode="numeric" pattern="[0-9]+" placeholder="Ej: 40123456" value="<?= htmlspecialchars($dniValue, ENT_QUOTES, 'UTF-8') ?>">
                </label>
                <label>
                    Clave
                    <input type="password" name="password" placeholder="Tu clave">
                </label>

                <button type="submit">Ingresar</button>
            </form>
            <p class="login-footer-link">¿Primera vez? <a href="registro.php">Registrá tu clave</a></p>
        </section>
    </main>
</body>
</html>
