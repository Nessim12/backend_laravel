<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workremote extends Model
{
    use HasFactory;

    protected $table = 'remote_work'; // Specify the table name

    protected $fillable = [
        'user_id',
        'date', // User who made the remote work request
        'reason', // Reason for the remote work request
        'status', // Status of the request (pending, accepted, rejected)
        // Add more fillable attributes if necessary
    ];

    public function user()
{
    return $this->belongsTo(User::class);
}

}
