@if ($customer->tanggal_jatuh_tempo)
    @php
        $dueDate = \Carbon\Carbon::parse($customer->tanggal_jatuh_tempo)->startOfDay();
        $daysRemaining = (int) \Carbon\Carbon::today()->diffInDays($dueDate, false);
    @endphp
    <span class="text-nowrap">{{ $dueDate->format('d-m-Y') }}</span><br>
    <small class="{{ $daysRemaining < 0 ? 'text-danger' : ($daysRemaining === 0 ? 'text-warning' : 'text-muted') }}">
        @if ($daysRemaining < 0)
            Terlambat {{ abs($daysRemaining) }} hari
        @elseif ($daysRemaining === 0)
            Jatuh tempo hari ini
        @else
            {{ $daysRemaining }} hari lagi
        @endif
    </small>
@else
    <span class="text-muted">Belum diatur</span>
@endif
