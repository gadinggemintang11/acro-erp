<div class="modal fade" id="traceModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title"><i class="fas fa-project-diagram"></i> Trace Recursive</h5>
        <button class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-hover">
          <thead class="thead-light">
            <tr>
              <th>Kode Material</th>
              <th>Nama Material</th>
              <th>Source Doc</th>
              <th>Base On</th>
              <th>Supplier</th>
              <th>BC Type</th>
              <th>BC Number</th>
              <th>Qty</th>
            </tr>
          </thead>
          <tbody id="traceBody"></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>
