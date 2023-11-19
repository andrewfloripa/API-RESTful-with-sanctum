<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Indice extends Model
{
    use HasFactory;

    protected $fillable = ['titulo', 'pagina', 'livro_id', 'indice_pai_id'];

    public function subindices()
    {
        return $this->hasMany(Indice::class, 'indice_pai_id');
    }

    public function indicePai()
    {
        return $this->belongsTo(Indice::class, 'indice_pai_id');
    }

    public function livro()
    {
        return $this->belongsTo(Livro::class, 'livro_id');
    }
}
