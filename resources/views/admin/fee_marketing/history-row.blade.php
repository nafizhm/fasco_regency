<tr>
<td><input aria-label="Tanggal fee sebelumnya" type="date" class="form-control form-control-sm" name="riwayat[{{ $i }}][tanggal]" value="{{ $row['tanggal'] ?? '' }}" required></td>
<td><input aria-label="Keterangan fee sebelumnya" class="form-control form-control-sm" name="riwayat[{{ $i }}][keterangan]" value="{{ $row['keterangan'] ?? '' }}" maxlength="500" required></td>
<td><input aria-label="Nominal fee sebelumnya" class="form-control form-control-sm rupiah text-right" inputmode="numeric" name="riwayat[{{ $i }}][nominal]" value="{{ $row['nominal'] ?? 0 }}" required></td>
<td><button class="btn btn-sm btn-outline-danger remove-row" type="button" aria-label="Hapus riwayat">Hapus</button></td>
</tr>

