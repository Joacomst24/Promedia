const apiUrl = 'api.php';

const state = {
    students: [],
    subjects: [],
    reports: [],
    rules: null,
};

const studentForm = document.getElementById('studentForm');
const subjectForm = document.getElementById('subjectForm');
const gradeForm = document.getElementById('gradeForm');
const studentSelect = document.getElementById('studentSelect');
const subjectSelect = document.getElementById('subjectSelect');
const studentSearch = document.getElementById('studentSearch');
const subjectSearch = document.getElementById('subjectSearch');
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

function renderSelects() {
    const studentFilter = (studentSearch.value || '').trim().toLowerCase();
    const visibleStudents = state.students.filter((s) => {
        if (studentFilter === '') {
            return true;
        }

        const searchable = `${s.name} ${s.course} ${s.dni ?? ''}`.toLowerCase();
        return searchable.includes(studentFilter);
    });

    studentSelect.innerHTML = visibleStudents.length
        ? visibleStudents.map((s) => `<option value="${s.id}">${s.name} (${s.course})</option>`).join('')
        : '<option value="">Sin estudiantes</option>';

    const subjectFilter = (subjectSearch.value || '').trim().toLowerCase();
    const visibleSubjects = state.subjects.filter((s) => {
        if (subjectFilter === '') {
            return true;
        }

        const searchable = `${s.name} ${s.year} ${s.abbreviation ?? ''}`.toLowerCase();
        return searchable.includes(subjectFilter);
    });

    subjectSelect.innerHTML = visibleSubjects.length
        ? visibleSubjects.map((s) => `<option value="${s.id}">${s.name} (${s.year})</option>`).join('')
        : '<option value="">Sin materias</option>';
}

function renderRules() {
    if (!state.rules) {
        rulesSummary.textContent = 'Sin reglas definidas.';
        return;
    }

    rulesSummary.innerHTML = `
        Nota mínima para aprobar: <strong>${state.rules.passing_grade}</strong>.<br>
        Promoción directa: <strong>${state.rules.max_failed_for_promotion}</strong> materias desaprobadas.<br>
        Intensificación: hasta <strong>${state.rules.max_failed_for_intensification}</strong> materias desaprobadas.<br>
        Asistencia considerada: <strong>${state.rules.attendance_considered ? 'Sí' : 'No'}</strong>.
    `;
}

function renderReports() {
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
                            <strong>${item.average.toFixed(2)} (${item.approved ? 'Aprobada' : 'Desaprobada'})</strong>
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

gradeForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const formData = new FormData(gradeForm);
    const payload = {
        student_id: Number(formData.get('student_id')),
        subject_id: Number(formData.get('subject_id')),
        term: formData.get('term'),
        score: Number(formData.get('score')),
        date: formData.get('date'),
    };

    try {
        await apiRequest('add_grade', {
            method: 'POST',
            body: JSON.stringify(payload),
        });
        gradeForm.reset();
        showToast('Calificación registrada');
        await refreshDashboard();
    } catch (error) {
        showToast(error.message);
    }
});

resetBtn.addEventListener('click', async () => {
    const confirmReset = window.confirm('Esto eliminará todos los datos cargados. ¿Deseás continuar?');

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

studentSearch.addEventListener('input', () => {
    renderSelects();
});

subjectSearch.addEventListener('input', () => {
    renderSelects();
});

analysisStudentSearch.addEventListener('input', () => {
    renderReports();
});

refreshDashboard().catch((error) => {
    reportsContainer.innerHTML = `<p>Error al cargar datos: ${error.message}</p>`;
});
