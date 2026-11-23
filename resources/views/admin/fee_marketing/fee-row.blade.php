<tr>
<td><input aria-label="Blok" class="form-control form-control-sm" name="rincian[{{ $i }}][blok]" value="{{ $row['blok'] ?? '' }}" required maxlength="150"></td>
<td><input aria-label="Level" class="form-control form-control-sm" name="rincian[{{ $i }}][level]" value="{{ $row['level'] ?? '' }}" required maxlength="100"></td>
@foreach(['harga_deal','blt'] as $field)
<td><input aria-label="{{ $field }}" data-field="{{ $field }}" class="form-control form-control-sm rupiah text-right" inputmode="numeric" name="rincian[{{ $i }}][{{ $field }}]" value="{{ $row[$field] ?? 0 }}" required></td>
@endforeach
<td><input aria-label="Persentase" type="number" class="form-control form-control-sm percent" name="rincian[{{ $i }}][persen]" value="{{ $row['persen'] ?? 0 }}" min="0" max="100" step="0.01" required></td>
<td><output class="komisi">0</output></td><td><output class="total-fee">0</output></td>
@foreach(['fee_sebelumnya','fee_saat_ini'] as $field)
<td><input aria-label="{{ $field }}" data-field="{{ $field }}" class="form-control form-control-sm rupiah text-right" inputmode="numeric" name="rincian[{{ $i }}][{{ $field }}]" value="{{ $row[$field] ?? 0 }}" required></td>
@endforeach
<td><button class="btn btn-sm btn-outline-danger remove-row" type="button" aria-label="Hapus rincian">Hapus</button></td>
</tr>

