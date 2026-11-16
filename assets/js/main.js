/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Javascript Application Logic
 * File: assets/js/main.js
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Sidebar Toggle untuk Tampilan Mobile
    const sidebarToggle = document.getElementById('sidebarToggle');
    const appSidebar = document.querySelector('.app-sidebar');
    const sidebarOverlay = document.querySelector('.sidebar-overlay');

    if (sidebarToggle && appSidebar) {
        sidebarToggle.addEventListener('click', function () {
            appSidebar.classList.toggle('show');
            if (sidebarOverlay) {
                sidebarOverlay.classList.toggle('show');
            }
        });
    }

    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function () {
            appSidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    }

    // 2. Inisialisasi Modal Konfirmasi Hapus Universal
    const modalHapus = document.getElementById('modalKonfirmasiHapus');
    if (modalHapus) {
        modalHapus.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const urlHapus = button.getAttribute('data-url');
            const namaItem = button.getAttribute('data-nama') || 'data ini';

            const teksItem = modalHapus.querySelector('#namaItemHapus');
            const formHapus = modalHapus.querySelector('#formHapusUniversal');

            if (teksItem) {
                teksItem.textContent = namaItem;
            }
            if (formHapus && urlHapus) {
                formHapus.setAttribute('action', urlHapus);
            }
        });
    }

    // 3. Auto-close Alerts setelah 6 detik
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 6000);
    });

    // 4. Inisialisasi Tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

/**
 * Format input angka ribuan
 */
function formatRibuan(angka) {
    let number_string = angka.replace(/[^,\d]/g, '').toString(),
        split = number_string.split(','),
        sisa = split[0].length % 3,
        rupiah = split[0].substr(0, sisa),
        ribuan = split[0].substr(sisa).match(/\d{3}/gi);

    if (ribuan) {
        separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }

    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    return rupiah;
}
