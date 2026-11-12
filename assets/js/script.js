document.addEventListener('DOMContentLoaded', () => {

    // INISIALISASI ICON LUCIDE
    if (window.lucide) {
        lucide.createIcons();
    }

    // TOGGLE PASSWORD
    document.querySelectorAll('[data-toggle-password]').forEach(btn => {
        btn.addEventListener('click', () => {

            const input = document.getElementById(
                btn.dataset.togglePassword
            );

            if (!input) return;

            const show = input.type === 'password';

            input.type = show ? 'text' : 'password';

            btn.innerHTML = `
                <i data-lucide="${show ? 'eye-off' : 'eye'}"></i>
            `;

            if (window.lucide) {
                lucide.createIcons();
            }
        });
    });

    // VALIDASI FORM LOGIN
    const login = document.getElementById('loginForm');

    login?.addEventListener('submit', e => {

        const u = login.querySelector('[name=username]');
        const p = login.querySelector('[name=password]');

        if (!u.value.trim() || !p.value) {
            e.preventDefault();

            alert('Username dan password wajib diisi.');

            (!u.value.trim() ? u : p).focus();

            return;
        }

        const b = login.querySelector('button[type=submit]');

        b.disabled = true;
        b.querySelector('span').textContent = 'Memproses...';
    });

    // VALIDASI FORM REGISTRASI
    const reg = document.getElementById('registerForm');

    reg?.addEventListener('submit', e => {

        const p = reg.querySelector('[name=password]');
        const c = reg.querySelector('[name=confirmation]');

        if (p.value.length < 8) {
            e.preventDefault();

            alert('Password minimal 8 karakter.');

            p.focus();

            return;
        }

        if (p.value !== c.value) {
            e.preventDefault();

            alert('Konfirmasi password tidak sama.');

            c.focus();
        }
    });

    // SIDEBAR
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    const close = () => {
        sidebar?.classList.remove('mobile-open');
        backdrop?.classList.remove('visible');
    };

    // BUKA SIDEBAR MOBILE
    document.getElementById('mobileMenu')?.addEventListener('click', () => {
        sidebar?.classList.add('mobile-open');
        backdrop?.classList.add('visible');
    });

    // TUTUP SIDEBAR MELALUI BACKDROP
    backdrop?.addEventListener('click', close);

    // COLLAPSE SIDEBAR
    document.getElementById('sidebarCollapse')?.addEventListener('click', () => {

        if (matchMedia('(min-width:901px)').matches) {

            document.body.classList.toggle('sidebar-collapsed');

            const icon = document.querySelector('#sidebarCollapse i');

            if (icon) {
                icon.setAttribute(
                    'data-lucide',
                    document.body.classList.contains('sidebar-collapsed')
                        ? 'panel-left-open'
                        : 'panel-left-close'
                );
            }

            if (window.lucide) {
                lucide.createIcons();
            }

        } else {
            close();
        }
    });

    // MODAL LOGOUT
    const modal = document.getElementById('logoutModal');
    const form = document.querySelector('[data-logout-form]');

    // BUKA MODAL
    const open = () => {
        modal?.classList.add('show');
        modal?.setAttribute('aria-hidden', 'false');
    };

    // TUTUP MODAL
    const shut = () => {
        modal?.classList.remove('show');
        modal?.setAttribute('aria-hidden', 'true');
    };

    // TOMBOL LOGOUT
    document.querySelector('[data-logout-trigger]')
        ?.addEventListener('click', open);

    // TOMBOL BATAL LOGOUT
    document.querySelector('[data-logout-cancel]')
        ?.addEventListener('click', shut);

    // KONFIRMASI LOGOUT
    document.querySelector('[data-logout-confirm]')
        ?.addEventListener('click', () => {
            form?.submit();
        });

    // TUTUP MODAL SAAT KLIK DI LUAR MODAL
    modal?.addEventListener('click', e => {
        if (e.target === modal) {
            shut();
        }
    });

    // TUTUP MODAL DENGAN TOMBOL ESCAPE
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            shut();
        }
    });

    // TANGGAL SAAT INI
    const date = document.getElementById('currentDate');

    if (date) {
        date.textContent = new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        }).format(new Date());
    }

    // NOTIFIKASI SETELAH LOGOUT
    if (new URLSearchParams(location.search).has('logged_out')) {

        const el = document.createElement('div');

        el.className = 'login-query-notice';
        el.textContent = 'Anda telah berhasil logout.';

        document.querySelector('.auth-content')?.prepend(el);
    }

});