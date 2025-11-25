<div class="container-fluid">

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $title; ?></h1>
        <?php if (check_access('log_activity', 'delete')): ?>
            <a href="<?= base_url('log_activity/clear'); ?>" class="btn btn-danger shadow-sm" id="btn-clear-log">
                <i class="fas fa-trash fa-sm text-white-50"></i> Kosongkan Log
            </a>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Aktivitas</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Waktu</th>
                            <th>Username</th>
                            <th>Modul</th>
                            <th>Aksi</th>
                            <th>Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada data log.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = $this->uri->segment(3) + 1; foreach ($logs as $log): ?>
                            <tr>
                                <td><?= $no++; ?></td>
                                <td><?= date('d M Y, H:i:s', strtotime($log->log_time)); ?></td>
                                <td><?= html_escape($log->username); ?></td>
                                <td><?= html_escape($log->module); ?></td>
                                <td>
                                    <?php 
                                        $action = html_escape($log->action);
                                        if ($action == 'CREATE') {
                                            echo '<span class="badge badge-success">CREATE</span>';
                                        } elseif ($action == 'UPDATE') {
                                            echo '<span class="badge badge-warning">UPDATE</span>';
                                        } elseif ($action == 'DELETE') {
                                            echo '<span class="badge badge-danger">DELETE</span>';
                                        } else {
                                            echo '<span class="badge badge-info">' . $action . '</span>';
                                        }
                                    ?>
                                </td>
                                <td><?= html_escape($log->description); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="row">
                <div class="col-md-12">
                    <?= $pagination; ?>
                </div>
            </div>

        </div>
    </div>

</div>
<script>
$(document).ready(function() {
    // Konfirmasi SweetAlert saat hapus log
    $('#btn-clear-log').on('click', function(e) {
        e.preventDefault(); // Hentikan aksi link
        const href = $(this).attr('href'); // Ambil URL

        Swal.fire({
            title: 'Anda Yakin?',
            text: "Semua data log akan dihapus permanen dan tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, hapus semua!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Arahkan ke URL penghapusan
                window.location.href = href;
            }
        });
    });
});
</script>