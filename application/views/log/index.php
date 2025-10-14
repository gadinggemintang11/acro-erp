<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Log Aktivitas Sistem</h1>
</div>

<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Username</th>
                        <th>Aktivitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) : ?>
                        <tr>
                            <td><?php echo $log['timestamp']; ?></td>
                            <td><?php echo html_escape($log['username']); ?></td>
                            <td><?php echo html_escape($log['activity']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>