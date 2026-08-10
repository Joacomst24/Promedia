const apiUrl = 'api.php';
const appRole = document.body?.dataset?.role || '';
const loggedStudentId = Number(document.body?.dataset?.studentId || 0);

const state = {
    students: [],
    subjects: [],
    reports: [],
    rules: null,
    studentLookup: new Map(),
    subjectLookup: new Map(),
    fullStudentLookup: new Map(),
    fullSubjectLookup: new Map(),
    orientationSets: {},
    allMaterias: new Set(),
    allTalleres: new Set(),
};

let selectedReportStudentId = null;

const studentForm = document.getElementById('studentForm');
const subjectForm = document.getElementById('subjectForm');
const gradeForm = document.getElementById('gradeForm');
const gradeStudentInput = document.getElementById('gradeStudentInput');
const gradeSubjectInput = document.getElementById('gradeSubjectInput');
const gradeStudentId = document.getElementById('gradeStudentId');
const gradeSubjectId = document.getElementById('gradeSubjectId');
const studentsList = document.getElementById('studentsList');
const subjectsList = document.getElementById('subjectsList');
const subjectOrientationFilter = document.getElementById('subjectOrientationFilter');
const subjectTypeFilter = document.getElementById('subjectTypeFilter');
const subjectYearFilter = document.getElementById('subjectYearFilter');
const analysisStudentSearch = document.getElementById('analysisStudentSearch');
const reportsContainer = document.getElementById('reportsContainer');
const rulesSummary = document.getElementById('rulesSummary');
const toast = document.getElementById('toast');
const toastMessage = document.getElementById('toastMessage');
const toastIcon = document.getElementById('toastIcon');
const resetBtn = document.getElementById('resetBtn');

let toastTimer = null;

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

function hideToast() {
    if (!toast) {
        return;
    }

    toast.classList.remove('show');
    toast.setAttribute('aria-hidden', 'true');
}

function showToast(message, type = 'success') {
    if (!toast) {
        return;
    }

    if (toastMessage) {
        toastMessage.textContent = message;
    }

    toast.classList.remove('toast--success', 'toast--error');
    toast.classList.add(type === 'error' ? 'toast--error' : 'toast--success');

    if (toastIcon) {
        toastIcon.innerHTML = type === 'error' ? '&times;' : '&check;';
    }

    toast.classList.add('show');
    toast.setAttribute('aria-hidden', 'false');

    if (toastTimer) {
        clearTimeout(toastTimer);
    }

    toastTimer = setTimeout(hideToast, 1600);
}

if (toast) {
    toast.addEventListener('click', (event) => {
        if (event.target === toast) {
            hideToast();
        }
    });
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

function buildSubjects(report) {
    if (!report.subjects.length) {
        return '<p class="report-empty">Sin notas cargadas para este estudiante.</p>';
    }

    const rows = report.subjects
        .map((item) => `
            <div class="subject-line ${item.approved ? 'is-approved' : 'is-pending'}">
                <span class="subject-line__name">${item.subject_name}</span>
                <span class="subject-line__meta">
                    <strong>${item.average.toFixed(2)}</strong>
                    <small>Asist. ${item.attendance.toFixed(2)}%</small>
                    <span class="subject-tag ${item.approved ? 'subject-tag--ok' : 'subject-tag--warn'}">${item.approved ? 'Aprobada' : 'Intensificar'}</span>
                </span>
            </div>
        `)
        .join('');

    return `<div class="report-subjects">
        <p class="report-subjects__title">Materias</p>
        ${rows}
    </div>`;
}

function buildReportCard(report) {
    return `
        <article class="report-card">
            <div class="report-card__head">
                <div class="report-card__id">
                    <h3>${report.student.name}</h3>
                    <p class="report-card__course">Curso ${report.student.course}</p>
                </div>
                ${statusBadge(report.status)}
            </div>
            <div class="report-stats">
                <div class="report-stat report-stat--avg">
                    <span class="report-stat__label">Promedio</span>
                    <strong class="report-stat__value">${report.overall_average.toFixed(2)}</strong>
                </div>
                <div class="report-stat report-stat--ok">
                    <span class="report-stat__label">Aprobadas</span>
                    <strong class="report-stat__value">${report.approved_subjects}</strong>
                </div>
                <div class="report-stat report-stat--bad">
                    <span class="report-stat__label">Desaprobadas</span>
                    <strong class="report-stat__value">${report.failed_subjects}</strong>
                </div>
            </div>
            <p class="report-status"><span>Situación</span> ${report.status}</p>
            ${buildSubjects(report)}
        </article>
    `;
}

function buildMatchItem(report) {
    return `
        <button type="button" class="match-item" data-student-id="${report.student.id}">
            <span class="match-item__info">
                <strong>${report.student.name}</strong>
                <small>Curso ${report.student.course}${report.student.dni ? ` · DNI ${report.student.dni}` : ''}</small>
            </span>
            <span class="match-item__status">${statusBadge(report.status)}</span>
        </button>
    `;
}

function studentOptionLabel(student) {
    return `${student.name} (${student.course}${student.dni ? ` - DNI ${student.dni}` : ''})`;
}

function subjectOptionLabel(subject) {
    return `${subject.name} (${subject.year}${subject.abbreviation ? ` - ${subject.abbreviation}` : ''})`;
}

function normalizeSubjectName(value) {
    return (value || '')
        .toString()
        .toUpperCase()
        .replace(/[^A-Z0-9 ]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

async function loadOrientaciones() {
    if (!subjectOrientationFilter) {
        return;
    }

    let config = {};
    try {
        const response = await fetch('data/orientaciones.json', { cache: 'no-cache' });
        if (response.ok) {
            const data = await response.json();
            config = data.orientaciones || {};
        }
    } catch (error) {
        config = {};
    }

    state.orientationSets = {};
    state.allMaterias = new Set();
    state.allTalleres = new Set();

    Object.entries(config).forEach(([orientation, groups]) => {
        const materias = new Set((groups.materias || []).map(normalizeSubjectName));
        const talleres = new Set((groups.talleres || []).map(normalizeSubjectName));
        const all = new Set([...materias, ...talleres]);

        state.orientationSets[orientation] = { materias, talleres, all };
        materias.forEach((name) => state.allMaterias.add(name));
        talleres.forEach((name) => state.allTalleres.add(name));
    });

    subjectOrientationFilter.innerHTML =
        '<option value="">Todas</option>' +
        Object.keys(config)
            .map((orientation) => `<option value="${orientation}">${orientation}</option>`)
            .join('');
}

function populateYearFilter() {
    if (!subjectYearFilter) {
        return;
    }

    const previous = subjectYearFilter.value;
    const years = [...new Set(state.subjects.map((s) => String(s.year ?? '').trim()).filter((y) => y !== ''))];
    years.sort((a, b) => {
        const na = Number(a);
        const nb = Number(b);
        if (!Number.isNaN(na) && !Number.isNaN(nb)) {
            return na - nb;
        }
        return a.localeCompare(b);
    });

    subjectYearFilter.innerHTML =
        '<option value="">Todos</option>' +
        years
            .map((year) => {
                const label = /^\d+$/.test(year) ? `${year}°` : year;
                return `<option value="${year}">${label}</option>`;
            })
            .join('');

    if (years.includes(previous)) {
        subjectYearFilter.value = previous;
    }
}

function subjectMatchesFilters(subject) {
    const orientation = subjectOrientationFilter ? subjectOrientationFilter.value : '';
    const type = subjectTypeFilter ? subjectTypeFilter.value : '';
    const year = subjectYearFilter ? subjectYearFilter.value : '';

    if (year !== '' && String(subject.year ?? '').trim() !== year) {
        return false;
    }

    const norm = normalizeSubjectName(subject.name);

    let materiasScope = state.allMaterias;
    let talleresScope = state.allTalleres;

    if (orientation !== '') {
        const sets = state.orientationSets[orientation];
        if (!sets || !sets.all.has(norm)) {
            return false;
        }
        materiasScope = sets.materias;
        talleresScope = sets.talleres;
    }

    if (type === 'Materia' && !materiasScope.has(norm)) {
        return false;
    }

    if (type === 'Taller' && !talleresScope.has(norm)) {
        return false;
    }

    return true;
}

function syncHiddenIds() {
    if (!gradeStudentInput || !gradeSubjectInput || !gradeStudentId || !gradeSubjectId) {
        return;
    }

    const studentVal = gradeStudentInput.value.trim().normalize('NFC');
    const foundStudent = state.students.find((s) => studentOptionLabel(s).normalize('NFC') === studentVal);
    gradeStudentId.value = foundStudent ? String(foundStudent.id) : '';

    const subjectVal = gradeSubjectInput.value.trim().normalize('NFC');
    const foundSubject = state.subjects.find((s) => subjectOptionLabel(s).normalize('NFC') === subjectVal);
    gradeSubjectId.value = foundSubject ? String(foundSubject.id) : '';
}

function renderSelects() {
    if (!gradeStudentInput || !gradeSubjectInput || !studentsList || !subjectsList) {
        return;
    }

    // Rebuild full lookup maps (all records, no filter) so syncHiddenIds always resolves.
    state.fullStudentLookup = new Map();
    state.students.forEach((s) => state.fullStudentLookup.set(studentOptionLabel(s), s.id));

    state.fullSubjectLookup = new Map();
    state.subjects.forEach((s) => state.fullSubjectLookup.set(subjectOptionLabel(s), s.id));

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
        if (!subjectMatchesFilters(s)) {
            return false;
        }

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
    if (!reportsContainer) {
        return;
    }

    if (!state.reports.length) {
        reportsContainer.innerHTML = '<p>No hay análisis disponibles. Cargá estudiantes, materias y notas.</p>';
        return;
    }

    if (appRole === 'alumno') {
        const ownReports = state.reports.filter((report) => (loggedStudentId > 0 ? report.student.id === loggedStudentId : true));

        if (!ownReports.length) {
            reportsContainer.innerHTML = '<p>No hay notas cargadas para tu usuario.</p>';
            return;
        }

        reportsContainer.innerHTML = ownReports.map(buildReportCard).join('');

        return;
    }

    if (!analysisStudentSearch) {
        reportsContainer.innerHTML = '<p>No se encontró el buscador de estudiantes.</p>';
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
        selectedReportStudentId = null;
        reportsContainer.innerHTML = '<p>No se encontraron análisis para la búsqueda ingresada.</p>';
        return;
    }

    const selectedReport = selectedReportStudentId
        ? filteredReports.find((report) => report.student.id === selectedReportStudentId)
        : null;

    if (selectedReport) {
        reportsContainer.innerHTML = `
            <div class="report-detail">
                <button type="button" class="report-back" data-report-back>&larr; Volver a la lista</button>
                ${buildReportCard(selectedReport)}
            </div>
        `;
        return;
    }

    reportsContainer.innerHTML = `
        <div class="match-list">
            <p class="match-list__title">${filteredReports.length} coincidencia${filteredReports.length === 1 ? '' : 's'} · elegí un alumno</p>
            ${filteredReports.map(buildMatchItem).join('')}
        </div>
    `;
}

async function refreshDashboard() {
    const data = await apiRequest('dashboard');
    state.students = data.students;
    state.subjects = data.subjects;
    state.reports = data.reports;
    state.rules = data.rules;

    renderRules();
    populateYearFilter();
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
            student_password: formData.get('student_password'),
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
            showToast(error.message, 'error');
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
            showToast(error.message, 'error');
        }
    });
}

if (gradeForm) {
    gradeForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        syncHiddenIds();

        const studentId = gradeStudentId ? Number(gradeStudentId.value) : 0;
        const subjectId = gradeSubjectId ? Number(gradeSubjectId.value) : 0;

        if (!studentId || !subjectId) {
            showToast('Selecciona estudiante y materia desde la lista', 'error');
            return;
        }

        const formData = new FormData(gradeForm);

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
            showToast(error.message, 'error');
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
            showToast(error.message, 'error');
        }
    });
}

if (gradeStudentInput) {
    gradeStudentInput.addEventListener('input', () => {
        renderSelects();
    });
    gradeStudentInput.addEventListener('change', () => {
        syncHiddenIds();
    });
}

if (gradeSubjectInput) {
    gradeSubjectInput.addEventListener('input', () => {
        renderSelects();
    });
    gradeSubjectInput.addEventListener('change', () => {
        syncHiddenIds();
    });
}

[subjectOrientationFilter, subjectTypeFilter, subjectYearFilter].forEach((filterSelect) => {
    if (filterSelect) {
        filterSelect.addEventListener('change', () => {
            renderSelects();
        });
    }
});

if (analysisStudentSearch) {
    analysisStudentSearch.addEventListener('input', () => {
        selectedReportStudentId = null;
        renderReports();
    });
}

if (reportsContainer) {
    reportsContainer.addEventListener('click', (event) => {
        const back = event.target.closest('[data-report-back]');
        if (back) {
            selectedReportStudentId = null;
            renderReports();
            return;
        }

        const item = event.target.closest('.match-item');
        if (item) {
            selectedReportStudentId = Number(item.dataset.studentId) || null;
            renderReports();
        }
    });
}

loadOrientaciones()
    .then(refreshDashboard)
    .catch((error) => {
        if (reportsContainer) {
            reportsContainer.innerHTML = `<p>Error al cargar datos: ${error.message}</p>`;
        }

        if (rulesSummary) {
            rulesSummary.innerHTML = `<p>Error al cargar criterios: ${error.message}</p>`;
        }
    });