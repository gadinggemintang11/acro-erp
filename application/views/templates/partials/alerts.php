<?php
// Notifikasi Sukses
if ($this->session->flashdata('success')) {
    echo '
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-check-circle mr-2"></i>
        ' . $this->session->flashdata('success') . '
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>';
}

// Notifikasi Error
if ($this->session->flashdata('error')) {
    echo '
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        ' . $this->session->flashdata('error') . '
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>';
}

// Notifikasi Error Validasi
if (validation_errors()) {
     echo '
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <h6 class="alert-heading font-weight-bold"><i class="fas fa-ban mr-2"></i>Validasi Gagal!</h6>
        <hr>
        ' . validation_errors('<p class="mb-0">', '</p>') . '
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>';
}
?>