<script>
    (() => {
        const cluster = $('#unit_cluster');
        const unit = $('#id_kavling');
        if (!cluster.length) return;
        let units = [], pending;
        const clusterName = item => (item.nama_cluster || '').trim() || '__unassigned';
        const label = name => name === '__unassigned' ? 'Tanpa cluster' : name;
        function renderUnits(selectedId = '') {
            unit.empty().append(new Option('', ''));
            unit.prop('disabled', !cluster.val());
            units.filter(item => clusterName(item) === cluster.val()).forEach(item => {
                const option = new Option(label(clusterName(item)) + ' / ' + item.kode_kavling,
                    item.id_kavling, false, String(selectedId) === String(item.id_kavling));
                $(option).attr('data-project-id', item.id_master_project || '');
                unit.append(option);
            });
            unit.trigger('change');
        }
        window.loadOutgoingUnits = function(locationId, selectedId = '') {
            if (pending) pending.abort();
            units = [];
            cluster.empty().append(new Option('', '')).prop('disabled', true).trigger('change.select2');
            renderUnits();
            if (!locationId) return;
            cluster.append(new Option('Memuat cluster...', '', false, true)).trigger('change.select2');
            pending = $.get(@json(route('barang-keluar.unit-by-lokasi', ['id' => '__id__'])).replace('__id__', locationId))
                .done(response => {
                    units = response;
                    cluster.empty().append(new Option('', ''));
                    [...new Set(units.map(clusterName))].sort((a,b) => label(a).localeCompare(label(b), 'id'))
                        .forEach(name => cluster.append(new Option(label(name), name)));
                    cluster.prop('disabled', !units.length);
                    const selected = units.find(item => String(item.id_kavling) === String(selectedId));
                    if (selected) cluster.val(clusterName(selected));
                    if (!units.length) cluster.append(new Option('Belum ada unit di lokasi ini', ''));
                    cluster.trigger('change.select2');
                    renderUnits(selectedId);
                }).fail((xhr, status) => {
                    if (status === 'abort') return;
                    cluster.empty().append(new Option('Gagal memuat. Pilih ulang lokasi.', '')).trigger('change.select2');
                });
        };
        cluster.on('change', () => renderUnits());
        $(function() {
            cluster.select2({theme: 'bootstrap4', placeholder: 'Pilih cluster terlebih dahulu', width: '100%'});
            window.loadOutgoingUnits($('#id_lokasi').val(), @json($data->id_kavling ?? null));
        });
    })();
</script>
