<?php if (!empty($documents)): ?>
    <div class="modal-body">
        <div class="alert alert-info">
            <strong>Adjustment:</strong> <?php echo $header->doc_no; ?> 
            <br><small>Tanggal: <?php echo date('d M Y H:i', strtotime($header->doc_date)); ?></small>
        </div>
        
        <ul class="list-group">
            <?php foreach ($documents as $doc): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <a href="<?php echo base_url('adjustment/view_document/' . $doc->id); ?>" target="_blank" class="font-weight-bold text-primary">
                                <i class="fas fa-file"></i> <?php echo $doc->file_name; ?>
                            </a>
                            <?php if ($doc->description): ?>
                                <p class="mb-1 text-muted small"><?php echo $doc->description; ?></p>
                            <?php endif; ?>
                            <small class="text-muted d-block">
                                <i class="far fa-calendar"></i> <?php echo date('d M Y H:i', strtotime($doc->uploaded_at)); ?>
                                | <i class="fas fa-user"></i> <?php echo $doc->uploaded_by_name; ?>
                            </small>
                        </div>
                        <span class="badge badge-secondary ml-2"><?php echo number_format($doc->file_size / 1024, 1); ?> KB</span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php else: ?>
    <div class="modal-body">
        <div class="alert alert-warning mb-0">
            <i class="fas fa-info-circle"></i> Tidak ada dokumen untuk adjustment ini.
        </div>
    </div>
<?php endif; ?>
