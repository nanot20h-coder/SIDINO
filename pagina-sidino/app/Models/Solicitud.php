<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $table = 'solicitudes';

    public $timestamps = false;

    protected $fillable = [
        'colegio',
        'direccion',
        'email',
        'telefono'
    ];
}