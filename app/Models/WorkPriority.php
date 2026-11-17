<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkPriority extends Model
{
    protected $fillable = ['title', 'notes', 'priority', 'status'];
}
