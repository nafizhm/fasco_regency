<div class="border rounded p-3 mb-4">
    <h6 class="font-weight-bold">Lokasi Unit</h6>
    <div class="row">
        <div class="form-group col-md-4 mb-md-0">
            <label for="id_lokasi">Perumahan</label>
            <select name="id_lokasi" id="id_lokasi" class="form-control select-lokasi" required>
                <option value=""></option>
                @foreach ($lokasi as $l)
                    <option value="{{ $l->id }}">{{ $l->nama_kavling }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-md-4 mb-md-0">
            <label for="nama_cluster">Nama Cluster</label>
            <select id="nama_cluster" class="form-control" required disabled>
                <option value="">Pilih perumahan dahulu</option>
            </select>
        </div>
        <div class="form-group col-md-4 mb-0">
            <label for="id_kavling">Blok / Kavling</label>
            <select name="id_kavling" id="id_kavling" class="form-control select-kavling" required disabled>
                <option value=""></option>
            </select>
            <small id="kavling-help" class="form-text text-muted" aria-live="polite">Pilih perumahan dan nama cluster terlebih dahulu.</small>
        </div>
    </div>
</div>
