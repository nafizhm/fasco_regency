        <p><strong>Tanggal:</strong> {{ \Carbon\Carbon::parse($data->tanggal)->format('d-m-Y') }}<br><strong>Supplier:</strong> {{ $data->supplier?->nama ?? '-' }}<br><strong>Keterangan:</strong> {{ $data->keterangan ?: '-' }}</p>
        <div class="table-responsive"><table class="table table-striped table-bordered"><thead><tr><th>Barang</th><th>Satuan</th><th>Diajukan</th><th>Harga (Rp)</th><th>Subtotal (Rp)</th><th>Diterima</th><th>Sisa</th></tr></thead><tbody>
            @foreach($lines as $line)<tr><td>{{ $line['nama'] }}</td><td>{{ $line['satuan'] }}</td>@foreach(['jumlah', 'harga', 'subtotal', 'diterima', 'sisa'] as $key)<td>{{ number_format($line[$key], 0, ',', '.') }}</td>@endforeach</tr>@endforeach
        </tbody><tfoot><tr><th colspan="4" class="text-right">Total (Rp)</th><th class="text-right">{{ number_format(collect($lines)->sum('subtotal'), 0, ',', '.') }}</th><th colspan="2"></th></tr></tfoot></table></div>
        <p><strong>Status:</strong> {{ collect($lines)->sum('sisa') === 0 ? 'Sudah diterima semua' : (collect($lines)->sum('diterima') > 0 ? 'Diterima sebagian' : 'Belum diterima') }}</p>
        @if($data->penerimaan->isEmpty())
            @if($permissions['edit'] ?? false)<a class="btn btn-primary" href="{{ route('pengajuan-pembelian.edit', $data->id) }}" title="Edit" aria-label="Edit"><i class="fas fa-edit"></i></a>@endif
            @if($permissions['hapus'] ?? false)<form class="d-inline" method="POST" action="{{ route('pengajuan-pembelian.destroy', $data->id) }}" onsubmit="return confirm('Hapus pengajuan pembelian ini?')">@csrf @method('DELETE')<button class="btn btn-danger" title="Hapus" aria-label="Hapus"><i class="fas fa-trash"></i></button></form>@endif
        @else
            <p class="text-muted mt-3">Pengajuan sudah digunakan untuk penerimaan dan tidak dapat diubah atau dihapus.</p>
        @endif
        @if($data->penerimaan->isNotEmpty())<h5 class="mt-4">Riwayat Barang Masuk</h5><ul>@foreach($data->penerimaan as $receipt)<li><a href="{{ route('barang-masuk.show', $receipt->id) }}">BM-{{ str_pad($receipt->id, 5, '0', STR_PAD_LEFT) }}</a> — {{ $receipt->tanggal }} — Rp {{ number_format($receipt->total_harga, 0, ',', '.') }}</li>@endforeach</ul>@endif
