/**
 * House page: AJAX area updates (no full reload) + scroll position for other forms.
 */

function showToast(message) {
    const el = document.getElementById('ajax-status-toast') || document.getElementById('admin-ajax-toast');
    if (!el) {
        return;
    }
    el.textContent = message;
    el.classList.remove('hidden', 'opacity-0');
    clearTimeout(showToast._timer);
    showToast._timer = setTimeout(() => {
        el.classList.add('opacity-0');
        setTimeout(() => el.classList.add('hidden'), 300);
    }, 2800);
}

function escapeHtml(text) {
    const d = document.createElement('div');
    d.textContent = text;
    return d.innerHTML;
}

function renderList(items, emptyText) {
    if (!items?.length) {
        return `<p class="text-sm text-slate-500">${escapeHtml(emptyText)}</p>`;
    }
    return `<ol class="list-decimal list-inside space-y-2 text-sm leading-relaxed">${
        items.map((t) => `<li class="pr-1">${escapeHtml(t)}</li>`).join('')
    }</ol>`;
}

function updateAreaCard(area) {
    const root = document.getElementById(`area-${area.id}`);
    if (!root) {
        return;
    }

    const alpine = root._x_dataStack?.[0];
    if (alpine) {
        alpine.editing = false;
        alpine.notesList = [...(area.notes || [])];
        alpine.recommendationsList = [...(area.recommendations || [])];
    }

    const nameEl = root.querySelector('[data-area-name]');
    if (nameEl) {
        nameEl.textContent = area.name;
    }

    const scoreText = root.querySelector('[data-area-score-text]');
    if (scoreText) {
        scoreText.textContent = `${area.score}% (${area.label})`;
    }

    const scoreBar = root.querySelector('[data-area-score-bar]');
    if (scoreBar) {
        scoreBar.style.width = `${area.score}%`;
        scoreBar.className = `h-full ${area.color_class}`;
    }

    const notesBox = root.querySelector('[data-area-notes]');
    if (notesBox) {
        notesBox.innerHTML = renderList(area.notes, 'لا توجد ملاحظات — اضغط «تعديل» لإضافتها.');
    }

    const recsBox = root.querySelector('[data-area-recs]');
    if (recsBox) {
        recsBox.innerHTML = renderList(area.recommendations, 'لا توجد توصيات — اضغط «تعديل» لإضافتها.');
    }

    root.classList.add('ring-2', 'ring-emerald-500/40');
    setTimeout(() => root.classList.remove('ring-2', 'ring-emerald-500/40'), 1200);
}

async function submitAreaForm(form) {
    const submitBtn = form.querySelector('[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
    }

    const formData = new FormData(form);
    formData.set('ajax', '1');

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        });

        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            throw new Error(data.message || 'فشل الحفظ');
        }

        if (data.deleted_area_id) {
            document.getElementById(`area-${data.deleted_area_id}`)?.remove();
            showToast(data.message || 'تم الحذف');
            return;
        }

        if (data.area) {
            updateAreaCard(data.area);
        }

        showToast(data.message || 'تم الحفظ');
    } catch (err) {
        showToast(err.message || 'حدث خطأ أثناء الحفظ');
        console.error(err);
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
        }
    }
}

function preserveScrollOnSubmit() {
    document.addEventListener(
        'submit',
        (e) => {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }
            if (form.dataset.ajaxAreaForm !== undefined) {
                return;
            }
            if (!form.closest('[data-house-page]')) {
                return;
            }
            sessionStorage.setItem('houseScrollY', String(window.scrollY));
            sessionStorage.setItem('houseScrollPath', location.pathname + location.search);
        },
        true,
    );
}

function restoreScrollOnLoad() {
    const path = sessionStorage.getItem('houseScrollPath');
    if (path !== location.pathname + location.search) {
        return;
    }

    const run = () => {
        if (location.hash) {
            const target = document.querySelector(location.hash);
            if (target) {
                target.scrollIntoView({ behavior: 'instant', block: 'start' });
                sessionStorage.removeItem('houseScrollY');
                sessionStorage.removeItem('houseScrollPath');
                return;
            }
        }

        const y = parseInt(sessionStorage.getItem('houseScrollY') || '0', 10);
        if (y > 0) {
            window.scrollTo(0, y);
        }
        sessionStorage.removeItem('houseScrollY');
        sessionStorage.removeItem('houseScrollPath');
    };

    if (document.readyState === 'complete') {
        requestAnimationFrame(run);
    } else {
        window.addEventListener('load', () => requestAnimationFrame(run));
    }
}

function bindAjaxAreaForms() {
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }
        if (form.dataset.ajaxAreaForm === undefined) {
            return;
        }
        e.preventDefault();
        submitAreaForm(form);
    });

    document.querySelectorAll('form[data-ajax-reorder]').forEach((form) => {
        const input = form.querySelector('input[name="sort_order"]');
        if (!input) {
            return;
        }
        input.addEventListener('change', () => {
            const fd = new FormData(form);
            fd.set('ajax', '1');
            fetch(form.action, {
                method: 'POST',
                body: fd,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            })
                .then((r) => r.json())
                .then((data) => {
                    if (data.area) {
                        updateAreaCard(data.area);
                    }
                    showToast(data.message || 'تم تحديث الترتيب');
                })
                .catch(() => showToast('فشل تحديث الترتيب'));
        });
        input.removeAttribute('onchange');
    });
}

export function initHouseForms() {
    if (!document.querySelector('[data-house-page]')) {
        return;
    }
    preserveScrollOnSubmit();
    restoreScrollOnLoad();
    bindAjaxAreaForms();
}
