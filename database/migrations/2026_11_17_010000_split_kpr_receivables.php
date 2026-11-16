<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('piutang', function (Blueprint $table) {
            $table->string('jenis_piutang', 20)->nullable()->index();
        });
        foreach (\App\Models\Customer::whereRaw('UPPER(TRIM(jenis_pembelian)) = ?', ['KPR'])->pluck('id') as $id) {
            app(\App\Services\PiutangPaymentService::class)->syncCustomer($id);
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Piutang DP dan bank beserta alokasi pembayaran harus dipertahankan.');
    }
};
