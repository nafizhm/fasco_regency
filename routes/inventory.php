<?php

use Illuminate\Support\Facades\Route;

Route::get('admin/master/barang/{barang}/history', [\App\Http\Controllers\Master\BarangController::class, 'history'])->name('barang.history');
Route::resource('admin/master/barang', \App\Http\Controllers\Master\BarangController::class)->except(['create', 'show']);
Route::resource('admin/master/supplier', \App\Http\Controllers\Master\SupplierController::class)->except(['create', 'show']);
Route::resource('admin/master/satuan', \App\Http\Controllers\Master\SatuanController::class)->except(['create', 'show']);
Route::resource('admin/gudang/barang-masuk', \App\Http\Controllers\BarangMasukController::class)->except(['edit']);
Route::get('admin/gudang/barang-masuk/{barang_masuk}/nota', [\App\Http\Controllers\BarangMasukController::class, 'nota'])->name('barang-masuk.nota');
Route::get('admin/gudang/barang-masuk/{barang_masuk}/payment-info', [\App\Http\Controllers\BarangMasukController::class, 'paymentInfo'])->name('barang-masuk.payment-info');
Route::post('admin/gudang/barang-masuk/{barang_masuk}/payment', [\App\Http\Controllers\BarangMasukController::class, 'storePayment'])->name('barang-masuk.payment.store');
Route::post('admin/gudang/barang-masuk/validasi-jumlah', [\App\Http\Controllers\BarangMasukController::class, 'validateJumlah'])->name('barang-masuk.validate-jumlah');
Route::get('admin/gudang/barang-keluar/unit-by-lokasi/{id}', [\App\Http\Controllers\BarangKeluarController::class, 'getUnitsByLokasi'])->name('barang-keluar.unit-by-lokasi');
Route::get('admin/gudang/barang-keluar/pekerjaan-by-project/{id}', [\App\Http\Controllers\BarangKeluarController::class, 'getPekerjaanByProject'])->name('barang-keluar.pekerjaan-by-project');
Route::resource('admin/gudang/barang-keluar', \App\Http\Controllers\BarangKeluarController::class);
Route::get('admin/gudang/barang-keluar/{barang_keluar}/nota', [\App\Http\Controllers\BarangKeluarController::class, 'nota'])->name('barang-keluar.nota');
Route::resource('admin/gudang/pengajuan-barang', \App\Http\Controllers\Gudang\PengajuanBarangController::class);
Route::get('admin/gudang/riwayat-mutasi', [\App\Http\Controllers\Gudang\RiwayatMutasiController::class, 'index'])->name('riwayat-mutasi.index');
Route::resource('admin/pengaturan/tukang', \App\Http\Controllers\Pengaturan\TukangController::class)->except(['create', 'show']);
Route::get('admin/pengaturan/setting-proyek/units-by-lokasi/{id}', [\App\Http\Controllers\Pengaturan\SettingProyekController::class, 'getUnitsByLokasi'])->name('setting-proyek.units-by-lokasi');
Route::resource('admin/pengaturan/setting-proyek', \App\Http\Controllers\Pengaturan\SettingProyekController::class)->except(['create', 'show']);
Route::get('admin/pengaturan/master-project/{master_project}/pekerjaan', [\App\Http\Controllers\Pengaturan\MasterProjectController::class, 'pekerjaanIndex'])->name('master-project.pekerjaan.index');
Route::post('admin/pengaturan/master-project/{master_project}/pekerjaan', [\App\Http\Controllers\Pengaturan\MasterProjectController::class, 'storePekerjaan'])->name('master-project.pekerjaan.store');
Route::get('admin/pengaturan/master-project/pekerjaan/{pekerjaan}/edit', [\App\Http\Controllers\Pengaturan\MasterProjectController::class, 'editPekerjaan'])->name('master-project.pekerjaan.edit');
Route::put('admin/pengaturan/master-project/pekerjaan/{pekerjaan}', [\App\Http\Controllers\Pengaturan\MasterProjectController::class, 'updatePekerjaan'])->name('master-project.pekerjaan.update');
Route::delete('admin/pengaturan/master-project/pekerjaan/{pekerjaan}', [\App\Http\Controllers\Pengaturan\MasterProjectController::class, 'destroyPekerjaan'])->name('master-project.pekerjaan.destroy');
Route::get('admin/pengaturan/master-project/pekerjaan/{pekerjaan}/material', [\App\Http\Controllers\Pengaturan\MasterProjectController::class, 'material'])->name('master-project.material');
Route::put('admin/pengaturan/master-project/pekerjaan/{pekerjaan}/material', [\App\Http\Controllers\Pengaturan\MasterProjectController::class, 'updateMaterial'])->name('master-project.material.update');
Route::get('admin/pengaturan/master-project/pekerjaan/{pekerjaan}/jasa', [\App\Http\Controllers\Pengaturan\MasterProjectController::class, 'jasa'])->name('master-project.jasa');
Route::put('admin/pengaturan/master-project/pekerjaan/{pekerjaan}/jasa', [\App\Http\Controllers\Pengaturan\MasterProjectController::class, 'updateJasa'])->name('master-project.jasa.update');
Route::resource('admin/pengaturan/master-project', \App\Http\Controllers\Pengaturan\MasterProjectController::class)->except(['create', 'show']);

Route::get('admin/gudang/stok-barang', [\App\Http\Controllers\Gudang\StokBarangController::class, 'index'])->name('stok-barang.index');
