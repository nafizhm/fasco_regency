<form class="d-inline-block mr-2 mb-2" method="POST" action="{{ route('spk.destroy', $spk) }}" onsubmit="return confirm('Hapus SPK ini beserta seluruh transaksi pembayaran dan bukti bayarnya? Data yang dihapus tidak dapat dikembalikan.')">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash" aria-hidden="true"></i> Hapus SPK</button>
</form>
