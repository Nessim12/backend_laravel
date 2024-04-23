<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Motif extends Model
{
    use HasFactory;
    protected $fillable = [
        'motif_name'
    ];
    public function conges()
    {
        return $this->hasMany(Conge::class);
    }
}
