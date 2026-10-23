<div class="attachment-actions d-flex mt-2" data-field="{{ $field }}">
    <button type="button" class="btn btn-sm btn-outline-primary mr-2 attachment-print" @disabled(!$data->$field)>
        <i class="fas fa-print"></i> Cetak
    </button>
    <a class="btn btn-sm btn-outline-success attachment-download {{ $data->$field ? '' : 'disabled' }}"
        @if ($data->$field) href="{{ asset('assets/booking/' . $data->$field) }}" download="{{ $data->$field }}" @endif
        aria-disabled="{{ $data->$field ? 'false' : 'true' }}">
        <i class="fas fa-download"></i> Download
    </a>
</div>
