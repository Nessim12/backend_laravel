<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pointing extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entre',
        'sortie',
        'date',
        'statusjour',
        'status_available',


    ];

    // Define relationships if necessary
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
