<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Support\Facades\View::composer('admin.layout_admin', function () {
            if (!\Illuminate\Support\Facades\Auth::check()) return;
            $allowed = \App\Models\HakAkses::where('id_user', \Illuminate\Support\Facades\Auth::id())
                ->where('lihat', 1)->pluck('id_menu');
            $menus = \App\Models\Menu::where('id_parent', 0)->whereIn('id', $allowed)
                ->orderBy('urutan')->with(['children' => fn ($q) => $q->whereIn('id', $allowed)])->get();
            session(['getmenus' => $menus]);
        });
        foreach (['saved', 'deleted'] as $event) {
            \App\Models\Pemasukan::$event(function ($payment) {
                app(\App\Services\PiutangPaymentService::class)->paymentChanged($payment);
            });
            \App\Models\Piutang::$event(function ($bill) {
                $service = app(\App\Services\PiutangPaymentService::class);
                foreach (array_unique(array_filter([$bill->id_customer, $bill->getRawOriginal('id_customer')])) as $id) {
                    $service->syncCustomer($id);
                }
                if (!$bill->id_customer && $bill->exists) $service->syncPiutang($bill->id);
            });
        }
    }
}
