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
        'CREATE TABLE IF NOT EXISTS promedia_teachers (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            dni INT UNSIGNED NOT NULL,
            role TINYINT(1) NOT NULL DEFAULT 0,
            email VARCHAR(160) NULL,
            approval_status VARCHAR(20) NOT NULL DEFAULT \'pending\',
            approved_at TIMESTAMP NULL DEFAULT NULL,
            approved_by_superior_id INT UNSIGNED NULL,
            first_name VARCHAR(80) NOT NULL,
            last_name VARCHAR(80) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY ux_promedia_teachers_dni (dni)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS promedia_superiors (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            dni INT UNSIGNED NOT NULL,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(160) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY ux_promedia_superiors_dni (dni)
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

    if (!dbColumnExists($pdo, 'promedia_students', 'student_password_hash')) {
        $pdo->exec('ALTER TABLE promedia_students ADD COLUMN student_password_hash VARCHAR(255) NULL');
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

    if (!dbColumnExists($pdo, 'promedia_teachers', 'role')) {
        $pdo->exec('ALTER TABLE promedia_teachers ADD COLUMN role TINYINT(1) NOT NULL DEFAULT 0 AFTER dni');
    }

    if (!dbColumnExists($pdo, 'promedia_teachers', 'email')) {
        $pdo->exec('ALTER TABLE promedia_teachers ADD COLUMN email VARCHAR(160) NULL AFTER role');
    }

    if (!dbColumnExists($pdo, 'promedia_teachers', 'approval_status')) {
        $pdo->exec('ALTER TABLE promedia_teachers ADD COLUMN approval_status VARCHAR(20) NOT NULL DEFAULT \'pending\' AFTER email');
    }

    if (!dbColumnExists($pdo, 'promedia_teachers', 'approved_at')) {
        $pdo->exec('ALTER TABLE promedia_teachers ADD COLUMN approved_at TIMESTAMP NULL DEFAULT NULL AFTER approval_status');
    }

    if (!dbColumnExists($pdo, 'promedia_teachers', 'approved_by_superior_id')) {
        $pdo->exec('ALTER TABLE promedia_teachers ADD COLUMN approved_by_superior_id INT UNSIGNED NULL AFTER approved_at');
    }

    if (dbColumnExists($pdo, 'promedia_teachers', 'role')) {
        $teacherRoleType = dbColumnDataType($pdo, 'promedia_teachers', 'role');
        if ($teacherRoleType !== 'tinyint') {
            $pdo->exec(
                "UPDATE promedia_teachers
                 SET role = CASE
                     WHEN LOWER(TRIM(COALESCE(role, ''))) IN ('1', 'profesor', 'teacher', 'true') THEN '1'
                     ELSE '0'
                 END"
            );
            $pdo->exec('ALTER TABLE promedia_teachers MODIFY role TINYINT(1) NOT NULL DEFAULT 0');
        }
    }

    $superiorsCount = (int)$pdo->query('SELECT COUNT(*) FROM promedia_superiors')->fetchColumn();
    if ($superiorsCount === 0) {
        $defaultDni = getenv('SUPERIOR_DNI') ?: '10000000';
        $defaultName = getenv('SUPERIOR_NAME') ?: 'Superior General';
        $defaultEmail = getenv('SUPERIOR_EMAIL') ?: 'superior@promedia.local';
        $defaultPass = getenv('SUPERIOR_PASS') ?: 'admin1234';

        if (ctype_digit($defaultDni)) {
            $stmt = $pdo->prepare(
                'INSERT INTO promedia_superiors (dni, name, email, password_hash)
                 VALUES (:dni, :name, :email, :password_hash)'
            );
            $stmt->execute([
                ':dni' => (int)$defaultDni,
                ':name' => $defaultName,
                ':email' => $defaultEmail,
                ':password_hash' => password_hash($defaultPass, PASSWORD_DEFAULT),
            ]);
        }
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

function dbColumnDataType(PDO $pdo, string $tableName, string $columnName): ?string
{
    $stmt = $pdo->prepare(
        'SELECT DATA_TYPE
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

    $type = $stmt->fetchColumn();

    return is_string($type) ? strtolower($type) : null;
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
    $studentPassword = (string)($payload['student_password'] ?? '');
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
    $passwordToUse = $studentPassword !== '' ? $studentPassword : '1234';
    $studentPasswordHash = password_hash($passwordToUse, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO promedia_students
            (name, course, legacy_dni, first_name, last_name, birth_date, sex, address, email, phone, student_password_hash)
         VALUES
            (:name, :course, :legacy_dni, :first_name, :last_name, :birth_date, :sex, :address, :email, :phone, :student_password_hash)'
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
        ':student_password_hash' => $studentPasswordHash,
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

function dbFindStudentByDni(PDO $pdo, string $dni): ?array
{
    if ($dni === '' || !ctype_digit($dni)) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT
            id,
            name,
            course,
            legacy_dni AS dni
         FROM promedia_students
         WHERE legacy_dni = :dni
         LIMIT 1'
    );
    $stmt->execute([':dni' => (int)$dni]);

    $student = $stmt->fetch();

    return is_array($student) ? $student : null;
}

function dbFindTeacherByDni(PDO $pdo, string $dni): ?array
{
    if ($dni === '' || !ctype_digit($dni)) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT id, dni, role, email, approval_status, approved_at, approved_by_superior_id, first_name, last_name, password_hash
         FROM promedia_teachers
         WHERE dni = :dni
         LIMIT 1'
    );
    $stmt->execute([':dni' => (int)$dni]);

    $teacher = $stmt->fetch();

    return is_array($teacher) ? $teacher : null;
}

function dbRegisterTeacher(PDO $pdo, string $dni, string $email, string $firstName, string $lastName, string $password): array
{
    $stmt = $pdo->prepare(
        'INSERT INTO promedia_teachers (dni, role, email, approval_status, approved_at, approved_by_superior_id, first_name, last_name, password_hash)
            VALUES (:dni, 0, :email, \'pending\', NULL, NULL, :first_name, :last_name, :password_hash)
         ON DUPLICATE KEY UPDATE
            email = VALUES(email),
                approval_status = \'pending\',
            approved_at = NULL,
            approved_by_superior_id = NULL,
            first_name = VALUES(first_name),
            last_name = VALUES(last_name),
            password_hash = VALUES(password_hash)'
    );
    $stmt->execute([
        ':dni' => (int)$dni,
        ':email' => $email,
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);

    $teacher = dbFindTeacherByDni($pdo, $dni);

    return is_array($teacher) ? $teacher : [];
}

function dbFindSuperiorByDni(PDO $pdo, string $dni): ?array
{
    if ($dni === '' || !ctype_digit($dni)) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT id, dni, name, email, password_hash
         FROM promedia_superiors
         WHERE dni = :dni
         LIMIT 1'
    );
    $stmt->execute([':dni' => (int)$dni]);

    $superior = $stmt->fetch();

    return is_array($superior) ? $superior : null;
}

function dbValidateSuperiorPassword(PDO $pdo, int $superiorId, string $password): bool
{
    if ($superiorId <= 0 || $password === '') {
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT password_hash
         FROM promedia_superiors
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $superiorId]);
    $hash = $stmt->fetchColumn();

    return is_string($hash) && $hash !== '' && password_verify($password, $hash);
}

function dbGetTeacherAccounts(PDO $pdo): array
{
    $stmt = $pdo->query(
        'SELECT
            t.id,
            t.dni,
            t.role,
            t.email,
            t.approval_status,
            t.approved_at,
            t.approved_by_superior_id,
            t.first_name,
            t.last_name,
            t.created_at,
            s.name AS approved_by_name
         FROM promedia_teachers t
         LEFT JOIN promedia_superiors s ON s.id = t.approved_by_superior_id
         ORDER BY
            CASE t.approval_status
                WHEN \'pending\' THEN 0
                WHEN \'rejected\' THEN 1
                ELSE 2
            END,
            t.created_at DESC'
    );

    return $stmt->fetchAll();
}

function dbSetTeacherApproval(PDO $pdo, int $teacherId, string $status, int $role, int $superiorId): bool
{
    if ($teacherId <= 0 || $superiorId <= 0) {
        return false;
    }

    if (!in_array($status, ['approved', 'rejected'], true)) {
        return false;
    }

    if (!in_array($role, [0, 1], true)) {
        return false;
    }

    $stmt = $pdo->prepare(
        'UPDATE promedia_teachers
         SET approval_status = :approval_status,
             role = :role,
             approved_at = CURRENT_TIMESTAMP,
             approved_by_superior_id = :approved_by
         WHERE id = :id'
    );
    $stmt->execute([
        ':approval_status' => $status,
        ':role' => $role,
        ':approved_by' => $superiorId,
        ':id' => $teacherId,
    ]);

    return $stmt->rowCount() > 0;
}

function dbUpdateApprovedTeacherRole(PDO $pdo, int $teacherId, int $role, int $superiorId): bool
{
    if ($teacherId <= 0 || $superiorId <= 0) {
        return false;
    }

    if (!in_array($role, [0, 1], true)) {
        return false;
    }

    $stmt = $pdo->prepare(
        'UPDATE promedia_teachers
         SET role = :role,
             approved_by_superior_id = :approved_by
         WHERE id = :id
           AND approval_status = \'approved\''
    );
    $stmt->execute([
        ':role' => $role,
        ':approved_by' => $superiorId,
        ':id' => $teacherId,
    ]);

    return $stmt->rowCount() > 0;
}

function dbFindTeacherById(PDO $pdo, int $teacherId): ?array
{
    if ($teacherId <= 0) {
        return null;
    }

    $stmt = $pdo->prepare(
        'SELECT id, dni, role, email, approval_status, first_name, last_name
         FROM promedia_teachers
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $teacherId]);

    $teacher = $stmt->fetch();

    return is_array($teacher) ? $teacher : null;
}

function dbValidateTeacherPassword(PDO $pdo, int $teacherId, string $password): bool
{
    if ($teacherId <= 0 || $password === '') {
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT password_hash
         FROM promedia_teachers
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $teacherId]);
    $hash = $stmt->fetchColumn();

    return is_string($hash) && $hash !== '' && password_verify($password, $hash);
}

function dbSetStudentPassword(PDO $pdo, int $studentId, string $plainPassword): bool
{
    if ($studentId <= 0 || $plainPassword === '') {
        return false;
    }

    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'UPDATE promedia_students SET student_password_hash = :hash WHERE id = :id'
    );
    $stmt->execute([':hash' => $hash, ':id' => $studentId]);

    return $stmt->rowCount() > 0;
}

function dbValidateStudentPassword(PDO $pdo, int $studentId, string $password): bool
{
    if ($studentId <= 0 || $password === '') {
        return false;
    }

    $stmt = $pdo->prepare(
        'SELECT id, legacy_dni AS dni, student_password_hash
         FROM promedia_students
         WHERE id = :id
         LIMIT 1'
    );
    $stmt->execute([':id' => $studentId]);
    $student = $stmt->fetch();

    if (!is_array($student)) {
        return false;
    }

    $hash = (string)($student['student_password_hash'] ?? '');
    if ($hash !== '') {
        return password_verify($password, $hash);
    }

    if (!dbTableExists($pdo, 'alumnos')) {
        return false;
    }

    $dni = (int)($student['dni'] ?? 0);
    if ($dni <= 0) {
        return false;
    }

    $legacyStmt = $pdo->prepare('SELECT clave FROM alumnos WHERE dni = :dni LIMIT 1');
    $legacyStmt->execute([':dni' => $dni]);
    $legacyPassword = $legacyStmt->fetchColumn();

    if (!is_string($legacyPassword) || $legacyPassword === '') {
        return false;
    }

    if (!hash_equals($legacyPassword, $password)) {
        return false;
    }

    // Upgrade legacy plain password to a local hash after first successful login.
    $upgradeHash = password_hash($password, PASSWORD_DEFAULT);
    $upgradeStmt = $pdo->prepare('UPDATE promedia_students SET student_password_hash = :hash WHERE id = :id');
    $upgradeStmt->execute([
        ':hash' => $upgradeHash,
        ':id' => (int)$student['id'],
    ]);

    return true;
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
