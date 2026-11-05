<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['customer', 'customer_tempo'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->unsignedBigInteger('harga_jual_awal')->nullable();
                $table->unsignedBigInteger('diskon')->default(0);
            });
        }
        Schema::table('customer', fn (Blueprint $table) => $table->unsignedBigInteger('id_booking')->nullable()->index());
        Schema::table('pengajuan_hold', fn (Blueprint $table) => $table->json('rincian_biaya_penjualan')->nullable());
        foreach (DB::table('customer')->get() as $customer) {
            $matches = DB::table('pengajuan_hold')->where('id_kavling', $customer->id_kavling)->where('stt_reg', 2)
                ->when($customer->nik, fn ($q) => $q->where('nik', $customer->nik))->get();
            $booking = $matches->count() === 1 ? $matches->first() : null;
            $discount = (int) ($booking->diskon ?? 0);
            DB::table('customer')->where('id', $customer->id)->update([
                'harga_jual_awal' => max(0, (int) $customer->hrg_jual) + $discount,
                'diskon' => $discount, 'id_booking' => $booking?->id,
            ]);
        }
    }

    public function down(): void
    {
        foreach (['customer', 'customer_tempo'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['harga_jual_awal', 'diskon']));
        }
        Schema::table('customer', fn (Blueprint $table) => $table->dropColumn('id_booking'));
        Schema::table('pengajuan_hold', fn (Blueprint $table) => $table->dropColumn('rincian_biaya_penjualan'));
    }
};
