document.addEventListener('DOMContentLoaded', () => {

    // 1. INISIALISASI LUCIDE ICONS
    if (window.lucide) {
        lucide.createIcons();
    }

    // 2. TOGGLE PASSWORD (LIHAT / SEMBUNYIKAN PASSWORD)
    document.querySelectorAll('[data-toggle-password]').forEach(btn => {
        btn.addEventListener('click', () => {
            const inputId = btn.getAttribute('data-toggle-password');
            const input = document.getElementById(inputId);
            if (!input) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            btn.innerHTML = `<i data-lucide="${isPassword ? 'eye-off' : 'eye'}" class="w-4 h-4 text-slate-400"></i>`;
            if (window.lucide) lucide.createIcons();
        });
    });

    // 3. SIDEBAR RESPONSIVE (MOBILE)
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const mobileMenu = document.getElementById('mobileMenu');
    const sidebarClose = document.getElementById('sidebarClose');

    const openSidebar = () => {
        sidebar?.classList.remove('-translate-x-full');
        backdrop?.classList.remove('hidden');
    };

    const closeSidebar = () => {
        sidebar?.classList.add('-translate-x-full');
        backdrop?.classList.add('hidden');
    };

    mobileMenu?.addEventListener('click', openSidebar);
    sidebarClose?.addEventListener('click', closeSidebar);
    backdrop?.addEventListener('click', closeSidebar);

    // 4. MODAL DIALOG (TAILWIND TOGGLE HIDDEN / FLEX)
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modalId = btn.getAttribute('data-modal-open');
            const target = document.getElementById(modalId);
            if (target) {
                target.classList.remove('hidden');
                target.classList.add('flex');
                if (window.lucide) lucide.createIcons();
            }
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.app-modal');
            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        });
    });

    // TUTUP MODAL SAAT KLIK DI LUAR KARTU ATAU ESCAPE
    document.querySelectorAll('.app-modal').forEach(m => {
        m.addEventListener('click', e => {
            if (e.target === m) {
                m.classList.add('hidden');
                m.classList.remove('flex');
            }
        });
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.app-modal.flex').forEach(m => {
                m.classList.add('hidden');
                m.classList.remove('flex');
            });
            closeSidebar();
        }
    });

    // 5. TANGGAL HARI INI DI TOPBAR
    const dateEl = document.getElementById('currentDate');
    if (dateEl) {
        dateEl.textContent = new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        }).format(new Date());
    }

    // 6. NOTIFIKASI SETELAH LOGOUT
    if (new URLSearchParams(location.search).has('logged_out')) {
        const noticeEl = document.getElementById('logoutNotice');
        if (noticeEl) {
            noticeEl.classList.remove('hidden');
        }
    }

    // 7. VALIDASI FORM LOGIN & REGISTRASI SEDERHANA
    const loginForm = document.getElementById('loginForm');
    loginForm?.addEventListener('submit', e => {
        const u = loginForm.querySelector('[name=username]');
        const p = loginForm.querySelector('[name=password]');
        if (!u.value.trim() || !p.value) {
            e.preventDefault();
            alert('Username dan password wajib diisi.');
            (!u.value.trim() ? u : p).focus();
            return;
        }
        const btn = loginForm.querySelector('button[type=submit]');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span>Memproses...</span>';
        }
    });

    const regForm = document.getElementById('registerForm');
    regForm?.addEventListener('submit', e => {
        const p = regForm.querySelector('[name=password]');
        const c = regForm.querySelector('[name=confirmation]');
        if (p && p.value.length < 8) {
            e.preventDefault();
            alert('Password minimal 8 karakter.');
            p.focus();
            return;
        }
        if (p && c && p.value !== c.value) {
            e.preventDefault();
            alert('Konfirmasi password tidak sama.');
            c.focus();
        }
    });

});
