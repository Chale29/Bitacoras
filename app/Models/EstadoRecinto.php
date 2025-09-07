<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoRecinto extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'color',
    ]; 

    public function recinto()
    {
        return $this->hasOne(Recinto::class, 'estadoRecinto_id');
    }

    protected $table = 'estadorecinto';

}
