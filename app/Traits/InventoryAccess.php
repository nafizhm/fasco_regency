<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait InventoryAccess
{
    protected function authorizeInventory(string $route, string $action = 'lihat'): void
    {
        abort_unless(in_array($action, ['lihat', 'tambah', 'edit', 'hapus'], true), 403);
        abort_unless(DB::table('hak_akses')->join('menu', 'menu.id', '=', 'hak_akses.id_menu')
            ->where('hak_akses.id_user', auth()->id())->where('menu.route_name', $route)
            ->where('hak_akses.lihat', 1)->where('hak_akses.'.$action, 1)->exists(), 403);
    }
}
