<?php

declare(strict_types=1);

const PROFESSOR_DNI_MAX = 43000000;

function appSessionStart(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function authUser(): ?array
{
    appSessionStart();

    $auth = $_SESSION['auth_user'] ?? null;

    return is_array($auth) ? $auth : null;
}

function authIsLoggedIn(): bool
{
    return authUser() !== null;
}

function authHasRole(string $role): bool
{
    $user = authUser();

    return isset($user['role']) && (string)$user['role'] === $role;
}

function authDniIsProfessor(string $dni): bool
{
    if ($dni === '' || !ctype_digit($dni)) {
        return false;
    }

    return (int)$dni < PROFESSOR_DNI_MAX;
}

function authLoginProfessor(array $teacher): void
{
    appSessionStart();

    $fullName = trim((string)($teacher['last_name'] ?? '') . ' ' . (string)($teacher['first_name'] ?? ''));

    $_SESSION['auth_user'] = [
        'role' => 'profesor',
        'teacher_id' => (int)($teacher['id'] ?? 0),
        'teacher_name' => $fullName !== '' ? $fullName : 'Profesor',
        'dni' => (string)($teacher['dni'] ?? ''),
    ];
}

function authLoginStudent(array $student): void
{
    appSessionStart();

    $_SESSION['auth_user'] = [
        'role' => 'alumno',
        'student_id' => (int)($student['id'] ?? 0),
        'student_name' => (string)($student['name'] ?? ''),
        'dni' => (string)($student['dni'] ?? ''),
    ];
}

function authLoginSuperior(array $superior): void
{
    appSessionStart();

    $_SESSION['auth_user'] = [
        'role' => 'superior',
        'superior_id' => (int)($superior['id'] ?? 0),
        'superior_name' => (string)($superior['name'] ?? 'Superior'),
        'dni' => (string)($superior['dni'] ?? ''),
    ];
}

function authLogout(): void
{
    appSessionStart();
    unset($_SESSION['auth_user']);
}

function authRequireLogin(?array $allowedRoles = null): void
{
    $user = authUser();

    if ($user === null) {
        header('Location: login.php');
        exit;
    }

    if (is_array($allowedRoles) && !in_array((string)($user['role'] ?? ''), $allowedRoles, true)) {
        $role = (string)($user['role'] ?? '');
        $target = 'index.php';

        if ($role === 'superior') {
            $target = 'superior_panel.php';
        } elseif ($role === 'alumno') {
            $target = 'analisis.php';
        }

        $currentPath = basename(parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
        if ($currentPath === $target) {
            $target = 'login.php';
        }

        header('Location: ' . $target);
        exit;
    }
}
