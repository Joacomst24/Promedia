<?php

declare(strict_types=1);

const PASSING_GRADE = 7.0;
const MAX_FAILED_FOR_PROMOTION = 0;
const MAX_FAILED_FOR_INTENSIFICATION = 2;
const MIN_ATTENDANCE_PERCENT = 80.0;

function average(array $values): float
{
    if (empty($values)) {
        return 0.0;
    }

    return array_sum($values) / count($values);
}

function buildStudentReport(array $student, array $subjects, array $grades): array
{
    $subjectMap = [];
    foreach ($subjects as $subject) {
        $subjectMap[(int)$subject['id']] = $subject;
    }

    $gradesBySubject = [];
    $attendanceBySubject = [];
    foreach ($grades as $grade) {
        if ((int)$grade['student_id'] !== (int)$student['id']) {
            continue;
        }

        $subjectId = (int)$grade['subject_id'];
        $gradesBySubject[$subjectId] ??= [];
        $gradesBySubject[$subjectId][] = (float)$grade['score'];

        $attendanceBySubject[$subjectId] ??= [];
        $attendanceBySubject[$subjectId][] = isset($grade['attendance']) ? (float)$grade['attendance'] : 100.0;
    }

    $subjectsReport = [];
    $failedSubjects = 0;
    $approvedSubjects = 0;
    $subjectsInIntensificationByAttendance = 0;

    foreach ($gradesBySubject as $subjectId => $scores) {
        if (!isset($subjectMap[$subjectId])) {
            continue;
        }

        $subjectAverage = round(average($scores), 2);
        $subjectAttendance = round(average($attendanceBySubject[$subjectId] ?? [100.0]), 2);
        $hasRequiredAttendance = $subjectAttendance >= MIN_ATTENDANCE_PERCENT;
        $isApproved = $subjectAverage >= PASSING_GRADE && $hasRequiredAttendance;

        if ($isApproved) {
            $approvedSubjects++;
        } else {
            $failedSubjects++;
        }

        if (!$hasRequiredAttendance) {
            $subjectsInIntensificationByAttendance++;
        }

        $subjectsReport[] = [
            'subject_id' => $subjectId,
            'subject_name' => $subjectMap[$subjectId]['name'],
            'grades' => $scores,
            'average' => $subjectAverage,
            'attendance' => $subjectAttendance,
            'attendance_ok' => $hasRequiredAttendance,
            'approved' => $isApproved,
        ];
    }

    usort($subjectsReport, static fn(array $a, array $b): int => strcmp($a['subject_name'], $b['subject_name']));

    $overallAverage = round(average(array_column($subjectsReport, 'average')), 2);

    $academicStatus = 'Sin información suficiente';
    if (!empty($subjectsReport)) {
        if ($failedSubjects <= MAX_FAILED_FOR_PROMOTION && $subjectsInIntensificationByAttendance === 0) {
            $academicStatus = 'Promociona al siguiente año';
        } elseif ($failedSubjects <= MAX_FAILED_FOR_INTENSIFICATION) {
            $academicStatus = 'Debe intensificar contenidos';
        } else {
            $academicStatus = 'Debe recursar materias';
        }
    }

    return [
        'student' => $student,
        'subjects' => $subjectsReport,
        'approved_subjects' => $approvedSubjects,
        'failed_subjects' => $failedSubjects,
        'attendance_intensification_subjects' => $subjectsInIntensificationByAttendance,
        'overall_average' => $overallAverage,
        'status' => $academicStatus,
        'criteria' => [
            'passing_grade' => PASSING_GRADE,
            'max_failed_for_promotion' => MAX_FAILED_FOR_PROMOTION,
            'max_failed_for_intensification' => MAX_FAILED_FOR_INTENSIFICATION,
            'min_attendance_percent' => MIN_ATTENDANCE_PERCENT,
        ],
    ];
}

function buildDashboard(array $students, array $subjects, array $grades): array
{
    $reports = [];

    foreach ($students as $student) {
        $reports[] = buildStudentReport($student, $subjects, $grades);
    }

    usort(
        $reports,
        static fn(array $a, array $b): int => strcmp($a['student']['name'], $b['student']['name'])
    );

    return [
        'students' => $students,
        'subjects' => $subjects,
        'grades' => $grades,
        'reports' => $reports,
        'rules' => [
            'passing_grade' => PASSING_GRADE,
            'max_failed_for_promotion' => MAX_FAILED_FOR_PROMOTION,
            'max_failed_for_intensification' => MAX_FAILED_FOR_INTENSIFICATION,
            'attendance_considered' => true,
            'min_attendance_percent' => MIN_ATTENDANCE_PERCENT,
        ],
    ];
}
