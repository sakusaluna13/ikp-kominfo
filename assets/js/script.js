document.addEventListener('DOMContentLoaded', () => {

    // inisialisasi icon lucide
    if (window.lucide) {
        lucide.createIcons();
    }

    // toggle lihat/sembunyikan password
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

    // sidebar responsif mobile
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const sidebarClose = document.getElementById('sidebarClose');

    const openSidebar = () => {
        if (!sidebar) return;
        sidebar.classList.remove('-translate-x-full');
        backdrop?.classList.remove('hidden');
        document.body.classList.add('overflow-hidden'); // kunci scroll latar saat sidebar terbuka
    };

    const closeSidebar = () => {
        if (!sidebar) return;
        sidebar.classList.add('-translate-x-full');
        backdrop?.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    };

    // tombol pembuka sidebar
    document.querySelectorAll('[data-open-sidebar], #mobileMenu').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            openSidebar();
        });
    });

    sidebarClose?.addEventListener('click', closeSidebar);
    backdrop?.addEventListener('click', closeSidebar);

    // tutup sidebar saat menu di mobile diklik
    sidebar?.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 768) {
                closeSidebar();
            }
        });
    });

    // kontrol buka modal
    document.querySelectorAll('[data-modal-open]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const modalId = btn.getAttribute('data-modal-open');
            const target = document.getElementById(modalId);
            if (target) {
                target.classList.remove('hidden');
                target.classList.add('flex');
                if (window.lucide) lucide.createIcons();
            }
            // tutup dropdown profil bila terbuka
            const profileDropdown = document.getElementById('profileDropdown');
            if (profileDropdown) profileDropdown.classList.add('hidden');
            // tutup sidebar mobile bila terbuka
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar) sidebar.classList.add('-translate-x-full');
            if (backdrop) backdrop.classList.add('hidden');
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

    // tutup modal saat klik area luar
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

    // tampilkan tanggal hari ini di topbar
    const dateEl = document.getElementById('currentDate');
    if (dateEl) {
        dateEl.textContent = new Intl.DateTimeFormat('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        }).format(new Date());
    }

    // notifikasi setelah logout
    if (new URLSearchParams(location.search).has('logged_out')) {
        const noticeEl = document.getElementById('logoutNotice');
        if (noticeEl) {
            noticeEl.classList.remove('hidden');
        }
    }

    // validasi form login dan registrasi
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

    // dropdown profil
    const profileBtn = document.getElementById('profileDropdownBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            profileDropdown.classList.toggle('hidden');
            if (window.lucide) lucide.createIcons();
        });
        document.addEventListener('click', () => profileDropdown.classList.add('hidden'));
    }

    // hilangkan pesan flash secara otomatis
    const flashMsg = document.querySelector('[data-flash-message]');
    if (flashMsg) {
        setTimeout(() => {
            flashMsg.style.transition = 'opacity 0.5s';
            flashMsg.style.opacity = '0';
            setTimeout(() => flashMsg.remove(), 500);
        }, 4000);
    }

});

// sistem notifikasi toast
window.showToast = function(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const colors = {
        success: 'bg-emerald-500',
        error: 'bg-rose-500',
        warning: 'bg-amber-500',
        info: 'bg-blue-500'
    };
    const icons = {
        success: 'check-circle',
        error: 'alert-triangle',
        warning: 'alert-circle',
        info: 'info'
    };
    const toast = document.createElement('div');
    toast.className = `flex items-center gap-3 px-4 py-3 rounded-xl text-white text-xs font-semibold shadow-lg ${colors[type] || colors.info} translate-y-2 opacity-0 transition-all duration-300`;
    toast.innerHTML = `<i data-lucide="${icons[type] || 'info'}" class="w-4 h-4 shrink-0"></i><span>${message}</span><button onclick="this.parentElement.remove()" class="ml-auto pl-2 opacity-70 hover:opacity-100"><i data-lucide="x" class="w-3.5 h-3.5"></i></button>`;
    container.appendChild(toast);
    if (window.lucide) lucide.createIcons({nodes: [toast]});
    requestAnimationFrame(() => {
        toast.classList.remove('translate-y-2', 'opacity-0');
    });
    setTimeout(() => {
        toast.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => toast.remove(), 300);
    }, 4000);
};
