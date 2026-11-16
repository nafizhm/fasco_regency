const routeGetKavling = @json(route('customer.getKavling', ':id'));
let kavlingRequest = null;
let kavlingVersion = 0;
let clusterUnits = [];
const unitCluster = item => (item.nama_cluster || '').trim() || '__none__';

function filterClusterUnits(selectedKavling = null) {
    const cluster = $('#nama_cluster').val();
    const units = clusterUnits.filter(item => unitCluster(item) === cluster);
    const kavling = $('#id_kavling').empty().append(new Option('', ''));
    units.forEach(item => kavling.append(new Option(item.kode_kavling, item.id)));
    kavling.prop('disabled', !cluster || units.length === 0).val(selectedKavling || '').trigger('change');
    $('#kavling-help').text(cluster ? 'Pilih kavling sesuai nama cluster.' : 'Pilih nama cluster terlebih dahulu.');
}

$('#nama_cluster').on('change', function () { filterClusterUnits(); });

function loadClusterKavling(clusterId, selectedKavling = null) {
    const version = ++kavlingVersion;
    if (kavlingRequest) kavlingRequest.abort();
    clusterUnits = [];
    const clusterSelect = $('#nama_cluster').empty()
        .append(new Option(clusterId ? 'Memuat cluster...' : 'Pilih perumahan dahulu', ''))
        .prop('disabled', true);
    const kavling = $('#id_kavling');
    kavling.empty().append(new Option('', '')).prop('disabled', true).trigger('change');
    $('#kavling-help').text(clusterId ? 'Memuat cluster dan kavling...' : 'Pilih perumahan terlebih dahulu.');
    if (!clusterId) return;

    kavlingRequest = $.get(routeGetKavling.replace(':id', encodeURIComponent(clusterId)))
        .done(function (data) {
            if (version !== kavlingVersion) return;
            clusterUnits = data;
            clusterSelect.empty().append(new Option('Pilih Nama Cluster', ''));
            [...new Set(data.map(unitCluster))].sort().forEach(function (cluster) {
                clusterSelect.append(new Option(cluster === '__none__' ? 'Tanpa Cluster' : cluster, cluster));
            });
            clusterSelect.prop('disabled', data.length === 0);
            const selectedUnit = data.find(item => String(item.id) === String(selectedKavling));
            if (selectedUnit) clusterSelect.val(unitCluster(selectedUnit));
            filterClusterUnits(selectedKavling);
            if (!data.length) $('#kavling-help').text('Belum ada kavling pada perumahan ini.');
        })
        .fail(function (xhr, status) {
            if (status === 'abort' || version !== kavlingVersion) return;
            clusterSelect.empty().append(new Option('Gagal memuat cluster', ''));
            $('#kavling-help').text('Gagal memuat data. Pilih ulang perumahan untuk mencoba lagi.');
        });
}

$('#id_lokasi').on('change', function () {
    loadClusterKavling($(this).val());
});
