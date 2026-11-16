const routeGetKavling = @json(route('customer.getKavling', ':id'));
let kavlingRequest = null;
let kavlingVersion = 0;

function loadClusterKavling(clusterId, selectedKavling = null) {
    const version = ++kavlingVersion;
    if (kavlingRequest) kavlingRequest.abort();
    const kavling = $('#id_kavling');
    kavling.empty().append(new Option('', '')).prop('disabled', true).trigger('change');
    $('#kavling-help').text(clusterId ? 'Memuat kavling...' : 'Pilih cluster terlebih dahulu.');
    if (!clusterId) return;

    kavlingRequest = $.get(routeGetKavling.replace(':id', encodeURIComponent(clusterId)))
        .done(function (data) {
            if (version !== kavlingVersion) return;
            data.forEach(function (item) {
                kavling.append(new Option(item.kode_kavling, item.id));
            });
            kavling.prop('disabled', data.length === 0).val(selectedKavling || '').trigger('change');
            $('#kavling-help').text(data.length ? 'Pilih kavling sesuai cluster.' : 'Belum ada kavling pada cluster ini.');
        })
        .fail(function (xhr, status) {
            if (status === 'abort' || version !== kavlingVersion) return;
            $('#kavling-help').text('Gagal memuat kavling. Pilih ulang cluster untuk mencoba lagi.');
        });
}

$('#id_lokasi').on('change', function () {
    loadClusterKavling($(this).val());
});
