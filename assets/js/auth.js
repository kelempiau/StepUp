// assets/js/auth.js
function confirmLogout(logoutUrl) {
    confirmModernAlert({
        title: 'Keluar Platform?',
        text: 'Anda akan keluar dari sesi belajar saat ini. Pastikan semua progres Anda telah tersimpan.',
        icon: 'warning',
        confirmButtonText: 'Ya, Keluar Sekarang',
        cancelButtonText: 'Tetap di Sini',
        confirmButtonColor: '#ef4444'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = logoutUrl;
        }
    })
}
