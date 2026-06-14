/**
 * Syntara v3.0 — JavaScript Global
 */

// ─── THEME TOGGLE ───
function toggleDark() {
    document.body.classList.toggle('dark');
    const isDark = document.body.classList.contains('dark');
    localStorage.setItem('syntara_dark', isDark ? '1' : '0');
    document.cookie = 'syntara_dark=' + (isDark ? '1' : '0') + ';path=/;max-age=31536000';
    const btn = document.querySelector('.theme-toggle');
    if (btn) btn.textContent = isDark ? '☀️' : '🌙';
}

// Restore theme
(function() {
    const dark = localStorage.getItem('syntara_dark') === '1';
    if (dark) {
        document.body.classList.add('dark');
        const btn = document.querySelector('.theme-toggle');
        if (btn) btn.textContent = '☀️';
    }
})();

// ─── MOBILE MENU ───
function toggleMenu() {
    const nav = document.getElementById('navLinks');
    if (nav) nav.classList.toggle('open');
}

document.addEventListener('click', function(e) {
    const nav = document.getElementById('navLinks');
    const toggle = document.querySelector('.nav-toggle');
    if (nav && toggle && !nav.contains(e.target) && !toggle.contains(e.target)) {
        nav.classList.remove('open');
    }
});

// ─── PASSWORD VISIBILITY ───
function togglePassword(fieldId) {
    const input = document.getElementById(fieldId);
    if (input) input.type = input.type === 'password' ? 'text' : 'password';
}

// ─── DELETE CONFIRMATION (Modal) ─
document.querySelectorAll('.btn-delete').forEach(function(btn) {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const url = this.getAttribute('href');
        const name = this.closest('tr') ? this.closest('tr').querySelector('td:first-child')?.textContent?.trim() || 'item' : 'item';

        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.id = 'deleteModal';
        overlay.innerHTML =
            '<div class="modal-box">' +
                '<div class="modal-icon">⚠️</div>' +
                '<h3>Confirmar exclusão</h3>' +
                '<p>Deseja realmente excluir <strong>' + name + '</strong>? Esta ação não pode ser desfeita.</p>' +
                '<div class="modal-actions">' +
                    '<a href="' + url + '" class="btn btn-danger btn-sm">Sim, excluir</a>' +
                    ' <button class="btn btn-ghost btn-sm" onclick="closeModal()">Cancelar</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';
    });
});

function closeModal() {
    const modal = document.getElementById('deleteModal');
    if (modal) {
        modal.style.opacity = '0';
        modal.style.transition = 'opacity 0.2s';
        setTimeout(function() { modal.remove(); document.body.style.overflow = ''; }, 200);
    }
}

document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) closeModal();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});

// ─── ALERT AUTO-DISMISS ───
document.querySelectorAll('.alert-close').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const alert = this.closest('.alert');
        if (alert) {
            alert.classList.add('alert-hiding');
            setTimeout(function() { alert.remove(); }, 400);
        }
    });
});

// Auto-dismiss after 6s
document.querySelectorAll('.alert').forEach(function(alert) {
    setTimeout(function() {
        if (alert.parentNode) {
            alert.classList.add('alert-hiding');
            setTimeout(function() { alert.remove(); }, 400);
        }
    }, 6000);
});

// ─── TOAST NOTIFICATIONS ───
function showToast(message, type) {
    type = type || 'info';
    var container = document.getElementById('toastContainer');
    if (!container) return;

    var icons = { success: '✅', error: '❌', info: 'ℹ️', warning: '⚠️' };
    var toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.innerHTML = '<span>' + (icons[type] || '') + '</span><span>' + message + '</span>';

    container.appendChild(toast);
    setTimeout(function() {
        toast.classList.add('toast-hiding');
        setTimeout(function() { toast.remove(); }, 300);
    }, 4000);
}

// ─── FORM LOADING STATE ───
document.querySelectorAll('form').forEach(function(form) {
    form.addEventListener('submit', function(e) {
        var btn = form.querySelector('button[type="submit"]');
        if (btn) btn.classList.add('loading');
    });
});

// ─── ACTIVE NAV LINK ───
(function() {
    var path = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(function(link) {
        if (link.getAttribute('href') && path.indexOf(link.getAttribute('href').replace(/^\//, '')) !== -1) {
            link.classList.add('active');
        }
    });
})();

// ─── SMOOTH ANIMATIONS ───
document.querySelectorAll('.card, .stat-box, .feedback-box, .empty-state').forEach(function(el) {
    el.classList.add('fade-in');
});
