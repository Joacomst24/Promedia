const apiUrl = 'api.php';

const state = {
    students: [],
    subjects: [],
    reports: [],
    rules: null,
    studentLookup: new Map(),
    subjectLookup: new Map(),
};

const studentForm = document.getElementById('studentForm');
const subjectForm = document.getElementById('subjectForm');
const gradeForm = document.getElementById('gradeForm');
const gradeStudentInput = document.getElementById('gradeStudentInput');
const gradeSubjectInput = document.getElementById('gradeSubjectInput');
const gradeStudentId = document.getElementById('gradeStudentId');
const gradeSubjectId = document.getElementById('gradeSubjectId');
const studentsList = document.getElementById('studentsList');
const subjectsList = document.getElementById('subjectsList');
const analysisStudentSearch = document.getElementById('analysisStudentSearch');
const reportsContainer = document.getElementById('reportsContainer');
const rulesSummary = document.getElementById('rulesSummary');
const toast = document.getElementById('toast');
const resetBtn = document.getElementById('resetBtn');

async function apiRequest(action, options = {}) {
    const response = await fetch(`${apiUrl}?action=${encodeURIComponent(action)}`, {
        headers: {
            'Content-Type': 'application/json',
        },
        ...options,
    });

    const data = await response.json();

    if (!response.ok || !data.ok) {
        throw new Error(data.error || 'Error inesperado en la API');
    }

    return data.data;
}

function showToast(message) {
    if (!toast) {
        return;
    }

    toast.textContent = message;
    toast.classList.add('show');

    setTimeout(() => {
        toast.classList.remove('show');
    }, 2200);
}

function statusBadge(status) {
    if (status.includes('Promociona')) {
        return '<span class="badge ok">Promociona</span>';
    }

    if (status.includes('intensificar')) {
        return '<span class="badge warn">Intensificación</span>';
    }

    if (status.includes('recursar')) {
        return '<span class="badge bad">Recursa</span>';
    }

    return '<span class="badge warn">Sin definir</span>';
}

function studentOptionLabel(student) {
    return `${student.name} (${student.course}${student.dni ? ` - DNI ${student.dni}` : ''})`;
}

function subjectOptionLabel(subject) {
    return `${subject.name} (${subject.year}${subject.abbreviation ? ` - ${subject.abbreviation}` : ''})`;
}

function syncHiddenIds() {
    if (!gradeStudentInput || !gradeSubjectInput || !gradeStudentId || !gradeSubjectId) {
        return;
    }

    const selectedStudentId = state.studentLookup.get(gradeStudentInput.value.trim());
    gradeStudentId.value = selectedStudentId ? String(selectedStudentId) : '';

    const selectedSubjectId = state.subjectLookup.get(gradeSubjectInput.value.trim());
    gradeSubjectId.value = selectedSubjectId ? String(selectedSubjectId) : '';
}

function renderSelects() {
    if (!gradeStudentInput || !gradeSubjectInput || !studentsList || !subjectsList) {
        return;
    }

    state.studentLookup = new Map();
    const studentFilter = (gradeStudentInput.value || '').trim().toLowerCase();
    const visibleStudents = state.students.filter((s) => {
        if (studentFilter === '') {
            return true;
        }

        const searchable = `${s.name} ${s.course} ${s.dni ?? ''}`.toLowerCase();
        return searchable.includes(studentFilter);
    });

    studentsList.innerHTML = visibleStudents
        .map((s) => {
            const label = studentOptionLabel(s);
            state.studentLookup.set(label, s.id);
            return `<option value="${label}"></option>`;
        })
        .join('');

    state.subjectLookup = new Map();
    const subjectFilter = (gradeSubjectInput.value || '').trim().toLowerCase();
    const visibleSubjects = state.subjects.filter((s) => {
        if (subjectFilter === '') {
            return true;
        }

        const searchable = `${s.name} ${s.year} ${s.abbreviation ?? ''}`.toLowerCase();
        return searchable.includes(subjectFilter);
    });

    subjectsList.innerHTML = visibleSubjects
        .map((s) => {
            const label = subjectOptionLabel(s);
            state.subjectLookup.set(label, s.id);
            return `<option value="${label}"></option>`;
        })
        .join('');

    syncHiddenIds();
}

function renderRules() {
    if (!rulesSummary) {
        return;
    }

    if (!state.rules) {
        rulesSummary.textContent = 'Sin reglas definidas.';
        return;
    }

    const stats = [
        { label: 'Nota mínima', value: state.rules.passing_grade },
        { label: 'Promoción directa', value: `hasta ${state.rules.max_failed_for_promotion} desap.` },
        { label: 'Intensificación', value: `hasta ${state.rules.max_failed_for_intensification} desap.` },
        { label: 'Asistencia mínima', value: `${state.rules.min_attendance_percent}%` },
    ];

    rulesSummary.innerHTML = stats
        .map((s) => `<div class="rules-stat"><span class="rules-stat__label">${s.label}</span><strong class="rules-stat__value">${s.value}</strong></div>`)
        .join('');
}

function renderReports() {
    if (!reportsContainer || !analysisStudentSearch) {
        return;
    }

    if (!state.reports.length) {
        reportsContainer.innerHTML = '<p>No hay análisis disponibles. Cargá estudiantes, materias y notas.</p>';
        return;
    }

    const query = (analysisStudentSearch.value || '').trim().toLowerCase();

    if (query === '') {
        reportsContainer.innerHTML = '<p>Escribí un alumno para ver su análisis académico.</p>';
        return;
    }

    const filteredReports = state.reports.filter((report) => {
        const searchable = `${report.student.name} ${report.student.course} ${report.student.dni ?? ''}`.toLowerCase();
        return searchable.includes(query);
    });

    if (!filteredReports.length) {
        reportsContainer.innerHTML = '<p>No se encontraron análisis para la búsqueda ingresada.</p>';
        return;
    }

    reportsContainer.innerHTML = filteredReports
        .map((report) => {
            const subjects = report.subjects.length
                ? report.subjects
                    .map((item) => `
                        <div class="subject-line">
                            <span>${item.subject_name}</span>
                            <strong>${item.average.toFixed(2)} | Asistencia ${item.attendance.toFixed(2)}% (${item.approved ? 'Aprobada' : 'Intensificar'})</strong>
                        </div>
                    `)
                    .join('')
                : '<p>Sin notas cargadas para este estudiante.</p>';

            return `
                <article class="report-card">
                    ${statusBadge(report.status)}
                    <h3>${report.student.name}</h3>
                    <p><strong>Curso:</strong> ${report.student.course}</p>
                    <p><strong>Promedio general:</strong> ${report.overall_average.toFixed(2)}</p>
                    <p><strong>Aprobadas:</strong> ${report.approved_subjects} | <strong>Desaprobadas:</strong> ${report.failed_subjects}</p>
                    <p><strong>Situación:</strong> ${report.status}</p>
                    <div>${subjects}</div>
                </article>
            `;
        })
        .join('');
}

async function refreshDashboard() {
    const data = await apiRequest('dashboard');
    state.students = data.students;
    state.subjects = data.subjects;
    state.reports = data.reports;
    state.rules = data.rules;

    renderRules();
    renderSelects();
    renderReports();
}

if (studentForm) {
    studentForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(studentForm);
        const payload = {
            dni: formData.get('dni'),
            first_name: formData.get('first_name'),
            last_name: formData.get('last_name'),
            name: formData.get('name'),
            course: formData.get('course'),
            birth_date: formData.get('birth_date'),
            sex: formData.get('sex'),
            address: formData.get('address'),
            email: formData.get('email'),
            phone: formData.get('phone'),
        };

        try {
            await apiRequest('add_student', {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            studentForm.reset();
            showToast('Estudiante registrado');
            await refreshDashboard();
        } catch (error) {
            showToast(error.message);
        }
    });
}

if (subjectForm) {
    subjectForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(subjectForm);
        const payload = {
            name: formData.get('name'),
            year: formData.get('year'),
            abbreviation: formData.get('abbreviation'),
            summary: formData.get('summary'),
            department: formData.get('department'),
            teacher: formData.get('teacher'),
            status: formData.get('status'),
        };

        try {
            await apiRequest('add_subject', {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            subjectForm.reset();
            showToast('Materia registrada');
            await refreshDashboard();
        } catch (error) {
            showToast(error.message);
        }
    });
}

if (gradeForm) {
    gradeForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(gradeForm);
        const studentId = Number(formData.get('student_id'));
        const subjectId = Number(formData.get('subject_id'));

        if (!studentId || !subjectId) {
            showToast('Selecciona estudiante y materia desde la lista');
            return;
        }

        const payload = {
            student_id: studentId,
            subject_id: subjectId,
            term: formData.get('term'),
            score: Number(formData.get('score')),
            attendance: Number(formData.get('attendance')),
            date: formData.get('date'),
        };

        try {
            await apiRequest('add_grade', {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            gradeForm.reset();
            if (gradeStudentId) {
                gradeStudentId.value = '';
            }
            if (gradeSubjectId) {
                gradeSubjectId.value = '';
            }
            showToast('Calificacion registrada');
            await refreshDashboard();
        } catch (error) {
            showToast(error.message);
        }
    });
}

if (resetBtn) {
    resetBtn.addEventListener('click', async () => {
        const confirmReset = window.confirm('Esto eliminara todos los datos cargados. Deseas continuar?');

        if (!confirmReset) {
            return;
        }

        try {
            await apiRequest('reset_demo', {
                method: 'POST',
                body: JSON.stringify({}),
            });
            showToast('Datos reiniciados');
            await refreshDashboard();
        } catch (error) {
            showToast(error.message);
        }
    });
}

if (gradeStudentInput) {
    gradeStudentInput.addEventListener('input', () => {
        renderSelects();
    });
}

if (gradeSubjectInput) {
    gradeSubjectInput.addEventListener('input', () => {
        renderSelects();
    });
}

if (analysisStudentSearch) {
    analysisStudentSearch.addEventListener('input', () => {
        renderReports();
    });
}

refreshDashboard().catch((error) => {
    if (reportsContainer) {
        reportsContainer.innerHTML = `<p>Error al cargar datos: ${error.message}</p>`;
    }

    if (rulesSummary) {
        rulesSummary.innerHTML = `<p>Error al cargar criterios: ${error.message}</p>`;
    }
});
