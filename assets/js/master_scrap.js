/* ============================================================
   Master Scrap - AJAX Form Handler
   ============================================================ */

$(document).ready(function() {
    
    // Handle Edit Form Submit via AJAX
    $('form[action*="master_scrap/update"]').on('submit', function(e) {
        e.preventDefault(); // Prevent default form submission
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        const actionUrl = form.attr('action');
        const modal = form.closest('.modal');
        
        // Disable button and show loading
        submitBtn.prop('disabled', true)
                 .html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
        
        // Send AJAX request
        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                submitBtn.prop('disabled', false).html(originalBtnText);
                
                if (response.success) {
                    modal.modal('hide');
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message || 'Data berhasil diperbarui',
                        timer: 2000,
                        showConfirmButton: false,
                        timerProgressBar: true
                    }).then(() => {
                        location.reload(); // Reload to show updated data
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: response.message || 'Gagal memperbarui data',
                        confirmButtonColor: '#d33'
                    });
                }
            },
            error: function(xhr, status, error) {
                submitBtn.prop('disabled', false).html(originalBtnText);
                
                console.error('AJAX Error:', {xhr, status, error});
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Terjadi kesalahan koneksi. Silakan coba lagi.',
                    confirmButtonColor: '#d33',
                    footer: '<small>Error: ' + error + '</small>'
                });
            }
        });
    });
    
    // Handle Add Form Submit via AJAX
    $('form[action*="master_scrap/add"]').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalBtnText = submitBtn.html();
        const actionUrl = form.attr('action');
        const modal = form.closest('.modal');
        
        submitBtn.prop('disabled', true)
                 .html('<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...');
        
        $.ajax({
            url: actionUrl,
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                submitBtn.prop('disabled', false).html(originalBtnText);
                
                if (response.success) {
                    modal.modal('hide');
                    form[0].reset(); // Reset form
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message || 'Data berhasil ditambahkan',
                        timer: 2000,
                        showConfirmButton: false,
                        timerProgressBar: true
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: response.message || 'Gagal menambah data',
                        confirmButtonColor: '#d33'
                    });
                }
            },
            error: function(xhr, status, error) {
                submitBtn.prop('disabled', false).html(originalBtnText);
                
                console.error('AJAX Error:', {xhr, status, error});
                
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Terjadi kesalahan koneksi. Silakan coba lagi.',
                    confirmButtonColor: '#d33',
                    footer: '<small>Error: ' + error + '</small>'
                });
            }
        });
    });
    
});
