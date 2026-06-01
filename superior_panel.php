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

    if ($action === 'update_approved_role') {
        if ($teacherId <= 0) {
            $error = 'Seleccioná una cuenta aprobada.';
        } elseif (!in_array($roleValue, [0, 1], true)) {
            $error = 'Rol inválido.';
        } else {
            $updated = dbUpdateApprovedTeacherRole($pdo, $teacherId, $roleValue, $superiorId);

            if (!$updated) {
                $error = 'No se pudo cambiar el rol. Verificá que la cuenta esté aprobada.';
            } else {
                $message = 'Rol actualizado para la cuenta aprobada.';
            }
        }
    } elseif ($teacherId <= 0) {
        $error = 'Cuenta inválida.';
    } elseif (!in_array($roleValue, [0, 1], true)) {
        $error = 'Rol inválido.';
    } elseif ($action !== 'approve' && $action !== 'reject') {
        $error = 'Acción inválida.';
    } else {
        $status = $action === 'approve' ? 'approved' : 'rejected';
        $updated = dbSetTeacherApproval($pdo, $teacherId, $status, $roleValue, $superiorId);

        if (!$updated) {
            $error = 'No se pudo actualizar la cuenta.';
        } else {
            $teacher = dbFindTeacherById($pdo, $teacherId);
            if ($teacher !== null && $status === 'approved') {
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
    return $role === 1 ? 'Profesor' : 'Alumno';
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
            border-color: rgba(124, 58, 237, 0.2);
            box-shadow: 0 18px 34px rgba(76, 29, 149, 0.1);
        }

        .superior-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .superior-toolbar .button-link {
            background: #6d28d9;
            color: #ffffff;
        }

        .superior-toolbar .button-link.button-link--ghost {
            background: transparent;
            color: #6d28d9;
            border: 1px solid rgba(109, 40, 217, 0.45);
        }

        .superior-table {
            width: 100%;
            border-collapse: collapse;
        }

        .superior-table th,
        .superior-table td {
            border-bottom: 1px solid rgba(109, 40, 217, 0.16);
            padding: 0.65rem;
            text-align: left;
            vertical-align: top;
            font-size: 0.92rem;
        }

        .superior-table th {
            color: #5b21b6;
        }

        .status-pill {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.8rem;
            background: rgba(124, 58, 237, 0.12);
            color: #6d28d9;
        }

        .status-pill--approved { background: rgba(76, 29, 149, 0.18); color: #4c1d95; }
        .status-pill--rejected { background: rgba(190, 24, 93, 0.14); color: #9d174d; }
        .status-pill--pending { background: rgba(109, 40, 217, 0.16); color: #6d28d9; }

        .decision-form {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
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
                <a class="button-link button-link--ghost" href="logout.php">Salir</a>
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
                                                </select>
                                                <button type="submit" name="action" value="approve">Aprobar</button>
                                                <button type="submit" name="action" value="reject" class="button-link--ghost">Rechazar</button>
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
                                            <form method="post" class="approved-form">
                                                <input type="hidden" name="action" value="update_approved_role">
                                                <input type="hidden" name="teacher_id" value="<?= (int)($approved['id'] ?? 0) ?>">
                                                <select name="role" required>
                                                    <option value="1" <?= $currentRole === 1 ? 'selected' : '' ?>>Profesor</option>
                                                    <option value="0" <?= $currentRole === 0 ? 'selected' : '' ?>>Alumno</option>
                                                </select>
                                                <button type="submit">Guardar</button>
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
