$(function () {
    $('.notary-modal-select').each(function () {
        $(this).select2({theme: 'bootstrap4', width: '100%', dropdownParent: $(this).closest('.modal'), minimumResultsForSearch: 0});
    });
    function filterBills() {
        const filter = $('#notary-bill-filter').val();
        let count = 0;
        $('#notary-bills tr[data-state]').each(function () {
            this.hidden = filter !== 'all' && this.dataset.state !== filter;
            if (!this.hidden) count++;
        });
        $('#notary-no-matching-bills').prop('hidden', count > 0 || !$('#notary-bills tr[data-state]').length);
    }
    $('#notary-bill-filter').on('change', filterBills); filterBills();
    function updateTotal() {
        let total = 0;
        $('#notary-cost-items .notary-money').each(function () { total += Number(this.value.replace(/\./g, '')) || 0; });
        $('#notary-cost-total').text('Total Rp ' + total.toLocaleString('id-ID'));
    }
    $(document).on('input', '.notary-money', function () {
        this.value = this.value.replace(/\D/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.'); updateTotal();
    });
    let costIndex = $('.notary-cost-row').length;
    $('#notary-add-cost').on('click', function () {
        if ($('.notary-cost-row').length >= 30) return;
        const row = $('.notary-cost-row').first().clone();
        row.find('input').each(function () { this.name = this.name.replace(/items\[\d+\]/, 'items[' + costIndex + ']'); this.value = ''; });
        costIndex++; $('#notary-cost-items').append(row); row.find('input').first().trigger('focus');
    });
    $(document).on('click', '.notary-remove-cost', function () {
        if ($('.notary-cost-row').length <= 1) return;
        $(this).closest('.notary-cost-row').remove(); updateTotal();
    });
    $('#notary-pay-bill').on('change', function () {
        const selected = this.options[this.selectedIndex];
        const costs = JSON.parse(selected?.dataset.items || '[]');
        const select = document.getElementById('notary-pay-item');
        select.replaceChildren(new Option('Pilih jenis biaya', ''));
        costs.filter(item => item.remaining > 0).forEach(item => {
            const option = new Option(item.name + ' - Sisa Rp ' + Number(item.remaining).toLocaleString('id-ID'), item.index);
            option.dataset.remaining = item.remaining;
            select.add(option);
        });
        if (select.options.length === 2) select.selectedIndex = 1;
        $(select).trigger('change');
        $('#notary-pay-date').attr('min', selected?.dataset.date || '');
    });
    $('#notary-pay-item').on('change', function () {
        const amount = Number(this.options[this.selectedIndex]?.dataset.remaining || 0);
        $('#notary-pay-amount').val(amount ? amount.toLocaleString('id-ID') : '');
    });
    $('.notary-pay-bill').on('click', function () {
        $('#notary-pay-bill').val(this.dataset.bill).trigger('change'); $('#notaryPayModal').modal('show');
    });
    $('.notary-reverse').on('click', function () {
        $('#notary-reverse-form').attr('action', this.dataset.url);
        $('#notaryReverseTitle').text('Balikkan Transaksi #' + this.dataset.id);
        $('#notaryReverseModal').modal('show');
    });
    $('.notary-attachment').on('click', function () {
        const container = document.getElementById('notary-attachment-content'); container.replaceChildren();
        const url = this.dataset.url;
        $('#notary-attachment-original').attr('href', url);
        const pdf = /\.pdf$/i.test(this.dataset.name || '');
        const media = document.createElement(pdf ? 'iframe' : 'img'); media.src = url;
        if (pdf) { media.title = 'Lampiran PDF'; media.style.cssText = 'width:100%;height:70vh;border:0'; }
        else { media.alt = this.dataset.name || 'Lampiran'; media.style.cssText = 'max-width:100%;max-height:70vh;object-fit:contain'; }
        container.append(media); $('#notaryAttachmentModal').modal('show');
    });
    $('#notaryAttachmentModal').on('hidden.bs.modal', function () {
        document.getElementById('notary-attachment-content').replaceChildren(); $('#notary-attachment-original').removeAttr('href');
    });
    updateTotal();
    const state = document.getElementById('notary-form-state');
    if (state?.dataset.form) {
        if (state.dataset.form === 'pay') {
            $('#notary-pay-bill').val(state.dataset.bill).trigger('change');
            $('#notary-pay-item').val(state.dataset.item).trigger('change');
            $('#notary-pay-amount').val(state.dataset.amount);
        }
        const modal = {deposit: '#notaryDepositModal', pay: '#notaryPayModal', bill: '#notaryBillModal'}[state.dataset.form];
        if (modal) $(modal).modal('show');
    }
    $('#notaryDepositModal form, #notaryPayModal form, #notaryBillModal form, #notary-reverse-form').on('submit', function () {
        $(this).find('button[type="submit"], .modal-footer button:not([type="button"])').prop('disabled', true).text('Menyimpan...');
    });
});
