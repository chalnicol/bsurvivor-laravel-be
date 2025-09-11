<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    
    //
    protected $fillable = [
        'user_id',
        'is_like',
        'voted_at'
    ];

    protected $cast = [
        'voted_at' => 'datetime'
    ];

}
