<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotaryDepositService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Schema, Storage};
use Illuminate\Support\Str;
use Tests\TestCase;

class NotaryDepositTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->travelTo(now('Asia/Jakarta')->setDate(2026, 11, 5)->setTime(12, 0));
        Schema::create('notaris', function (Blueprint $t) { $t->id(); $t->string('nama_notaris'); });
        Schema::create('lokasi_kavling', function (Blueprint $t) { $t->id(); $t->string('nama_kavling'); });
        Schema::create('kavling_peta', function (Blueprint $t) { $t->id(); $t->integer('id_lokasi'); $t->string('kode_kavling'); $t->string('nama_cluster')->nullable(); });
        Schema::create('menu', function (Blueprint $t) {
            $t->id(); $t->integer('id_parent'); $t->string('title'); $t->string('route_name')->nullable(); $t->string('icon')->nullable(); $t->integer('urutan')->default(1);
            foreach (['lihat','tambah','edit','hapus'] as $field) $t->boolean($field)->default(1);
        });
        foreach (['hak_akses' => 'id_user', 'role_user' => 'id_role'] as $table => $key) {
            Schema::create($table, function (Blueprint $t) use ($key) {
                $t->id(); $t->integer($key); $t->integer('id_menu');
                foreach (['lihat','tambah','edit','hapus','beranda'] as $field) $t->boolean($field)->default(1);
            });
        }
        Schema::create('konfigurasi', fn (Blueprint $t) => $t->id());
        Schema::create('konfigurasi_media', function (Blueprint $t) { $t->id(); $t->string('jenis_data'); $t->boolean('stt_aktif'); });
        Schema::create('log_aktivitas_pengguna', function (Blueprint $t) { $t->id(); $t->integer('id_user'); $t->string('user_name'); $t->text('aktivitas'); });
        DB::table('menu')->insert([
            ['id' => 1, 'id_parent' => 0, 'title' => 'Keuangan', 'route_name' => null],
            ['id' => 2, 'id_parent' => 1, 'title' => 'Pengeluaran', 'route_name' => 'pengeluaran.index'],
        ]);
        DB::table('hak_akses')->insert(['id_user' => 1, 'id_menu' => 2]);
        DB::table('role_user')->insert(['id_role' => 1, 'id_menu' => 2]);
        (require database_path('migrations/2026_11_05_130000_create_notary_deposits.php'))->up();
        DB::table('notaris')->insert([['id' => 1, 'nama_notaris' => 'NOTARIS SATU'], ['id' => 2, 'nama_notaris' => 'NOTARIS DUA']]);
        DB::table('lokasi_kavling')->insert([['id' => 1, 'nama_kavling' => 'PERUMAHAN A'], ['id' => 2, 'nama_kavling' => 'PERUMAHAN B']]);
        DB::table('kavling_peta')->insert([['id' => 1, 'id_lokasi' => 1, 'kode_kavling' => 'A1'], ['id' => 2, 'id_lokasi' => 2, 'kode_kavling' => 'B1']]);
        foreach ([[1,1,1],[2,2,1],[3,1,2]] as [$id,$notary,$location]) DB::table('notary_deposit_accounts')->insert(['id' => $id, 'notary_id' => $notary, 'location_id' => $location]);
        $this->actingAs((new User)->forceFill(['id' => 1, 'username' => 'tester']));
    }

    private function deposit(int $amount = 100000000, int $account = 1, array $extra = []): array
    {
        $data = array_replace(['amount' => number_format($amount,0,',','.'), 'entry_date' => '2026-10-31', 'request_token' => (string) Str::uuid()], $extra);
        $this->post(route('notary-deposits.deposit', $account), $data)->assertRedirect()->assertSessionHasNoErrors();
        return $data;
    }

    private function bill(int $amount = 27000000, int $account = 1, int $unit = 1): int
    {
        $this->post(route('notary-deposits.bill', $account), ['unit_id' => $unit, 'bill_date' => '2026-11-01',
            'items' => [['name' => 'BPHTB', 'amount' => number_format($amount,0,',','.')]], 'request_token' => (string) Str::uuid()])
            ->assertRedirect()->assertSessionHasNoErrors();
        return DB::table('notary_deposit_bills')->max('id');
    }

    private function payment(int $bill, int $amount, array $extra = []): array
    {
        return array_replace(['bill_id' => $bill, 'item_index' => 0, 'amount' => $amount, 'entry_date' => '2026-11-02', 'request_token' => (string) Str::uuid()], $extra);
    }

    public function test_complete_deposit_partial_and_full_payment_and_print_flow(): void
    {
        $this->deposit(); $bill = $this->bill();
        $this->post(route('notary-deposits.pay', 1), $this->payment($bill, 10000000))->assertSessionHasNoErrors();
        $this->assertSame(90000000, app(NotaryDepositService::class)->balance(1));
        $this->assertSame(10000000, app(NotaryDepositService::class)->paid($bill));
        $this->get(route('notary-deposits.show', 1))->assertOk()->assertSee('Sebagian')->assertSee('17.000.000');
        $this->post(route('notary-deposits.pay', 1), $this->payment($bill, 17000000))->assertSessionHasNoErrors();
        $this->assertSame(73000000, app(NotaryDepositService::class)->balance(1));
        $this->get(route('notary-deposits.statement', 1))->assertOk()->assertSee('Lunas')->assertSee('73.000.000');
        $this->assertFalse(Schema::hasTable('pengeluaran'));
        $this->get(route('notary-deposits.index'))->assertOk()->assertSee('NOTARIS SATU');
    }

    public function test_accounts_and_units_are_isolated_by_notary_and_location(): void
    {
        $this->deposit(); $bill = $this->bill();
        $this->assertSame(0, app(NotaryDepositService::class)->balance(2));
        $this->assertSame(0, app(NotaryDepositService::class)->balance(3));
        $this->postJson(route('notary-deposits.pay', 2), $this->payment($bill, 1000))->assertUnprocessable()->assertJsonValidationErrors('bill_id');
        $this->postJson(route('notary-deposits.bill', 1), ['unit_id' => 2, 'bill_date' => '2026-11-01',
            'items' => [['name' => 'PPh', 'amount' => 1000]], 'request_token' => (string) Str::uuid()])->assertUnprocessable()->assertJsonValidationErrors('unit_id');
    }

    public function test_insufficient_balance_overpayment_and_backdated_negative_balance_are_rejected(): void
    {
        $this->deposit(10000000, 1, ['entry_date' => '2026-11-03']); $bill = $this->bill(20000000);
        $url = route('notary-deposits.pay', 1);
        $this->postJson($url, $this->payment($bill, 11000000, ['entry_date' => '2026-11-04']))->assertUnprocessable();
        $this->postJson($url, $this->payment($bill, 21000000, ['entry_date' => '2026-11-04']))->assertUnprocessable();
        $this->postJson($url, $this->payment($bill, 1000000))->assertUnprocessable();
        $this->postJson($url, $this->payment($bill, 1, ['entry_date' => '2026-10-30']))->assertUnprocessable();
        $this->assertDatabaseCount('notary_deposit_entries', 1);
        $this->assertSame(10000000, app(NotaryDepositService::class)->balance(1));
    }

    public function test_reversal_restores_balance_and_reopens_bill_without_deleting_history(): void
    {
        $this->deposit(); $bill = $this->bill();
        $this->post(route('notary-deposits.pay', 1), $this->payment($bill, 27000000))->assertSessionHasNoErrors();
        $entry = DB::table('notary_deposit_entries')->where('type','payment')->value('id');
        $this->post(route('notary-deposits.reverse',$entry), ['notes'=>'Koreksi pembayaran','request_token'=>(string)Str::uuid()])->assertSessionHasNoErrors();
        $this->assertSame(100000000, app(NotaryDepositService::class)->balance(1));
        $this->assertSame(0, app(NotaryDepositService::class)->paid($bill));
        $this->assertDatabaseCount('notary_deposit_entries', 3);
        $this->postJson(route('notary-deposits.reverse',$entry), ['notes'=>'Ulang','request_token'=>(string)Str::uuid()])->assertUnprocessable();
        $reversal=DB::table('notary_deposit_entries')->where('type','reversal')->value('id');
        $this->postJson(route('notary-deposits.reverse',$reversal), ['notes'=>'Ulang','request_token'=>(string)Str::uuid()])->assertUnprocessable();
    }

    public function test_used_deposit_cannot_be_reversed_into_negative_balance(): void
    {
        $this->deposit(); $bill=$this->bill();
        $this->post(route('notary-deposits.pay',1),$this->payment($bill,10000000))->assertSessionHasNoErrors();
        $entry=DB::table('notary_deposit_entries')->where('type','deposit')->value('id');
        $this->postJson(route('notary-deposits.reverse',$entry),['notes'=>'Salah deposit','request_token'=>(string)Str::uuid()])->assertUnprocessable();
        $this->assertDatabaseCount('notary_deposit_entries',2);
    }

    public function test_duplicate_form_submission_does_not_duplicate_deposit_or_bill(): void
    {
        $data=$this->deposit();
        $this->postJson(route('notary-deposits.deposit',1),$data)->assertUnprocessable()->assertJsonValidationErrors('request_token');
        $bill=$this->bill(); $token=DB::table('notary_deposit_bills')->where('id',$bill)->value('request_token');
        $this->postJson(route('notary-deposits.bill',1),['unit_id'=>1,'bill_date'=>'2026-11-01','items'=>[['name'=>'PPh','amount'=>1000]],'request_token'=>$token])->assertUnprocessable();
        $this->assertDatabaseCount('notary_deposit_entries',1); $this->assertDatabaseCount('notary_deposit_bills',1);
    }

    public function test_only_unpaid_bills_can_be_cancelled_and_cancelled_bills_cannot_be_paid(): void
    {
        $this->deposit();$bill=$this->bill();
        $this->post(route('notary-deposits.pay',1),$this->payment($bill,1000))->assertSessionHasNoErrors();
        $this->deleteJson(route('notary-deposits.cancel',[1,$bill]))->assertUnprocessable();
        $unpaid=$this->bill(1000);
        $this->delete(route('notary-deposits.cancel',[1,$unpaid]))->assertSessionHasNoErrors();
        $this->postJson(route('notary-deposits.pay',1),$this->payment($unpaid,1000))->assertUnprocessable();
    }

    public function test_money_dates_permission_and_duplicate_accounts_are_validated(): void
    {
        $url=route('notary-deposits.deposit',1);
        foreach(['-1','1,5','0'] as $amount) $this->postJson($url,['amount'=>$amount,'entry_date'=>'2026-11-01','request_token'=>(string)Str::uuid()])->assertUnprocessable();
        $this->postJson($url,['amount'=>1000,'entry_date'=>'2026-11-06','request_token'=>(string)Str::uuid()])->assertUnprocessable();
        $this->postJson(route('notary-deposits.store'),['notary_id'=>1,'location_id'=>1])->assertUnprocessable();
        $menu=DB::table('menu')->where('route_name','notary-deposits.index')->value('id');
        DB::table('hak_akses')->where('id_menu',$menu)->update(['tambah'=>0]);
        $this->postJson($url,['amount'=>1000])->assertForbidden();
        $this->get(route('notary-deposits.show',1))->assertOk()->assertDontSee('id="notaryDepositModal"', false);
        DB::table('hak_akses')->where('id_menu',$menu)->update(['lihat'=>0]);
        $this->get(route('notary-deposits.index'))->assertForbidden();
    }

    public function test_each_cost_has_its_own_remaining_balance_and_reversal(): void
    {
        $this->deposit();
        $this->post(route('notary-deposits.bill',1), ['unit_id'=>1,'bill_date'=>'2026-11-01',
            'items'=>[2=>['name'=>'BPHTB','amount'=>'16.000.000'],5=>['name'=>'PPh','amount'=>'11.000.000']],
            'request_token'=>(string)Str::uuid()])->assertSessionHasNoErrors();
        $bill=DB::table('notary_deposit_bills')->max('id');
        $payment=$this->payment($bill,16000000);
        $this->post(route('notary-deposits.pay',1),$payment)->assertSessionHasNoErrors();
        $this->postJson(route('notary-deposits.pay',1),$payment)->assertUnprocessable();
        $this->postJson(route('notary-deposits.pay',1),$this->payment($bill,1))->assertUnprocessable()->assertJsonValidationErrors('amount');
        $this->get(route('notary-deposits.show',1))->assertOk()->assertSee('BPHTB')->assertSee('PPh')->assertSee('11.000.000');
        $this->post(route('notary-deposits.pay',1),$this->payment($bill,5000000,['item_index'=>1]))->assertSessionHasNoErrors();
        $this->assertSame(21000000,app(NotaryDepositService::class)->paid($bill));
        $entry=DB::table('notary_deposit_entries')->where('item_index',0)->value('id');
        $this->post(route('notary-deposits.reverse',$entry),['notes'=>'Koreksi BPHTB','request_token'=>(string)Str::uuid()])->assertSessionHasNoErrors();
        $this->assertSame(5000000,app(NotaryDepositService::class)->paid($bill));
        $view=app(\App\Http\Controllers\Keuangan\NotaryDepositController::class)->show(1);
        $items=$view->getData()['bills']->first()->item_balances;
        $this->assertSame(16000000,$items[0]['remaining']);
        $this->assertSame(6000000,$items[1]['remaining']);
    }

    public function test_new_account_is_created_and_invalid_bill_data_is_rejected(): void
    {
        $this->post(route('notary-deposits.store'),['notary_id'=>2,'location_id'=>2])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('notary_deposit_accounts',['notary_id'=>2,'location_id'=>2]);
        $this->postJson(route('notary-deposits.bill',1),['unit_id'=>1,'bill_date'=>'2026-11-01','items'=>'invalid','request_token'=>(string)Str::uuid()])->assertUnprocessable();
        $this->postJson(route('notary-deposits.deposit',1),['amount'=>['invalid'],'entry_date'=>'2026-11-01','request_token'=>(string)Str::uuid()])->assertUnprocessable();
    }

    public function test_private_attachment_and_failed_upload_cleanup(): void
    {
        Storage::fake('local');
        $data=$this->deposit(1000,1,['attachment'=>UploadedFile::fake()->image('bukti.png')]);
        $entry=DB::table('notary_deposit_entries')->first(); Storage::disk('local')->assertExists($entry->attachment);
        $this->get(route('notary-deposits.attachment',$entry->id))->assertOk();
        $data['attachment']=UploadedFile::fake()->image('duplicate.png');
        $this->postJson(route('notary-deposits.deposit',1),$data)->assertUnprocessable();
        $this->assertCount(1,Storage::disk('local')->allFiles('notary-deposits'));
        $menu=DB::table('menu')->where('route_name','notary-deposits.index')->value('id');
        DB::table('hak_akses')->where('id_menu',$menu)->update(['lihat'=>0]);
        $this->get(route('notary-deposits.attachment',$entry->id))->assertForbidden();
    }
}
