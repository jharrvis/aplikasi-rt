<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiJimpitian extends Model
{
    protected $guarded = [];

    public function warga()
    {
        return $this->belongsTo(Warga::class);
    }
}
