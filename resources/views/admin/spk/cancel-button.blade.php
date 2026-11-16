<form class="d-inline-block mr-2 mb-2" method="POST" action="{{ route('spk.cancel', $spk) }}" onsubmit="return confirm('Batalkan SPK ini? Setelah dibatalkan, SPK tidak dapat diedit atau menerima pembayaran baru. Riwayat pembayaran tetap tersimpan.')">
    @csrf
    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-ban" aria-hidden="true"></i> Batalkan SPK</button>
</form>
