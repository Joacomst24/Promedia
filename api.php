<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/lib/mysql_storage.php';
require_once __DIR__ . '/lib/academic.php';

function respond(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function inputJson(): array
{
    $raw = file_get_contents('php://input');

    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $data = json_decode($raw, true);

    return is_array($data) ? $data : [];
}

function normalizeText(mixed $value): string
{
    return trim((string)$value);
}

$action = $_GET['action'] ?? 'dashboard';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $pdo = escuelaDbConnection();
} catch (Throwable $e) {
    respond(['ok' => false, 'error' => 'No se pudo conectar a la base de datos escuela.'], 500);
}

if (dbShouldSyncLegacy($pdo)) {
    dbSyncLegacyData($pdo);
}

$students = dbGetStudents($pdo);
$subjects = dbGetSubjects($pdo);
$grades = dbGetGrades($pdo);

if ($action === 'dashboard' && $method === 'GET') {
    respond(['ok' => true, 'data' => buildDashboard($students, $subjects, $grades)]);
}

if ($action === 'add_student' && $method === 'POST') {
    $payload = inputJson();
    $name = normalizeText($payload['name'] ?? '');
    $firstName = normalizeText($payload['first_name'] ?? '');
    $lastName = normalizeText($payload['last_name'] ?? '');
    $course = normalizeText($payload['course'] ?? '');

    if ($name === '' && ($firstName === '' || $lastName === '')) {
        respond(['ok' => false, 'error' => 'Completá nombre y apellido, o nombre completo.'], 422);
    }

    if ($course === '') {
        respond(['ok' => false, 'error' => 'El curso es obligatorio.'], 422);
    }

    try {
        $newStudent = dbAddStudent($pdo, $payload);
    } catch (Throwable $e) {
        if (str_contains(strtolower($e->getMessage()), 'ux_promedia_students_legacy_dni')) {
            respond(['ok' => false, 'error' => 'Ya existe un estudiante con ese DNI.'], 422);
        }

        respond(['ok' => false, 'error' => 'No se pudo guardar el estudiante.'], 500);
    }

    respond(['ok' => true, 'data' => $newStudent], 201);
}

if ($action === 'add_subject' && $method === 'POST') {
    $payload = inputJson();
    $name = normalizeText($payload['name'] ?? '');
    $year = normalizeText($payload['year'] ?? '');

    if ($name === '' || $year === '') {
        respond(['ok' => false, 'error' => 'Materia y año son obligatorios.'], 422);
    }

    $newSubject = dbAddSubject($pdo, $payload);

    respond(['ok' => true, 'data' => $newSubject], 201);
}

if ($action === 'add_grade' && $method === 'POST') {
    $payload = inputJson();

    $studentId = (int)($payload['student_id'] ?? 0);
    $subjectId = (int)($payload['subject_id'] ?? 0);
    $term = normalizeText($payload['term'] ?? '');
    $score = (float)($payload['score'] ?? -1);
    $gradeDate = normalizeText($payload['date'] ?? '');

    $studentExists = dbStudentExists($pdo, $studentId);
    $subjectExists = dbSubjectExists($pdo, $subjectId);

    if ($studentId <= 0 || !$studentExists) {
        respond(['ok' => false, 'error' => 'Estudiante inválido.'], 422);
    }

    if ($subjectId <= 0 || !$subjectExists) {
        respond(['ok' => false, 'error' => 'Materia inválida.'], 422);
    }

    if ($term === '') {
        respond(['ok' => false, 'error' => 'El período es obligatorio.'], 422);
    }

    if ($score < 1 || $score > 10) {
        respond(['ok' => false, 'error' => 'La nota debe estar entre 1 y 10.'], 422);
    }

    if ($gradeDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $gradeDate)) {
        respond(['ok' => false, 'error' => 'Fecha inválida. Usá formato YYYY-MM-DD.'], 422);
    }

    $newGrade = dbAddGrade($pdo, $studentId, $subjectId, $term, $score, $gradeDate === '' ? null : $gradeDate);

    respond(['ok' => true, 'data' => $newGrade], 201);
}

if ($action === 'reset_demo' && $method === 'POST') {
    dbResetDemo($pdo);

    respond(['ok' => true]);
}

if ($action === 'sync_legacy' && $method === 'POST') {
    $result = dbSyncLegacyData($pdo);

    respond(['ok' => true, 'data' => $result]);
}

respond(['ok' => false, 'error' => 'Ruta no encontrada.'], 404);
