<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RequestAccess extends Model
{
    protected $fillable = [
        'id_number',
        'fullname',
        'email',
        'password',
        'role',
        'status'
    ];
}
