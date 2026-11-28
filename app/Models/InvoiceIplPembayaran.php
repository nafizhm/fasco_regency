<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceIplPembayaran extends Model
{
    protected $table = 'invoice_ipl_pembayaran';

    protected $guarded = ['id'];

    public $timestamps = false;
}
