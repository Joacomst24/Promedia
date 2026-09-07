<?php
require_once __DIR__ . '/auth.php';

$pageTitle = $pageTitle ?? 'Promedia';
$currentPage = $currentPage ?? 'home';

$user = authUser();
$role = (string)($user['role'] ?? '');
$roleLabel = $role === 'profesor' ? 'Profesor' : ($role === 'superior' ? 'Superior' : 'Alumno');

$navigation = [
    'home' => ['label' => 'Inicio', 'href' => 'index.php'],
    'students' => ['label' => 'Estudiantes', 'href' => 'estudiantes.php'],
    'subjects' => ['label' => 'Materias', 'href' => 'materias.php'],
    'grades' => ['label' => 'Notas', 'href' => 'notas.php'],
    'analysis' => ['label' => 'Analisis', 'href' => 'analisis.php'],
];

if ($role === 'alumno') {
    $navigation = [
        'home' => ['label' => 'Inicio', 'href' => 'index.php'],
        'analysis' => ['label' => 'Mis notas', 'href' => 'analisis.php'],
    ];
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body data-role="<?= htmlspecialchars($role, ENT_QUOTES, 'UTF-8') ?>" data-student-id="<?= htmlspecialchars((string)($user['student_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <div class="bg-shape bg-shape-a"></div>
    <div class="bg-shape bg-shape-b"></div>

    <header class="site-header">
        <div class="site-header__inner">
            <a class="brand-link" href="index.php">
                <span class="eyebrow">Gestion escolar</span>
                <strong>Promedia</strong>
            </a>
            <nav class="quick-nav" aria-label="Secciones principales">
                <?php foreach ($navigation as $key => $item): ?>
                    <a href="<?= htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8') ?>"<?= $currentPage === $key ? ' class="is-active" aria-current="page"' : '' ?>><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></a>
                <?php endforeach; ?>
            </nav>
            <?php if ($user !== null): ?>
                <div class="session-chip">
                    <span><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <strong>
                        <?php if ($role === 'profesor'): ?>
                            <?= htmlspecialchars((string)($user['teacher_name'] ?? 'Profesor'), ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($user['dni'])): ?>
                                - DNI <?= htmlspecialchars((string)$user['dni'], ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        <?php elseif ($role === 'superior'): ?>
                            <?= htmlspecialchars((string)($user['superior_name'] ?? 'Superior'), ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($user['dni'])): ?>
                                - DNI <?= htmlspecialchars((string)$user['dni'], ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        <?php else: ?>
                            <?= htmlspecialchars((string)($user['student_name'] ?? 'Alumno'), ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($user['dni'])): ?>
                                - DNI <?= htmlspecialchars((string)$user['dni'], ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </strong>
                    <a href="logout.php">Salir</a>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <main class="container">