<div class="modal fade" id="tagihanModal" tabindex="-1" role="dialog" aria-label="Form Pengajuan Tagihan"><div class="modal-dialog modal-lg modal-dialog-scrollable" role="document"><div class="modal-content"></div></div></div>
@push('scripts')
<script>
$(function () {
 let loading = false;
 $(document).on('click', '.open-tagihan', function (event) {
  event.preventDefault(); if (loading) return;
  loading = true;
  const modal = $('#tagihanModal');
  modal.find('.modal-content').html('<div class="modal-body">Memuat form pengajuan...</div>');
  modal.modal('show');
  $.ajax({url: this.href, dataType: 'html'})
   .done(html => modal.find('.modal-content').html(html))
   .fail(() => modal.find('.modal-content').html('<div class="modal-header"><h5 class="modal-title">Form Pengajuan</h5><button type="button" class="close" data-dismiss="modal" aria-label="Tutup">&times;</button></div><div class="modal-body text-danger">Form gagal dimuat. Tutup modal dan coba kembali.</div>'))
   .always(() => { loading = false; });
 });
});
</script>
@endpush
