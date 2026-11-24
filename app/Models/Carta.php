<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Carta extends Model
{
    public $timestamps = false;
    protected $fillable = ['remitente', 'edad', 'ciudad', 'regalos'];
    protected $casts = ['regalos' => 'array', 'edad' => 'integer'];
}
