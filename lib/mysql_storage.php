<?php

declare(strict_types=1);

function escuelaDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $dbname = getenv('DB_NAME') ?: 'escuela';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS');

    if ($pass === false) {
        $pass = '';
    }

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbname);

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    ensurePromediaSchema($pdo);

    return $pdo;
}

function ensurePromediaSchema(PDO $pdo): void
{
    static $initialized = false;

    if ($initialized) {
        return;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS promedia_students (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            course VARCHAR(80) NOT NULL,
            legacy_dni INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS promedia_subjects (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(120) NOT NULL,
            year_label VARCHAR(40) NOT NULL,
            legacy_subject_id INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS promedia_grades (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            student_id INT UNSIGNED NOT NULL,
            subject_id INT UNSIGNED NOT NULL,
            term_label VARCHAR(40) NOT NULL,
            score DECIMAL(4,2) NOT NULL,
            attendance_percent DECIMAL(5,2) NOT NULL DEFAULT 100,
            grade_date DATE NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_promedia_grades_student (student_id),
            INDEX idx_promedia_grades_subject (subject_id),
            CONSTRAINT fk_promedia_grades_student FOREIGN KEY (student_id)
                REFERENCES promedia_students(id) ON DELETE CASCADE,
            CONSTRAINT fk_promedia_grades_subject FOREIGN KEY (subject_id)
                REFERENCES promedia_subjects(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    if (!dbColumnExists($pdo, 'promedia_students', 'legacy_dni')) {
        $pdo->exec('ALTER TABLE promedia_students ADD COLUMN legacy_dni INT NULL');
    }

    if (!dbColumnExists($pdo, 'promedia_students', 'first_name')) {
        $pdo->exec('ALTER TABLE promedia_students ADD COLUMN first_name VARCHAR(80) NULL');
    }

    if (!dbColumnExists($pdo, 'promedia_students', 'last_name')) {
        $pdo->exec('ALTER TABLE promedia_students ADD COLUMN last_name VARCHAR(80) NULL');
    }

    if (!dbColumnExists($pdo, 'promedia_students', 'birth_date')) {
        $pdo->exec('ALTER TABLE promedia_students ADD COLUMN birth_date DATE NULL');
    }

    if (!dbColumnExists($pdo, 'promedia_students', 'sex')) {
        $pdo->exec('ALTER TABLE promedia_students ADD COLUMN sex VARCHAR(1) NULL');
    }

    if (!dbColumnExists($pdo, 'promedia_students', 'address')) {
        $pdo->exec('ALTER TABLE promedia_students ADD COLUMN address VARCHAR(180) NULL');
    }

    if (!dbColumnExists($pdo, 'promedia_students', 'email')) {
        $pdo->exec('ALTER TABLE promedia_students ADD COLUMN email VARCHAR(120) NULL');
    }

    if (!dbColumnExists($pdo, 'promedia_students', 'phone')) {
        $pdo->exec('ALTER TABLE promedia_students ADD COLUMN phone VARCHAR(40) NULL');
    }

    if (!dbColumnExists($pdo, 'promedia_subjects', 'legacy_subject_id')) {
        $pdo->exec('ALTER TABLE promedia_subjects ADD COLUMN legacy_subject_id INT NULL');
    }

    if (!dbColumnExists($pdo, 'promedia_subjects', 'abbreviation')) {
        $pdo->exec('ALTER TABLE promedia_subjects ADD COLUMN abbreviation VARCHAR(20) NULL');
    }

    if (!dbColumnExists($pdo, 'promedia_subjects', 'summary')) {
        $pdo->exec('ALTER TABLE promedia_subjects ADD COLUMN summary VARCHAR(120) NULL');
    }

    if (!dbColumnExists($pdo, 'promedia_subjects', 'department')) {
        $pdo->exec('ALTER TABLE promedia_subjects ADD COLUMN department VARCHAR(80) NULL');
    }

    if (!dbColumnExists($pdo, 'promedia_subjects', 'teacher')) {
        $pdo->exec('ALTER TABLE promedia_subjects ADD COLUMN teacher VARCHAR(120) NULL');
    }

    if (!dbColumnExists($pdo, 'promedia_subjects', 'status')) {
        $pdo->exec('ALTER TABLE promedia_subjects ADD COLUMN status VARCHAR(20) NULL');
    }

    if (!dbColumnExists($pdo, 'promedia_grades', 'attendance_percent')) {
        $pdo->exec('ALTER TABLE promedia_grades ADD COLUMN attendance_percent DECIMAL(5,2) NOT NULL DEFAULT 100');
    }

    if (!dbIndexExists($pdo, 'promedia_students', 'ux_promedia_students_legacy_dni')) {
        $pdo->exec('CREATE UNIQUE INDEX ux_promedia_students_legacy_dni ON promedia_students (legacy_dni)');
    }

    if (!dbIndexExists($pdo, 'promedia_subjects', 'ux_promedia_subjects_legacy_subject_id')) {
        $pdo->exec('CREATE UNIQUE INDEX ux_promedia_subjects_legacy_subject_id ON promedia_subjects (legacy_subject_id)');
    }

    $initialized = true;
}

function dbColumnExists(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND COLUMN_NAME = :column_name
         LIMIT 1'
    );
    $stmt->execute([
        ':table_name' => $tableName,
        ':column_name' => $columnName,
    ]);

    return (bool)$stmt->fetchColumn();
}

function dbTableExists(PDO $pdo, string $tableName): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1
         FROM INFORMATION_SCHEMA.TABLES
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
         LIMIT 1'
    );
    $stmt->execute([':table_name' => $tableName]);

    return (bool)$stmt->fetchColumn();
}

function dbIndexExists(PDO $pdo, string $tableName, string $indexName): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1
         FROM INFORMATION_SCHEMA.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = :table_name
           AND INDEX_NAME = :index_name
         LIMIT 1'
    );
    $stmt->execute([
        ':table_name' => $tableName,
        ':index_name' => $indexName,
    ]);

    return (bool)$stmt->fetchColumn();
}

function dbShouldSyncLegacy(PDO $pdo): bool
{
    if (!dbTableExists($pdo, 'alumnos') || !dbTableExists($pdo, 'materias')) {
        return false;
    }

    $studentsCount = (int)$pdo->query('SELECT COUNT(*) FROM promedia_students')->fetchColumn();
    $subjectsCount = (int)$pdo->query('SELECT COUNT(*) FROM promedia_subjects')->fetchColumn();

    return $studentsCount === 0 && $subjectsCount === 0;
}

function dbSubjectYearColumn(PDO $pdo): string
{
    return dbColumnExists($pdo, 'promedia_subjects', 'year_label') ? 'year_label' : 'year';
}

function dbGradeTermColumn(PDO $pdo): string
{
    return dbColumnExists($pdo, 'promedia_grades', 'term_label') ? 'term_label' : 'term';
}

function dbGradeDateColumn(PDO $pdo): string
{
    return dbColumnExists($pdo, 'promedia_grades', 'grade_date') ? 'grade_date' : 'date';
}

function dbGradeAttendanceColumn(PDO $pdo): string
{
    return dbColumnExists($pdo, 'promedia_grades', 'attendance_percent') ? 'attendance_percent' : 'attendance';
}

function dbSyncLegacyData(PDO $pdo): array
{
    if (!dbTableExists($pdo, 'alumnos') || !dbTableExists($pdo, 'materias')) {
        return ['students' => 0, 'subjects' => 0];
    }

    $studentsBefore = (int)$pdo->query('SELECT COUNT(*) FROM promedia_students')->fetchColumn();
    $subjectsBefore = (int)$pdo->query('SELECT COUNT(*) FROM promedia_subjects')->fetchColumn();

    $pdo->exec(
        "INSERT INTO promedia_students (name, course, legacy_dni)
         SELECT
             CONCAT(TRIM(a.apellido), ', ', TRIM(a.nombre)) AS name,
             COALESCE(
                 (
                     SELECT CONCAT(c.ano, '°', c.division, ' ', c.turno)
                     FROM asignacionesalumnos aa
                     INNER JOIN cursosciclolectivo ccl ON ccl.id = aa.id_cursosciclolectivo
                     INNER JOIN cursos c ON c.id = ccl.id_cursos
                     WHERE aa.dni_alumnos = a.dni
                     ORDER BY ccl.ciclolectivo DESC, aa.id DESC
                     LIMIT 1
                 ),
                 'Sin curso'
             ) AS course,
             a.dni
         FROM alumnos a
         WHERE a.dni IS NOT NULL
           AND a.dni > 0
           AND NOT EXISTS (
               SELECT 1
               FROM promedia_students ps
               WHERE ps.legacy_dni = a.dni
           )"
    );

    $yearColumn = dbSubjectYearColumn($pdo);

    $pdo->exec(
        "INSERT INTO promedia_subjects (name, {$yearColumn}, legacy_subject_id)
         SELECT
             m.nombre,
             COALESCE(
                 (
                     SELECT CAST(c.ano AS CHAR)
                     FROM cupof cu
                     INNER JOIN cursos c ON c.id = cu.id_cursos
                     WHERE cu.id_materias = m.id
                     ORDER BY cu.cupof DESC
                     LIMIT 1
                 ),
                 'General'
             ) AS year_value,
             m.id
         FROM materias m
         WHERE m.id IS NOT NULL
           AND m.id > 0
           AND NOT EXISTS (
               SELECT 1
               FROM promedia_subjects ps
               WHERE ps.legacy_subject_id = m.id
           )"
    );

    $studentsAfter = (int)$pdo->query('SELECT COUNT(*) FROM promedia_students')->fetchColumn();
    $subjectsAfter = (int)$pdo->query('SELECT COUNT(*) FROM promedia_subjects')->fetchColumn();

    return [
        'students' => max(0, $studentsAfter - $studentsBefore),
        'subjects' => max(0, $subjectsAfter - $subjectsBefore),
    ];
}

function dbGetStudents(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT
            id,
            name,
            course,
            legacy_dni AS dni,
            first_name,
            last_name,
            birth_date,
            sex,
            address,
            email,
            phone
         FROM promedia_students
         ORDER BY name ASC'
    );

    return $stmt->fetchAll();
}

function dbGetSubjects(PDO $pdo): array
{
    $yearColumn = dbSubjectYearColumn($pdo);
    $stmt = $pdo->query(
        "SELECT
            id,
            name,
            {$yearColumn} AS year,
            abbreviation,
            summary,
            department,
            teacher,
            status
         FROM promedia_subjects
         ORDER BY name ASC"
    );

    return $stmt->fetchAll();
}

function dbGetGrades(PDO $pdo): array
{
    $termColumn = dbGradeTermColumn($pdo);
    $dateColumn = dbGradeDateColumn($pdo);
    $attendanceColumn = dbGradeAttendanceColumn($pdo);

    $stmt = $pdo->query(
        "SELECT id, student_id, subject_id, {$termColumn} AS term, score, {$attendanceColumn} AS attendance, {$dateColumn} AS date
         FROM promedia_grades
         ORDER BY id ASC"
    );

    $grades = $stmt->fetchAll();
    foreach ($grades as &$grade) {
        $grade['score'] = (float)$grade['score'];
        $grade['attendance'] = isset($grade['attendance']) ? (float)$grade['attendance'] : 100.0;
    }
    unset($grade);

    if (dbTableExists($pdo, 'nota') && dbTableExists($pdo, 'notastrimestrales')) {
        $legacyStmt = $pdo->query(
            "SELECT
                (100000000 + nt.id) AS id,
                ps.id AS student_id,
                psub.id AS subject_id,
                CONCAT('Trimestre ', nt.trimestre) AS term,
                CAST(nt.nota AS DECIMAL(4,2)) AS score,
                     100.00 AS attendance,
                CURRENT_DATE() AS date
             FROM notastrimestrales nt
             INNER JOIN nota n ON n.id = nt.id_nota
             INNER JOIN asignacionesalumnos aa ON aa.id = n.id_asignacionesalumnos
             INNER JOIN cupof cu ON cu.cupof = n.cupof
             INNER JOIN promedia_students ps ON ps.legacy_dni = aa.dni_alumnos
             INNER JOIN promedia_subjects psub ON psub.legacy_subject_id = cu.id_materias
             WHERE nt.nota BETWEEN 1 AND 10"
        );

        $legacyGrades = $legacyStmt->fetchAll();
        foreach ($legacyGrades as &$legacyGrade) {
            $legacyGrade['score'] = (float)$legacyGrade['score'];
            $legacyGrade['attendance'] = (float)$legacyGrade['attendance'];
        }
        unset($legacyGrade);

        $grades = array_merge($grades, $legacyGrades);
    }

    return $grades;
}

function dbAddStudent(PDO $pdo, array $payload): array
{
    $firstName = trim((string)($payload['first_name'] ?? ''));
    $lastName = trim((string)($payload['last_name'] ?? ''));
    $dniRaw = trim((string)($payload['dni'] ?? ''));
    $birthDate = trim((string)($payload['birth_date'] ?? ''));
    $sex = trim((string)($payload['sex'] ?? ''));
    $address = trim((string)($payload['address'] ?? ''));
    $email = trim((string)($payload['email'] ?? ''));
    $phone = trim((string)($payload['phone'] ?? ''));
    $course = trim((string)($payload['course'] ?? ''));
    $name = trim((string)($payload['name'] ?? ''));

    if ($name === '') {
        $name = trim($lastName . ' ' . $firstName);
    }

    $dni = null;
    if ($dniRaw !== '' && ctype_digit($dniRaw)) {
        $dni = (int)$dniRaw;
    }

    $birthDateValue = $birthDate !== '' ? $birthDate : null;
    $sexValue = $sex !== '' ? strtoupper(substr($sex, 0, 1)) : null;
    $addressValue = $address !== '' ? $address : null;
    $emailValue = $email !== '' ? $email : null;
    $phoneValue = $phone !== '' ? $phone : null;

    $stmt = $pdo->prepare(
        'INSERT INTO promedia_students
            (name, course, legacy_dni, first_name, last_name, birth_date, sex, address, email, phone)
         VALUES
            (:name, :course, :legacy_dni, :first_name, :last_name, :birth_date, :sex, :address, :email, :phone)'
    );
    $stmt->execute([
        ':name' => $name,
        ':course' => $course,
        ':legacy_dni' => $dni,
        ':first_name' => $firstName !== '' ? $firstName : null,
        ':last_name' => $lastName !== '' ? $lastName : null,
        ':birth_date' => $birthDateValue,
        ':sex' => $sexValue,
        ':address' => $addressValue,
        ':email' => $emailValue,
        ':phone' => $phoneValue,
    ]);

    return [
        'id' => (int)$pdo->lastInsertId(),
        'name' => $name,
        'course' => $course,
        'dni' => $dni,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'birth_date' => $birthDateValue,
        'sex' => $sexValue,
        'address' => $addressValue,
        'email' => $emailValue,
        'phone' => $phoneValue,
    ];
}

function dbAddSubject(PDO $pdo, array $payload): array
{
    $yearColumn = dbSubjectYearColumn($pdo);
    $name = trim((string)($payload['name'] ?? ''));
    $year = trim((string)($payload['year'] ?? ''));
    $abbreviation = trim((string)($payload['abbreviation'] ?? ''));
    $summary = trim((string)($payload['summary'] ?? ''));
    $department = trim((string)($payload['department'] ?? ''));
    $teacher = trim((string)($payload['teacher'] ?? ''));
    $status = trim((string)($payload['status'] ?? ''));

    $stmt = $pdo->prepare(
        "INSERT INTO promedia_subjects (name, {$yearColumn}, abbreviation, summary, department, teacher, status)
         VALUES (:name, :year_label, :abbreviation, :summary, :department, :teacher, :status)"
    );
    $stmt->execute([
        ':name' => $name,
        ':year_label' => $year,
        ':abbreviation' => $abbreviation !== '' ? $abbreviation : null,
        ':summary' => $summary !== '' ? $summary : null,
        ':department' => $department !== '' ? $department : null,
        ':teacher' => $teacher !== '' ? $teacher : null,
        ':status' => $status !== '' ? $status : null,
    ]);

    return [
        'id' => (int)$pdo->lastInsertId(),
        'name' => $name,
        'year' => $year,
        'abbreviation' => $abbreviation,
        'summary' => $summary,
        'department' => $department,
        'teacher' => $teacher,
        'status' => $status,
    ];
}

function dbStudentExists(PDO $pdo, int $studentId): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM promedia_students WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $studentId]);

    return (bool)$stmt->fetchColumn();
}

function dbSubjectExists(PDO $pdo, int $subjectId): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM promedia_subjects WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $subjectId]);

    return (bool)$stmt->fetchColumn();
}

function dbAddGrade(PDO $pdo, int $studentId, int $subjectId, string $term, float $score, float $attendance, ?string $gradeDate = null): array
{
    $scoreRounded = round($score, 2);
    $attendanceRounded = round($attendance, 2);
    $date = $gradeDate && $gradeDate !== '' ? $gradeDate : date('Y-m-d');
    $termColumn = dbGradeTermColumn($pdo);
    $dateColumn = dbGradeDateColumn($pdo);
    $attendanceColumn = dbGradeAttendanceColumn($pdo);

    $stmt = $pdo->prepare(
        "INSERT INTO promedia_grades (student_id, subject_id, {$termColumn}, score, {$attendanceColumn}, {$dateColumn})
         VALUES (:student_id, :subject_id, :term_label, :score, :attendance, :grade_date)"
    );
    $stmt->execute([
        ':student_id' => $studentId,
        ':subject_id' => $subjectId,
        ':term_label' => $term,
        ':score' => $scoreRounded,
        ':attendance' => $attendanceRounded,
        ':grade_date' => $date,
    ]);

    return [
        'id' => (int)$pdo->lastInsertId(),
        'student_id' => $studentId,
        'subject_id' => $subjectId,
        'term' => $term,
        'score' => $scoreRounded,
        'attendance' => $attendanceRounded,
        'date' => $date,
    ];
}

function dbResetDemo(PDO $pdo): void
{
    $pdo->exec('DELETE FROM promedia_grades');
    $pdo->exec('DELETE FROM promedia_students');
    $pdo->exec('DELETE FROM promedia_subjects');
}
