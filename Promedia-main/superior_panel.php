<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/lib/mysql_storage.php';
require_once __DIR__ . '/lib/mailer.php';

appSessionStart();
authRequireLogin(['superior']);

$user = authUser();
$superiorId = (int)($user['superior_id'] ?? 0);
$superiorName = (string)($user['superior_name'] ?? 'Superior');

$message = '';
$error = '';

try {
    $pdo = escuelaDbConnection();
} catch (Throwable $e) {
    $pdo = null;
    $error = 'No se pudo conectar a la base de datos.';
}

if ($pdo instanceof PDO && (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST')) {
    $action = trim((string)($_POST['action'] ?? ''));
    $teacherId = (int)($_POST['teacher_id'] ?? 0);
    $roleValue = (int)($_POST['role'] ?? 0);

    if ($action === 'update_role') {
        if ($teacherId <= 0 || !in_array($roleValue, [0, 1, 2], true)) {
            $error = 'Rol inválido.';
        } else {
            $updated = dbUpdateTeacherRole($pdo, $teacherId, $roleValue, $superiorId);
            if (!$updated) {
                $error = 'No se pudo actualizar el rol de la cuenta.';
            } elseif ($roleValue === 0 && !dbEnsureStudentForTeacher($pdo, $teacherId)) {
                $error = 'El rol cambió, pero no se pudo crear el registro del alumno.';
            } else {
                $message = 'Rol actualizado correctamente.';
            }
        }
    } elseif ($action === 'edit_account') {
        $dniValue = trim((string)($_POST['dni'] ?? ''));
        $emailValue = trim((string)($_POST['email'] ?? ''));
        $firstNameValue = trim((string)($_POST['first_name'] ?? ''));
        $lastNameValue = trim((string)($_POST['last_name'] ?? ''));
        $passwordValue = (string)($_POST['password'] ?? '');
        $courseValue = trim((string)($_POST['course'] ?? ''));
        $averageValue = trim((string)($_POST['general_average'] ?? ''));
        $approvedValue = trim((string)($_POST['approved_subjects'] ?? ''));
        $failedValue = trim((string)($_POST['failed_subjects'] ?? ''));
        $statusValue = trim((string)($_POST['academic_status'] ?? ''));
        $newRoleValue = (int)($_POST['role'] ?? -1);

        if ($teacherId <= 0 || $dniValue === '' || !ctype_digit($dniValue)) {
            $error = 'DNI inválido.';
        } elseif ($emailValue !== '' && !filter_var($emailValue, FILTER_VALIDATE_EMAIL)) {
            $error = 'Email inválido.';
        } elseif ($firstNameValue === '' || $lastNameValue === '') {
            $error = 'Completá nombre y apellido.';
        } elseif ($passwordValue !== '' && strlen($passwordValue) < 4) {
            $error = 'La clave debe tener al menos 4 caracteres.';
        } else {
            try {
                $editedAccount = dbFindTeacherById($pdo, $teacherId);
                $updated = dbUpdateTeacherAccount($pdo, $teacherId, $dniValue, $emailValue, $firstNameValue, $lastNameValue, $passwordValue);
                if ($newRoleValue !== -1 && !in_array($newRoleValue, [0, 1, 2], true)) {
                    throw new RuntimeException('Rol inválido.');
                }
                if ($newRoleValue !== -1 && $newRoleValue !== (int)($editedAccount['role'] ?? -1)) {
                    $updated = dbUpdateTeacherRole($pdo, $teacherId, $newRoleValue, $superiorId) || $updated;
                }
                $effectiveRole = $newRoleValue !== -1 ? $newRoleValue : (int)($editedAccount['role'] ?? -1);
                if ($effectiveRole === 0) {
                    if ($courseValue === '' || !is_numeric($averageValue) || (float)$averageValue < 0 || (float)$averageValue > 10 || !ctype_digit($approvedValue) || !ctype_digit($failedValue) || $statusValue === '') {
                        throw new RuntimeException('Datos académicos inválidos.');
                    }

                    dbEnsureStudentForTeacher($pdo, $teacherId);
                    $academicUpdated = dbUpdateStudentAcademicData(
                        $pdo,
                        $teacherId,
                        $courseValue,
                        (float)$averageValue,
                        (int)$approvedValue,
                        (int)$failedValue,
                        $statusValue
                    );
                    $updated = $updated || $academicUpdated;
                }
                $message = $updated ? 'Datos de usuario actualizados.' : 'No se pudieron actualizar los datos.';
            } catch (Throwable $e) {
                $error = 'No se pudieron actualizar los datos. Verificá el DNI y los datos académicos.';
            }
        }
    } elseif ($teacherId <= 0) {
        $error = 'Cuenta inválida.';
    } elseif (!in_array($roleValue, [0, 1, 2], true)) {
        $error = 'Rol inválido.';
    } elseif ($action !== 'approve' && $action !== 'reject') {
        $error = 'Acción inválida.';
    } else {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $updated = dbSetTeacherApproval($pdo, $teacherId, $status, $roleValue, $superiorId);

        if (!$updated) {
            $error = 'No se pudo actualizar la cuenta.';
        } else {
            if ($status === 'approved' && $roleValue === 0 && !dbEnsureStudentForTeacher($pdo, $teacherId)) {
                $error = 'La cuenta fue aprobada, pero no se pudo crear el registro del alumno.';
            }
            $teacher = dbFindTeacherById($pdo, $teacherId);
            if ($error === '' && $teacher !== null && $status === 'approved') {
                $email = trim((string)($teacher['email'] ?? ''));
                $fullName = trim((string)($teacher['last_name'] ?? '') . ' ' . (string)($teacher['first_name'] ?? ''));
                $sent = sendAccountApprovalEmail($email, $fullName, $roleValue);

                if ($sent) {
                    $message = 'Cuenta aprobada y correo enviado.';
                } else {
                    $message = 'Cuenta aprobada, pero no se pudo enviar el correo.';
                }
            } elseif ($status === 'rejected') {
                $message = 'Cuenta rechazada correctamente.';
            }
        }
    }
}

$accounts = [];
if ($pdo instanceof PDO) {
    $accounts = dbGetTeacherAccounts($pdo);
}

$pendingAccounts = array_values(array_filter(
    $accounts,
    static fn(array $account): bool => strtolower((string)($account['approval_status'] ?? '')) === 'pending'
));

$approvedAccounts = array_values(array_filter(
    $accounts,
    static fn(array $account): bool => strtolower((string)($account['approval_status'] ?? '')) === 'approved'
));

$currentTab = strtolower(trim((string)($_GET['tab'] ?? 'pending')));
if (!in_array($currentTab, ['pending', 'approved'], true)) {
    $currentTab = 'pending';
}

function statusLabel(string $status): string
{
    return match (strtolower($status)) {
        'approved' => 'Aprobada',
        'rejected' => 'Rechazada',
        default => 'Pendiente',
    };
}

function roleLabel(int $role): string
{
    return match ($role) {
        1 => 'Profesor',
        2 => 'Administrador',
        default => 'Alumno',
    };
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promedia - Panel Superior</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        body {
            background: linear-gradient(130deg, #f6f1ff 0%, #f1ecff 48%, #ede7ff 100%);
        }

        .hero-section .section-tag {
            background: rgba(91, 33, 182, 0.14);
            color: #5b21b6;
        }

        .hero-section h1 {
            color: #4c1d95;
        }

        .hero-section .subtitle {
            color: #6d28d9;
        }

        .page-panel {
            border: 1px solid #d8dee9;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            background: #ffffff;
        }

        .superior-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .superior-toolbar .button-link {
            background: #1d4ed8;
            color: #ffffff;
        }

        .superior-toolbar .button-link.button-link--ghost {
            background: transparent;
            color: #1d4ed8;
            border: 1px solid #93c5fd;
        }

        .superior-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0.8rem;
        }

        .superior-table th,
        .superior-table td {
            border-bottom: 1px solid #e2e8f0;
            padding: 0.8rem;
            text-align: left;
            vertical-align: top;
            font-size: 0.92rem;
        }

        .superior-table th {
            color: #475569;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .superior-table tbody tr {
            background: #f8fafc;
            box-shadow: 0 5px 16px rgba(15, 23, 42, 0.06);
        }

        .superior-table tbody tr td:first-child {
            border-radius: 10px 0 0 10px;
        }

        .superior-table tbody tr td:last-child {
            border-radius: 0 10px 10px 0;
        }

        .status-pill {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.8rem;
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-pill--approved { background: #dcfce7; color: #166534; }
        .status-pill--rejected { background: #fee2e2; color: #b91c1c; }
        .status-pill--pending { background: #fef3c7; color: #92400e; }

        .decision-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
            padding: 0.65rem;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            background: #eff6ff;
        }

        .decision-form select,
        .decision-form button {
            font-size: 0.82rem;
        }

        .decision-form button {
            padding: 0.4rem 0.6rem;
        }

        .approved-panel {
            margin-top: 1.25rem;
            padding-top: 1rem;
            border-top: 1px dashed rgba(109, 40, 217, 0.35);
        }

        .tabs-nav {
            display: flex;
            gap: 1rem;
            border-bottom: 1px solid rgba(109, 40, 217, 0.24);
            margin: 1rem 0;
            overflow-x: auto;
        }

        .tabs-nav a {
            display: inline-block;
            text-decoration: none;
            color: #7e22ce;
            padding: 0.5rem 0.15rem 0.65rem;
            border-bottom: 3px solid transparent;
            font-weight: 600;
            white-space: nowrap;
        }

        .tabs-nav a.is-active {
            color: #6d28d9;
            border-bottom-color: #6d28d9;
        }

        .tab-panel {
            margin-top: 0.35rem;
        }

        .approved-panel h2 {
            margin: 0 0 0.35rem;
            color: #5b21b6;
            font-size: 1.1rem;
        }

        .approved-panel p {
            margin: 0 0 0.8rem;
            color: #6d28d9;
            font-size: 0.92rem;
        }

        .approved-form {
            display: flex;
            gap: 0.6rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .approved-form select,
        .approved-form button {
            font-size: 0.9rem;
        }

        .approved-form button {
            padding: 0.45rem 0.7rem;
        }

        .account-edit-form {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.4rem;
            margin-top: 0.5rem;
            padding: 0.7rem;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
        }

        .account-edit-form::before {
            content: 'Editar datos';
            grid-column: 1 / -1;
            color: #0f172a;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .account-edit-form input,
        .account-edit-form select {
            min-width: 0;
            padding: 0.4rem;
            font-size: 0.8rem;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }

        .account-edit-form button {
            padding: 0.4rem 0.6rem;
            font-size: 0.82rem;
            grid-column: 1 / -1;
            background: #0f766e;
            color: #ffffff;
            border: 0;
            border-radius: 6px;
            cursor: pointer;
        }

        .account-edit-form button:hover {
            background: #115e59;
        }

        .role-edit-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.45rem;
            margin-bottom: 0.6rem;
            padding: 0.7rem;
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            background: #eff6ff;
        }

        .role-edit-form label {
            grid-column: 1 / -1;
            color: #1e3a8a;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .role-edit-form select,
        .role-edit-form button {
            min-width: 0;
            padding: 0.45rem;
            border-radius: 6px;
            font-size: 0.82rem;
        }

        .role-edit-form select {
            border: 1px solid #93c5fd;
            background: #ffffff;
        }

        .role-edit-form button {
            border: 0;
            background: #1d4ed8;
            color: #ffffff;
            cursor: pointer;
        }

        .role-edit-form button:hover {
            background: #1e40af;
        }

        @media (max-width: 800px) {
            .superior-table thead {
                display: none;
            }

            .superior-table,
            .superior-table tbody,
            .superior-table tr,
            .superior-table td {
                display: block;
                width: 100%;
            }

            .superior-table tbody tr {
                margin-bottom: 1rem;
                padding: 0.45rem;
            }

            .superior-table tbody tr td,
            .superior-table tbody tr td:first-child,
            .superior-table tbody tr td:last-child {
                border-radius: 0;
                border-bottom: 0;
                padding: 0.5rem;
            }

            .superior-table tbody tr td::before {
                display: block;
                margin-bottom: 0.2rem;
                color: #64748b;
                content: attr(data-label);
                font-size: 0.7rem;
                font-weight: 700;
                text-transform: uppercase;
            }

            .account-edit-form {
                grid-template-columns: 1fr;
            }

            .account-edit-form::before,
            .account-edit-form button {
                grid-column: auto;
            }

            .role-edit-form {
                grid-template-columns: 1fr;
            }

            .role-edit-form label {
                grid-column: auto;
            }
        }
    </style>
</head>
<body>
    <div class="bg-shape bg-shape-a"></div>
    <div class="bg-shape bg-shape-b"></div>

    <main class="container">
        <section class="hero hero-section">
            <p class="section-tag">Control superior</p>
            <h1>Panel de autorizaciones</h1>
            <p class="subtitle">Revisá cuentas registradas, decidí acceso y asigná rol antes de habilitar ingreso.</p>
        </section>

        <section class="panel page-panel">
            <div class="superior-toolbar">
                <div>
                    <strong><?= htmlspecialchars($superiorName, ENT_QUOTES, 'UTF-8') ?></strong>
                    <p style="margin:0.25rem 0 0;">DNI <?= htmlspecialchars((string)($user['dni'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <div class="hero-actions">
                    <a class="button-link button-link--ghost" href="estudiantes.php">Cargar estudiantes</a>
                    <a class="button-link button-link--ghost" href="materias.php">Cargar materias</a>
                    <a class="button-link button-link--ghost" href="logout.php">Salir</a>
                </div>
            </div>

            <?php if ($error !== ''): ?>
                <p class="login-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <?php if ($message !== ''): ?>
                <p class="login-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>

            <nav class="tabs-nav" aria-label="Estados de cuenta">
                <a href="superior_panel.php?tab=pending" class="<?= $currentTab === 'pending' ? 'is-active' : '' ?>">
                    Pendientes (<?= count($pendingAccounts) ?>)
                </a>
                <a href="superior_panel.php?tab=approved" class="<?= $currentTab === 'approved' ? 'is-active' : '' ?>">
                    Aprobados (<?= count($approvedAccounts) ?>)
                </a>
            </nav>

            <?php if ($currentTab === 'pending'): ?>
                <section class="tab-panel" aria-label="Cuentas pendientes">
                    <div style="overflow:auto;">
                        <table class="superior-table">
                            <thead>
                                <tr>
                                    <th>DNI</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Alta</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pendingAccounts)): ?>
                                    <tr>
                                        <td colspan="5">No hay cuentas pendientes.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($pendingAccounts as $account): ?>
                                    <?php
                                        $currentRole = (int)($account['role'] ?? 0);
                                        $fullName = trim((string)($account['last_name'] ?? '') . ' ' . (string)($account['first_name'] ?? ''));
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)($account['dni'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($account['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($account['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td>
                                            <form method="post" class="decision-form">
                                                <input type="hidden" name="teacher_id" value="<?= (int)($account['id'] ?? 0) ?>">
                                                <select name="role">
                                                    <option value="1" <?= $currentRole === 1 ? 'selected' : '' ?>>Profesor</option>
                                                    <option value="0" <?= $currentRole === 0 ? 'selected' : '' ?>>Alumno</option>
                                                    <option value="2" <?= $currentRole === 2 ? 'selected' : '' ?>>Administrador</option>
                                                </select>
                                                <button type="submit" name="action" value="approve">Aprobar</button>
                                                <button type="submit" name="action" value="reject" class="button-link--ghost">Rechazar</button>
                                            </form>
                                            <form method="post" class="account-edit-form">
                                                <input type="hidden" name="action" value="edit_account">
                                                <input type="hidden" name="teacher_id" value="<?= (int)($account['id'] ?? 0) ?>">
                                                <input type="text" name="dni" inputmode="numeric" value="<?= htmlspecialchars((string)($account['dni'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="DNI" required>
                                                <input type="text" name="first_name" value="<?= htmlspecialchars((string)($account['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nombre" required>
                                                <input type="text" name="last_name" value="<?= htmlspecialchars((string)($account['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Apellido" required>
                                                <input type="email" name="email" value="<?= htmlspecialchars((string)($account['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Email">
                                                <input type="password" name="password" placeholder="Nueva clave">
                                                <select name="role" aria-label="Rol">
                                                    <option value="0" <?= $currentRole === 0 ? 'selected' : '' ?>>Alumno</option>
                                                    <option value="1" <?= $currentRole === 1 ? 'selected' : '' ?>>Profesor</option>
                                                    <option value="2" <?= $currentRole === 2 ? 'selected' : '' ?>>Administrador</option>
                                                </select>
                                                <input type="text" name="course" value="<?= htmlspecialchars((string)($account['course'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Curso">
                                                <input type="number" name="general_average" min="0" max="10" step="0.01" value="<?= htmlspecialchars((string)($account['general_average'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Promedio general">
                                                <input type="number" name="approved_subjects" min="0" step="1" value="<?= htmlspecialchars((string)($account['approved_subjects'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Aprobadas">
                                                <input type="number" name="failed_subjects" min="0" step="1" value="<?= htmlspecialchars((string)($account['failed_subjects'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Desaprobadas">
                                                <input type="text" name="academic_status" value="<?= htmlspecialchars((string)($account['academic_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Situación">
                                                <button type="submit">Editar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php else: ?>
                <section class="tab-panel" aria-label="Cuentas aprobadas">
                    <div style="overflow:auto;">
                        <table class="superior-table">
                            <thead>
                                <tr>
                                    <th>DNI</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Rol</th>
                                    <th>Revisión</th>
                                    <th>Cambiar rol</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($approvedAccounts)): ?>
                                    <tr>
                                        <td colspan="6">No hay cuentas aprobadas.</td>
                                    </tr>
                                <?php endif; ?>

                                <?php foreach ($approvedAccounts as $approved): ?>
                                    <?php
                                        $currentRole = (int)($approved['role'] ?? 0);
                                        $approvedName = trim((string)($approved['last_name'] ?? '') . ' ' . (string)($approved['first_name'] ?? ''));
                                        $approvedBy = trim((string)($approved['approved_by_name'] ?? ''));
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)($approved['dni'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($approvedName, ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($approved['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><span class="status-pill status-pill--approved"><?= htmlspecialchars(roleLabel($currentRole), ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td>
                                            <?= htmlspecialchars((string)($approved['approved_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?><br>
                                            <small><?= htmlspecialchars($approvedBy !== '' ? $approvedBy : 'Sin nombre', ENT_QUOTES, 'UTF-8') ?></small>
                                        </td>
                                        <td>
                                            <form method="post" class="role-edit-form">
                                                <input type="hidden" name="action" value="update_role">
                                                <input type="hidden" name="teacher_id" value="<?= (int)($approved['id'] ?? 0) ?>">
                                                <label for="role-<?= (int)($approved['id'] ?? 0) ?>">Rol actual</label>
                                                <select id="role-<?= (int)($approved['id'] ?? 0) ?>" name="role" required>
                                                    <option value="0" <?= $currentRole === 0 ? 'selected' : '' ?>>Alumno</option>
                                                    <option value="1" <?= $currentRole === 1 ? 'selected' : '' ?>>Profesor</option>
                                                    <option value="2" <?= $currentRole === 2 ? 'selected' : '' ?>>Administrador</option>
                                                </select>
                                                <button type="submit">Guardar rol</button>
                                            </form>
                                            <form method="post" class="account-edit-form">
                                                <input type="hidden" name="action" value="edit_account">
                                                <input type="hidden" name="teacher_id" value="<?= (int)($approved['id'] ?? 0) ?>">
                                                <input type="text" name="dni" inputmode="numeric" value="<?= htmlspecialchars((string)($approved['dni'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="DNI" required>
                                                <input type="text" name="first_name" value="<?= htmlspecialchars((string)($approved['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Nombre" required>
                                                <input type="text" name="last_name" value="<?= htmlspecialchars((string)($approved['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Apellido" required>
                                                <input type="email" name="email" value="<?= htmlspecialchars((string)($approved['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Email">
                                                <input type="password" name="password" placeholder="Nueva clave">
                                                <select name="role" aria-label="Rol">
                                                    <option value="0" <?= $currentRole === 0 ? 'selected' : '' ?>>Alumno</option>
                                                    <option value="1" <?= $currentRole === 1 ? 'selected' : '' ?>>Profesor</option>
                                                    <option value="2" <?= $currentRole === 2 ? 'selected' : '' ?>>Administrador</option>
                                                </select>
                                                <input type="text" name="course" value="<?= htmlspecialchars((string)($approved['course'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Curso">
                                                <input type="number" name="general_average" min="0" max="10" step="0.01" value="<?= htmlspecialchars((string)($approved['general_average'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Promedio general">
                                                <input type="number" name="approved_subjects" min="0" step="1" value="<?= htmlspecialchars((string)($approved['approved_subjects'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Aprobadas">
                                                <input type="number" name="failed_subjects" min="0" step="1" value="<?= htmlspecialchars((string)($approved['failed_subjects'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Desaprobadas">
                                                <input type="text" name="academic_status" value="<?= htmlspecialchars((string)($approved['academic_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Situación">
                                                <button type="submit">Editar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
