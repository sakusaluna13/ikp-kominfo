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
                target.style.display = 'flex';
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
                modal.style.display = 'none';
            }
        });
    });

    // tutup modal saat klik area luar
    document.querySelectorAll('.app-modal').forEach(m => {
        m.addEventListener('click', e => {
            if (e.target === m) {
                m.classList.add('hidden');
                m.classList.remove('flex');
                m.style.display = 'none';
            }
        });
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.app-modal').forEach(m => {
                m.classList.add('hidden');
                m.classList.remove('flex');
                m.style.display = 'none';
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
            appAlert('Username dan password wajib diisi.', 'warning', 'Kolom Wajib Diisi');
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
            appAlert('Password minimal 8 karakter.', 'warning', 'Password Terlalu Pendek');
            p.focus();
            return;
        }
        if (p && c && p.value !== c.value) {
            e.preventDefault();
            appAlert('Konfirmasi password tidak sama.', 'warning', 'Password Tidak Cocok');
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

// dialog konfirmasi kustom pengganti confirm bawaan browser
window.appConfirm = function(options) {
    const title        = typeof options === 'string' ? 'Konfirmasi' : (options.title || 'Konfirmasi Tindakan');
    const text         = typeof options === 'string' ? options : (options.text || '');
    const icon         = (typeof options === 'object' && options.icon) || 'question';
    const confirmText  = (typeof options === 'object' && options.confirmText) || 'Ya, Lanjutkan';
    const cancelText   = (typeof options === 'object' && options.cancelText) || 'Batal';
    const confirmColor = (typeof options === 'object' && options.confirmColor) || '#102b4e';

    if (window.Swal) {
        return Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: confirmColor,
            cancelButtonColor: '#94a3b8',
            confirmButtonText: confirmText,
            cancelButtonText: cancelText,
            reverseButtons: true,
            focusCancel: false,
            customClass: {
                popup:         'swal-popup-custom',
                title:         'swal-title-custom',
                htmlContainer: 'swal-text-custom',
                confirmButton: 'swal-btn-custom',
                cancelButton:  'swal-btn-cancel-custom'
            }
        });
    }
    return Promise.resolve({ isConfirmed: window.confirm(text) });
};

// notifikasi alert kustom pengganti alert bawaan browser
window.appAlert = function(message, type, title) {
    type = type || 'info';
    const defaultTitles = { success: 'Berhasil!', error: 'Terjadi Kesalahan', warning: 'Perhatian', info: 'Informasi' };
    title = title || defaultTitles[type] || 'Pemberitahuan';

    if (window.Swal) {
        return Swal.fire({
            title: title,
            text: message,
            icon: type,
            confirmButtonColor: '#102b4e',
            confirmButtonText: 'Mengerti',
            customClass: {
                popup:         'swal-popup-custom',
                title:         'swal-title-custom',
                htmlContainer: 'swal-text-custom',
                confirmButton: 'swal-btn-custom'
            }
        });
    }
    window.alert(message);
    return Promise.resolve({ isConfirmed: true });
};

// intersepsi submit form yang memiliki atribut data-confirm
document.addEventListener('submit', function(e) {
    const form = e.target;
    if (!form || !form.hasAttribute('data-confirm')) return;
    if (form.dataset.confirmed === 'true') {
        form.removeAttribute('data-confirmed');
        return;
    }
    e.preventDefault();
    e.stopImmediatePropagation();

    window.appConfirm({
        title:        form.getAttribute('data-confirm-title') || 'Konfirmasi Tindakan',
        text:         form.getAttribute('data-confirm'),
        icon:         form.getAttribute('data-confirm-icon') || 'question',
        confirmText:  form.getAttribute('data-confirm-btn') || 'Ya, Lanjutkan',
        confirmColor: form.getAttribute('data-confirm-color') || '#102b4e'
    }).then(function(result) {
        if (result.isConfirmed) {
            form.dataset.confirmed = 'true';
            form.submit();
        }
    });
}, true);
