<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Conge extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'demande_conges';

    protected $fillable = [
        'user_id',
        'date_d',
        'date_f',
        'motif_id', // Change 'motif' to 'motif_id' to store motif's ID
        'description', // Correct typo 'desciprtion' to 'description'
        'status',
        'refuse_reason',
        'solde',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function motif()
    {
        return $this->belongsTo(Motif::class); // Establishing the relationship with Motif
    }
}
