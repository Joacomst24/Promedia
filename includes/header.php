<?php
$pageTitle = $pageTitle ?? 'Promedia';
$currentPage = $currentPage ?? 'home';

$navigation = [
    'home' => ['label' => 'Inicio', 'href' => 'index.php'],
    'students' => ['label' => 'Estudiantes', 'href' => 'estudiantes.php'],
    'subjects' => ['label' => 'Materias', 'href' => 'materias.php'],
    'grades' => ['label' => 'Notas', 'href' => 'notas.php'],
    'analysis' => ['label' => 'Analisis', 'href' => 'analisis.php'],
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
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
        </div>
    </header>

    <main class="container">