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
    <link rel="stylesheet" href="assets/css/admin.css">
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
                <div style="display:flex; gap:0.6rem; align-items:center; flex-wrap:wrap;">
                    <a class="button-link" href="estudiantes.php">Cargar estudiantes</a>
                    <a class="button-link" href="materias.php">Cargar materias</a>
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
