<?php
/**
 * SISTEM MANAJEMEN SUPERMARKET
 * Footer Layout Template
 * File: includes/footer.php
 */
?>
        </main>

        <!-- Footer Bar -->
        <footer class="bg-white border-top py-3 px-4 text-muted d-flex flex-column flex-sm-row justify-content-between align-items-center" style="font-size: 0.8rem;">
            <div>
                &copy; <?= date('Y') ?> <strong>Sistem Manajemen Supermarket</strong>. Pembelajaran Basis Data Relasional SMK RPL.
            </div>
            <div>
                PHP Native 8+ &bull; MySQL Relasional &bull; Bootstrap 5
            </div>
        </footer>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Universal -->
<div class="modal fade" id="modalKonfirmasiHapus" tabindex="-1" aria-labelledby="modalHapusLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fs-6" id="modalHapusLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus Data
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4 text-center">
                <i class="bi bi-trash3 text-danger" style="font-size: 3rem;"></i>
                <h6 class="mt-3">Apakah Anda yakin ingin menghapus data ini?</h6>
                <p class="text-muted small mb-0">
                    Data: <strong id="namaItemHapus" class="text-dark">item</strong>
                </p>
                <div class="alert alert-warning py-2 mt-3 small text-start mb-0">
                    <i class="bi bi-info-circle me-1"></i> Data yang memiliki relasi Foreign Key pada transaksi lain tidak dapat dihapus demi integritas database.
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <form id="formHapusUniversal" method="POST" action="" class="d-inline">
                    <?= csrf_input() ?>
                    <button type="submit" class="btn btn-danger btn-sm px-3">
                        <i class="bi bi-trash me-1"></i>Ya, Hapus Data
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom Application JS -->
<script src="<?= base_url('assets/js/main.js') ?>"></script>

</body>
</html>
