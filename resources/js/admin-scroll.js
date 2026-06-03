/**
 * Preserve scroll position on admin form submits + AJAX for ready-notes inline edits.
 */

function showAdminToast(message) {
    const el = document.getElementById('ajax-status-toast');
    if (!el) {
        return;
    }
    el.textContent = message;
    el.classList.remove('hidden', 'opacity-0');
    clearTimeout(showAdminToast._t);
    showAdminToast._t = setTimeout(() => {
        el.classList.add('opacity-0');
        setTimeout(() => el.classList.add('hidden'), 300);
    }, 2600);
}

function preserveScrollBeforeSubmit() {
    document.addEventListener(
        'submit',
        (e) => {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) {
                return;
            }
            if (form.dataset.ajaxAreaForm !== undefined || form.dataset.ajaxReadyForm !== undefined) {
                return;
            }
            if (!form.closest('[data-admin-page]')) {
                return;
            }
            sessionStorage.setItem('adminScrollY', String(window.scrollY));
            sessionStorage.setItem('adminScrollPath', location.pathname + location.search);
        },
        true,
    );
}

function restoreScrollOnLoad() {
    const path = sessionStorage.getItem('adminScrollPath');
    if (path !== location.pathname + location.search) {
        return;
    }

    const run = () => {
        if (location.hash) {
            const target = document.querySelector(location.hash);
            if (target) {
                target.scrollIntoView({ behavior: 'instant', block: 'start' });
                sessionStorage.removeItem('adminScrollY');
                sessionStorage.removeItem('adminScrollPath');
                return;
            }
        }
        const y = parseInt(sessionStorage.getItem('adminScrollY') || '0', 10);
        if (y > 0) {
            window.scrollTo(0, y);
        }
        sessionStorage.removeItem('adminScrollY');
        sessionStorage.removeItem('adminScrollPath');
    };

    requestAnimationFrame(run);
}

async function submitReadyForm(form) {
    const btn = form.querySelector('[type="submit"]');
    if (btn) {
        btn.disabled = true;
    }

    const fd = new FormData(form);
    fd.set('ajax', '1');

    try {
        const res = await fetch(form.action, {
            method: 'POST',
            body: fd,
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

        const row = form.closest('[data-ready-row]');
        if (row) {
            if (data.text !== undefined) {
                const textEl = row.querySelector('[data-ready-text]');
                if (textEl) {
                    textEl.textContent = data.text;
                }
            }
            if (data.name !== undefined) {
                const nameEl = row.querySelector('[data-ready-field="name"]');
                if (nameEl) {
                    nameEl.textContent = data.name;
                }
            }
            if (data.category_name !== undefined || data.name !== undefined) {
                const catEl = row.querySelector('[data-ready-field="category"]');
                if (catEl) {
                    catEl.textContent = data.category_name
                        ? `مرتبط بتصنيف: ${data.category_name}`
                        : 'غير مرتبط بتصنيف ملاحظات';
                    catEl.className = data.category_name
                        ? 'text-xs text-emerald-500/80 mt-0.5'
                        : 'text-xs text-slate-500 mt-0.5';
                }
            }
            const alpine = row._x_dataStack?.[0];
            if (alpine) {
                alpine.editingNote = false;
                alpine.editingRec = false;
                alpine.editing = false;
            }
        }

        showAdminToast(data.message || 'تم الحفظ');
    } catch (err) {
        showAdminToast(err.message || 'حدث خطأ');
    } finally {
        if (btn) {
            btn.disabled = false;
        }
    }
}

function bindReadyAjaxForms() {
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }
        if (form.dataset.ajaxReadyForm === undefined) {
            return;
        }
        e.preventDefault();
        submitReadyForm(form);
    });
}

export function initAdminScroll() {
    if (!document.querySelector('[data-admin-page]')) {
        return;
    }
    preserveScrollBeforeSubmit();
    restoreScrollOnLoad();
    bindReadyAjaxForms();
}
