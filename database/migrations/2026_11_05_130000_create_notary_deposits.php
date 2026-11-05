<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notary_deposit_accounts', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('notary_id'); $t->unsignedBigInteger('location_id');
            $t->timestamps(); $t->unique(['notary_id', 'location_id']);
        });
        Schema::create('notary_deposit_bills', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('account_id')->index(); $t->unsignedBigInteger('unit_id')->index();
            $t->date('bill_date'); $t->string('reference')->nullable(); $t->text('notes')->nullable();
            $t->json('items'); $t->unsignedBigInteger('amount'); $t->unsignedBigInteger('user_id');
            $t->uuid('request_token')->unique(); $t->timestamp('cancelled_at')->nullable(); $t->timestamps();
        });
        Schema::create('notary_deposit_entries', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('account_id')->index(); $t->unsignedBigInteger('bill_id')->nullable()->index();
            $t->unsignedInteger('item_index')->nullable();
            $t->date('entry_date'); $t->string('type', 20); $t->bigInteger('amount');
            $t->string('reference')->nullable(); $t->text('notes')->nullable();
            $t->string('attachment')->nullable(); $t->string('attachment_name')->nullable();
            $t->unsignedBigInteger('reversal_of')->nullable()->unique(); $t->unsignedBigInteger('user_id');
            $t->uuid('request_token')->unique(); $t->timestamps(); $t->index(['account_id', 'entry_date', 'id']);
        });
        $source = DB::table('menu')->where('route_name', 'pengeluaran.index')->first();
        $parent = $source?->id_parent ?: DB::table('menu')->where('title', 'Keuangan')->value('id');
        if (! $parent) throw new RuntimeException('Menu Keuangan tidak ditemukan.');
        $id = DB::table('menu')->insertGetId([
            'id_parent' => $parent, 'title' => 'Deposit Notaris', 'route_name' => 'notary-deposits.index',
            'icon' => 'fas fa-wallet', 'urutan' => (int) DB::table('menu')->where('id_parent', $parent)->max('urutan') + 1,
            'lihat' => 1, 'tambah' => 1, 'edit' => 1, 'hapus' => 1,
        ]);
        if ($source) {
            foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $key) {
                foreach (DB::table($table)->where('id_menu', $source->id)->get() as $access) {
                    DB::table($table)->insert([$key => $access->$key, 'id_menu' => $id, 'lihat' => $access->lihat,
                        'beranda' => 0, 'tambah' => $access->tambah, 'edit' => $access->edit, 'hapus' => $access->hapus]);
                }
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('menu')->where('route_name', 'notary-deposits.index')->pluck('id');
        foreach (['hak_akses', 'role_user'] as $table) DB::table($table)->whereIn('id_menu', $ids)->delete();
        DB::table('menu')->whereIn('id', $ids)->delete();
        Schema::dropIfExists('notary_deposit_entries'); Schema::dropIfExists('notary_deposit_bills'); Schema::dropIfExists('notary_deposit_accounts');
    }
};
