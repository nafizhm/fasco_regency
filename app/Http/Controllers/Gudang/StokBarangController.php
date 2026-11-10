<?php

namespace App\Http\Controllers\Gudang;

use App\Http\Controllers\Controller;
use App\Models\Barang;
use App\Traits\InventoryAccess;
use Illuminate\Http\Request;

class StokBarangController extends Controller
{
    use InventoryAccess;

    public function index(Request $request)
    {
        $this->authorizeInventory('stok-barang.index');
        $request->validate(['q' => 'nullable|string|max:100']);
        $items = Barang::with('satuan')
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($search) => $search
                ->where('nama', 'like', '%'.$request->q.'%')->orWhere('sku', 'like', '%'.$request->q.'%')))
            ->orderBy('nama')->paginate(30)->withQueryString();
        $value = Barang::selectRaw('COALESCE(SUM(stok * harga_beli), 0) as total')->value('total');

        return view('admin.gudang.stok_barang.index', compact('items', 'value'));
    }
}
